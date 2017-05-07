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
    ],
    'view_manager' => [
        'template_path_stack' => [
            __DIR__ . '/../view',
        ],
    ],
    'view_helpers' => [
        'invokables' => [
            'tagSelector' => View\Helper\TagSelector::class,
        ],
        'factories' => [
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
            'Folksonomy\Form\TagCloudBlock' => Form\TagCloudBlock::class,
        ],
        'factories' => [
            'Folksonomy\Form\Tagging' => Service\Form\TaggingFactory::class,
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Taggings', // @translate
                'route' => 'admin/tagging',
                'resource' => Controller\Admin\Tagging::class,
                'privilege' => 'browse',
                'pages' => [
                    [
                        'route' => 'admin/tagging',
                        'visible' => false,
                    ],
                    [
                        'route' => 'admin/tag',
                        'visible' => false,
                    ],
                ],
            ],
        ],
    ],
    'controllers' => [
        'invokables' => [
            'Folksonomy\Controller\Admin\Tagging' => Controller\Admin\TaggingController::class,
            'Folksonomy\Controller\Admin\Tag' => Controller\Admin\TagController::class,
            'Folksonomy\Controller\Site\Tag' => Controller\Site\TagController::class,
            'Folksonomy\Controller\Tagging' => Controller\TaggingController::class,
            'Folksonomy\Controller\Tag' => Controller\TagController::class,
        ],
        'factories' => [
            'Folksonomy\Controller\Site\Tagging' => Service\Controller\Site\TaggingControllerFactory::class,
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
                    'tagging-id' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/tagging/:resource-id[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Site',
                                'controller' => 'Tagging',
                                'action' => 'add',
                                'resource-id' => '\d+',
                            ],
                        ],
                    ],
                    'tag' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/tag[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Site',
                                'controller' => 'Tag',
                                'action' => 'browse',
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
                                'controller' => 'Tag',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                ],
            ],
            'admin' => [
                'child_routes' => [
                    'tagging' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/tagging[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Admin',
                                'controller' => 'Tagging',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'tag' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/tag[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Admin',
                                'controller' => 'Tag',
                                'action' => 'browse',
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
];
