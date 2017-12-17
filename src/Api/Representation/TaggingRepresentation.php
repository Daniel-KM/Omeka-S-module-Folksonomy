<?php
namespace Folksonomy\Api\Representation;

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

    public function status()
    {
        return $this->resource->getStatus();
    }

    public function statusLabel()
    {
        $status = $this->resource->getStatus();
        // May avoid a notice.
        return isset($this->statusLabels[$status])
            ? $this->statusLabels[$status]
            : 'Undefined'; // @translate
    }

    public function isPublic()
    {
        return in_array($this->status(), [Tagging::STATUS_ALLOWED, Tagging::STATUS_APPROVED])
            && !empty($this->resource->getTag());
    }

    /**
     * Get the tag representation of this resource.
     *
     * @return TagRepresentation
     */
    public function tag()
    {
        return $this->getAdapter('tags')
            ->getRepresentation($this->resource->getTag());
    }

    /**
     * Get the tag representation of this resource.
     *
     * @return AbstractResourceRepresentation
     */
    public function resource()
    {
        // TODO Check if true resource.
        return $this->getAdapter('resources')
            ->getRepresentation($this->resource->getResource());
    }

    /**
     * Get the owner representation of this resource.
     *
     * @return UserRepresentation
     */
    public function owner()
    {
        return $this->getAdapter('users')
            ->getRepresentation($this->resource->getOwner());
    }

    public function created()
    {
        return $this->resource->getCreated();
    }

    public function modified()
    {
        return $this->resource->getModified();
    }
}
