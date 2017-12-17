<?php
namespace Folksonomy\View\Helper;

use Folksonomy\Form\SearchForm;
use Zend\View\Helper\AbstractHelper;

class SearchTagForm extends AbstractHelper
{
    /**
     * @var SearchForm
     */
    protected $searchForm;

    /**
     * Return the partial to display search tags.
     *
     * @return string
     */
    public function __invoke()
    {
        return $this->searchForm;
    }

    public function setSearchForm(SearchForm $searchForm)
    {
        $this->searchForm = $searchForm;
    }
}
