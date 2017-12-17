<?php
namespace Folksonomy\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Folksonomy\Api\Representation\TaggingRepresentation;
use Folksonomy\Entity\Tag;
use Folksonomy\Entity\Tagging;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Item;
use Omeka\Entity\ItemSet;
use Omeka\Entity\Media;
use Omeka\Entity\Resource;
use Omeka\Entity\User;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

class TaggingAdapter extends AbstractEntityAdapter
{
    use QueryBuilderTrait;

    protected $sortFields = [
        'id' => 'id',
        'status' => 'status',
        'tag_id' => 'tag',
        'resource_id' => 'resource',
        'item_set_id' => 'resource',
        'item_id' => 'resource',
        'media_id' => 'resource',
        'owner_id' => 'owner',
        'owner_name' => 'owner',
        // For info.
        // 'tag_name' => 'tag',
        // // 'resource_title' => 'resource',
    ];

    protected $statuses = [
        Tagging::STATUS_PROPOSED,
        Tagging::STATUS_ALLOWED,
        Tagging::STATUS_APPROVED,
        Tagging::STATUS_REJECTED,
    ];

    public function getResourceName()
    {
        return 'taggings';
    }

    public function getRepresentationClass()
    {
        return TaggingRepresentation::class;
    }

    public function getEntityClass()
    {
        return Tagging::class;
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();

        // The owner, tag and resource can be null.
        switch ($request->getOperation()) {
            case Request::CREATE:
                $entity->setStatus($data['o:status']);

                $this->hydrateOwner($request, $entity);

                if (isset($data['o-module-folksonomy:tag'])) {
                    if (is_object($data['o-module-folksonomy:tag'])) {
                        $tag = $data['o-module-folksonomy:tag'] instanceof Tag
                            ? $data['o-module-folksonomy:tag']
                            : null;
                    } elseif (strlen($data['o-module-folksonomy:tag'])) {
                        $tag = $this->getAdapter('tags')
                            ->findEntity(['name' => $data['o-module-folksonomy:tag']]);
                    } else {
                        $tag = null;
                    }
                    $entity->setTag($tag);
                }

                if (isset($data['o:resource'])) {
                    if (is_object($data['o:resource'])) {
                        $resource = $data['o:resource'] instanceof Resource
                            ? $data['o:resource']
                            : null;
                    } elseif (is_numeric($data['o:resource']['o:id'])) {
                        $resource = $this->getAdapter('resources')
                            ->findEntity(['id' => $data['o:resource']['o:id']]);
                    } else {
                        $resource = null;
                    }
                    $entity->setResource($resource);
                }
                break;

            // Only update status.
            case Request::UPDATE:
                if ($this->shouldHydrate($request, 'o:status')) {
                    $entity->setStatus($data['o:status']);
                }
                break;
        }

        $this->updateTimestamps($request, $entity);
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (isset($data['o:status'])) {
            $this->validateStatus($data['o:status'], $errorStore);
        }
        if (Request::CREATE == $request->getOperation()) {
            if (!isset($data['o-module-folksonomy:tag'])
                || (is_string($data['o-module-folksonomy:tag']) && trim($data['o-module-folksonomy:tag']) === '')
            ) {
                $errorStore->addError(
                    'o-module-folksonomy:tag',
                    'The name of the tag must be set.'); // @translate
            }
            if (empty($data['o:resource'])) {
                $errorStore->addError(
                    'o:resource',
                    'The tagged resource must be set.'); // @translate
            }
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $this->validateStatus($entity->getStatus(), $errorStore);

        // Validate uniqueness.
        $tag = $entity->getTag();
        $owner = $entity->getOwner();
        $resource = $entity->getResource();
        if ($tag && $tag instanceof Tag
            && $owner && $owner instanceof User
            && $resource && $resource instanceof Resource
        ) {
            $criteria = [
                'tag' => $tag->getId(),
                'resource' => $resource->getId(),
                'owner' => $owner->getId(),
            ];
            if (!$this->isUnique($entity, $criteria)) {
                $errorStore->addError('o-module-folksonomy:tagging', new Message(
                    'The user #%d has already added the tag "%s" on the resource #%d.', // @translate
                    $owner->getId(), $tag->getName(), $resource->getId()
                ));
            }
        }
    }

    protected function validateStatus($status, ErrorStore $errorStore)
    {
        if (!in_array($status, $this->statuses)) {
            $errorStore->addError(
                'o:status',
                sprintf('The status "%s" is unknown.', $status)); // @translate
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        // TODO Check status according to admin/public.
        // TODO Check resource and owner visibility for public view.

        if (array_key_exists('id', $query)) {
            $this->buildQueryIdsItself($qb, $query['id'], 'id');
        }

        if (isset($query['status'])) {
            $this->buildQueryValuesItself($qb, $query['status'], 'status');
        }

        // All taggins with any tag ("OR"), because one tagging references
        // only one tag.
        if (array_key_exists('tag', $query)) {
            $tags = is_array($query['tag']) ? $query['tag'] : [$query['tag']];
            $tagAlias = $this->createAlias();
            $qb
                ->innerJoin(
                   Tag::class,
                    $tagAlias,
                    'WITH',
                    $qb->expr()->andX(
                        $qb->expr()->eq($tagAlias . '.id', $this->getEntityClass(). '.tag'),
                        $qb->expr()->in(
                            $tagAlias . '.name',
                            $this->createNamedParameter($qb, $tags)
                        )
                    )
                );
        }

        // All taggings with any entities ("OR"). If multiple, mixed with "AND".
        foreach ([
            'tag_id' => 'tag',
            'resource_id' => 'resource',
            'item_set_id' => 'resource',
            'item_id' => 'resource',
            'media_id' => 'resource',
            'owner_id' => 'owner',
        ] as $queryKey => $column) {
            if (array_key_exists($queryKey, $query)) {
                $this->buildQueryIds($qb, $query[$queryKey], $column, 'id');
            }
        }

        if (array_key_exists('has_resource', $query)) {
            // An empty string means true in order to manage get/post query.
            if (in_array($query['has_resource'], [false, 'false', 0, '0'], true)) {
                $qb
                    ->andWhere($qb->expr()->isNull($this->getEntityClass() . '.resource'));
            } else {
                $qb
                    ->andWhere($qb->expr()->isNotNull($this->getEntityClass() . '.resource'));
            }
        }

        if (array_key_exists('resource_type', $query)) {
            $mapResourceTypes = [
                // 'users' => User::class,
                'resources' => Resource::class,
                'item_sets' => ItemSet::class,
                'items' => Item::class,
                'media' => Media::class,
            ];
            if ($query['resource_type'] === 'resources') {
                $qb
                     ->andWhere($qb->expr()->isNotNull($this->getEntityClass() . '.resource'));
            // TODO Distinct users, else there may be x times the same tagger.
            // The issue doesn't occur for resource, since there is a check
            // before.
            // } elseif ($query['resource_type'] === 'users') {
            //     $qb
            //         ->andWhere($qb->expr()->isNotNull($this->getEntityClass() . '.owner'));
            } elseif (isset($mapResourceTypes[$query['resource_type']])) {
                $entityAlias = $this->createAlias();
                $qb
                    ->innerJoin(
                        $mapResourceTypes[$query['resource_type']],
                        $entityAlias,
                        'WITH',
                        $qb->expr()->eq(
                            $this->getEntityClass() . '.resource',
                            $entityAlias . '.id'
                        )
                    );
            } elseif ($query['resource_type'] !== '') {
                $qb
                    ->andWhere('1 = 0');
            }
        }
    }

    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            switch ($query['sort_by']) {
                case 'tag_name':
                    $alias = $this->createAlias();
                    $qb->leftJoin(Tagging::class . '.tag', $alias)
                        ->addOrderBy($alias . '.name', $query['sort_order']);
                    break;
                // case 'resource_title':
                //     // TODO Order tagging by resource title.
                //     // @see AbstractResourceEntityAdapter::sortQuery()
                //     $alias = $this->createAlias();
                //     break;
                case 'owner_name':
                    $alias = $this->createAlias();
                    $qb->leftJoin(Tagging::class . '.owner', $alias)
                        ->addOrderBy($alias . '.name', $query['sort_order']);
                    break;
                default:
                    parent::sortQuery($qb, $query);
                    break;
            }
        }
    }

    public function preprocessBatchUpdate(array $data, Request $request)
    {
        $updatables = [
            'o:status' => true,
        ];
        $rawData = $request->getContent();
        $rawData = array_intersect_key($rawData, $updatables);
        $data = $rawData + $data;
        return $data;
    }
}
