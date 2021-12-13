<?php declare(strict_types=1);

namespace Folksonomy;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $serviceLocator
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Api\Manager $api
 */
$services = $serviceLocator;
$settings = $services->get('Omeka\Settings');
$config = require dirname(__DIR__, 2) . '/config/module.config.php';
$connection = $services->get('Omeka\Connection');
$entityManager = $services->get('Omeka\EntityManager');
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');

if (version_compare($oldVersion, '3.3.3', '<')) {
    $config = require __DIR__ . '/config/module.config.php';
    $defaultSettings = $config['folksonomy']['config'];
    $settings = $serviceLocator->get('Omeka\Settings');
    $settings->set('folksonomy_append_item_set_show', $defaultSettings['folksonomy_append_item_set_show']);
    $settings->set('folksonomy_append_item_show', $defaultSettings['folksonomy_append_item_show']);
    $settings->set('folksonomy_append_media_show', $defaultSettings['folksonomy_append_media_show']);
}

if (version_compare($oldVersion, '3.3.7', '<')) {
    $config = require __DIR__ . '/config/module.config.php';
    $defaultSettings = $config['folksonomy']['site_settings'];
    $siteSettings = $serviceLocator->get('Omeka\Settings\Site');
    $settings = $serviceLocator->get('Omeka\Settings');
    $api = $serviceLocator->get('Omeka\ApiManager');
    $sites = $api->search('sites')->getContent();
    foreach ($sites as $site) {
        $siteSettings->setTargetId($site->id());
        $siteSettings->set('folksonomy_append_item_set_show',
            $settings->get('folksonomy_append_item_set_show', $defaultSettings['folksonomy_append_item_set_show'])
        );
        $siteSettings->set('folksonomy_append_item_show',
            $settings->get('folksonomy_append_item_show', $defaultSettings['folksonomy_append_item_show'])
        );
        $siteSettings->set('folksonomy_append_media_show',
            $settings->get('folksonomy_append_media_show', $defaultSettings['folksonomy_append_media_show'])
        );
    }
    $settings->delete('folksonomy_append_item_set_show');
    $settings->delete('folksonomy_append_item_show');
    $settings->delete('folksonomy_append_media_show');
}

if (version_compare($oldVersion, '3.3.9', '<')) {
    $sql = <<<'SQL'
DELETE FROM site_setting
WHERE id IN ("folksonomy_append_item_set_show", "folksonomy_append_item_show", "folksonomy_append_media_show");
SQL;
    $connection->executeStatement($sql);
}
