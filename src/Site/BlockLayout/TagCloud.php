<?php
namespace Folksonomy\Site\BlockLayout;

use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Zend\Form\Element\Select;
use Zend\Form\Form;
use Zend\View\Renderer\PhpRenderer;

class TagCloud extends AbstractBlockLayout
{
    public function getLabel()
    {
        return 'Tag cloud'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        $data = $block ? $block->data() : [];

        $form = new Form();
        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][resource_name]',
            'type' => 'Select',
            'options' => [
                'label' => 'Select resource', // @translate
                'info' => 'Browse links are available only for item sets and items.',
                'value_options' => [
                    '' => 'All resources (separately)', // @translate
                    'resources' => 'All resources (together)',  // @translate
                    'item_sets' => 'Item sets',  // @translate
                    'items' => 'Items',  // @translate
                    'media' => 'Media',  // @translate
                ],
            ],
        ]);
        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][max_classes]',
            'type' => 'Number',
            'options' => [
                'label' => 'Max classes', // @translate
            ],
        ]);
        $form->add([
            'name' => 'o:block[__blockIndex__][o:data][tag_numbers]',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Display tag numbers', // @translate
            ],
        ]);

        if ($data) {
            $form->setData([
                'o:block[__blockIndex__][o:data][resource_name]' => $data['resource_name'],
                'o:block[__blockIndex__][o:data][max_classes]' => $data['max_classes'],
                'o:block[__blockIndex__][o:data][tag_numbers]' => $data['tag_numbers'],
            ]);
        }

        return $view->formCollection($form);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return $view->partial(
            'common/block-layout/tag-cloud',
            ['block' => $block]
        );
    }
}
