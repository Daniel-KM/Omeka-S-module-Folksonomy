<?php declare(strict_types=1);

namespace Folksonomy\Api\Representation;

use DateTime;
use Folksonomy\Entity\Tagging;
use Omeka\Api\Representation\AbstractEntityRepresentation;
use Omeka\Api\Representation\AbstractResourceRepresentation;
use Omeka\Api\Representation\UserRepresentation;

class TaggingRepresentation extends AbstractEntityRepresentation
{
    /**
     * @var array
     */
    protected $statusLabels = [
        Tagging::STATUS_PROPOSED => 'Proposed', // @translate
        Tagging::STATUS_ALLOWED => 'Allowed', // @translate
        Tagging::STATUS_APPROVED => 'Approved', // @translate
        Tagging::STATUS_REJECTED => 'Rejected', // @translate
    ];

    public function getControllerName()
    {
        return 'tagging';
    }

    public function getJsonLdType()
    {
        return 'o-module-folksonomy:Tagging';
    }

    public function getJsonLd()
    {
        $tag = null;
        if ($this->tag()) {
            $tag = $this->tag()->getReference();
        }

        $resource = null;
        if ($this->resource()) {
            $resource = $this->resource()->getReference();
        }

        $owner = null;
        if ($this->owner()) {
            $owner = $this->owner()->getReference();
        }

        $created = [
            '@value' => $this->getDateTime($this->created()),
            '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
        ];
        $modified = null;
        if ($this->modified()) {
            $modified = [
                '@value' => $this->getDateTime($this->modified()),
                '@type' => 'http://www.w3.org/2001/XMLSchema#dateTime',
            ];
        }

        // TODO Describe parameters of the tagging (@id, not only o:id, etc.)?

        return [
            'o:id' => $this->id(),
            'o:status' => $this->status(),
            'o-module-folksonomy:tag' => $tag,
            'o:resource' => $resource,
            'o:owner' => $owner,
            'o:created' => $created,
            'o:modified' => $modified,
        ];
    }

    public function status(): string
    {
        return $this->resource->getStatus();
    }

    public function statusLabel(): string
    {
        $status = $this->resource->getStatus();
        return $this->statusLabels[$status] ?? 'Undefined'; // @translate
    }

    public function isPublic(): bool
    {
        return in_array($this->status(), [Tagging::STATUS_ALLOWED, Tagging::STATUS_APPROVED])
            && !empty($this->resource->getTag());
    }

    /**
     * Get the tag representation of this tagging.
     */
    public function tag(): ?TagRepresentation
    {
        $tag = $this->resource->getTag();
        return $tag
            ? $this->getAdapter('tags')->getRepresentation($tag)
            : null;
    }

    /**
     * Get the tag representation of this resource.
     */
    public function resource(): ?AbstractResourceRepresentation
    {
        $taggedResource = $this->resource->getResource();
        return $taggedResource
            ? $this->getAdapter('resources')->getRepresentation($taggedResource)
            : null;
    }

    /**
     * Get the owner representation of this resource.
     *
     * @return UserRepresentation
     */
    public function owner(): ?UserRepresentation
    {
        $owner = $this->resource->getOwner();
        return $owner
            ? $this->getAdapter('users')->getRepresentation($owner)
            : null;
    }

    public function created(): DateTime
    {
        return $this->resource->getCreated();
    }

    public function modified(): ?DateTime
    {
        return $this->resource->getModified();
    }
}
