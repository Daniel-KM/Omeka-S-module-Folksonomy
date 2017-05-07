<?php
namespace Folksonomy\Service\ViewHelper;

use Folksonomy\View\Helper\TagCount;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class TagCountFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $entityManager = $services->get('Omeka\EntityManager');
        $conn = $entityManager->getConnection();
        return new TagCount($conn);
    }
}
