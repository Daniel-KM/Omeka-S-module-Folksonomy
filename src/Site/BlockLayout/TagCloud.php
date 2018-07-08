<?php
namespace Folksonomy\Site\BlockLayout;

use Folksonomy\Form\TagCloudBlockForm;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Zend\Form\FormElementManager\FormElementManagerV3Polyfill as FormElementManager;
use Zend\View\Renderer\PhpRenderer;

class TagCloud extends AbstractBlockLayout
{
    /**
     * @var FormElementManager
     */
    protected $formElementManager;

    /**
     * @var array
     */
    protected $defaultSettings = [];

    /**
     * @param FormElementManager $formElementManager
     * @param array $defaultSettings
     */
    public function __construct(FormElementManager $formElementManager, array $defaultSettings)
    {
        $this->formElementManager = $formElementManager;
        $this->defaultSettings = $defaultSettings;
    }

    public function getLabel()
    {
        return 'Tag cloud'; // @translate
    }

    public function form(PhpRenderer $view, SiteRepresentation $site,
        SitePageRepresentation $page = null, SitePageBlockRepresentation $block = null
    ) {
        /** @var \Folksonomy\Form\TagCloudBlockForm $form */
        $form = $this->formElementManager->get(TagCloudBlockForm::class);

        $data = $block
            ? $block->data() + $this->defaultSettings
            : $this->defaultSettings;
        $form->setData([
            'o:block[__blockIndex__][o:data][resource_name]' => $data['resource_name'],
            'o:block[__blockIndex__][o:data][max_classes]' => $data['max_classes'],
            'o:block[__blockIndex__][o:data][tag_numbers]' => $data['tag_numbers'],
        ]);

        $form->prepare();

        return $view->formCollection($form);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        return $view->partial(
            'common/block-layout/tag-cloud',
            ['block' => $block]
        );
    }
}
