<?php
namespace Folksonomy\Form;

use Zend\Form\Element;
use Zend\Form\Fieldset;

class SiteSettingsFieldset extends Fieldset
{
    protected $label = 'Folksonomy'; // @translate

    public function init()
    {
        $this
            ->add([
                'name' => 'folksonomy_append_item_set_show',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Append automatically to item set page', // @translate
                    'info' => 'If unchecked, the tags can be added via the helper in the theme or the block in any page.', // @translate
                ],
                'attributes' => [
                    'id' => 'folksonomy_append_item_set_show',
                ],
            ])
            ->add([
                'name' => 'folksonomy_append_item_show',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Append automatically to item page', // @translate
                    'info' => 'If unchecked, the tags can be added via the helper in the theme or the block in any page.', // @translate
                ],
                'attributes' => [
                    'id' => 'folksonomy_append_item_show',
                ],
            ])
            ->add([
                'name' => 'folksonomy_append_media_show',
                'type' => Element\Checkbox::class,
                'options' => [
                    'label' => 'Append automatically to media page', // @translate
                    'info' => 'If unchecked, the tags can be added via the helper in the theme or the block in any page.', // @translate
                ],
                'attributes' => [
                    'id' => 'folksonomy_append_media_show',
                ],
            ])
        ;
    }
}
