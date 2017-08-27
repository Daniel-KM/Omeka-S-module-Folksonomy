<?php
namespace Folksonomy\View\Helper;

use Folksonomy\Entity\Tagging;
use Omeka\Api\Representation\AbstractResourceRepresentation;
use Zend\View\Helper\AbstractHelper;

class ShowTags extends AbstractHelper
{
    /**
     * Return the partial to display tags.
     *
     * @return string
     */
    public function __invoke(AbstractResourceRepresentation $resource)
    {
        $view = $this->getView();
        $tags = $this->listResourceTags($resource);
        $taggings = $this->listResourceTaggings($resource);
        return $view->partial(
            'common/site/tags-resource.phtml',
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
     * @param AbstractResourceRepresentation $resource
     * @return array
     */
    protected function listResourceTags(AbstractResourceRepresentation $resource)
    {
        $resourceJson = $resource->jsonSerialize();
        return empty($resourceJson['o-module-folksonomy:tag'])
            ? []
            : $resourceJson['o-module-folksonomy:tag'];
    }

    /**
     * Helper to return taggings of a resource.
     *
     * @param AbstractResourceRepresentation $resource
     * @return array
     */
    protected function listResourceTaggings(AbstractResourceRepresentation $resource)
    {
        $resourceJson = $resource->jsonSerialize();
        return empty($resourceJson['o-module-folksonomy:tagging'])
            ? []
            : $resourceJson['o-module-folksonomy:tagging'];
    }
}
