<?php declare(strict_types=1);

namespace Folksonomy\Form;

use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Omeka\Form\Element as OmekaElement;

class TagCloudFieldset extends Fieldset
{
    public function init(): void
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][heading]',
                'type' => Element\Text::class,
                'options' => [
                    'label' => 'Block title', // @translate
                ],
                'attributes' => [
                    'id' => 'tag-cloud-heading',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][resource_name]',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Resource type', // @translate
                    'info' => 'Browse links are available only for item sets and items.', // @translate
                    'value_options' => [
                        '' => 'All resources (separately)', // @translate
                        'resources' => 'All resources (together)',  // @translate
                        'item_sets' => 'Item sets',  // @translate
                        'items' => 'Items',  // @translate
                        'media' => 'Media',  // @translate
                    ],
                ],
                'attributes' => [
                    'id' => 'tag-cloud-resource-name',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][query]',
                'type' => OmekaElement\Query::class,
                'options' => [
                    'label' => 'Query', // @translate
                    'info' => 'Display resources using this search query. The query is limited to items when resources are selected.', // @translate
                    'query_resource_type' => null,
                    'query_partial_excludelist' => ['common/advanced-search/site'],
                ],
                'attributes' => [
                    'id' => 'tag-cloud-query',
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][max_classes]',
                'type' => Element\Number::class,
                'options' => [
                    'label' => 'Max classes', // @translate
                ],
                'attributes' => [
                    'id' => 'tag-cloud-max-classes',
                    'min' => 1,
                    'max' => 99,
                ],
            ])
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][tag_numbers]',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Display tag numbers', // @translate
                ],
                'attributes' => [
                    'id' => 'tag-cloud-tag-numbers',
                ],
            ])
        ;
    }
}
