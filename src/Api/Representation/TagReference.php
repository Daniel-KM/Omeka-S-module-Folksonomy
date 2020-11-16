<?php declare(strict_types=1);
namespace Folksonomy\Api\Representation;

use Omeka\Api\Adapter\AdapterInterface;
use Omeka\Api\Representation\ResourceReference;
use Omeka\Api\ResourceInterface;

class TagReference extends ResourceReference
{
    public function __construct(ResourceInterface $resource, AdapterInterface $adapter)
    {
        parent::__construct($resource, $adapter);
        $this->id = $resource->getName();
    }
}
