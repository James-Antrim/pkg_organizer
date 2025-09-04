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

use Joomla\CMS\Form\FormField;
use THM\Organizer\Adapters\Input;

/**
 * Class creates two hidden fields for merging. One has the lowest selected id as its value, the other has all
 * other selected ids (comma separated) as its value.
 */
class MergeIDs extends FormField
{
    use Translated;

    /**
     * Method to get the field input markup for a generic list.
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        $selectedIDs = Input::selectedIDs();
        asort($selectedIDs);
        $values = implode(',', $selectedIDs);

        return '<input type="hidden" name="' . $this->name . '" value="' . $values . '"/>';
    }
}
