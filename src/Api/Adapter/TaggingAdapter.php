<?php
namespace Folksonomy\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Folksonomy\Entity\Tag;
use Folksonomy\Entity\Tagging;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Resource;
use Omeka\Entity\User;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

class TaggingAdapter extends AbstractEntityAdapter
{
    use QueryBuilderTrait;

    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'id' => 'id',
        'status' => 'status',
        'tag_id' => 'tag',
        'resource_id' => 'resource',
        'item_set_id' => 'resource',
        'item_id' => 'resource',
        'media_id' => 'resource',
        'owner_id' => 'owner',
        // For info.
        // 'tag_name' => 'tag',
        // 'owner_name' => 'owner',
        // // 'resource_title' => 'resource',
    ];

    protected $statuses = [
        Tagging::STATUS_PROPOSED,
        Tagging::STATUS_ALLOWED,
        Tagging::STATUS_APPROVED,
        Tagging::STATUS_REJECTED,
    ];

    /**
     * {@inheritdoc}
     */
    public function getResourceName()
    {
        return 'taggings';
    }

    /**
     * {@inheritdoc}
     */
    public function getRepresentationClass()
    {
        return 'Folksonomy\Api\Representation\TaggingRepresentation';
    }

    /**
     * {@inheritdoc}
     */
    public function getEntityClass()
    {
        return 'Folksonomy\Entity\Tagging';
    }

    /**
     * {@inheritdoc}
     */
    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        $data = $request->getContent();

        // The owner, tag and resource can be null.
        if (Request::CREATE == $request->getOperation()) {
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
                        ->findEntity($data['o:resource']['o:id']);
                } else {
                    $resource = null;
                }
                $entity->setResource($resource);
            }
        }

        // Only update status.
        elseif (Request::UPDATE == $request->getOperation()) {
            if ($this->shouldHydrate($request, 'o:status')) {
                $entity->setStatus($data['o:status']);
            }
        }
    }

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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

    /**
     * {@inheritdoc}
     */
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
                    'Folksonomy\Entity\Tag',
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
    }

    /**
     * {@inheritdoc}
     */
    public function sortQuery(QueryBuilder $qb, array $query)
    {
        if (is_string($query['sort_by'])) {
            switch ($query['sort_by']) {
                case 'tag_name':
                    $alias = $this->createAlias();
                    $qb->leftJoin('Folksonomy\Entity\Tagging.tag', $alias)
                        ->addOrderBy($alias . '.name', $query['sort_order']);
                    break;
                // case 'resource_title':
                //     // TODO Order tagging by resource title.
                //     // @see AbstractResourceEntityAdapter::sortQuery()
                //     $alias = $this->createAlias();
                //     break;
                case 'owner_name':
                    $alias = $this->createAlias();
                    $qb->leftJoin('Folksonomy\Entity\Tagging.owner', $alias)
                        ->addOrderBy($alias . '.name', $query['sort_order']);
                    break;
                default:
                    parent::sortQuery($qb, $query);
                    break;
            }
        }
    }
}
