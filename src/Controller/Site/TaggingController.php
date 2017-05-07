<?php
namespace Folksonomy\Controller\Site;

use Zend\Http\Headers;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class TaggingController extends AbstractActionController
{
    public function addAction()
    {
        $this->addJsonHeader();

        // TODO Validate via form.
        // $form = $this->getForm(TaggingForm::class);

        $legalText = $this->settings()->get('folksonomy_legal_text', '');
        if ($legalText && empty($this->params()->fromPost('legal_agreement'))) {
            return $this->jsonErrorLegalAgreement();
        }

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
                'moderation' => $this->settings()->get('folksonomy_public_require_moderation')
                    || !$this->userIsAllowed(Tagging::class, 'update'),
            ],
        ]);
    }

    protected function jsonErrorLegalAgreement()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_400);
        return new JsonModel(['error' => 'You should accept the legal agreement.']); // @translate
    }

    protected function jsonErrorEmpty()
    {
        $response = $this->getResponse();
        $response->setStatusCode(Response::STATUS_CODE_400);
        return new JsonModel(['error' => 'No tags submitted.']); // @translate
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
        return new JsonModel(['error' => 'Resource not found.']); // @translate
    }

    /**
     * Make compatible with not up-to-date dependencies of Omeka S (json is
     * returned as html in the Omeka S Beta 3 release).
     */
    protected function addJsonHeader()
    {
        $headers = new Headers();
        $headers->addHeaderLine('Content-Type', 'application/json; charset=utf-8');
        $this->getResponse()->setHeaders($headers);
    }
}
