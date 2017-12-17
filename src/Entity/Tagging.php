<?php
namespace Folksonomy\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Exception\InvalidArgumentException;
use Omeka\Entity\User;

/**
 * Taggings are events, so one person can add one tag to one resource at a time.
 *
 * @Entity
 * @Table(
 *      uniqueConstraints={
 *          @UniqueConstraint(
 *              name="owner_tag_resource",
 *              columns={"owner_id", "tag_id", "resource_id"}
 *          )
 *      },
 *      indexes={
 *          @Index(columns={"status"})
 *      }
 * )
 * @HasLifecycleCallbacks
 */
class Tagging extends AbstractEntity
{
    const STATUS_PROPOSED = 'proposed';
    const STATUS_ALLOWED = 'allowed';
    const STATUS_APPROVED = 'approved';
    const STATUS_REJECTED = 'rejected';

    /**
     * @Id
     * @Column(type="integer")
     * @GeneratedValue
     */
    protected $id;

    /**
     * Note: Doctrine doesn't recommand enums.
     * @Column(type="string", length=190)
     */
    protected $status;

    /**
     * @ManyToOne(
     *     targetEntity="Folksonomy\Entity\Tag",
     *     fetch="LAZY"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $tag;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\Resource",
     *     fetch="LAZY",
     *     cascade={"persist"}
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $resource;

    /**
     * @ManyToOne(
     *     targetEntity="Omeka\Entity\User",
     *     fetch="LAZY"
     * )
     * @JoinColumn(
     *     nullable=true,
     *     onDelete="SET NULL"
     * )
     */
    protected $owner;

    /**
     * @Column(type="datetime")
     */
    protected $created;

    /**
     * @Column(type="datetime", nullable=true)
     */
    protected $modified;

    public function getId()
    {
        return $this->id;
    }

    public function setStatus($status)
    {
        if (!in_array($status, [
            self::STATUS_PROPOSED,
            self::STATUS_ALLOWED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ])) {
            throw new InvalidArgumentException('Invalid tagging status.');
        }
        $this->status = $status;
    }

    public function getStatus()
    {
        return $this->status;
    }

    public function setTag(Tag $tag = null)
    {
        $this->tag = $tag;
    }

    public function getTag()
    {
        return $this->tag;
    }

    public function setResource($resource = null)
    {
        $this->resource = $resource;
    }

    public function getResource()
    {
        return $this->resource;
    }

    public function setOwner(User $owner = null)
    {
        $this->owner = $owner;
    }

    public function getOwner()
    {
        return $this->owner;
    }

    public function setCreated(DateTime $dateTime)
    {
        $this->created = $dateTime;
    }

    public function getCreated()
    {
        return $this->created;
    }

    public function setModified(DateTime $dateTime = null)
    {
        $this->modified = $dateTime;
    }

    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs)
    {
        $this->created = new DateTime('now');
    }

    /**
     * @PreUpdate
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs)
    {
        $this->modified = new DateTime('now');
    }
}
