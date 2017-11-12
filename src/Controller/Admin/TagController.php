<?php
namespace Folksonomy\Controller\Admin;

use Omeka\Form\ConfirmForm;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class TagController extends AbstractActionController
{
    public function browseAction()
    {
        $this->setBrowseDefaults('name', 'asc');
        $response = $this->api()->search('tags', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $tags = $response->getContent();
        $tagCount = $this->viewHelpers()->get('tagCount');
        $tagCount = $tagCount($tags);

        $view = new ViewModel;
        $view->setVariable('tags', $tags);
        $view->setVariable('tagCount', $tagCount);
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('tags', $this->params('id'));
        $tag = $response->getContent();

        $tagCount = $this->viewHelpers()->get('tagCount');
        $tagCount = $tagCount($tag);
        $tagCount = reset($tagCount);

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $tag);
        $view->setVariable('tagCount', $tagCount);
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('tags', $this->params('id'));
                if ($response) {
                    $this->messenger()->addSuccess('Tag successfully deleted.'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute('admin/tag');
    }

    public function deleteConfirmAction()
    {
        $response = $this->api()->read('tags', $this->params('id'));
        $tag = $response->getContent();

        $tagCount = $this->viewHelpers()->get('tagCount');
        $tagCount = $tagCount($tag);
        $tagCount = reset($tagCount);

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('tag', $tag);
        $view->setVariable('tagCount', $tagCount);
        $view->setVariable('resource', $tag);
        $view->setVariable('resourceLabel', 'tag');
        $view->setVariable('partialPath', 'folksonomy/admin/tag/show-details');
        return $view;
    }

    public function batchDeleteConfirmAction()
    {
        $form = $this->getForm(ConfirmForm::class);
        $routeAction = $this->params()->fromQuery('all') ? 'batch-delete-all' : 'batch-delete';
        $form->setAttribute('action', $this->url()->fromRoute(null, ['action' => $routeAction], true));
        $form->setButtonLabel('Confirm delete'); // @translate
        $form->setAttribute('id', 'batch-delete-confirm');
        $form->setAttribute('class', $routeAction);

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('form', $form);
        return $view;
    }

    public function batchDeleteAction()
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }

        $resourceIds = $this->params()->fromPost('resource_ids', []);
        if (!$resourceIds) {
            $this->messenger()->addError('You must select at least one tag to batch delete.'); // @translate
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }

        $form = $this->getForm(ConfirmForm::class);
        $form->setData($this->getRequest()->getPost());
        if ($form->isValid()) {
            $response = $this->api($form)->batchDelete('tags', $resourceIds, [], ['continueOnError' => true]);
            if ($response) {
                $this->messenger()->addSuccess('Tags successfully deleted.'); // @translate
            }
        } else {
            $this->messenger()->addFormErrors($form);
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function batchDeleteAllAction()
    {
        $this->messenger()->addError('Delete of all tags is not supported currently.'); // @translate
    }

    public function updateAction()
    {
        $id = $this->params('id');
        $name = $this->params()->fromPost('text');
        $tag = $this->api()->read('tags', $id)->getContent();

        $data = [];
        $data['o:name'] = $name;
        $response = $this->api()->update('tags', $id, $data, ['isPartial' => true]);
        if (!$response) {
            return $this->jsonErrorName();
        }

        $tag = $response->getContent();
        $escape = $this->viewHelpers()->get('escapeHtml');
        return new JsonModel([
            'content' => [
                'text' => $tag->name(),
                'escaped' => $escape($tag->name()),
                'urls' => [
                    'update' => $tag->url('update'),
                    'delete_confirm' => $tag->url('delete-confirm'),
                    'item_sets' => $tag->urlResources('item-set'),
                    'items' => $tag->urlResources('item'),
                    'media' => $tag->urlResources('media'),
                ],
            ],
        ]);
    }

    public function browseResourcesAction()
    {
        return $this->redirect()->toRoute(
            'admin/default',
            [
                'controller' => $this->params('resource', 'item'),
                'action' => 'browse',
            ],
            ['query' => ['tag' => $this->params('id', '')]]
        );
    }

    protected function jsonErrorEmpty()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_400);
        return new JsonModel(['error' => 'No tags submitted.']); // @translate
    }

    protected function jsonErrorName()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_400);
        return new JsonModel(['error' => 'This tag is invalid: it is a duplicate or it contains forbidden characters.']); // @translate
    }

    protected function jsonErrorUnauthorized()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_403);
        return new JsonModel(['error' => 'Unauthorized access.']); // @translate
    }

    protected function jsonErrorNotFound()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_404);
        return new JsonModel(['error' => 'Tag not found.']); // @translate
    }

    protected function jsonErrorUpdate()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_500);
        return new JsonModel(['error' => 'An internal error occurred.']); // @translate
    }
}
