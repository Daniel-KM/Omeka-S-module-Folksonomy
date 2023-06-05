<?php declare(strict_types=1);

namespace Folksonomy\Site\ResourcePageBlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Omeka\Site\ResourcePageBlockLayout\ResourcePageBlockLayoutInterface;

class Tagging implements ResourcePageBlockLayoutInterface
{
    public function getLabel() : string
    {
        return 'Folksonomy tagging'; // @translate
    }

    public function getCompatibleResourceNames() : array
    {
        return [
            'items',
            'media',
            'item_sets',
        ];
    }

    public function render(PhpRenderer $view, AbstractResourceEntityRepresentation $resource) : string
    {
        return $view->partial('common/resource-page-block-layout/tagging', [
            'resource' => $resource,
        ]);
    }
}
