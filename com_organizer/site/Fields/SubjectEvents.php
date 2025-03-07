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
use THM\Organizer\Adapters\{Application, Database, HTML, Input, Text};

/**
 * Class creates a select box for explicitly associating subjects with events. This is also done
 * implicitly during the schedule import process according to degree programs and the subject's module number.
 */
class SubjectEvents extends FormField
{
    use Translated;

    /**
     * Returns a select box where stored pool can be chosen as a parent node
     * @return string  the HTML output
     */
    public function getInput(): string
    {
        $query     = Database::query();
        $subjectID = Input::getID();
        $tag       = Application::tag();
        $query->select("id AS value, name_$tag AS name")->from('#__organizer_events')->order('name');
        Database::set($query);
        $events  = Database::arrays();
        $options = [HTML::option('', Text::_('SELECT_EVENT'))];

        foreach ($events as $event) {
            $options[] = HTML::option($event['value'], $event['name']);
        }

        $fieldName  = $this->getAttribute('name');
        $attributes = ['multiple' => 'multiple', 'size' => '10'];

        $query = Database::query();
        $query->select('eventID')->from('#__organizer_subject_events')->where("subjectID = $subjectID");
        Database::set($query);
        $selected = Database::integers();

        return HTML::selectBox($fieldName, $options, $attributes, $selected);
    }
}
