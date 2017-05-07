<?php
namespace Folksonomy\Service\ViewHelper;

use Folksonomy\View\Helper\TagSelect;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class TagSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $formElementManager = $services->get('FormElementManager');
        return new TagSelect($formElementManager);
    }
}
