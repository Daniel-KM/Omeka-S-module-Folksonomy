<?php
namespace Folksonomy\Service\Form\Element;

use Folksonomy\Form\Element\TagSelect;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class TagSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new TagSelect(null, $options);
        $element->setApiManager($services->get('Omeka\ApiManager'));
        $element->setUrlHelper($services->get('ViewHelperManager')->get('Url'));
        return $element;
    }
}
