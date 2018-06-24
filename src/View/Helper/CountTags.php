<?php
namespace Folksonomy\View\Helper;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Zend\View\Helper\AbstractHelper;

class CountTags extends AbstractHelper
{
    /**
     * Count the tags of a resource.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return int
     */
    public function __invoke(AbstractResourceEntityRepresentation $resource)
    {
        $tags = $this->listResourceTags($resource);
        return count($tags);
    }

    /**
     * Helper to return tags of a resource.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return array
     */
    protected function listResourceTags(AbstractResourceEntityRepresentation $resource)
    {
        $resourceJson = $resource->jsonSerialize();
        return empty($resourceJson['o-module-folksonomy:tag'])
            ? []
            : $resourceJson['o-module-folksonomy:tag'];
    }
}
