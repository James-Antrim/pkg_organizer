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
use THM\Organizer\Adapters\{Database, HTML, Input};
use THM\Organizer\Tables;

/** @inheritDoc */
class Coordinators extends ListField
{
    /** @inheritDoc */
    protected function getInput(): string
    {
        $eventID = Input::getID();
        $query   = Database::query();
        $query->select('DISTINCT personID')
            ->from('#__organizer_event_coordinators')
            ->where("eventID = $eventID");
        Database::set($query);
        $this->value = Database::integers();

        return parent::getInput();
    }

    /** @inheritDoc */
    public function getOptions(): array
    {
        $eventID = Input::getID();
        $event   = new Tables\Events();
        $options = [];

        if (!$event->load($eventID) or !$organizationID = $event->organizationID) {
            return $options;
        }

        $query = Database::query();
        $query->select('DISTINCT p.id, p.forename, p.surname')
            ->from('#__organizer_persons AS p')
            ->innerJoin('#__organizer_associations AS a ON a.personID = p.id')
            ->where("a.organizationID = $organizationID")
            ->order('p.surname, p.forename');
        Database::set($query);

        if (!$persons = Database::arrays()) {
            return $options;
        }

        foreach ($persons as $person) {
            $name      = empty($person['forename']) ? $person['surname'] : "{$person['surname']}, {$person['forename']}";
            $options[] = HTML::option($person['id'], $name);
        }

        return $options;
    }
}
