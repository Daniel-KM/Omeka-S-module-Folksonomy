<?php declare(strict_types=1);

namespace Folksonomy\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Folksonomy\Api\Representation\TagRepresentation;
use Folksonomy\Entity\Tag;
use Folksonomy\Entity\Tagging;
use Laminas\EventManager\Event;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Entity\EntityInterface;
use Omeka\Entity\Item;
use Omeka\Entity\ItemSet;
use Omeka\Entity\Media;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;

class TagAdapter extends AbstractEntityAdapter
{
    use QueryBuilderTrait;

    protected $sortFields = [
        'internal_id' => 'id',
        // "Tag" is an alias of "name".
        'name' => 'name',
        'tag' => 'name',
        // For info.
        // 'count' => 'total',
        // 'total' => 'total',
        // 'item_sets' => 'item_sets',
        // 'items' => 'items',
        // 'media' => 'media',
        // 'recent' => 'recent',
    ];

    protected $scalarFields = [
        'id' => 'id',
        'name' => 'name',
        'tag' => 'name',
    ];

    public function getResourceName()
    {
        return 'tags';
    }

    public function getRepresentationClass()
    {
        return TagRepresentation::class;
    }

    public function getEntityClass()
    {
        return Tag::class;
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ): void {
        if ($this->shouldHydrate($request, 'o:name')) {
            $name = trim($request->getValue('o:name'));
            $entity->setName($name);
        }
    }

