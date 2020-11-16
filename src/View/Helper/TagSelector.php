<?php declare(strict_types=1);
namespace Folksonomy\View\Helper;

use Laminas\View\Helper\AbstractHelper;

class TagSelector extends AbstractHelper
{
    /**
     * Return the tag selector form control.
     *
     * @return string
     */
    public function __invoke()
    {
        $response = $this->getView()->api()->search('tags', ['sort_by' => 'name']);
        $tags = $response->getContent();
        return $this->getView()->partial(
            'common/tag-selector',
            [
                'tags' => $tags,
                'totalTagCount' => $response->getTotalResults(),
            ]
        );
    }
}
