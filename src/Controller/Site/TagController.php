<?php
namespace Folksonomy\Controller\Site;

use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\ViewModel;

class TagController extends AbstractActionController
{
    public function browseAction()
    {
        $site = $this->currentSite();

        $this->setBrowseDefaults('name', 'asc');

        $query = $this->params()->fromQuery();
        $query['site_id'] = $site->id();
        $query['limit'] = 0;
        // Never display "rejected" tags on public, in all cases.
        // The "proposed" status may be filtered via the visibility filter.
        $query['status'] = ['allowed', 'approved', 'proposed'];
        $response = $this->api()->search('tags', $query);
        $tags = $response->getContent();

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
