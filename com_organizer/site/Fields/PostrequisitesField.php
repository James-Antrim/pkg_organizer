<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Joomla\CMS\Form\FormField;
use Organizer\Helpers;

/**
 * Class creates a select box for superordinate pool resources.
 */
class PostrequisitesField extends DependencyOptions
{
    /**
     * Type
     *
     * @var    String
     */
    protected $type = 'Postrequisites';

    /**
     * Returns a select box in which resources can be chosen as a super ordinates
     *
     * @return string  the HTML for the super ordinate resources select box
     */
    public function getInput()
    {
        $options = $this->getOptions();
        $select  = '<select id="postrequisites" name="jform[postrequisites][]" multiple="multiple" size="10">';
        $select  .= implode('', $options) . '</select>';

        return $select;
    }

    /**
     * Gets pool options for a select list. All parameters come from the
     *
     * @return array  the options
     */
    protected function getOptions()
    {
        $subjectID = Helpers\Input::getID();
        $values    = Helpers\Subjects::getPostrequisites($subjectID);

        $selected = empty($values) ? ' selected' : '';
        $text     = Helpers\Languages::_('ORGANIZER_NO_POSTREQUISITES');

        $defaultOption     = "<option value=\"\"$selected>$text</option>";
        $dependencyOptions = parent::getDependencyOptions($subjectID, $values);

        return [$defaultOption] + $dependencyOptions;
    }
}
