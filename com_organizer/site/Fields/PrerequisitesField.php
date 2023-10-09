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

use Organizer\Helpers;
use stdClass;

/**
 * Class creates a select box for superordinate pool resources.
 */
class PrerequisitesField extends DependencyOptions
{
    /**
     * Type
     * @var    String
     */
    protected $type = 'Prerequisites';

    /**
     * Returns a select box in which resources can be chosen as a super ordinates
     * @return string  the HTML for the super ordinate resources select box
     */
    public function getInput(): string
    {
        $options = $this->getOptions();
        $select  = '<select id="prerequisites" name="jform[prerequisites][]" multiple="multiple" size="10">';
        $select  .= implode('', $options) . '</select>';

        return $select;
    }

    /**
     * Gets pool options for a select list. All parameters come from the
     * @return stdClass[]  the options
     */
    protected function getOptions(): array
    {
        $subjectID = Helpers\Input::getID();
        $values    = Helpers\Subjects::getPrerequisites($subjectID);

        $selected = empty($values) ? ' selected' : '';
        $text     = Helpers\Languages::_('ORGANIZER_NO_PREREQUISITES');

        $defaultOption     = "<option value=\"-1\"$selected>$text</option>";
        $dependencyOptions = parent::getDependencyOptions($subjectID, $values);

        return [$defaultOption] + $dependencyOptions;
    }
}
