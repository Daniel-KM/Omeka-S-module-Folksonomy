<?php
namespace Folksonomy\Controller\Site;

use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class TaggingController extends AbstractActionController
{
    public function addAction()
    {
        // TODO Validate via form.
        // $form = $this->getForm(TaggingForm::class);

        if (!empty($this->params()->fromPost('o-module-folksonomy:check'))) {
            return $this->jsonErrorUnauthorized();
        }

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

        if ($this->settings()->get('folksonomy_public_notification')) {
            $this->notifyEmail($resourceId, $addedTags);
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

    /**
     * Notify by email for taggings on a resource.
     *
     * @param int $resourceId
     * @param array $tags
     */
    protected function notifyEmail($resourceId, $tags)
    {
        $site = @$_SERVER['SERVER_NAME'] ?: sprintf('Server (%s)', @$_SERVER['SERVER_ADDR']); // @translate
        $subject = sprintf('[%s] New public tags', $site); // @translate

        $total = count($tags);
        $stringTags = implode('", "', $tags);
        $body = $total <= 1
            ? sprintf('%d tag added to resource #%d: "%s".', // @translate
                $total, $resourceId, $stringTags)
            : sprintf('%d tags added to resource #%d: "%s".', // @translate
                $total, $resourceId, $stringTags);
        $body .= "\r\n\r\n";

        $adminEmail = $this->settings()->get('administrator_email');

        $mailer = $this->mailer();
        $message = $mailer->createMessage();
        $message
            ->addTo($adminEmail)
            ->setSubject($subject)
            ->setBody($body);
        $mailer->send($message);
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
}
