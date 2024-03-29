<?php declare(strict_types=1);

namespace Folksonomy\Service\Form\Element;

use Folksonomy\Form\Element\TagSelect;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class TagSelectFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $element = new TagSelect(null, $options ?? []);
        return $element
            ->setApiManager($services->get('Omeka\ApiManager'))
            ->setUrlHelper($services->get('ViewHelperManager')->get('Url'));
    }
}
