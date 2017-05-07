<?php
namespace Folksonomy\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Api\ResourceInterface;

/**
 * Tag representation.
 *
 * @internal The api representation of the tag is the name, not the internal id.
 * The internal id is used only internally by the entity and the adapter for
 * performance reasons. The adapter manages the id and the queries automatically
 * with the keys "tag" and "internal_id".
 */
class TagRepresentation extends AbstractEntityRepresentation
{
    public function __construct(ResourceInterface $resource, AdapterInterface $adapter)
    {
        parent::__construct($resource, $adapter);
        $this->setId($resource->getName());
    }

    /**
     * Get the internal database id.
     *
     * @return int
     */
    public function internalId()
    {
        return $this->resource->getId();
    }

    /**
     * Get the name of the tag (alias of the id for representation).
     *
     * @return string
     */
    public function name()
    {
        return $this->resource->getName();
    }

    public function getControllerName()
    {
        return 'tag';
    }

    public function getJsonLdType()
    {
        return 'o-module-folksonomy:Tag';
    }

    public function getJsonLd()
    {
        return [
            'o:id' => $this->id(),
        ];
    }

    public function getReference()
    {
        return new TagReference($this->resource, $this->getAdapter());
    }

    /**
     * Get the taggings associated with this tag.
     *
     * @return array Array of TaggingRepresentations
     */
    public function taggings()
    {
        $taggings = [];
        $taggingAdapter = $this->getAdapter('taggings');
        foreach ($this->resource->getTaggings() as $taggingEntity) {
            $taggings[$taggingEntity->getId()] =
                $taggingAdapter->getRepresentation($taggingEntity);
        }
        return $taggings;
    }

    /**
     * Get the resources associated with this tag.
     *
     * @return array Array of ItemSetRepresentations
     */
    public function resources()
    {
        //TODO Check if resources are true resources (item, item set, media).
        $resources = [];
        $resourceAdapter = $this->getAdapter('resources');
        foreach ($this->resource->getResources() as $resourceEntity) {
            $resources[$resourceEntity->getId()] =
                $resourceAdapter->getRepresentation($resourceEntity);
        }
        return $resources;
    }

    /**
     * Get the owners associated with this tag.
     *
     * @return array Array of UserRepresentations
     */
    public function owners()
    {
        $owners = [];
        $ownerAdapter = $this->getAdapter('users');
        foreach ($this->resource->getOwners() as $ownerEntity) {
            $owners[$ownerEntity->getId()] =
                $ownerAdapter->getRepresentation($ownerEntity);
        }
        return $owners;
    }

    /**
     * Get this tag's specific resource count.
     *
     * @param string $resourceName
     * @return int
     */
    public function count($resourceName = 'resources')
    {
        static $counts = [];
        if (!isset($counts[$resourceName])) {
            if ($resourceName == 'resources') {
                $counts[$resourceName] = $this->resourceCount();
            } else {
                $response = $this->getServiceLocator()->get('Omeka\ApiManager')
                    ->search($resourceName, [
                        'internal_id' => $this->internalId(),
                        'limit' => 0,
                    ]);
                $counts[$resourceName] = $response->getTotalResults();
            }
        }
        return $counts[$resourceName];
    }

    /**
     * Get the total of resource for this tag.
     *
     * @todo Use a NamedNativeQueries.
     */
    protected function resourceCount()
    {
        $entityManager = $this->getServiceLocator()->get('Omeka\EntityManager');
        $conn = $entityManager->getConnection();
        $qb = $conn->createQueryBuilder();
        $qb
            ->select('COUNT(tagging.tag_id)')
            ->from('tagging', 'tagging')
            ->where($qb->expr()->eq('tagging.tag_id', ':tag'))
            ->setParameter('tag', $this->internalId())
            ->groupBy('tagging.tag_id');
        $stmt = $conn->executeQuery($qb, $qb->getParameters());
        $result = $stmt->fetch(\PDO::FETCH_COLUMN);
        return $result;
    }
}
