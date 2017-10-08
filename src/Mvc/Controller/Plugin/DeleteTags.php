<?php
namespace Folksonomy\Mvc\Controller\Plugin;

use Doctrine\ORM\EntityManager;
use Folksonomy\Entity\Tag;
use Folksonomy\Entity\Tagging;
use Omeka\Api\Manager as Api;
use Omeka\Entity\Resource;
use Omeka\Permissions\Acl;
use Zend\Mvc\Controller\Plugin\AbstractPlugin;

class DeleteTags extends AbstractPlugin
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
     * @var EntityManager
     */
    protected $entityManager;

    /**
     * @param Api $api
     * @param Acl $acl
     * @param EntityManager $entityManager
     */
    public function __construct(Api $api, Acl $acl, EntityManager $entityManager)
    {
        $this->api = $api;
        $this->acl = $acl;
        $this->entityManager = $entityManager;
    }

    /**
     * Delete tags from a resource. The tags themselves are not deleted.
     *
     * @param Resource $resource
     * @param array $tags List of tag names to remove from the resource.
     * @return array|null List of tag names that were removed from the resource.
     */
    public function __invoke(Resource $resource, array $tags)
    {
        if (empty($tags)) {
            return [];
        }

        if (!$this->acl->userIsAllowed(Tagging::class, 'delete')) {
            return;
        }

        $resourceId = $resource->getId();

        // This search is "or".
        $tagsToDelete = $this->api->search('tags', [
            // The search on "name" is "OR".
            'name' => $tags,
        ])->getContent();

        $deletedTags = [];

        foreach ($tagsToDelete as $tag) {
            $tagName = $tag->name();
            $taggings = $this->api
                ->search(
                    'taggings',
                    ['tag' => $tagName, 'resource_id' => $resourceId],
                    ['responseContent' => 'resource']
                )
                ->getContent();
            foreach ($taggings as $tagging) {
                $this->entityManager->remove($tagging);
            }
            $deletedTags[] = $tagName;
        }

        return $deletedTags;
    }
}
