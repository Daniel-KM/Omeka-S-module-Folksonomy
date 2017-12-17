<?php
namespace Folksonomy\View\Helper;

use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Zend\View\Helper\AbstractHelper;

class ShowTags extends AbstractHelper
{
    /**
     * Return the partial to display tags.
     *
     * @return string
     */
    public function __invoke(AbstractResourceEntityRepresentation $resource)
    {
        $view = $this->getView();
        $tags = $this->listResourceTags($resource);
        $taggings = $this->listResourceTaggings($resource);
        return $view->partial(
            'common/site/tag-resource',
            [
                'resource' => $resource,
                'tags' => $tags,
                'taggings' => $taggings,
            ]
        );
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

    /**
     * Helper to return taggings of a resource.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @return array
     */
    protected function listResourceTaggings(AbstractResourceEntityRepresentation $resource)
    {
        $resourceJson = $resource->jsonSerialize();
        return empty($resourceJson['o-module-folksonomy:tagging'])
            ? []
            : $resourceJson['o-module-folksonomy:tagging'];
    }
}
