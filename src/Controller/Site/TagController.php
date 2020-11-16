<?php declare(strict_types=1);
namespace Folksonomy\Controller\Site;

use Laminas\Mvc\Controller\AbstractActionController;
use Laminas\View\Model\ViewModel;

class TagController extends AbstractActionController
{
    public function browseAction()
    {
        $site = $this->currentSite();

        $settings = $this->settings();
        $options = [
            'resourceName' => $settings->get('folksonomy_page_resource_name', 'items'),
            'maxClasses' => $settings->get('folksonomy_page_max_classes', 9),
            'tagNumbers' => $settings->get('folksonomy_page_tag_numbers', false),
        ];

        $this->setBrowseDefaults('name', 'asc');

        $query = $this->params()->fromQuery();
        $query['site_id'] = $site->id();
        // TODO Check if this limit should be removed (check adapter, too).
        $query['limit'] = 0;
        // Never display "rejected" tags on public, in all cases.
        // The "proposed" status may be filtered via the visibility filter.
        $query['status'] = ['allowed', 'approved', 'proposed'];
        $response = $this->api()->search('tags', $query);
        $tags = $response->getContent();

        $view = new ViewModel;
        $view->setVariable('site', $site);
        $view->setVariable('tags', $tags);
        $view->setVariable('options', $options);
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
