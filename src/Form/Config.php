<?php
namespace Folksonomy\Form;

use Omeka\Form\Element\Ckeditor as Ckeditorinline;
use Zend\Form\Form;

class Config extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'Fieldset',
            'name' => 'folksonomy_public_rights',
            'options' => [
                'label' => 'Public Rights', // @translate
            ],
        ]);
        $publicRightsFieldset = $this->get('folksonomy_public_rights');
        $publicRightsFieldset->add([
            'name' => 'folksonomy_public_allow_tag',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Allow public to tag', // @translate
            ],
        ]);
        $publicRightsFieldset->add([
            'name' => 'folksonomy_public_require_moderation',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Require approbation for public tags', // @translate
            ],
        ]);
        $publicRightsFieldset->add([
            'name' => 'folksonomy_public_notification',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Notify by email for public tagging', // @translate
            ],
        ]);

        $this->add([
            'type' => 'Fieldset',
            'name' => 'folksonomy_tagging_form',
            'options' => [
                'label' => 'Tagging Form', // @translate
            ],
        ]);
        $taggingFormFieldset = $this->get('folksonomy_tagging_form');
        $taggingFormFieldset->add([
            'name' => 'folksonomy_max_length_tag',
            'type' => 'Text',
            'options' => [
                'label' => 'Max length of a proposed tag', // @translate
                'info' => 'The maximum for the database is 190 characters.', // @translate
            ],
        ]);
        $taggingFormFieldset->add([
            'name' => 'folksonomy_max_length_total',
            'type' => 'Text',
            'options' => [
                'label' => 'Max length for all proposed tags', // @translate
                'info' => 'Multiple tags can be proposed in one time, separated by comma.', // @translate
            ],
        ]);
        $taggingFormFieldset->add([
            'name' => 'folksonomy_message',
            'type' => 'Text',
            'options' => [
                'label' => 'Message to invite to tag', // @translate
                'info' => 'The text to click to display the tag form (a simple "+" by default, customizable in the theme).', // @translate
            ],
        ]);
        $taggingFormFieldset->add([
            'name' => 'folksonomy_legal_text',
            'type' => CkeditorInline::class,
            'options' => [
                'label' => 'Legal agreement', // @translate
                'info' => 'This text will be shown beside the legal checkbox.' // @translate
                    . ' ' . 'Let empty if you don’t want to use a legal agreement.', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy-legal-text',
            ],
        ]);
        $taggingFormFieldset->add([
            'name' => 'folksonomy_append_item_set_show',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Append to public item set page automatically', // @translate
            ],
        ]);
        $taggingFormFieldset->add([
            'name' => 'folksonomy_append_item_show',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Append to public item page automatically', // @translate
            ],
        ]);
        $taggingFormFieldset->add([
            'name' => 'folksonomy_append_media_show',
            'type' => 'Checkbox',
            'options' => [
                'label' => 'Append to public media page automatically', // @translate
            ],
        ]);
    }
}
