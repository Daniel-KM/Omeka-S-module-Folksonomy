<?php
namespace Folksonomy\View\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Folksonomy\Entity\Tag;
use Omeka\Entity\Item;
use Omeka\Entity\ItemSet;
use Omeka\Entity\Media;
use Zend\View\Helper\AbstractHelper;

class TagCount extends AbstractHelper
{
    /**
     * @var Connection
     */
    protected $connection;

    /**
     * @param Connection $connection
     */
    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return the count for a list of tags for a specified resource type.
     *
     * The stats are available directly as method of Tag, so this helper is
     * mainly used for performance (one query for all stats).
     *
     * @todo Use Doctrine native queries (here: DBAL query builder) or repositories.
     *
     * @param array|string $tags If empty, return an array of all the tags. The
     * tag may be an entity, a representation or a name.
     * @param string $resourceName If empty returns the count of each resource
     * (item set, item and media), and the total (resources).
     * @param array|string $statuses Filter these statuses.
     * @param bool $usedOnly Returns only the used tags (default: all tags).
     * @param string $orderBy Sort column and direction, for example "tag.name"
     * (default), "count asc", "item_sets", "items" or "media".
     * @param bool $keyPair Returns a flat array of names and counts when a
     * resource name is set.
     * @return array Associative array with names as keys.
     */
    public function __invoke(
        $tags = [],
        $resourceName = '',
        $statuses = [],
        $usedOnly = false,
        $orderBy = '',
        $keyPair = false
    ) {
        $qb = $this->connection->createQueryBuilder();

        $select = [];
        $select['name'] = 'tag.name';

        $types = [
            'item_sets' => ItemSet::class,
            'items' => Item::class,
            'media' => Media::class,
            'item_set' => ItemSet::class,
            'item' => Item::class,
            ItemSet::class => ItemSet::class,
            Item::class => Item::class,
            Media::class => Media::class,
        ];
        $resourceType = isset($types[$resourceName]) ? $types[$resourceName] : '';

        $eqTagTagging = $qb->expr()->eq('tag.id', 'tagging.tag_id');
        $eqResourceTagging = $qb->expr()->eq('resource.id', 'tagging.resource_id');

        // Select all types of resource separately and together.
        if (empty($resourceType)) {
            $select['count'] = 'COUNT(resource.resource_type) AS "count"';
            $select['item_sets'] = 'SUM(CASE WHEN resource.resource_type = "Omeka\\\\Entity\\\\ItemSet" THEN 1 ELSE 0 END) AS "item_sets"';
            $select['items'] = 'SUM(CASE WHEN resource.resource_type = "Omeka\\\\Entity\\\\Item" THEN 1 ELSE 0 END) AS "items"';
            $select['media'] = 'SUM(CASE WHEN resource.resource_type = "Omeka\\\\Entity\\\\Media" THEN 1 ELSE 0 END) AS "media"';
            if ($usedOnly) {
                $qb
                    ->innerJoin('tag', 'tagging', 'tagging', $eqTagTagging)
                    ->innerJoin('tagging', 'resource', 'resource', $eqResourceTagging);
            } else {
                $qb
                    ->leftJoin('tag', 'tagging', 'tagging', $eqTagTagging)
                    ->leftJoin('tagging', 'resource', 'resource', $eqResourceTagging);
            }
        }

        // Select all resources together.
        elseif ($resourceType === Resource::class) {
            $select['count'] = 'COUNT(tagging.tag_id) AS "count"';
            if ($usedOnly) {
                $qb
                    ->innerJoin(
                        'tag',
                        'tagging',
                        'tagging',
                        $qb->expr()->andX(
                            $eqTagTagging,
                            $qb->expr()->isNotNull('tagging.resource_id')
                    ));
            } else {
                $qb
                    ->leftJoin('tag', 'tagging', 'tagging', $eqTagTagging);
            }
        }

        // Select one type of resource.
        else {
            $eqResourceType = $qb->expr()->eq('resource.resource_type', ':resource_type');
            $qb
                ->setParameter('resource_type', $resourceType);
            if ($usedOnly) {
                $select['count'] = 'COUNT(tagging.tag_id) AS "count"';
                $qb
                    ->innerJoin('tag', 'tagging', 'tagging', $eqTagTagging)
                    ->innerJoin(
                        'tagging',
                        'resource',
                        'resource',
                        $qb->expr()->andX(
                            $eqResourceTagging,
                            $eqResourceType
                    ));
            } else {
                $select['count'] = 'COUNT(resource.resource_type) AS "count"';
                $qb
                    ->leftJoin('tag', 'tagging', 'tagging', $eqTagTagging)
                    ->leftJoin(
                        'tagging',
                        'resource',
                        'resource',
                        $qb->expr()->andX(
                            $eqResourceTagging,
                            $eqResourceType
                    ));
            }
        }

        if ($tags) {
            // Get a list of tag names from a various list of tags (entity,
            // representation, names).
            $tags = array_unique(array_map(function ($v) {
                return is_object($v) ? ($v instanceof Tag ? $v->getName() : $v->name()) : $v;
            }, is_array($tags) || $tags instanceof ArrayCollection ? $tags : [$tags]));

            // TODO How to do a "WHERE IN" with doctrine and strings?
            $quotedTags = array_map([$this->connection, 'quote'], $tags);
            $qb
                ->andWhere($qb->expr()->in('tag.name', $quotedTags));
        }

        if ($statuses) {
            // TODO How to do a "WHERE IN" with doctrine and strings?
            $statuses = array_map([$this->connection, 'quote'], (array) $statuses);
            if ($usedOnly) {
                $qb
                   ->andWhere($qb->expr()->in('tagging.status', $statuses));
            } else {
                $qb
                    ->andWhere(
                        $qb->expr()->orX(
                            $qb->expr()->in('tagging.status', $statuses),
                            $qb->expr()->isNull('tagging.status')
                    ));
            }
        }

        $orderBy = trim($orderBy);
        if (strpos($orderBy, ' ')) {
            $order = explode(' ', $orderBy);
            $orderBy = $orderBy[0];
            $orderDir = $orderBy[1];
        } else {
            $orderBy = $orderBy ?: 'tag.name';
            $orderDir = 'ASC';
        }

        $qb
            ->select($select)
            ->from('tag', 'tag')
            ->groupBy('tag.id')
            ->orderBy($orderBy, $orderDir);

        $stmt = $this->connection->executeQuery($qb, $qb->getParameters());
        $fetchMode = $keyPair && $resourceType
            ? \PDO::FETCH_KEY_PAIR
            : (\PDO::FETCH_GROUP | \PDO::FETCH_UNIQUE);
        $result = $stmt->fetchAll($fetchMode);
        return $result;
    }
}
