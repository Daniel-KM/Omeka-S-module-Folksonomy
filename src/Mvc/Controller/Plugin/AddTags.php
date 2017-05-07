<?php
namespace Folksonomy\Mvc\Controller\Plugin;

use Doctrine\ORM\EntityManager;
use Folksonomy\Entity\Tag;
use Folksonomy\Entity\Tagging;
use Omeka\Api\Adapter\Manager as ApiAdapterManager;
use Omeka\Api\Manager as Api;
use Omeka\Api\Request;
use Omeka\Entity\Resource;
use Omeka\Entity\User;
use Omeka\Permissions\Acl;
use Omeka\Settings\Settings;
use Omeka\Stdlib\ErrorStore;
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
     * Add tags to a resource.
     *
     * @param Resource $resource
     * @param array $tags List of tag names to add.
     * @return array|null List of tag names that were added.
     */
    public function __invoke(Resource $resource, array $tags)
    {
        // A quick cleaning (and "0" may be a valid tag).
        $tags = array_filter(array_unique(array_map('trim', $tags)), function ($v) { return strlen($v); });
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
        $entityManager = $tagAdapter->getEntityManager();
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
                ->search('tags', ['name' => $newTag], ['responseContent' => 'resource'])
                ->getContent();
            if ($tag) {
                $tagsToAdd[$tag[0]->getName()] = $tag[0];
            } else {
                $subRequest = new Request(Request::CREATE, 'tags');
                $subRequest->setContent(['o:name' => $newTag]);
                $tag = new Tag;
                $tagAdapter->hydrateEntity($subRequest, $tag, new ErrorStore);
                $entityManager->persist($tag);
                $tagsToAdd[$tag->getName()] = $tag;
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
                        ['tag' => $tagName, 'resource_id' => $resourceId],
                        ['responseContent' => 'resource'])
                    ->getContent()
                : [];
            if (empty($taggings)) {
                $data = [
                    'o:status' => $status,
                    'o-module-folksonomy:tag' => $tag,
                    'o:resource' => ['o:id' => $resourceId],
                ];
                $subRequest = new Request(Request::CREATE, 'taggings');
                $subRequest->setContent($data);
                $tagging = new Tagging;
                $taggingAdapter->hydrateEntity($subRequest, $tagging, new ErrorStore);
                $entityManager->persist($tagging);
                $addedTags = $tagName;
            } elseif ($updateRight) {
                foreach ($taggings as $tagging) {
                    if ($tagging->getStatus() === $status) {
                        continue;
                    }
                    $subRequest = new Request(Request::UPDATE, 'taggings');
                    $subRequest->setId($tagging->getId());
                    $subRequest->setContent($dataUpdate);
                    $taggingAdapter->hydrateEntity($subRequest, $tagging, new ErrorStore());
                    $entityManager->persist($tagging);
                }
            }
        }
        return $addedTags;
    }
}
