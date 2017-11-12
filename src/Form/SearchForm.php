<?php
namespace Folksonomy\Form;

use Folksonomy\Form\Element\TagSelect;
use Zend\Form\Element\Checkbox;
use Zend\Form\Form;

class SearchForm extends Form
{
    public function init()
    {
        $this->add([
            'name' => 'has_tags',
            'type' => Checkbox::class,
            'options' => [
                'label' => 'Has tags', // @translate
            ],
        ]);

        $this->add([
            'name' => 'tag',
            'type' => TagSelect::class,
            'options' => [
                'label' => 'Search by tag', // @translate
                'chosen' => true,
            ],
            'attributes' => [
                'multiple' => true,
            ],
        ]);
    }
}
