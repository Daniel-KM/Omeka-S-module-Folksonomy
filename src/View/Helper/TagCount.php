<?php
namespace Folksonomy\View\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Folksonomy\Entity\Tag;
use PDO;
use Zend\View\Helper\AbstractHelper;

class TagCount extends AbstractHelper
{
    /**
     * @var Connection
     */
    protected $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Return the count for a list of tags for a specified resource type.
     *
     * @todo Use Doctrine native queries (here: DBAL query builder).
     *
     * @param array|string $tags If empty, return an array of all tags.
     * @param string $resourceName If empty returns the count of each resource
     * (item set, item and media), the total (resources).
     * @param array|string $statuses Filter these statuses.
     * @param string $orderBy Sort column and direction, for example "tag.name"
     * (default), "count asc", "item_sets", "items" or "media".
     * @param bool $usedOnly Returns only the used tags (default: all tags).
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

        $eqTagTagging = $qb->expr()->eq('tagging.tag_id', 'tag.id');
        $eqResourceTagging = $qb->expr()->eq('resource.id', 'tagging.resource_id');

        // Select all types of resource separately and together.
        if (empty($resourceName)) {
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
        elseif (in_array($resourceName, ['resources', 'resource', 'Omeka\Entity\Resource'])) {
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
            $types = [
                'item_sets' => 'Omeka\Entity\ItemSet',
                'items' => 'Omeka\Entity\Item',
                'media' => 'Omeka\Entity\Media',
                'item_set' => 'Omeka\Entity\ItemSet',
                'item' => 'Omeka\Entity\Item',
                'Omeka\Entity\ItemSet' => 'Omeka\Entity\ItemSet',
                'Omeka\Entity\Item' => 'Omeka\Entity\Item',
                'Omeka\Entity\Media' => 'Omeka\Entity\Media',
            ];
            $resourceType = isset($types[$resourceName]) ? $types[$resourceName] : '';
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
            $tags = array_map(function ($v) {
                return is_object($v) ? ($v instanceof Tag ? $v->getName() : $v->name()) : $v;
            }, is_array($tags) || $tags instanceof ArrayCollection ? $tags : [$tags]);

            // TODO How to do a "WHERE IN" with doctrine and strings?
            $tags = array_map([$this->connection, 'quote'], $tags);
            $qb
                ->andWhere($qb->expr()->in('tag.name', $tags));
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
        $fetchMode = $keyPair && $resourceName
            ? PDO::FETCH_KEY_PAIR
            : (PDO::FETCH_GROUP | PDO::FETCH_UNIQUE);
        $result = $stmt->fetchAll($fetchMode);
        return $result;
    }
}
