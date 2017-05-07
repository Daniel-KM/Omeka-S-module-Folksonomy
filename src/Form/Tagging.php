<?php

class Tagging_Form_Tagging extends Omeka_Form
{
    protected $_record;

    /**
     * Constructor
     *
     * Registers form view helper as decorator
     *
     * @param mixed $options
     */
    public function __construct($record = null)
    {
        $this->_record = $record;

        parent::__construct();
    }

    public function init()
    {
        parent::init();

        $this->setAction(WEB_ROOT . '/tagging/index/add');
        $this->setAttrib('id', 'tagging-form');
        $user = current_user();

        $this->addElement('text', 'tagging', [
            'label' => __('Add Tags'),
            'description' => __('Separate multiple tags with a "%s".', get_option('tag_delimiter')),
            'required' => true,
            'size' => 60,
            // An internal validator is used after (allow some non alnum
            // characters).
            // TODO Use the regex validator here?
            'validators' => [
                ['validator' => 'StringLength', 'options' => [
                    'min' => 1,
                    'max' => get_option('tagging_max_length_total'),
                    'messages' => [
                        Zend_Validate_StringLength::TOO_SHORT =>
                            __('Proposed tag cannot be empty.'),
                        Zend_Validate_StringLength::TOO_LONG =>
                            __('Proposed tags cannot be longer than %d characters.', get_option('tagging_max_length_total')),
                    ],
                ]],
            ],
            'decorators' => [],
        ]);

        // Assume registered users are trusted and don't make them play recaptcha.
        if (!$user && get_option('recaptcha_public_key') && get_option('recaptcha_private_key')) {
            $this->addElement('captcha', 'captcha', [
                'class' => 'hidden',
                'label' => __("Please verify you're a human"),
                'captcha' => [
                    'captcha' => 'ReCaptcha',
                    'pubkey' => get_option('recaptcha_public_key'),
                    'privkey' => get_option('recaptcha_private_key'),
                    // Make the connection secure so IE8 doesn't complain. if
                    // works, should branch around http: vs https:
                    'ssl' => true,
                ],
                'decorators' => [],
            ]);
        }

        // The legal agreement is checked by default for logged users.
        if (get_option('tagging_legal_text')) {
            $this->addElement('checkbox', 'tagging_legal_text', [
                'label' => get_option('tagging_legal_text'),
                'value' => (boolean) $user,
                'required' => true,
                'uncheckedValue' => '',
                'checkedValue' => 'checked',
                'validators' => [
                    ['notEmpty', true, [
                        'messages' => [
                            'isEmpty' => __('You must agree to the terms and conditions.'),
                        ],
                    ]],
                ],
                'decorators' => ['ViewHelper', 'Errors', ['label', ['escape' => false]]],
            ]);
        }

        // Add some hidden fields to simplify redirection.
        $request = Zend_Controller_Front::getInstance()->getRequest();
        $record_type = empty($this->_record)
            ? ucfirst(Inflector::singularize($request->getControllerName()))
            : get_class($this->_record);
        $record_id = empty($this->_record)
            ? $request->getParam('id')
            : $this->_record->id;
        $this->addElement('hidden', 'path', [
            'value' => $request->getPathInfo(),
            'hidden' => true,
            'class' => 'hidden',
        ]);
        $this->addElement('hidden', 'record_type', [
            'value' => $record_type,
            'hidden' => true,
            'class' => 'hidden',
        ]);
        $this->addElement('hidden', 'record_id', [
            'value' => $record_id,
            'hidden' => true,
            'class' => 'hidden',
        ]);

        $this->addElement('submit', 'submit', [
            'label' => __('Tag it'),
        ]);
    }
}
