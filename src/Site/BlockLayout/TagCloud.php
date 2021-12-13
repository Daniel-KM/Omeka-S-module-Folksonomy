<?php declare(strict_types=1);

namespace Folksonomy\Site\BlockLayout;

use Laminas\View\Renderer\PhpRenderer;
use Omeka\Api\Representation\SitePageBlockRepresentation;
use Omeka\Api\Representation\SitePageRepresentation;
use Omeka\Api\Representation\SiteRepresentation;
use Omeka\Entity\SitePageBlock;
use Omeka\Site\BlockLayout\AbstractBlockLayout;
use Omeka\Stdlib\ErrorStore;

class TagCloud extends AbstractBlockLayout
{
    /**
     * The default partial view script.
     */
    const PARTIAL_NAME = 'common/block-layout/tag-cloud';

    public function getLabel()
    {
        return 'Tag cloud'; // @translate
    }

    public function onHydrate(SitePageBlock $block, ErrorStore $errorStore)
    {
        $data = $block->getData();
        $data['query'] = ltrim((string) $data['query'], "? \t\n\r\0\x0B");
        $block->setData($data);
    }

    public function form(
        PhpRenderer $view,
        SiteRepresentation $site,
        SitePageRepresentation $page = null,
        SitePageBlockRepresentation $block = null
    ) {
        // Factory is not used to make rendering simpler.
        $services = $site->getServiceLocator();
        $formElementManager = $services->get('FormElementManager');
        $defaultSettings = $services->get('Config')['folksonomy']['block_settings']['tagCloud'];
        $blockFieldset = \Folksonomy\Form\TagCloudFieldset::class;

        $data = $block ? $block->data() + $defaultSettings : $defaultSettings;

        $dataForm = [];
        foreach ($data as $key => $value) {
            $dataForm['o:block[__blockIndex__][o:data][' . $key . ']'] = $value;
        }

        // TODO There is no form for resources.
        $resourceType = $data['resource_name'] ?? 'items';
        if (!in_array($resourceType, ['items', 'item_sets', 'media'])) {
            $resourceType = 'items';
        }

        $fieldset = $formElementManager->get($blockFieldset);
        $fieldset->get('o:block[__blockIndex__][o:data][query]')
            ->setOption('query_resource_type', $resourceType);
        $fieldset->populateValues($dataForm);

        return $view->formCollection($fieldset);
    }

    public function render(PhpRenderer $view, SitePageBlockRepresentation $block)
    {
        // The trim is kept for compatibility with old core blocks.
        $query = [];
        parse_str(ltrim((string) $block->dataValue('query'), "? \t\n\r\0\x0B"), $query);

        $site = $block->page()->site();
        if ($view->siteSetting('browse_attached_items', false)) {
            $query['site_attachments_only'] = true;
        }

        // Allow to force to display resources from another site.
        if (empty($query['site_id'])) {
            $query['site_id'] = $site->id();
        }

        return $view->partial(self::PARTIAL_NAME, [
            'block' => $block,
            'resourceName' => $block->dataValue('resource_name', ''),
            'query' => $query,
            'maxClasses' => (int) $block->dataValue('max_classes', 9),
            'tagNumbers' => (bool) $block->dataValue('tag_numbers', false),
        ]);
    }
}
