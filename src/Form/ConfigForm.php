<?php
namespace Folksonomy\Form;

use Omeka\Form\Element\Ckeditor as Ckeditorinline;
use Zend\Form\Form;
use Zend\Form\Fieldset;
use Zend\Form\Element\Checkbox;
use Zend\Form\Element\Text;

class ConfigForm extends Form
{
    public function init()
    {
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
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Allow public to tag', // @translate
            ],
        ]);
        $publicRightsFieldset->add([
            'name' => 'folksonomy_public_require_moderation',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Require approbation for public tags', // @translate
            ],
        ]);
        $publicRightsFieldset->add([
            'name' => 'folksonomy_public_notification',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Notify by email for public tagging', // @translate
            ],
        ]);

        $this->add([
            'name' => 'folksonomy_tagging_form',
            'type' => Fieldset::class,
            'options' => [
                'label' => 'Tagging form', // @translate
            ],
        ]);
        $taggingFormFieldset = $this->get('folksonomy_tagging_form');
        $taggingFormFieldset->add([
            'name' => 'folksonomy_max_length_tag',
            'type' => Text::class,
            'options' => [
                'label' => 'Max length of a proposed tag', // @translate
                'info' => 'The maximum for the database is 190 characters.', // @translate
            ],
        ]);
        $taggingFormFieldset->add([
            'name' => 'folksonomy_max_length_total',
            'type' => Text::class,
            'options' => [
                'label' => 'Max length for all proposed tags', // @translate
                'info' => 'Multiple tags can be proposed in one time, separated by comma.', // @translate
            ],
        ]);
        $taggingFormFieldset->add([
            'name' => 'folksonomy_message',
            'type' => Text::class,
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
                'info' => 'This text will be shown beside the legal checkbox. Let empty if you don’t want to use a legal agreement.', // @translate
            ],
            'attributes' => [
                'id' => 'folksonomy-legal-text',
            ],
        ]);

        $inputFilter = $this->getInputFilter();

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
