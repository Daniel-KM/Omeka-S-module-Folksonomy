<?php declare(strict_types=1);

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
            'countTags' => View\Helper\CountTags::class,
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
    'resource_page_block_layouts' => [
        'invokables' => [
            'tagging' => Site\ResourcePageBlockLayout\Tagging::class,
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
            Form\TagCloudFieldset::class => Form\TagCloudFieldset::class,
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
                        'type' => \Laminas\Router\Http\Literal::class,
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
                        'type' => \Laminas\Router\Http\Segment::class,
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
                        'type' => \Laminas\Router\Http\Segment::class,
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
                        'type' => \Laminas\Router\Http\Literal::class,
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
                        'type' => \Laminas\Router\Http\Segment::class,
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
                        'type' => \Laminas\Router\Http\Segment::class,
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
                        'type' => \Laminas\Router\Http\Segment::class,
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
                        'type' => \Laminas\Router\Http\Segment::class,
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
                        'type' => \Laminas\Router\Http\Segment::class,
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
                        'type' => \Laminas\Router\Http\Segment::class,
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
        'They will be displayed definitely when approved.', // @translate
        'Reload page to see new tags.', // @translate
        'Request too long to process.', // @translate
        'The resource or the tag doesn’t exist.', // @translate
        'Something went wrong', // @translate

    ],
    'csvimport' => [
        'mappings' => [
            'item_sets' => [
                'mappings' => [
                    Mapping\FolksonomyMapping::class,
                ],
            ],
            'items' => [
                'mappings' => [
                    Mapping\FolksonomyMapping::class,
                ],
            ],
            'media' => [
                'mappings' => [
                    Mapping\FolksonomyMapping::class,
                ],
            ],
            'resources' => [
                'mappings' => [
                    Mapping\FolksonomyMapping::class,
                ],
            ],
        ],
        'automapping' => [
            'tag' => [
                'name' => 'tag',
                'value' => 1,
                'label' => 'Tagging [Tag]', // @translate
                'class' => 'tagging',
            ],
            'tagging' => [
                'name' => 'tagging',
                'value' => 1,
                'label' => 'Tagging []', // @translate
                'class' => 'tagging',
            ],
        ],
        'user_settings' => [
            'csvimport_automap_user_list' => [
                'tag' => 'tag',
                'tags' => 'tag',
                'tagger' => 'tagging {owner}',
                'tag status' => 'tagging {status}',
                'tag date' => 'tagging {created}',
            ],
        ],
    ],
    'folksonomy' => [
        'config' => [
            'folksonomy_page_resource_name' => 'items',
            'folksonomy_page_max_classes' => 9,
            'folksonomy_page_tag_numbers' => false,
            'folksonomy_public_allow_tag' => true,
            'folksonomy_public_require_moderation' => false,
            'folksonomy_public_notification' => true,
            'folksonomy_max_length_tag' => 190,
            'folksonomy_max_length_total' => 1000,
            'folksonomy_message' => '+',
            'folksonomy_legal_text' => '<p>I agree with <a rel="license" href="#" target="_blank">terms of use</a> and I accept to free my contribution under the license <a rel="license" href="https://creativecommons.org/licenses/by-sa/3.0/" target="_blank">CC&nbsp;BY-SA</a>.</p>',
        ],
        'block_settings' => [
            'tagCloud' => [
                'heading' => '',
                'resource_name' => 'items',
                'query' => '',
                'max_classes' => 9,
                'tag_numbers' => false,
            ],
        ],
    ],
];
