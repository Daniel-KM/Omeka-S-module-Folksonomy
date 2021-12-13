<?php declare(strict_types=1);

namespace Folksonomy\Service\ViewHelper;

use Folksonomy\View\Helper\TagCount;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class TagCountFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new TagCount(
            $services->get('Omeka\EntityManager')
        );
    }
}
