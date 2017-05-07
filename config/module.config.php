<?php
return [
    'api_adapters' => [
        'invokables' => [
            'tags' => 'Tagging\Api\Adapter\TagAdapter',
            'taggings' => 'Tagging\Api\Adapter\TaggingAdapter',
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
            'tagCloud' => 'Tagging\Site\BlockLayout\TagCloud',
        ],
    ],
    'navigation_links' => [
        'invokables' => [
            'browseTags' => 'Tagging\Site\Navigation\Link\BrowseTags',
        ],
    ],
    'form_elements' => [
        'invokables' => [
            'Tagging\Form\Config' => 'Tagging\Form\Config',
            'Tagging\Form\Tagging' => 'Tagging\Form\Tagging',
            'Tagging\Form\TagCloudBlock' => 'Tagging\Form\TagCloudBlock',
        ],
    ],
    'navigation' => [
        'AdminModule' => [
            [
                'label' => 'Taggings', // @translate
                'route' => 'admin/tagging',
                'resource' => 'Tagging\Controller\Admin\Tagging',
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
            'Tagging\Controller\Admin\Tagging' => 'Tagging\Controller\Admin\TaggingController',
            'Tagging\Controller\Site\Tagging' => 'Tagging\Controller\Site\TaggingController',
        ],
    ],
    'router' => [
        'routes' => [
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
                                '__NAMESPACE__' => 'Tagging\Controller\Admin',
                                'controller' => 'Tagging',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'tags' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/tag[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Tagging\Controller\Admin',
                                'controller' => 'Tag',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                ],
            ],
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
                                '__NAMESPACE__' => 'Tagging\Controller\Site',
                                'controller' => 'Tagging',
                                'action' => 'browse',
                            ],
                        ],
                    ],
                    'tags' => [
                        'type' => 'Segment',
                        'options' => [
                            'route' => '/tag[/:action]',
                            'constraints' => [
                                'action' => '[a-zA-Z][a-zA-Z0-9_-]*',
                            ],
                            'defaults' => [
                                '__NAMESPACE__' => 'Tagging\Controller\Site',
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