    public function validateRequest(Request $request, ErrorStore $errorStore): void
    {
        $data = $request->getContent();
        if (array_key_exists('o:name', $data)) {
            $result = $this->validateTagName($data['o:name'], $errorStore);
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore): void
    {
        $name = $entity->getName();
        if ($this->validateTagName($name, $errorStore)) {
            $criteria = [
                'name' => $name,
            ];
            if (!$this->isUnique($entity, $criteria)) {
                $errorStore->addError('o:name', new Message(
                    'The tag "%s" is already taken.', // @translate
                    $name
                ));
            }
        }
    }

    /**
     * Validate a name.
     */
    protected function validateTagName($name, ErrorStore $errorStore): bool
    {
        $result = true;
        $sanitized = $this->sanitizeLightString($name);
        if (is_string($name) && $sanitized !== '') {
            $name = $sanitized;
            $sanitized = $this->sanitizeString($sanitized);
            if ($name !== $sanitized) {
                $errorStore->addError('o:name', new Message(
                    'The tag "%s" contains forbidden characters.', // @translate
                    $name
                ));
                $result = false;
            }
        } else {
            $errorStore->addError('o:name', 'A tag must have a name.'); // @translate
            $result = false;
        }
        return $result;
    }

    public function buildQuery(QueryBuilder $qb, array $query): void
    {
        $expr = $qb->expr();

        if (isset($query['internal_id'])) {
            $this->buildQueryIdsItself($qb, $query['internal_id'], 'id');
        }

        if (isset($query['tag'])) {
            $this->buildQueryValuesItself($qb, $query['tag'], 'name');
        }

        if (isset($query['name'])) {
            $this->buildQueryValuesItself($qb, $query['name'], 'name');
        }

        // All tags for these entities ("OR"). If multiple, mixed with "AND",
        // so, for mixed resources, use "resource_id".
        $subQueryKeys = array_intersect_key(
            [
                'tagging_id' => 'id',
                'resource_id' => 'resource',
                'item_set_id' => 'resource',
                'item_id' => 'resource',
                'media_id' => 'resource',
                'owner_id' => 'owner',
                'status' => 'status',
            ],
            $query
        );
        foreach ($subQueryKeys as $queryKey => $column) {
            $entities = is_array($query[$queryKey]) ? $query[$queryKey] : [$query[$queryKey]];
            $taggingAlias = $this->createAlias();
            $qb
                ->innerJoin(
                    Tagging::class,
                    $taggingAlias,
                    'WITH',
                    $expr->andX(
                        $expr->eq($taggingAlias . '.tag', 'omeka_root.id'),
                        $expr->in(
                            $taggingAlias . '.' . $column,
                            $this->createNamedParameter($qb, $entities)
                        )
                    )
                );
        }
    }

    public function sortQuery(QueryBuilder $qb, array $query): void
    {
        if (is_string($query['sort_by'])) {
            $expr = $qb->expr();

            // TODO Use Doctrine native queries (here: ORM query builder).
            switch ($query['sort_by']) {
                // @deprecated Use "total" instead of "count".
                case 'count':
                case 'total':
                    $taggingAlias = $this->createAlias();
                    $orderAlias = $this->createAlias();
                    $orderBy = 'COUNT(' . $taggingAlias . '.tag)';
                    $qb
                        ->leftJoin(
                            Tagging::class,
                            $taggingAlias,
                            'WITH',
                            $expr->eq($taggingAlias . '.tag', Tag::class)
                        )
                        ->addSelect($orderBy . ' AS HIDDEN ' . $orderAlias)
                        ->addOrderBy($orderAlias, $query['sort_order'])
                    ;
                    break;
                case 'item_sets':
                case 'items':
                case 'media':
                    $types = [
                        'item_sets' => ItemSet::class,
                        'items' => Item::class,
                        'media' => Media::class,
                    ];
                    $resourceType = $types[$query['sort_by']];
                    $taggingAlias = $this->createAlias();
                    $resourceAlias = $this->createAlias();
                    $orderAlias = $this->createAlias();
                    $orderBy = 'COUNT(' . $resourceAlias . '.id)';
                    $qb
                        ->leftJoin(
                            Tagging::class,
                            $taggingAlias,
                            'WITH',
                            $expr->eq($taggingAlias . '.tag', Tag::class)
                        )
                        ->leftJoin(
                            $resourceType,
                            $resourceAlias,
                            'WITH',
                            $expr->eq($resourceAlias . '.id', $taggingAlias . '.resource')
                        )
                        ->addSelect($orderBy . ' AS HIDDEN ' . $orderAlias)
                        ->addOrderBy($orderAlias, $query['sort_order'])
                    ;
                    break;
                case 'recent':
                    $taggingAlias = $this->createAlias();
                    $orderBy = $taggingAlias . '.created';
                    $qb
                        ->leftJoin(
                            Tagging::class,
                            $taggingAlias,
                            'WITH',
                            $expr->eq($taggingAlias . '.tag', Tag::class)
                        )
                        ->addOrderBy($orderBy, $query['sort_order'])
                    ;
                    break;
                default:
                    parent::sortQuery($qb, $query);
                    break;
            }
        }
    }

    /** Next read()/update()/deleteEntity() allows to use the name as id. **/
    /* @todo Use event? (delete requires an overriding anyway) */

    /**
     * {@inheritDoc}
     * @see \Omeka\Api\Adapter\AbstractEntityAdapter::read()
     *
     * @internal Override the method to use the request id as name.
     */
    public function read(Request $request)
    {
        $entity = $this->findEntity(['name' => $request->getId()], $request);
        $this->authorize($entity, Request::READ);
        $event = new Event('api.find.post', $this, [
            'entity' => $entity,
            'request' => $request,
        ]);
        $this->getEventManager()->triggerEvent($event);
        return new Response($entity);
    }

    /**
     * Returns a sanitized string.
     *
     * @param string $string The string to sanitize.
     * @return string The sanitized string.
     */
    protected function sanitizeString($string): string
    {
        // Quote is allowed.
        $string = strip_tags((string) $string);
        $string = preg_replace('~^[\p{Z}/\\?<>:*%|"`&;]+|[\p{Z}/\\?<>:*%|"`&;]+$~u', '', $string);
        $string = preg_replace('/[\(\{]/', '[', $string);
        $string = preg_replace('/[\)\}]/', ']', $string);
        $string = preg_replace('~[[:cntrl:]/\\\?<>\*\%\|\"`\&\;#+\^\$\s]~', ' ', $string);
        return trim(preg_replace('/\s+/', ' ', $string));
    }

    /**
     * Returns a light sanitized string.
     *
     * @param string $string The string to sanitize.
     * @return string The sanitized string.
     */
    protected function sanitizeLightString($string): string
    {
        return trim(preg_replace('/\s+/', ' ', (string) $string));
    }

    /**
     * {@inheritDoc}
     * @see \Omeka\Api\Adapter\AbstractEntityAdapter::update()
     *
     * @internal Override the method to use the request id as name.
     */
    public function update(Request $request)
    {
        $entity = $this->findEntity(['name' => $request->getId()], $request);
        $this->hydrateEntity($request, $entity, new ErrorStore);
        if ($request->getOption('flushEntityManager', true)) {
            $this->getEntityManager()->flush();
        }
        return new Response($entity);
    }

    /**
     * {@inheritDoc}
     * @see \Omeka\Api\Adapter\AbstractEntityAdapter::deleteEntity()
     *
     * @internal Override the method to use the request id as name.
     */
    public function deleteEntity(Request $request)
    {
        $entity = $this->findEntity(['name' => $request->getId()], $request);
        $this->authorize($entity, Request::DELETE);
        $event = new Event('api.find.post', $this, [
            'entity' => $entity,
            'request' => $request,
        ]);
        $this->getEventManager()->triggerEvent($event);
        $this->getEntityManager()->remove($entity);
        return $entity;
    }
}
