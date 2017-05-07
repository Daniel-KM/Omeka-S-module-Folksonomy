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
        // TODO Sort tagging by tag name.
        // 'tag' => 'tag'
        'tag_id' => 'tag',
        'resource_id' => 'resource',
        'item_set_id' => 'resource',
        'item_id' => 'resource',
        'media_id' => 'resource',
        'owner_id' => 'owner',
    ];

    public function getResourceName()
    {
        return 'taggings';
    }

    public function getRepresentationClass()
    {
        return 'Folksonomy\Api\Representation\TaggingRepresentation';
    }

    public function getEntityClass()
    {
        return 'Folksonomy\Entity\Tagging';
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        // The owner, tag and resource can be null.
        $data = $request->getContent();
        $this->hydrateOwner($request, $entity);

        if (isset($data['o:status'])
            && $this->shouldHydrate($request, 'o:status')
        ) {
            $entity->setStatus($data['o:status']);
        }

        if ($this->shouldHydrate($request, 'o-module-folksonomy:tag')) {
            if (isset($data['o-module-folksonomy:tag'])) {
                if (is_object($data['o-module-folksonomy:tag'])) {
                    $tag = $data['o-module-folksonomy:tag'] instanceof Tag
                        ? $data['o-module-folksonomy:tag']
                        : null;
                } else {
                    $tag = $this->getAdapter('tags')
                        ->findEntity(['name' => $data['o-module-folksonomy:tag']]);
                }
            } else {
                $tag = null;
            }
            $entity->setTag($tag);
        }

        if ($this->shouldHydrate($request, 'o:resource')) {
            if (isset($data['o:resource']['o:id'])
                && is_numeric($data['o:resource']['o:id'])
            ) {
                $resource = $this->getAdapter('resources')
                    ->findEntity($data['o:resource']['o:id']);
            } else {
                $resource = null;
            }
            $entity->setResource($resource);
        }
    }

    public function validateRequest(Request $request, ErrorStore $errorStore)
    {
        $data = $request->getContent();
        if (isset($data['o:status'])) {
            $statuses = [
                Tagging::STATUS_PROPOSED,
                Tagging::STATUS_ALLOWED,
                Tagging::STATUS_APPROVED,
                Tagging::STATUS_REJECTED,
            ];
            if (!in_array($data['o:status'], $statuses)) {
                $errorStore->addError(
                    'o:status',
                    sprintf('The status "%s" is unknown.', $data['o:status'])); // @translate
            }
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        // Validate uniqueness.
        $owner = $entity->getOwner();
        $tag = $entity->getTag();
        $resource = $entity->getResource();
        if ($owner && $owner instanceof User
            && $tag && $tag instanceof Tag
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
}
