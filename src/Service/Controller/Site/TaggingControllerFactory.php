<?php
namespace Folksonomy\Service\Controller\Site;

use Folksonomy\Controller\Site\TaggingController;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class TaggingControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        $acl = $services->get('Omeka\Acl');
        $entityManager = $services->get('Omeka\EntityManager');
        $controller = new TaggingController(
            $acl,
            $entityManager
        );
        return $controller;
    }
}
