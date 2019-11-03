<?php
namespace Folksonomy\Form;

use Zend\Form\Element;
use Zend\Form\Fieldset;

class TagCloudFieldset extends Fieldset
{
    public function init()
    {
        $this
            ->add([
                'name' => 'o:block[__blockIndex__][o:data][resource_name]',
                'type' => Element\Select::class,
                'options' => [
                    'label' => 'Select resource', // @translate
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
