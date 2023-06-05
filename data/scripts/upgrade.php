<?php declare(strict_types=1);

namespace Folksonomy;

use Omeka\Stdlib\Message;

/**
 * @var Module $this
 * @var \Laminas\ServiceManager\ServiceLocatorInterface $services
 * @var string $newVersion
 * @var string $oldVersion
 *
 * @var \Omeka\Api\Manager $api
 * @var \Omeka\Settings\Settings $settings
 * @var \Doctrine\DBAL\Connection $connection
 * @var \Doctrine\ORM\EntityManager $entityManager
 * @var \Omeka\Mvc\Controller\Plugin\Messenger $messenger
 */
$plugins = $services->get('ControllerPluginManager');
$api = $plugins->get('api');
$settings = $services->get('Omeka\Settings');
$connection = $services->get('Omeka\Connection');
$messenger = $plugins->get('messenger');
$entityManager = $services->get('Omeka\EntityManager');

if (version_compare($oldVersion, '3.3.3', '<')) {
    $config = require __DIR__ . '/config/module.config.php';
    $defaultSettings = $config['folksonomy']['config'];
    $settings->set('folksonomy_append_item_set_show', $defaultSettings['folksonomy_append_item_set_show']);
    $settings->set('folksonomy_append_item_show', $defaultSettings['folksonomy_append_item_show']);
    $settings->set('folksonomy_append_media_show', $defaultSettings['folksonomy_append_media_show']);
}

if (version_compare($oldVersion, '3.3.7', '<')) {
    $config = require __DIR__ . '/config/module.config.php';
    $defaultSettings = $config['folksonomy']['site_settings'];
    $siteSettings = $services->get('Omeka\Settings\Site');
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

if (version_compare($oldVersion, '3.3.11.0', '<')) {
    $message = new Message(
        'It’s now possible to limit tag cloud to the current site or with a query.' // @translate
    );
    $messenger->addSuccess($message);
    $message = new Message(
        'The key for the count of tags has been renamed from "count" to "total". Check your theme if you modified the template.' // @translate
    );
    $messenger->addWarning($message);
}
