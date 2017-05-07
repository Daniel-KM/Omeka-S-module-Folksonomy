<?php
namespace Folksonomy\Service\Form;

use Folksonomy\Form\Tagging;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class TaggingFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $form = new Tagging(null, $options);
        $viewHelperManager = $services->get('ViewHelperManager');
        $form->setSettingHelper($viewHelperManager->get('setting'));
        $form->setUrlHelper($viewHelperManager->get('Url'));
        $form->setFormElementManager($services->get('FormElementManager'));
        return $form;
    }
}
