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
use THM\Organizer\Helpers;
use stdClass;

/**
 * Class creates a select box for superordinate pool resources.
 */
class SuperOrdinates extends FormField
{
    use Translated;

    /**
     * Returns a select box in which resources can be chosen as a superordinates
     * @return string
     */
    public function getInput(): string
    {
        $options = $this->getOptions();
        $select  = '<select id="superordinates" name="superordinates[]" multiple="multiple" size="10">';
        $select  .= implode('', $options) . '</select>';

        return $select;
    }

    /**
     * Gets available superordinate options.
     * @return stdClass[]
     */
    protected function getOptions(): array
    {
        $resourceID   = Input::getID();
        $contextParts = explode('.', $this->form->getName());
        $resourceType = str_replace('edit', '', $contextParts[1]);

        // Initial program ranges are dependent on existing ranges.
        $programRanges = $resourceType === 'pool' ?
            Helpers\Pools::programs($resourceID) : Helpers\Subjects::programs($resourceID);

        return Helpers\Pools::superOptions($resourceID, $resourceType, $programRanges);
    }
}
