<?php
namespace Folksonomy\Api\Representation;

use Omeka\Api\ResourceInterface;
use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ResourceReference;

class TagReference extends ResourceReference
{
    public function __construct(ResourceInterface $resource, AdapterInterface $adapter)
    {
        parent::__construct($resource, $adapter);
        $this->id = $resource->getName();
    }
}
