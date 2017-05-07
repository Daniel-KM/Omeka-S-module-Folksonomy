<?php
namespace Folksonomy\Service\ControllerPlugin;

use Folksonomy\Mvc\Controller\Plugin\AddTags;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class AddTagsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');
        $acl = $services->get('Omeka\Acl');
        $settings = $services->get('Omeka\Settings');
        $apiAdapterManager = $services->get('Omeka\ApiAdapterManager');
        $entityManagerFilters = $services->get('Omeka\EntityManager')->getFilters();
        $plugin = new AddTags(
            $api,
            $acl,
            $settings,
            $apiAdapterManager,
            $entityManagerFilters
        );
        return $plugin;
    }
}
