<?php declare(strict_types=1);

namespace Folksonomy\Form\Element;

use Laminas\Form\Element\Select;
use Laminas\View\Helper\Url;
use Omeka\Api\Manager as ApiManager;

class TagSelect extends Select
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    /**
     * @var \Laminas\View\Helper\Url
     */
    protected $urlHelper;

    public function getValueOptions(): array
    {
        $query = $this->getOption('query');
        if (!is_array($query)) {
            $query = [];
        }
        if (!isset($query['sort_by'])) {
            $query['sort_by'] = 'name';
        }

        $valueOptions = [];
        $response = $this->getApiManager()->search('tags', $query);
        foreach ($response->getContent() as $representation) {
            $valueOptions[$representation->id()] = $representation->name();
        }

        $prependValueOptions = $this->getOption('prepend_value_options');
        if (is_array($prependValueOptions)) {
            $valueOptions = $prependValueOptions + $valueOptions;
        }
        return $valueOptions;
    }

    public function setOptions($options)
    {
        if (!empty($options['chosen'])) {
            $defaultOptions = [
                'resource_value_options' => [
                    'resource' => 'tags',
                    'query' => [],
                    'option_text_callback' => function ($v) {
                        return $v->name();
                    },
                ],
            ];
            $options = $options
                ? array_merge_recursive($defaultOptions, $options)
                : $defaultOptions;

            $urlHelper = $this->getUrlHelper();
            $defaultAttributes = [
                'id' => 'select-tag',
                'class' => 'chosen-select',
                'data-placeholder' => 'Select tags', // @translate
                'data-api-base-url' => $urlHelper('api/default', ['resource' => 'tags']),
            ];
            $this->setAttributes($defaultAttributes);
        }

        return parent::setOptions($options);
    }

    public function setApiManager(ApiManager $apiManager): self
    {
        $this->apiManager = $apiManager;
        return $this;
    }

    public function getApiManager(): ApiManager
    {
        return $this->apiManager;
    }

    public function setUrlHelper(Url $urlHelper): self
    {
        $this->urlHelper = $urlHelper;
        return $this;
    }

    public function getUrlHelper(): Url
    {
        return $this->urlHelper;
    }
}
