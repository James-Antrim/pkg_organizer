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

use Organizer\Helpers;

/**
 * Class creates a select box for the association of persons with subject documentation.
 */
class DocumentedPersonsField extends OptionsField
{
    protected $type = 'DocumentedPersons';

    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options   = parent::getOptions();
        $poolID    = Helpers\Input::getFilterID('pool', Helpers\Input::getInt('poolID'));
        $programID = Helpers\Input::getFilterID('program', Helpers\Input::getInt('programID'));

        if (!$poolID and !$programID) {
            return $options;
        }

        $subjects = $poolID ? Helpers\Pools::getSubjects($poolID) : Helpers\Programs::getSubjects($programID);

        if (empty($subjects)) {
            return $options;
        }

        $aggregatedPersons = [];
        foreach ($subjects as $subject) {
            $subjectPersons = Helpers\Subjects::getPersons($subject['subjectID']);
            if (empty($subjectPersons)) {
                continue;
            }

            $aggregatedPersons = array_merge($aggregatedPersons, $subjectPersons);
        }

        ksort($aggregatedPersons);

        foreach ($aggregatedPersons as $name => $person) {
            $options[] = Helpers\HTML::_('select.option', $person['id'], $name);
        }

        return $options;
    }
}
