<?php
namespace Folksonomy\Entity;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Omeka\Entity\AbstractEntity;

/**
 * @Entity
 */
class Tag extends AbstractEntity
{
    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * Note: The limit of 190 is related to the format of the base (utf8mb4) and
     * to the fact that there is an index and the max index size is 767, so
     * 190 x 4 = 760.
     * @Column(length=190, unique=true)
     */
    protected $name;

    /**
     * One Tag has Many Taggings.
     * @var Collection
     * @OneToMany(
     *     targetEntity="Folksonomy\Entity\Tagging",
     *     mappedBy="tag",
     *     fetch="EXTRA_LAZY",
     *     orphanRemoval=true,
     *     indexBy="id"
     * )
     * @JoinTable(name="tagging",
     *      joinColumns={
     *          @JoinColumn(
     *              name="tag_id",
     *              referencedColumnName="id"
     *          )
     *      },
     *      inverseJoinColumns={
     *          @JoinColumn(
     *              name="tagging_id",
     *              referencedColumnName="id"
     *          )
     *      }
     * )
     */
    protected $taggings;

    /* *
     * @todo A many to many relation requires to set the relation in the core too.
     *
     * Many Tags have Many Resources.
     * @var Collection
     * @ManyToMany(
     *     targetEntity="Omeka\Entity\Resource",
     *     mappedBy="tag",
     *     fetch="EXTRA_LAZY",
     *     orphanRemoval=false,
     *     indexBy="id"
     * )
     * @JoinTable(name="tagging",
     *      joinColumns={
     *          @JoinColumn(
     *              name="tag_id",
     *              referencedColumnName="id"
     *          )
     *      },
     *      inverseJoinColumns={
     *          @JoinColumn(
     *              name="resource_id",
     *              referencedColumnName="id"
     *          )
     *      }
     * )
     */
    protected $resources;

    /* *
     * @todo A many to many relation requires to set the relation in the core too.
     *
     * Many Tags have Many Owners.
     * @var Collection
     * @ManyToMany(
     *     targetEntity="Omeka\Entity\User",
     *     mappedBy="tag",
     *     fetch="EXTRA_LAZY",
     *     orphanRemoval=false,
     *     indexBy="id"
     * )
     * @JoinTable(name="tagging",
     *      joinColumns={
     *          @JoinColumn(
     *               name="tag_id",
     *               referencedColumnName="id"
     *           )
     *      },
     *      inverseJoinColumns={
     *          @JoinColumn(
     *              name="owner_id",
     *              referencedColumnName="id"
     *          )
     *      }
     * )
     */
    protected $owners;

    public function __construct()
    {
        $this->taggings = new ArrayCollection;
        $this->resources = new ArrayCollection;
        $this->owners = new ArrayCollection;
    }

    public function getId()
    {
        return $this->id;
    }

    public function setName($name)
    {
        $this->name = $name;
    }

    public function getName()
    {
        return $this->name;
    }

    public function getTaggings()
    {
        return $this->taggings;
    }

    public function getResources()
    {
        return $this->resources;
    }

    public function getOwners()
    {
        return $this->owners;
    }
}
