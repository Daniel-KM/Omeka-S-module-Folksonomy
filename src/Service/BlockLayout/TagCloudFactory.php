<?php
namespace Folksonomy\Service\BlockLayout;

use Interop\Container\ContainerInterface;
use Folksonomy\Site\BlockLayout\TagCloud;
use Zend\ServiceManager\Factory\FactoryInterface;

class TagCloudFactory implements FactoryInterface
{
    /**
     * Create the TagCloud block layout service.
     *
     * @return TagCloud
     */
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        return new TagCloud(
            $services->get('FormElementManager'),
            $services->get('Config')['folksonomy']['block_settings']['tagCloud']
        );
    }
}
