<?php
namespace Folksonomy\View\Helper;

use Folksonomy\Entity\Tagging;
use Folksonomy\Form\TaggingForm;
use Omeka\Api\Representation\AbstractResourceEntityRepresentation;
use Zend\View\Helper\AbstractHelper;

class ShowTaggingForm extends AbstractHelper
{
    protected $formElementManager;

    public function __construct($formElementManager)
    {
        $this->formElementManager = $formElementManager;
    }

    /**
     * Return the partial to display the quick tagging form.
     *
     * @return string
     */
    public function __invoke(AbstractResourceEntityRepresentation $resource)
    {
        $view = $this->getView();
        if (!$view->userIsAllowed(Tagging::class, 'create')) {
            return '';
        }

        $user = $view->identity();
        $form = $this->formElementManager->get(TaggingForm::class);
        $form->setOptions([
            'site-slug' => $view->params()->fromRoute('site-slug'),
            'resource_id' => $resource->id(),
            'is_identified' => !empty($user),
        ]);
        $form->init();
        $view->vars()->offsetSet('taggingForm', $form);
        return $view->partial('common/tagging-quick-form');
    }
}
