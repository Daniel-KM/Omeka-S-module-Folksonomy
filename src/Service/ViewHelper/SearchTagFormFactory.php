<?php declare(strict_types=1);
namespace Folksonomy\Service\ViewHelper;

use Folksonomy\Form\SearchForm;
use Folksonomy\View\Helper\SearchTagForm;
use Interop\Container\ContainerInterface;
use Laminas\ServiceManager\Factory\FactoryInterface;

class SearchTagFormFactory implements FactoryInterface
{
    public function __invoke(ContainerInterface $services, $requestedName, array $options = null)
    {
        $formElementManager = $services->get('FormElementManager');
        $searchForm = $formElementManager->get(SearchForm::class);
        $helper = new SearchTagForm();
        $helper->setSearchForm($searchForm);
        return $helper;
    }
}
