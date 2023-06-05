<?php declare(strict_types=1);

namespace Folksonomy\View\Helper;

use Laminas\View\Helper\AbstractHelper;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;

class ShowTags extends AbstractHelper
{
    /**
     * @var string
     */
    protected $partial = 'common/site/tag-resource';

    /**
     * Return the partial to display tags.
     *
     * @param AbstractResourceEntityRepresentation $resource
     * @param string $partial
     * @param array $options Options to pass to the partial. Supported by
     * default: delimiter.
     * @return string
     */
    public function __invoke(AbstractResourceEntityRepresentation $resource, $partial = null, array $options = [])
    {
        $view = $this->getView();

        $options['resource'] = $resource;
        $options['tags'] = $this->listResourceTags($resource);
        if ($view->params()->fromRoute('__ADMIN__')) {
            $options['taggings'] = $this->listResourceTaggings($resource);
        }

        if (!$partial) {
            $partial = $this->partial;
        }

        return $view->partial(
            $partial,
            $options
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
