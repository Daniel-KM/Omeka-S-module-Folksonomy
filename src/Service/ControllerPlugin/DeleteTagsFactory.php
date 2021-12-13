<?php declare(strict_types=1);
namespace Folksonomy\Service\ControllerPlugin;

use Folksonomy\Mvc\Controller\Plugin\DeleteTags;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class DeleteTagsFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
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
