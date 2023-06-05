<?php declare(strict_types=1);

namespace Folksonomy\Service\Form;

use Folksonomy\Form\TaggingForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class TaggingFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $helpers = $services->get('ViewHelperManager');

        $form = new TaggingForm(null, $options ?? []);
        return $form
            ->setSettingHelper($helpers->get('setting'))
            ->setUrlHelper($helpers->get('Url'))
            ->setFormElementManager($services->get('FormElementManager'));
    }
}
