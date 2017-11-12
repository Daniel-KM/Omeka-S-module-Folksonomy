<?php
namespace Folksonomy\Service\ViewHelper;

use Folksonomy\Form\SearchForm;
use Folksonomy\View\Helper\SearchTagForm;
use Interop\Container\ContainerInterface;
use Zend\ServiceManager\Factory\FactoryInterface;

class SearchTagFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $formElementManager = $services->get('FormElementManager');
        $searchForm = $formElementManager->get(SearchForm::class);
        $form = new SearchTagForm();
        $form->setSearchForm($searchForm);
        return $form;
    }
}
