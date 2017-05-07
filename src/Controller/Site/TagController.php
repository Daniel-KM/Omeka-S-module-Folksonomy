<?php
namespace Folksonomy\Controller\Site;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\ServiceManager\ServiceLocatorInterface;
use Zend\View\Model\ViewModel;

class TagController extends AbstractActionController
{
    /**
     * @var ServiceLocatorInterface
     */
    protected $services;

    /**
     * @param EntityManager $entityManager
     */
    public function __construct(ServiceLocatorInterface $services)
    {
        $this->services = $services;
    }

    public function browseAction()
    {
        $site = $this->currentSite();

        $this->setBrowseDefaults('name', 'asc');

        $query = $this->params()->fromQuery();
        $query['site_id'] = $site->id();
        $query['limit'] = 0;

        $entityManagerFilters = $this->services->get('Omeka\EntityManager')->getFilters();

        $entityManagerFilters->enable('tagging_visibility');
        $entityManagerFilters->getFilter('tagging_visibility')->setServiceLocator($this->services);
        $response = $this->api()->search('tags', $query);
        $tags = $response->getContent();
        $entityManagerFilters->disable('tagging_visibility');

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('tags', $tags);
        return $view;
    }

    public function browseResourcesAction()
    {
        return $this->redirect()->toRoute(
            'site/resource',
            [
                'controller' => $this->params('resource', 'item'),
                'action' => 'browse',
                'site-slug' => $this->params('site-slug'),
            ],
            ['query' => ['tag' => $this->params('id', '')]]
        );
    }
}
