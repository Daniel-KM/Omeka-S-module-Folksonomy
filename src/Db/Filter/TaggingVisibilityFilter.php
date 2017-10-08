<?php
namespace Folksonomy\Db\Filter;

use Doctrine\DBAL\Types\Type;
use Doctrine\ORM\Mapping\ClassMetaData;
use Doctrine\ORM\Query\Filter\SQLFilter;
use Folksonomy\Entity\Tag;
use Folksonomy\Entity\Tagging;
use Omeka\Permissions\Acl;

/**
 * Filter tagging by visibility (status "allowed" and "approved" are public).
 *
 * Checks to see if the current user has permission to view taggings, and so the
 * attached tags of a resource.
 */
class TaggingVisibilityFilter extends SQLFilter
{
    /**
     * @var Acl
     */
    protected $acl;

    public function addFilterConstraint(ClassMetadata $targetEntity, $targetTableAlias)
    {
        if (Tagging::class === $targetEntity->getName()) {
            return $this->getTaggingConstraint($targetTableAlias);
        }

        if (Tag::class === $targetEntity->getName()) {
            $constraint = $this->getTaggingConstraint('t');
            if ($constraint !== '') {
                return sprintf(
                    '%1$s.id = (SELECT t.tag_id FROM tagging t WHERE (%2$s) AND t.tag_id = %1$s.id LIMIT 1)',
                    $targetTableAlias, $constraint
                );
            }
        }

        return '';
    }

    /**
     * Get the constraint for taggings.
     *
     * @param string $alias
     * @return string
     */
    protected function getTaggingConstraint($alias)
    {
        if ($this->acl->userIsAllowed(Tagging::class, 'view-all')) {
            return '';
        }

        $constraints = [];

        // Users can view public resources.
        $constraints[] = $alias . '.status IN ("'
            . Tagging::STATUS_ALLOWED . '","'
            . Tagging::STATUS_APPROVED . '")';

        // Users can view all resources they own.
        $identity = $this->acl->getAuthenticationService()->getIdentity();
        if ($identity) {
            $constraints[] = 'OR';
            $constraints[] = sprintf(
                $alias . '.owner_id = %s',
                $this->getConnection()->quote($identity->getId(), Type::INTEGER)
            );
        }

        return implode(' ', $constraints);
    }

    public function setAcl(Acl $acl)
    {
        $this->acl = $acl;
    }
}
