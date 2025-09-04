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
use THM\Organizer\Adapters\{HTML, Input};
use THM\Organizer\Helpers;

/** @inheritDoc */
class DocumentedPersons extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $options   = parent::getOptions();
        $poolID    = Input::integer('poolID');
        $programID = Input::integer('programID');

        if (!$poolID and !$programID) {
            return $options;
        }

        $subjects = $poolID ? Helpers\Pools::subjects($poolID) : Helpers\Programs::subjects($programID);

        if (empty($subjects)) {
            return $options;
        }

        $aggregatedPersons = [];
        foreach ($subjects as $subject) {
            $subjectPersons = Helpers\Subjects::persons($subject['subjectID']);
            if (empty($subjectPersons)) {
                continue;
            }

            $aggregatedPersons = array_merge($aggregatedPersons, $subjectPersons);
        }

        ksort($aggregatedPersons);

        foreach ($aggregatedPersons as $name => $person) {
            $options[] = HTML::option($person['id'], $name);
        }

        return $options;
    }
}
