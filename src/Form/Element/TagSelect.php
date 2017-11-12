<?php
namespace Folksonomy\Form\Element;

use Omeka\Api\Manager as ApiManager;
use Zend\Form\Element\Select;
use Zend\View\Helper\Url;

class TagSelect extends Select
{
    /**
     * @var ApiManager
     */
    protected $apiManager;

    public function getValueOptions()
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

    /**
     * @param ApiManager $apiManager
     */
    public function setApiManager(ApiManager $apiManager)
    {
        $this->apiManager = $apiManager;
    }

    /**
     * @return ApiManager
     */
    public function getApiManager()
    {
        return $this->apiManager;
    }

    /**
     * @param Url $urlHelper
     */
    public function setUrlHelper(Url $urlHelper)
    {
        $this->urlHelper = $urlHelper;
    }

    /**
     * @return Url
     */
    public function getUrlHelper()
    {
        return $this->urlHelper;
    }
}
