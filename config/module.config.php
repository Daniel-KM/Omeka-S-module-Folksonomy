<?php
return [
    'api_adapters' => [
        'invokables' => [
            'tags' => 'Folksonomy\Api\Adapter\TagAdapter',
            'taggings' => 'Folksonomy\Api\Adapter\TaggingAdapter',
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
            'tagSelector' => 'Folksonomy\View\Helper\TagSelector',
        ],
        'factories' => [
            'tagSelect' => 'Folksonomy\Service\ViewHelper\TagSelectFactory',
        ],
    ],
    'block_layouts' => [
        'invokables' => [
            'tagCloud' => 'Folksonomy\Site\BlockLayout\TagCloud',
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'browseTags' => 'Folksonomy\Site\Navigation\Link\BrowseTags',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            'Folksonomy\Form\Config' => 'Folksonomy\Form\Config',
            'Folksonomy\Form\Element\TagSelect' => 'Folksonomy\Service\Form\Element\TagSelectFactory',
            'Folksonomy\Form\TagCloudBlock' => 'Folksonomy\Form\TagCloudBlock',
        ],
        'factories' => [
            'Folksonomy\Form\Tagging' => 'Folksonomy\Service\Form\TaggingFactory',
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Taggings', // @translate
                'route' => 'admin/tagging',
                'resource' => 'Folksonomy\Controller\Admin\Tagging',
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
            'Folksonomy\Controller\Admin\Tagging' => 'Folksonomy\Controller\Admin\TaggingController',
            'Folksonomy\Controller\Admin\Tag' => 'Folksonomy\Controller\Admin\TagController',
            'Folksonomy\Controller\Site\Tag' => 'Folksonomy\Controller\Site\TagController',
            'Folksonomy\Controller\Tagging' => 'Folksonomy\Controller\TaggingController',
            'Folksonomy\Controller\Tag' => 'Folksonomy\Controller\TagController',
        ],
        'factories' => [
            'Folksonomy\Controller\Site\Tagging' => 'Folksonomy\Service\Controller\Site\TaggingControllerFactory',
        ],
    ],
    'controller_plugins' => [
        'factories' => [
            'addTags' => 'Folksonomy\Service\ControllerPlugin\AddTagsFactory',
            'deleteTags' => 'Folksonomy\Service\ControllerPlugin\DeleteTagsFactory',
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
