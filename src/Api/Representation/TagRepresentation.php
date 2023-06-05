<?php declare(strict_types=1);

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
    /**
     * Cache for the counts of resources.
     *
     * @var array
     */
    protected $cacheCounts = [];

    public function __construct(ResourceInterface $resource, AdapterInterface $adapter)
    {
        parent::__construct($resource, $adapter);
        $this->setId($resource->getName());
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

    public function getReference(): TagReference
    {
        return new TagReference($this->resource, $this->getAdapter());
    }

    /**
     * Get the internal database id.
     */
    public function internalId(): int
    {
        return $this->resource->getId();
    }

    /**
     * Get the name of the tag (alias of the id for representation).
     */
    public function name(): string
    {
        return $this->resource->getName();
    }

    /**
     * Get the taggings associated with this tag.
     *
     * @return TaggingRepresentation[] Associated by tagging ids.
     */
    public function taggings(): array
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
     * @return \Omeka\Api\Representation\AbstractResourceEntityRepresentation[] Associated by resource ids.
     */
    public function resources(): array
    {
        // Note: Use a workaround because the reverse doctrine relation cannot
        // be set. See the entity.
        // TODO Fix entities for many to many relations.
        $resources = [];
        // $resourceAdapter = $this->getAdapter('resources');
        // foreach ($this->resource->getResources() as $resourceEntity) {
        //     $resources[$resourceEntity->getId()] =
        //         $resourceAdapter->getRepresentation($resourceEntity);
        // }
        $taggings = $this->taggings();
        foreach ($taggings as $tagging) {
            if ($resource = $tagging->resource()) {
                $resources[$resource->id()] = $resource;
            }
        }
        return $resources;
    }

    /**
     * Get the owners associated with this tag.
     *
     * @return \Omeka\Api\Representation\UserRepresentation[] Associated by user ids.
     */
    public function owners(): array
    {
        // Note: Use a workaround because the reverse doctrine relation cannot
        // be set. See the entity.
        // TODO Fix entities for many to many relations.
        $owners = [];
        // $ownerAdapter = $this->getAdapter('users');
        // foreach ($this->resource->getOwners() as $ownerEntity) {
        //     $owners[$ownerEntity->getId()] =
        //         $ownerAdapter->getRepresentation($ownerEntity);
        // }
        $taggings = $this->taggings();
        foreach ($taggings as $tagging) {
            if ($owner = $tagging->owner()) {
                $owners[$owner->id()] = $owner;
            }
        }
        return $owners;
    }

    /**
     * Get this tag's specific resource count.
     */
    public function count(string $resourceType = 'resources'): int
    {
        if (!isset($this->cacheCounts[$resourceType])) {
            $response = $this->getServiceLocator()->get('Omeka\ApiManager')
                ->search('taggings', [
                    'tag' => $this->id(),
                    'resource_type' => $resourceType,
                    'limit' => 0,
                ]);
            $this->cacheCounts[$resourceType] = $response->getTotalResults();
        }
        return $this->cacheCounts[$resourceType];
    }

    public function adminUrl($action = null, $canonical = false)
    {
        $url = $this->getViewHelper('Url');
        return $url(
            'admin/tag-id',
            [
                'action' => $action ?: 'browse-resources',
                'id' => $this->name(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    public function siteUrl($siteSlug = null, $canonical = false)
    {
        if (!$siteSlug) {
            $siteSlug = $this->getServiceLocator()->get('Application')
                ->getMvcEvent()->getRouteMatch()->getParam('site-slug');
        }
        $url = $this->getViewHelper('Url');
        return $url(
            'site/tag-id',
            [
                'site-slug' => $siteSlug,
                'id' => $this->name(),
            ],
            ['force_canonical' => $canonical]
        );
    }

    /**
     * Return the public or admin URL to the resource browse page for this tag.
     *
     * Similar to url(), but with the type of resource.
     *
     * @param string|null $resourceType May be "resource" (unsupported), "item-set",
     * "item" or "media" (unsupported in public view).
     * @param bool $canonical Whether to return an absolute URL
     * @return string
     */
    public function urlResources($resourceType = null, $canonical = false): string
    {
        $mapResource = [
            'resources' => 'resource',
            'items' => 'item',
            'item_sets' => 'item-set',
        ];
        if (isset($mapResource[$resourceType])) {
            $resourceType = $mapResource[$resourceType];
        }
        $routeMatch = $this->getServiceLocator()->get('Application')
            ->getMvcEvent()->getRouteMatch();
        $url = null;
        if ($routeMatch->getParam('__ADMIN__')) {
            $url = $this->getViewHelper('Url');
            if (is_null($resourceType)) {
                $resourceType = 'item';
            }
            return $url(
                'admin/tag-resource',
                [
                    'id' => $this->name(),
                    'resource' => $resourceType,
                ],
                ['force_canonical' => $canonical]
            );
        } elseif ($routeMatch->getParam('__SITE__')) {
            $siteSlug = $this->getServiceLocator()->get('Application')
                ->getMvcEvent()->getRouteMatch()->getParam('site-slug');
            $url = $this->getViewHelper('Url');
            return $url(
                is_null($resourceType) ? 'site/tag-id' : 'site/tag-resource',
                [
                    'site-slug' => $siteSlug,
                    'id' => $this->name(),
                    'resource' => $resourceType,
                ],
                ['force_canonical' => $canonical]
            );
        }
        return $url;
    }
}
