<?php
namespace Folksonomy\Api\Adapter;

use Doctrine\ORM\QueryBuilder;
use Omeka\Api\Adapter\AbstractEntityAdapter;
use Omeka\Api\Request;
use Omeka\Api\Response;
use Omeka\Entity\EntityInterface;
use Omeka\Stdlib\ErrorStore;
use Omeka\Stdlib\Message;
use Zend\EventManager\Event;

class TagAdapter extends AbstractEntityAdapter
{
    use QueryBuilderTrait;

    /**
     * {@inheritDoc}
     */
    protected $sortFields = [
        'internal_id' => 'id',
        // "Tag" and "name" are alias.
        'name' => 'name',
        'tag' => 'name',
    ];

    public function getResourceName()
    {
        return 'tags';
    }

    public function getRepresentationClass()
    {
        return 'Folksonomy\Api\Representation\TagRepresentation';
    }

    public function getEntityClass()
    {
        return 'Folksonomy\Entity\Tag';
    }

    public function hydrate(Request $request, EntityInterface $entity,
        ErrorStore $errorStore
    ) {
        if ($this->shouldHydrate($request, 'o:name')) {
            $name = trim($request->getValue('o:name'));
            $entity->setName($name);
        }
    }

    public function validateEntity(EntityInterface $entity, ErrorStore $errorStore)
    {
        $name = $entity->getName();
        if (is_string($name) && trim($name) !== '') {
            $name = trim($name);
            $criteria = [
                'name' => $name,
            ];
            if (!$this->isUnique($entity, $criteria)) {
                $errorStore->addError('o:name', new Message(
                    'The tag "%s" is already taken.', // @translate
                    $name
                ));
            }
        } else {
            $errorStore->addError('o:name', 'A tag must have a name.'); // @translate
        }
    }

    public function buildQuery(QueryBuilder $qb, array $query)
    {
        if (isset($query['internal_id'])) {
            $this->buildQueryIdsItself($qb, $query['internal_id'], 'id');
        }

        if (isset($query['tag'])) {
            $this->buildQueryValuesItself($qb, $query['tag'], 'name');
        }

        if (isset($query['name'])) {
            $this->buildQueryValuesItself($qb, $query['name'], 'name');
        }

        // All tags for these entities ("OR"). If multiple, mixed with "AND".
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
                    'Folksonomy\Entity\Tagging',
                    $taggingAlias,
                    'WITH',
                    $qb->expr()->andX(
                        $qb->expr()->eq($taggingAlias . '.tag', $this->getEntityClass() . '.id'),
                        $qb->expr()->in(
                            $taggingAlias . '.' . $column,
                            $this->createNamedParameter($qb, $entities)
                        )
                    )
                );
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
