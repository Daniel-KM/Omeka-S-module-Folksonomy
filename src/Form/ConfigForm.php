<?php
namespace Folksonomy\Form;

use Omeka\Form\Element\Ckeditor as Ckeditorinline;
use Laminas\Form\Element;
use Laminas\Form\Fieldset;
use Laminas\Form\Form;

class ConfigForm extends Form
{
    public function init()
    {
        // TODO Use the elements of the block layout.
        $this->add([
            'name' => 'folksonomy_tag_page',
            'type' => Fieldset::class,
            'options' => [
                'label' => 'Tag cloud page', // @translate
            ],
        ]);
        $tagPageFieldset = $this->get('folksonomy_tag_page');
        $tagPageFieldset->add([
            'name' => 'folksonomy_page_resource_name',
            'type' => Element\Select::class,
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
            'attributes' => [
                'id' => 'folksonomy_page_resource_name',
            ],
        ]);
        $tagPageFieldset->add([
            'name' => 'folksonomy_page_max_classes',
            'type' => Element\Number::class,
            'options' => [
                'label' => 'Max classes', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy_page_max_classes',
                'min' => 1,
                'max' => 99,
            ],
        ]);
        $tagPageFieldset->add([
            'name' => 'folksonomy_page_tag_numbers',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Display tag numbers', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy_page_tag_numbers',
            ],
        ]);

        $this->add([
            'type' => Fieldset::class,
            'name' => 'folksonomy_public_rights',
            'options' => [
                'label' => 'Public rights', // @translate
            ],
        ]);
        $publicRightsFieldset = $this->get('folksonomy_public_rights');
        $publicRightsFieldset->add([
            'name' => 'folksonomy_public_allow_tag',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Allow public to tag', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy_public_allow_tag',
            ],
        ]);
        $publicRightsFieldset->add([
            'name' => 'folksonomy_public_require_moderation',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Require approbation for public tags', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy_public_require_moderation',
            ],
        ]);
        $publicRightsFieldset->add([
            'name' => 'folksonomy_public_notification',
            'type' => Element\Checkbox::class,
            'options' => [
                'label' => 'Notify by email for public tagging', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy_public_notification',
            ],
        ]);

        $this->add([
            'name' => 'folksonomy_tagging_form',
            'type' => Fieldset::class,
            'options' => [
                'label' => 'Tagging form', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy_tagging_form',
            ],
        ]);
        $taggingFormFieldset = $this->get('folksonomy_tagging_form');
        $taggingFormFieldset->add([
            'name' => 'folksonomy_max_length_tag',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Max length of a proposed tag', // @translate
                'info' => 'The maximum for the database is 190 characters.', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy_max_length_tag',
            ],
        ]);
        $taggingFormFieldset->add([
            'name' => 'folksonomy_max_length_total',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Max length for all proposed tags', // @translate
                'info' => 'Multiple tags can be proposed in one time, separated by comma.', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy_max_length_total',
            ],
        ]);
        $taggingFormFieldset->add([
            'name' => 'folksonomy_message',
            'type' => Element\Text::class,
            'options' => [
                'label' => 'Message to invite to tag', // @translate
                'info' => 'The text to click to display the tag form (a simple "+" by default, customizable in the theme).', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy_message',
            ],
        ]);
        $taggingFormFieldset->add([
            'name' => 'folksonomy_legal_text',
            'type' => CkeditorInline::class,
            'options' => [
                'label' => 'Legal agreement', // @translate
                'info' => 'This text will be shown beside the legal checkbox. Let empty if you don’t want to use a legal agreement.', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy_legal_text',
            ],
        ]);

        $inputFilter = $this->getInputFilter();

        $tagPageFilter = $inputFilter->get('folksonomy_tag_page');
        $tagPageFilter->add([
            'name' => 'folksonomy_page_resource_name',
            'required' => false,
        ]);
        $tagPageFilter->add([
            'name' => 'folksonomy_page_max_classes',
            'required' => false,
        ]);
        $tagPageFilter->add([
            'name' => 'folksonomy_page_tag_numbers',
            'required' => false,
        ]);

        $publicRightsFilter = $inputFilter->get('folksonomy_public_rights');
        $publicRightsFilter->add([
            'name' => 'folksonomy_public_allow_tag',
            'required' => false,
        ]);
        $publicRightsFilter->add([
            'name' => 'folksonomy_public_require_moderation',
            'required' => false,
        ]);
        $publicRightsFilter->add([
            'name' => 'folksonomy_public_notification',
            'required' => false,
        ]);

        $taggingFormFilter = $inputFilter->get('folksonomy_tagging_form');
        $taggingFormFilter->add([
            'name' => 'folksonomy_max_length_tag',
            'required' => false,
        ]);
        $taggingFormFilter->add([
            'name' => 'folksonomy_max_length_total',
            'required' => false,
        ]);
        $taggingFormFilter->add([
            'name' => 'folksonomy_message',
            'required' => false,
        ]);
        $taggingFormFilter->add([
            'name' => 'folksonomy_legal_text',
            'required' => false,
        ]);
    }
}
