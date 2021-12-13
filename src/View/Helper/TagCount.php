<?php declare(strict_types=1);

namespace Folksonomy\View\Helper;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\DBAL\Connection;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Query\Expr\Join;
use Folksonomy\Entity\Tag;
use Laminas\View\Helper\AbstractHelper;
use Omeka\Entity\Item;
use Omeka\Entity\ItemSet;
use Omeka\Entity\Media;
use Omeka\Entity\Resource;

class TagCount extends AbstractHelper
{
    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(EntityManager $entityManager)
    {
        $this->entityManager = $entityManager;
    }

    /**
     * Return the count for a list of tags for a specified resource type.
     *
     * The stats are available directly as method of Tag, so this helper is
     * mainly used for performance (one query for all stats).
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
        // The entity manager is used instead of the DBAL connection in order to
        // limit tags with an Omeka query.
        // See previous commits to get the DBAL query instead of the ORM one.
        $qb = $this->entityManager->createQueryBuilder();
        $expr = $qb->expr();

        $qb
            ->from(\Folksonomy\Entity\Tag::class, 'tag');

        $select = [];
        $select['name'] = 'tag.name';

        $entityClasses = [
            'item_sets' => ItemSet::class,
            'items' => Item::class,
            'media' => Media::class,
            'item_set' => ItemSet::class,
            'item' => Item::class,
            ItemSet::class => ItemSet::class,
            Item::class => Item::class,
            Media::class => Media::class,
        ];
        $resourceType = $entityClasses[$resourceName] ?? '';

        $eqTagTagging = $expr->eq('tag', 'tagging.tag');
        $eqResourceTagging = $expr->eq('resource', 'tagging.resource');

        // The resource type is not available in doctrine directly, because it
        // is the discriminator column.

        // Select all types of resource separately and together.
        if (empty($resourceType)) {
            $select['total'] = 'COUNT(resource) AS total';
            $select['item_sets'] = 'SUM(CASE WHEN resource INSTANCE OF :class_item_set THEN 1 ELSE 0 END) AS item_sets';
            $select['items'] = 'SUM(CASE WHEN resource INSTANCE OF :class_item THEN 1 ELSE 0 END) AS items';
            $select['media'] = 'SUM(CASE WHEN resource INSTANCE OF :class_media THEN 1 ELSE 0 END) AS media';
            $qb
                ->setParameter('class_item_set', \Omeka\Entity\ItemSet::class)
                ->setParameter('class_item', \Omeka\Entity\Item::class)
                ->setParameter('class_media', \Omeka\Entity\Media::class);
            if ($usedOnly) {
                $qb
                    ->innerJoin(\Folksonomy\Entity\Tagging::class, 'tagging', JOIN::WITH, $eqTagTagging)
                    ->innerJoin(\Omeka\Entity\Resource::class, 'resource', JOIN::WITH, $eqResourceTagging);
            } else {
                $qb
                    ->leftJoin(\Folksonomy\Entity\Tagging::class, 'tagging', JOIN::WITH, $eqTagTagging)
                    ->leftJoin(\Omeka\Entity\Resource::class, 'resource', JOIN::WITH, $eqResourceTagging);
            }
        }

        // Select all resources together.
        elseif ($resourceType === Resource::class) {
            $select['total'] = 'COUNT(tagging.tag) AS total';
            if ($usedOnly) {
                $qb
                    ->innerJoin(\Folksonomy\Entity\Tagging::class, 'tagging', JOIN::WITH, $expr->andX(
                        $eqTagTagging,
                        $expr->isNotNull('tagging.resource')
                    ));
            } else {
                $qb
                    ->leftJoin(\Folksonomy\Entity\Tagging::class, 'tagging', JOIN::WITH, $eqTagTagging);
            }
        }

        // Select one type of resource.
        else {
            $eqResourceType = 'resource INSTANCE OF :class_resource';
            $qb
                ->setParameter('class_resource', $resourceType);
            if ($usedOnly) {
                $select['total'] = 'COUNT(tagging.tag) AS total';
                $qb
                    ->innerJoin(\Folksonomy\Entity\Tagging::class, 'tagging', JOIN::WITH, $eqTagTagging)
                    ->innerJoin(\Omeka\Entity\Resource::class, 'resource', JOIN::WITH, $expr->andX(
                            $eqResourceTagging,
                            $eqResourceType
                    ));
            } else {
                $select['total'] = 'COUNT(resource) AS total';
                $qb
                    ->leftJoin(\Folksonomy\Entity\Tagging::class, 'tagging', JOIN::WITH, $eqTagTagging)
                    ->leftJoin(\Omeka\Entity\Resource::class, 'resource', JOIN::WITH, $expr->andX(
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

            $qb
                ->andWhere($expr->in('tag.name', ':tags'))
                ->setParameter('tags', $tags, Connection::PARAM_STR_ARRAY);
        }

        if ($statuses) {
            if ($usedOnly) {
                $qb
                   ->andWhere($expr->in('tagging.status', ':statuses'));
            } else {
                $qb
                    ->andWhere(
                        $expr->orX(
                            $expr->in('tagging.status', ':statuses'),
                            $expr->isNull('tagging.status')
                    ));
            }
            $qb
                ->setParameter('statuses', $statuses, Connection::PARAM_STR_ARRAY);
        }

        $orderBy = trim((string) $orderBy);
        if (strpos($orderBy, ' ')) {
            $orderBy = explode(' ', $orderBy);
            $orderBy = $orderBy[0];
            $orderDir = $orderBy[1];
        } else {
            $orderBy = $orderBy ?: 'tag.name';
            $orderDir = 'ASC';
        }

        $qb
            ->select(...array_values($select))
            ->groupBy('tag.id')
            ->orderBy($orderBy, $orderDir);

        $result = $qb->getQuery()->getScalarResult();
        return $keyPair && $resourceType
            ? array_column($result, 'total', 'name')
            // Combine is possible because tag names are unique.
            : array_combine(array_column($result, 'name'), $result);
    }
}
