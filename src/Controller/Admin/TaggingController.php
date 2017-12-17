<?php
namespace Folksonomy\Controller\Admin;

use Folksonomy\Entity\Tagging;
use Omeka\Form\ConfirmForm;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;
use Zend\View\Model\ViewModel;

class TaggingController extends AbstractActionController
{
    public function browseAction()
    {
        $this->setBrowseDefaults('created');
        $response = $this->api()->search('taggings', $this->params()->fromQuery());
        $this->paginator($response->getTotalResults(), $this->params()->fromQuery('page'));

        $view = new ViewModel;
        $taggings = $response->getContent();
        $view->setVariable('taggings', $taggings);
        return $view;
    }

    public function showDetailsAction()
    {
        $response = $this->api()->read('taggings', $this->params('id'));
        $tagging = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setVariable('resource', $tagging);
        return $view;
    }

    public function addAction()
    {
        // TODO Validate via form.
        // $form = $this->getForm(TaggingForm::class);

        if (!$this->userIsAllowed(Tagging::class, 'create')) {
            return $this->jsonErrorUnauthorized();
        }

        $resourceId = $this->params()->fromPost('resource_id');
        if (!$resourceId) {
            return $this->jsonErrorNotFound();
        }

        $resource = $this->api()
            ->read('resources', $resourceId, [], ['responseContent' => 'resource'])
            ->getContent();
        if (!$resource) {
            return $this->jsonErrorNotFound();
        }

        $tags = $this->params()->fromPost('o-module-folksonomy:tag-new', '');
        $tags = explode(',', $tags);

        $addedTags = $this->addTags($resource, $tags);
        if (is_null($addedTags)) {
            return $this->jsonErrorEmpty();
        }

        if (empty($addedTags)) {
            return $this->jsonErrorExistingTags();
        }

        return new JsonModel([
            'content' => [
                'resource_id' => $resourceId,
                'tags' => $addedTags,
                'moderation' => !$this->userIsAllowed(Tagging::class, 'update'),
            ],
        ]);
    }

    public function deleteConfirmAction()
    {
        $response = $this->api()->read('taggings', $this->params('id'));
        $tagging = $response->getContent();

        $view = new ViewModel;
        $view->setTerminal(true);
        $view->setTemplate('common/delete-confirm-details');
        $view->setVariable('tagging', $tagging);
        $view->setVariable('resource', $tagging);
        $view->setVariable('resourceLabel', 'tagging');
        $view->setVariable('partialPath', 'folksonomy/admin/tagging/show-details');
        return $view;
    }

    public function deleteAction()
    {
        if ($this->getRequest()->isPost()) {
            $form = $this->getForm(ConfirmForm::class);
            $form->setData($this->getRequest()->getPost());
            if ($form->isValid()) {
                $response = $this->api($form)->delete('taggings', $this->params('id'));
                if ($response) {
                    $this->messenger()->addSuccess('Tagging successfully deleted.'); // @translate
                }
            } else {
                $this->messenger()->addFormErrors($form);
            }
        }
        return $this->redirect()->toRoute('admin/tagging');
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
            $this->messenger()->addError('You must select at least one tagging to batch delete.'); // @translate
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }

        $form = $this->getForm(ConfirmForm::class);
        $form->setData($this->getRequest()->getPost());
        if ($form->isValid()) {
            $response = $this->api($form)->batchDelete('taggings', $resourceIds, [], ['continueOnError' => true]);
            if ($response) {
                $this->messenger()->addSuccess('Taggings successfully deleted.'); // @translate
            }
        } else {
            $this->messenger()->addFormErrors($form);
        }
        return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
    }

    public function batchDeleteAllAction()
    {
        $this->messenger()->addError('Delete of all taggings is not supported currently.'); // @translate
    }

    public function batchApproveAction()
    {
        return $this->batchUpdateStatus(Tagging::STATUS_APPROVED);
    }

    public function batchRejectAction()
    {
        return $this->batchUpdateStatus(Tagging::STATUS_REJECTED);
    }

    protected function batchUpdateStatus($status)
    {
        if (!$this->getRequest()->isPost()) {
            return $this->redirect()->toRoute(null, ['action' => 'browse'], true);
        }

        $resourceIds = $this->params()->fromPost('resource_ids', []);
        // Secure the input.
        $resourceIds = array_filter(array_map('intval', $resourceIds));
        if (empty($resourceIds)) {
            return $this->jsonErrorEmpty();
        }

        $data = [];
        $data['o:status'] = $status;
        $response = $this->api()
            ->batchUpdate('taggings', $resourceIds, $data, ['continueOnError' => true]);
        if (!$response) {
            return $this->jsonErrorUpdate();
        }

        return new JsonModel([
            'content' => [
                'status' => $status,
                'statusLabel' => ucfirst($status),
            ],
        ]);
    }

    public function toggleStatusAction()
    {
        $id = $this->params('id');
        $tagging = $this->api()->read('taggings', $id)->getContent();
        $status = $tagging->status() == Tagging::STATUS_APPROVED
            ? Tagging::STATUS_REJECTED
            : Tagging::STATUS_APPROVED;

        $data = [];
        $data['o:status'] = $status;
        $response = $this->api()
            ->update('taggings', $id, $data, ['isPartial' => true]);
        if (!$response) {
            return $this->jsonErrorUpdate();
        }

        return new JsonModel([
            'content' => [
                'status' => $status,
                'statusLabel' => $response->getContent()->statusLabel(),
            ],
        ]);
    }

    protected function jsonErrorEmpty()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_400);
        return new JsonModel(['error' => 'No taggings submitted.']); // @translate
    }

    protected function jsonErrorExistingTags()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_400);
        return new JsonModel(['error' => 'Submitted tags are already set.']); // @translate
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
        return new JsonModel(['error' => 'Tagging not found.']); // @translate
    }

    protected function jsonErrorUpdate()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_500);
        return new JsonModel(['error' => 'An internal error occurred.']); // @translate
    }
}
