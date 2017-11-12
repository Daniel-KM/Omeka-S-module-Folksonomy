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
            __DIR__ . '/../src/Entity',
        ],
        'proxy_paths' => [
            __DIR__ . '/../data/doctrine-proxies',
        ],
        'filters' => [
            'tagging_visibility' => Db\Filter\TaggingVisibilityFilter::class,
        ],
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
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
            'Folksonomy\Form\Config' => Form\Config::class,
            'Folksonomy\Form\Element\TagSelect' => Service\Form\Element\TagSelectFactory::class,
            'Folksonomy\Form\Search' => Form\Search::class,
            'Folksonomy\Form\TagCloudBlock' => Form\TagCloudBlock::class,
        ],
        'factories' => [
            'Folksonomy\Form\Tagging' => Service\Form\TaggingFactory::class,
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
                'base_dir' => __DIR__ . '/../language',
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
        'They will be displayed when approved.', // @translate
        'Reload page to see new tags.', // @translate
        'Request too long to process.', // @translate
        'The resource or the tag doesn’t exist.', // @translate
    ],
];
