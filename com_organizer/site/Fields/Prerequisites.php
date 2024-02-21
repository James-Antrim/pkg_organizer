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

use THM\Organizer\Adapters\{Input, Text};
use THM\Organizer\Helpers;
use stdClass;

/**
 * Class creates a select box for superordinate pool resources.
 */
class Prerequisites extends DependencyOptions
{
    /**
     * Returns a select box in which resources can be chosen as a prerequisites
     * @return string
     */
    public function getInput(): string
    {
        $options = $this->getOptions();
        $select  = '<select id="prerequisites" name="prerequisites[]" multiple="multiple" size="10">';
        $select  .= implode('', $options) . '</select>';

        return $select;
    }

    /**
     * Gets available prerequisite options.
     * @return stdClass[]
     */
    protected function getOptions(): array
    {
        $subjectID = Input::getID();
        $values    = Helpers\Subjects::prerequisites($subjectID);

        $selected = empty($values) ? ' selected' : '';
        $text     = Text::_('NO_PREREQUISITES');

        $defaultOption     = "<option value=\"-1\"$selected>$text</option>";
        $dependencyOptions = parent::getDependencyOptions($subjectID, $values);

        return [$defaultOption] + $dependencyOptions;
    }
}
