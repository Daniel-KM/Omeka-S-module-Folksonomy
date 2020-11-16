<?php declare(strict_types=1);
namespace Folksonomy\View\Helper;

use Folksonomy\Form\SearchForm;
use Laminas\View\Helper\AbstractHelper;

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

    public function setSearchForm(SearchForm $searchForm): void
    {
        $this->searchForm = $searchForm;
    }
}
