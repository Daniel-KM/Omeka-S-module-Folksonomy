<?php
namespace Folksonomy\Mvc\Controller\Plugin;

use Folksonomy\Entity\Tag;
use Folksonomy\Entity\Tagging;
use Omeka\Api\Adapter\Manager as ApiAdapterManager;
use Omeka\Api\Manager as Api;
use Omeka\Entity\Resource;
use Omeka\Entity\User;
use Omeka\Permissions\Acl;
use Omeka\Settings\Settings;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class AddTags extends AbstractPlugin
{
    /**
     * @var Api
     */
    protected $api;

    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @var Settings
     */
    protected $settings;

    /**
     * @var ApiAdapterManager
     */
    protected $apiAdapterManager;

    public function __construct(Api $api, Acl $acl, Settings $settings, ApiAdapterManager  $apiAdapterManager)
    {
        $this->api = $api;
        $this->acl = $acl;
        $this->settings = $settings;
        $this->apiAdapterManager = $apiAdapterManager;
    }

    /**
     * Add tags to a resource (create tag if needed, then tag the resource).
     *
     * @param Resource $resource
     * @param array $tags List of tag names to add to the resource.
     * @return array|null List of tag names that were added to the resource.
     */
    public function __invoke(Resource $resource, array $tags)
    {
        // A quick cleaning (and "0" may be a valid tag).
        $tags = array_filter(
            array_unique(
                array_map(
                    [$this, 'sanitizeString'],
                    $tags
                )
            ),
            function ($v) { return strlen($v); }
        );
        if (empty($tags)) {
            return;
        }

        $acl = $this->acl;
        $add = $acl->userIsAllowed(Tagging::class, 'create');
        if (!$add) {
            return;
        }

        $api = $this->api;
        $settings = $this->settings;
        $apiAdapterManager = $this->apiAdapterManager;
        $tagAdapter = $apiAdapterManager->get('tags');
        $taggingAdapter = $apiAdapterManager->get('taggings');
        $user = $acl->getAuthenticationService()->getIdentity();
        $resourceId = $resource->getId();

        // Prepare the tags.
        // Note: The tags don't belong to a user, who is only the first tagger.

        // Check if tags exist already via database requests to avoid issues
        // between sql and php characters transliterating.
        // By construction, the list of tags will contain only unique tags.
        // TODO Create a query that returns new tags as key and formatted as
        // value, or that keeps order and returns each existing value or null.
        $tagsToAdd = [];
        foreach ($tags as $newTag) {
            $tag = $api
                ->search('tags', ['name' => $newTag])
                ->getContent();
            if ($tag) {
                $tagsToAdd[$tag[0]->name()] = $tag[0];
            } else {
                $tag = $api
                    ->create('tags', ['o:name' => $newTag])
                    ->getContent();
                $tagsToAdd[$tag->name()] = $tag;
            }
        }

        // Update taggings according to all tags passed in the request.
        if (empty($tagsToAdd)) {
            return;
        }

        // Prepare the status to set for the tagging.
        if ($acl->userIsAllowed(Tagging::class, 'update')) {
            $updateRight = true;
            $status = Tagging::STATUS_APPROVED;
        } else {
            $updateRight = false;
            $status = $settings->get('folksonomy_public_require_moderation', false)
                ? Tagging::STATUS_PROPOSED
                : Tagging::STATUS_ALLOWED;
            $dataUpdate = ['o:status' => $status];
        }

        // Add tags to the resource and update the status if needed.
        $addedTags = [];
        foreach ($tagsToAdd as $tagName => $tag) {
            $taggings = $resourceId
                ? $api
                    ->search(
                        'taggings',
                        ['tag' => $tagName, 'resource_id' => $resourceId]
                    )
                    ->getContent()
                : [];
            if (empty($taggings)) {
                $data = [
                    'o:status' => $status,
                    'o-module-folksonomy:tag' => $tagName,
                    'o:resource' => ['o:id' => $resourceId],
                ];
                $response = $api
                    ->create('taggings', $data);
                $addedTags[] = $tagName;
            } elseif ($updateRight) {
                foreach ($taggings as $tagging) {
                    if ($tagging->status() === $status) {
                        continue;
                    }
                    $response = $api
                        ->update('taggings', $tagging->id(), $dataUpdate, ['isPartial' => true]);
                }
            }
        }
        return $addedTags;
    }

    /**
     * Returns a sanitized string.
     *
     * @param string $string The string to sanitize.
     * @return string The sanitized string.
     */
    protected function sanitizeString($string)
    {
        // Quote is allowed.
        $string = strip_tags($string);
        // The first character is a space and the last one is a no-break space.
        $string = trim($string, ' /\\?<>:*%|"`&; ' . "\t\n\r");
        $string = preg_replace('/[\(\{]/', '[', $string);
        $string = preg_replace('/[\)\}]/', ']', $string);
        $string = preg_replace('/[[:cntrl:]\/\\\?<>\*\%\|\"`\&\;#+\^\$\s]/', ' ', $string);
        return trim(preg_replace('/\s+/', ' ', $string));
    }
}
