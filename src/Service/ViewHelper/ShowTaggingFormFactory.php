<?php
namespace Folksonomy\Service\ViewHelper;

use Folksonomy\View\Helper\ShowTaggingForm;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class ShowTaggingFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $formElementManager = $services->get('FormElementManager');
        return new ShowTaggingForm($formElementManager);
    }
}
