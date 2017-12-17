<?php
namespace Folksonomy;

return [
    'api_adapters' => [
        'invokables' => [
            'tags' => Api\Adapter\TagAdapter::class,
            'taggings' => Api\Adapter\TaggingAdapter::class,
        ],
    ],
    'entity_manager' => [
        'mapping_classes_paths' => [
            dirname(__DIR__) . '/src/Entity',
        ],
        'proxy_paths' => [
            dirname(__DIR__) . '/data/doctrine-proxies',
        ],
        'filters' => [
            'tagging_visibility' => Db\Filter\TaggingVisibilityFilter::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            dirname(__DIR__) . '/view',
        ],
        'strategies' => [
            'ViewJsonStrategy',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'showTags' => View\Helper\ShowTags::class,
            'tagSelector' => View\Helper\TagSelector::class,
        ],
        'factories' => [
            'searchTagForm' => Service\ViewHelper\SearchTagFormFactory::class,
            'showTaggingForm' => Service\ViewHelper\ShowTaggingFormFactory::class,
            'tagCount' => Service\ViewHelper\TagCountFactory::class,
            'tagSelect' => Service\ViewHelper\TagSelectFactory::class,
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'tagCloud' => Site\BlockLayout\TagCloud::class,
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'browseTags' => Site\Navigation\Link\BrowseTags::class,
        ],
    ],
    'form_elements' => [
        'invokables' => [
            Form\ConfigForm::class => Form\ConfigForm::class,
            Form\SearchForm::class => Form\SearchForm::class,
        ],
        'factories' => [
            Form\Element\TagSelect::class => Service\Form\Element\TagSelectFactory::class,
            Form\TaggingForm::class => Service\Form\TaggingFormFactory::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Folksonomy', // @translate
                'route' => 'admin/tag',
                'controller' => Controller\Admin\TagController::class,
                'action' => 'browse',
                'pages' => [
                    [
                        'label' => 'Tags', // @translate
                        'route' => 'admin/tag',
                        'controller' => Controller\Admin\TagController::class,
                        'action' => 'browse',
                    ],
                    [
                        'label' => 'Taggings', // @translate
                        'route' => 'admin/tagging',
                        'controller' => Controller\Admin\TaggingController::class,
                        'action' => 'browse',
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            Controller\Admin\TagController::class => Controller\Admin\TagController::class,
            Controller\Admin\TaggingController::class => Controller\Admin\TaggingController::class,
            Controller\Site\TagController::class => Controller\Site\TagController::class,
            Controller\Site\TaggingController::class => Controller\Site\TaggingController::class,
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'addTags' => Service\ControllerPlugin\AddTagsFactory::class,
            'deleteTags' => Service\ControllerPlugin\DeleteTagsFactory::class,
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'tag' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/tag',
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Site',
                                'controller' => Controller\Site\TagController::class,
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'tag-id' => [
                        'type' => 'Segment',
                        'options' => [
                            // There is no action in public views, else force
                            // the ending "/" or make the action unskippable.
                            'route' => '/tag/:id',
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Site',
                                'controller' => Controller\Site\TagController::class,
                                'action' => 'browse-resources',
                                'resource' => 'item',
                            ],
                        ],
                    ],
                    'tag-resource' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:resource/tag/:id',
                            'constraints' => [
                                'resource' => 'item|item-set|media|resource',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Site',
                                'controller' => Controller\Site\TagController::class,
                                'resource' => 'item',
                                'action' => 'browse-resources',
                            ],
                        ],
                    ],
                    // A simple common alias to browse all tags.
                    'tags' => [
                        'type' => 'Literal',
                        'options' => [
                            'route' => '/tags',
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Site',
                                'controller' => Controller\Site\TagController::class,
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'tagging' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/tagging[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'resource-id' => '\d*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Site',
                                'controller' => Controller\Site\TaggingController::class,
                                'action' => 'add',
                            ],
                        ],
                    ],
                ],
            ],
            'admin' => [
                'child_routes' => [
                    'tag' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/tag[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Admin',
                                'controller' => Controller\Admin\TagController::class,
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'tag-id' => [
                        'type' => 'Segment',
                        'options' => [
                            // The action is not skippable in order to use name.
                            // Require ending "/" when there is no action.
                            'route' => '/tag/:id/:action',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Admin',
                                'controller' => Controller\Admin\TagController::class,
                                'action' => 'browse-resources',
                            ],
                        ],
                    ],
                    'tag-resource' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/:resource/tag/:id',
                            'constraints' => [
                                'resource' => 'item|item-set|media|resource',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Admin',
                                'controller' => Controller\Admin\TagController::class,
                                'resource' => 'item',
                                'action' => 'browse-resources',
                            ],
                        ],
                    ],
                    'tagging' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/tagging[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Admin',
                                'controller' => Controller\Admin\TaggingController::class,
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'tagging-id' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/tagging/:id[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                                'id' => '\d+',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Admin',
                                'controller' => Controller\Admin\TaggingController::class,
                                'action' => 'show',
                            ],
                        ],
                    ],
                ],
            ],
        ],
    ],
    'translator' => [
        'translation_file_patterns' => [
            [
                'type' => 'gettext',
                'base_dir' => dirname(__DIR__) . '/language',
                'pattern' => '%s.mo',
                'text_domain' => null,
            ],
        ],
    ],
    'js_translate_strings' => [
        'Proposed', // @translate
        'Allowed', // @translate
        'Approved', // @translate
        'Rejected', // @translate
        'You should accept the legal agreement.', // @translate
        'Data were added to the resource.', // @translate
        'They will be displayed definively when approved.', // @translate
        'Reload page to see new tags.', // @translate
        'Request too long to process.', // @translate
        'The resource or the tag doesn’t exist.', // @translate
        'Something went wrong', // @translate

    ],
    'folksonomy' => [
        'settings' => [
            'folksonomy_public_allow_tag' => true,
            'folksonomy_public_require_moderation' => false,
            'folksonomy_public_notification' => true,
            'folksonomy_max_length_tag' => 190,
            'folksonomy_max_length_total' => 1000,
            'folksonomy_message' => '+',
            'folksonomy_legal_text' => '<p>I agree with <a rel="licence" href="#" target="_blank">terms of use</a> and I accept to free my contribution under the licence <a rel="licence" href="https://creativecommons.org/licenses/by-sa/3.0/" target="_blank">CC BY-SA</a>.</p>',
        ],
        'site_settings' => [
            'folksonomy_append_item_set_show' => true,
            'folksonomy_append_item_show' => true,
            'folksonomy_append_media_show' => true,
        ],
    ],
];
