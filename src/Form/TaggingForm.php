<?php declare(strict_types=1);

namespace Folksonomy\Form;

use Laminas\Form\Element\Button;
use Laminas\Form\Element\Csrf;
use Laminas\Form\Form;
use Laminas\Http\PhpEnvironment\RemoteAddress;
use Laminas\ServiceManager\ServiceLocatorInterface as FormElementManager;
use Laminas\Validator\StringLength;
use Laminas\View\Helper\Url;
use Omeka\View\Helper\Setting;

class TaggingForm extends Form
{
    /**
     * @var Setting
     */
    protected $settingHelper;

    /**
     * @var Url
     */
    protected $urlHelper;

    /**
     * @var FormElementManager
     */
    protected $formElementManager;

    protected $options = [
        'site-slug' => null,
        'resource_id' => null,
        'is_identified' => false,
    ];

    public function init(): void
    {
        $settingHelper = $this->getSettingHelper();
        $urlHelper = $this->getUrlHelper();
        $resourceId = $this->getOption('resource_id');
        $siteSlug = $this->getOption('site-slug');
        $isPublic = (bool) strlen((string) $siteSlug);
        $action = $isPublic
            ? $urlHelper('site/tagging', ['action' => 'add', 'site-slug' => $siteSlug])
            : $urlHelper('admin/tagging', ['action' => 'add']);

        $this->setAttribute('id', 'tagging-form-' . $resourceId);
        $this->setAttribute('action', $action);
        $this->setAttribute('class', 'tagging-form');
        $this->setAttribute('data-resource-id', $resourceId);

        $this->add([
            'type' => 'hidden',
            'name' => 'resource_id',
            'attributes' => [
                'value' => $resourceId,
                'required' => true,
            ],
        ]);

        $this->add([
            'type' => 'Text',
            'name' => 'o-module-folksonomy:tag-new',
            'options' => [
                'label' => 'Add tags', // @translate
            ],
            'attributes' => [
                'placeholder' => 'Add one or multiple comma-separated new tags', // @translate
                'required' => true,
            ],
            'validators' => [
                ['validator' => 'StringLength', 'options' => [
                    'min' => 1,
                    'max' => $settingHelper('folksonomy_max_length_total'),
                    'messages' => [
                        StringLength::TOO_SHORT =>
                            'Proposed tag cannot be empty.', // @translate
                        StringLength::TOO_LONG =>
                        sprintf('Proposed tags cannot be longer than %d characters.', // @translate
                            $settingHelper('folksonomy_max_length_total')),
                    ],
                ]],
            ],
        ]);

        // Assume registered users are trusted and don't make them play recaptcha.
        if (!$this->getOption('is_identified')) {
            $siteKey = $settingHelper('recaptcha_site_key');
            $secretKey = $settingHelper('recaptcha_secret_key');
            if ($siteKey && $secretKey) {
                $element = $this->getFormElementManager()
                    ->get('Omeka\Form\Element\Recaptcha', [
                        'site_key' => $siteKey,
                        'secret_key' => $secretKey,
                        'remote_ip' => (new RemoteAddress)->getIpAddress(),
                    ]);
                $this->add($element);
            }
        }

        if ($isPublic) {
            // The legal agreement is checked by default for logged users.
            $legalText = $settingHelper('folksonomy_legal_text', '');
            if ($legalText) {
                // TODO Allow html legal agreement in the tagging form help from here.
                $legalText = str_replace('&nbsp;', ' ', strip_tags($legalText));
                $this->add([
                    'type' => 'checkbox',
                    'name' => 'legal_agreement',
                    'options' => [
                        'label' => 'Terms of service', // @translate
                        'info' => $legalText,
                        'label_options' => [
                            'disable_html_escape' => true,
                        ],
                        'use_hidden_element' => false,
                    ],
                    'attributes' => [
                        'value' => $this->getOption('is_identified'),
                        'required' => true,
                    ],
                    'validators' => [
                        ['notEmpty', true, [
                            'messages' => [
                                'isEmpty' => 'You must agree to the terms and conditions.', // @translate
                            ],
                        ]],
                    ],
                ]);
            }

            // An honeypot for anti-spam. It’s hidden, so only bots fill it.
            $this->add([
                'type' => 'Text',
                'name' => 'o-module-folksonomy:check',
                'options' => [
                    'label' => 'String to check', // @translate
                ],
                'attributes' => [
                    'placeholder' => 'Set the string to check', // @translate
                    'required' => false,
                    'style' => 'display: none;',
                ],
                'validators' => [
                    ['validator' => 'StringLength', 'options' => [
                        'min' => 0,
                        'max' => 0,
                    ]],
                ],
            ]);
        }

        $this->add([
            'type' => Csrf::class,
            'name' => sprintf('csrf_%s', $resourceId),
            'options' => [
                'csrf_options' => ['timeout' => 3600],
            ],
        ]);

        $this->add([
            'type' => Button::class,
            'name' => 'submit',
            'options' => [
                'label' => 'Tag it!', // @translate
            ],
            'attributes' => [
                'class' => 'fa fa-tag',
            ],
        ]);
    }

    public function setSettingHelper(Setting $settingHelper): self
    {
        $this->settingHelper = $settingHelper;
        return $this;
    }

    public function getSettingHelper(): Setting
    {
        return $this->settingHelper;
    }

    public function setUrlHelper(Url $urlHelper): self
    {
        $this->urlHelper = $urlHelper;
        return $this;
    }

    public function getUrlHelper(): Url
    {
        return $this->urlHelper;
    }

    public function setFormElementManager(FormElementManager $formElementManager): self
    {
        $this->formElementManager = $formElementManager;
        return $this;
    }

    public function getFormElementManager(): FormElementManager
    {
        return $this->formElementManager;
    }
}
