<?php declare(strict_types=1);
namespace  Folksonomy\Mapping;

use CSVImport\Mapping\AbstractMapping;
use Laminas\View\Renderer\PhpRenderer;

class FolksonomyMapping extends AbstractMapping
{
    protected $label = 'Folksonomy'; // @translate
    protected $name = 'folksonomy-module';

    public function getSidebar(PhpRenderer $view)
    {
        return $view->partial('common/admin/folksonomy-mapping-sidebar');
    }

    public function processRow(array $row)
    {
        // Reset the data and the map between rows.
        $this->setHasErr(false);
        $this->data = [];
        $this->map = [];

        // First, pull in the global settings.
        $this->processGlobalArgs();

        $multivalueMap = $this->args['column-multivalue'] ?? [];
        foreach ($row as $index => $values) {
            if (array_key_exists($index, $multivalueMap) && strlen($multivalueMap[$index])) {
                $values = explode($multivalueMap[$index], $values);
                $values = array_map(function ($v) {
                    return trim($v, "\t\n\r \u{a0}\u{202f}");
                }, $values);
            } else {
                $values = [$values];
            }
            $values = array_filter($values, 'strlen');
            if ($values) {
                $this->processCell($index, $values);
            }
        }

        return $this->data;
    }

    protected function processGlobalArgs(): void
    {
        $data = &$this->data;

        // Set columns.
        if (isset($this->args['column-tag'])) {
            $this->map['tag'] = $this->args['column-tag'];
            $data['o-module-folksonomy:tag-new'] = [];
        }

        // TODO Add a field for global tags for all rows.
        // Set default values.
        if (!empty($this->args['o-module-folksonomy:tag-new'])) {
            $data['o-module-folksonomy:tag-new'] = is_array($this->args['o-module-folksonomy:tag-new'])
                ? $this->args['o-module-folksonomy:tag-new']
                // TODO Explode global tags.
                : [$this->args['o-module-folksonomy:tag-new']];
        }
    }

    protected function processCell($index, array $values): void
    {
        $data = &$this->data;

        if (isset($this->map['tag'][$index])) {
            $data['o-module-folksonomy:tag-new'] = array_merge($data['o-module-folksonomy:tag-new'], $values);
        }
    }
}
