<?php
namespace Folksonomy\Service\Controller\Site;

use Folksonomy\Controller\Site\TagController;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class TagControllerFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedNamed, array $options = null)
    {
        $controller = new TagController(
            $services
        );
        return $controller;
    }
}
