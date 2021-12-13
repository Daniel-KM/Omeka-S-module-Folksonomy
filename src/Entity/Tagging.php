<?php declare(strict_types=1);

namespace Folksonomy\Entity;

use DateTime;
use Doctrine\ORM\Event\LifecycleEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Omeka\Entity\AbstractEntity;
use Omeka\Entity\Exception\InvalidArgumentException;
use Omeka\Entity\Resource;
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
     * @Column(
     *     type="string",
     *     length=190
     * )
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

    public function setStatus(string $status): self
    {
        if (!in_array($status, [
            self::STATUS_PROPOSED,
            self::STATUS_ALLOWED,
            self::STATUS_APPROVED,
            self::STATUS_REJECTED,
        ])) {
            throw new InvalidArgumentException('Invalid tagging status.'); // @translate
        }
        $this->status = $status;
        return $this;
    }

    public function getStatus(): string
    {
        return (string) $this->status;
    }

    public function setTag(?Tag $tag): self
    {
        $this->tag = $tag;
        return $this;
    }

    public function getTag(): ?Tag
    {
        return $this->tag;
    }

    public function setResource(?Resource $resource): self
    {
        $this->resource = $resource;
        return $this;
    }

    public function getResource(): ?Resource
    {
        return $this->resource;
    }

    public function setOwner(?User $owner): self
    {
        $this->owner = $owner;
        return $this;
    }

    public function getOwner(): ?User
    {
        return $this->owner;
    }

    public function setCreated(DateTime $dateTime): self
    {
        $this->created = $dateTime;
        return $this;
    }

    public function getCreated(): DateTime
    {
        return $this->created;
    }

    public function setModified(?DateTime $dateTime): self
    {
        $this->modified = $dateTime;
        return $this;
    }

    public function getModified(): ?DateTime
    {
        return $this->modified;
    }

    /**
     * @PrePersist
     */
    public function prePersist(LifecycleEventArgs $eventArgs): void
    {
        $this->created = new DateTime('now');
    }

    /**
     * @PreUpdate
     */
    public function preUpdate(PreUpdateEventArgs $eventArgs): void
    {
        $this->modified = new DateTime('now');
    }
}
