<?php
namespace Folksonomy\Controller\Site;

use Doctrine\ORM\EntityManager;
use Folksonomy\Entity\Tagging;
use Omeka\Permissions\Acl;
use Zend\Http\Response;
use Zend\Mvc\Controller\AbstractActionController;
use Zend\View\Model\JsonModel;

class TaggingController extends AbstractActionController
{
    /**
     * @var Acl
     */
    protected $acl;

    /**
     * @var EntityManager
     */
    protected $entityManager;

    public function __construct(Acl $acl, EntityManager $entityManager)
    {
        $this->acl = $acl;
        $this->entityManager = $entityManager;
    }

    public function addAction()
    {
        if ($this->settings('folksonomy_legal_text') && !$this->params()->fromQuery('legal_agreement')) {
            return $this->jsonErrorLegalAgreement();
        }

        $tags = array_filter(
            array_map(
                'trim',
                explode(',', $this->params()->fromQuery('o-module-folksonomy:tag-new'))
            ),
            function ($v) { return strlen($v); }
        );

        if (empty($tags)) {
            return $this->jsonErrorEmpty();
        }

        $id = $this->params()->fromQuery('resource_id');
        if (!$id) {
            return $this->jsonErrorNotFound();
        }

        // Avoid to throw an error.
        $resource = $this->api()
            ->read('resources', $id, [], ['responseContent' => 'resource'])
            ->getContent();
        if (!$resource) {
            return $this->jsonErrorNotFound();
        }

        if (!$this->acl->userIsAllowed(Tagging::class, 'create')) {
            return $this->jsonErrorUnauthorized();
        }

        $addedTags = $this->addTags($resource, $tags);
        if (empty($addedTags)) {
            return $this->jsonErrorExistingTags();
        }

        $this->entityManager->flush();

        return new JsonModel([
            'content' => [
                'tags' => $addedTags,
                'moderation' => $this->acl->userIsAllowed(Tagging::class, 'update'),
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
}
