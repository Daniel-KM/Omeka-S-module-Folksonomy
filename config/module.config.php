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
            'Folksonomy\Form\Tagging' => 'Folksonomy\Form\Tagging',
            'Folksonomy\Form\TagCloudBlock' => 'Folksonomy\Form\TagCloudBlock',
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
            'Folksonomy\Controller\Site\Tagging' => 'Folksonomy\Controller\Site\TaggingController',
            'Folksonomy\Controller\Site\Tag' => 'Folksonomy\Controller\Site\TagController',
        ],
    ],
    'router' => [
        'routes' => [
            'site' => [
                'child_routes' => [
                    'tagging' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/tagging[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Folksonomy\Controller\Site',
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
