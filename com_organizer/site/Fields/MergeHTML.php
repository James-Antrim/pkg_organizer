<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use THM\Organizer\Adapters\{HTML, Text};
use stdClass;

/**
 * Class creates a generalized select box for selection of a single column value among those already selected.
 */
class MergeHTML extends Options
{
    use Mergeable;

    /**
     * @var  string
     */
    protected $type = 'MergeHTML';

    /**
     * Returns a select box where resource attributes can be selected
     * @return stdClass[] the options for the select box
     */
    protected function getOptions(): array
    {
        if (!$this->validate()) {
            return [];
        }

        if (!$values = $this->getValues()) {
            return [HTML::option('-1', Text::_('NONE_GIVEN'))];
        }

        $values = array_unique($values);

        foreach ($values as &$value) {
            $value = preg_replace('/ style="[^"]+"/', '', $value);
        }

        return $this->createOptions($values);
    }
}
