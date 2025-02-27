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
use THM\Organizer\Adapters\{Database as DB, HTML, Input};
use THM\Organizer\Helpers\Subjects;

/** @inheritDoc */
class SubjectPersons extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        if (!$subjectID = Input::getID()) {
            return [];
        }

        $query = DB::query();
        $query->select(DB::qn(['p.id', 'surname', 'forename']))
            ->from(DB::qn('#__organizer_persons', 'p'))
            ->order(DB::qn(['surname', 'forename']));

        if ($organizationIDs = Subjects::organizationIDs($subjectID)) {

            $condition = DB::qc('a.personID', 'p.id');
            $table     = DB::qn('#__organizer_associations', 'a');

            if (empty($this->value)) {
                $query->innerJoin($table, $condition)
                    ->whereIn(DB::qn('organizationID'), $organizationIDs);
            }
            else {
                $oColumn = DB::qn('organizationID');
                $pColumn = DB::qn('personID');
                $query->leftJoin($table, $condition);

                $externalOrganizations = "$oColumn NOT IN (" . implode(',', $query->bindArray($organizationIDs)) . ')';
                $selectedPersons       = "$pColumn IN (" . implode(',', $query->bindArray($this->value)) . ')';

                $externalPersons  = "($externalOrganizations AND $selectedPersons)";
                $intOrganizations = "$oColumn IN (" . implode(',', $query->bindArray($organizationIDs)) . ')';

                $query->where("($intOrganizations OR $externalPersons)");
            }
        }

        DB::set($query);
        $options = parent::getOptions();

        if (!$persons = DB::arrays('id')) {
            return $options;
        }

        foreach ($persons as $person) {
            $text      = empty($person['forename']) ? $person['surname'] : "{$person['surname']}, {$person['forename']}";
            $options[] = HTML::option($person['id'], $text);
        }

        return $options;
    }
}
