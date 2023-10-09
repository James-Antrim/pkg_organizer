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
use Organizer\Adapters\Database;
use Organizer\Helpers;

/**
 * Class creates a select box for explicitly associating subjects with events. This is also done
 * implicitly during the schedule import process according to degree programs and the subject's module number.
 */
class SubjectEventsField extends FormField
{
    use Translated;

    protected $type = 'SubjectEvents';

    /**
     * Returns a select box where stored pool can be chosen as a parent node
     * @return string  the HTML output
     */
    public function getInput(): string
    {
        $query     = Database::getQuery(true);
        $subjectID = Helpers\Input::getID();
        $tag       = Helpers\Languages::getTag();
        $query->select("id AS value, name_$tag AS name")->from('#__organizer_events')->order('name');
        Database::setQuery($query);
        $events  = Database::loadAssocList();
        $options = [Helpers\HTML::_('select.option', '', Helpers\Languages::_('ORGANIZER_SELECT_EVENT'))];

        foreach ($events as $event) {
            $options[] = Helpers\HTML::_('select.option', $event['value'], $event['name']);
        }

        $fieldName  = $this->getAttribute('name');
        $attributes = ['multiple' => 'multiple', 'size' => '10'];

        $query = Database::getQuery(true);
        $query->select('eventID')->from('#__organizer_subject_events')->where("subjectID = $subjectID");
        Database::setQuery($query);
        $selected = Database::loadIntColumn();

        return Helpers\HTML::selectBox($options, $fieldName, $attributes, $selected, true);
    }
}
