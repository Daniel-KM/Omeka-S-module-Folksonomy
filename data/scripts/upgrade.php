<?php
namespace Folksonomy;

/**
 * @var Module $this
 * @var \Zend\ServiceManager\ServiceLocatorInterface $serviceLocator
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Api\Manager $api
 */
$services = $serviceLocator;
$settings = $services->get('Omeka\Settings');
$config = require dirname(dirname(__DIR__)) . '/config/module.config.php';
$connection = $services->get('Omeka\Connection');
$entityManager = $services->get('Omeka\EntityManager');
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$space = strtolower(__NAMESPACE__);

if (version_compare($oldVersion, '3.3.3', '<')) {
    $config = require __DIR__ . '/config/module.config.php';
    $defaultSettings = $config[strtolower(__NAMESPACE__)]['config'];
    $settings = $serviceLocator->get('Omeka\Settings');
    $settings->set('folksonomy_append_item_set_show',
        $defaultSettings['folksonomy_append_item_set_show']);
    $settings->set('folksonomy_append_item_show',
        $defaultSettings['folksonomy_append_item_show']);
    $settings->set('folksonomy_append_media_show',
        $defaultSettings['folksonomy_append_media_show']);
}

if (version_compare($oldVersion, '3.3.7', '<')) {
    $config = require __DIR__ . '/config/module.config.php';
    $defaultSettings = $config[strtolower(__NAMESPACE__)]['site_settings'];
    $siteSettings = $serviceLocator->get('Omeka\Settings\Site');
    $settings = $serviceLocator->get('Omeka\Settings');
    $api = $serviceLocator->get('Omeka\ApiManager');
    $sites = $api->search('sites')->getContent();
    foreach ($sites as $site) {
        $siteSettings->setTargetId($site->id());
        $siteSettings->set('folksonomy_append_item_set_show',
            $settings->get('folksonomy_append_item_set_show',
                $defaultSettings['folksonomy_append_item_set_show'])
        );
        $siteSettings->set('folksonomy_append_item_show',
            $settings->get('folksonomy_append_item_show',
                $defaultSettings['folksonomy_append_item_show'])
        );
        $siteSettings->set('folksonomy_append_media_show',
            $settings->get('folksonomy_append_media_show',
                $defaultSettings['folksonomy_append_media_show'])
        );
    }
    $settings->delete('folksonomy_append_item_set_show');
    $settings->delete('folksonomy_append_item_show');
    $settings->delete('folksonomy_append_media_show');
}
