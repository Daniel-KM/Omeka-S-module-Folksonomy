<?php
namespace Folksonomy\Form;

use Zend\Form\Form;

class Search extends Form
{
    public function init()
    {
        $this->add([
            'type' => 'checkbox',
            'name' => 'has_tags',
            'options' => [
                'label' => 'Has tags', // @translate
            ],
        ]);

        $this->add([
            'type' => 'Text',
            'name' => 'tag',
            'options' => [
                'label' => 'Search by tag', // @translate
                'info' => 'Multiple tags may be comma-separated.', // @translate
            ],
        ]);
    }
}
