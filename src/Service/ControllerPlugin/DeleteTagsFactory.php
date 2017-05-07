<?php
namespace Folksonomy\Service\ControllerPlugin;

use Folksonomy\Mvc\Controller\Plugin\DeleteTags;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class DeleteTagsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        $api = $services->get('Omeka\ApiManager');
        $acl = $services->get('Omeka\Acl');
        $entityManager = $services->get('Omeka\EntityManager');
        $plugin = new DeleteTags(
            $api,
            $acl,
            $entityManager
        );
        return $plugin;
    }
}
