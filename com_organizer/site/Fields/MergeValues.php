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

use Joomla\CMS\Form\Field\ListField;
use stdClass;

/**
 * Class creates a generalized select box for selection of a single column value among those already selected.
 */
class MergeValues extends ListField
{
    use Mergeable;

    /**
     * Returns a select box where resource attributes can be selected
     * @return stdClass[] the options for the select box
     */
    protected function getOptions(): array
    {
        if (!$this->validateContext()) {
            return [];
        }

        return $this->createOptions($this->getValues());
    }
}
