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

use THM\Organizer\Adapters\{Database, HTML, Input};
use THM\Organizer\Tables;

/**
 * Class creates a select box for organizations.
 */
class Coordinators extends Options
{
    /**
     * Method to get the field input markup.
     * @return  string  The field input markup.
     */
    protected function getInput(): string
    {
        $eventID = Input::getID();
        $query   = Database::getQuery();
        $query->select('DISTINCT personID')
            ->from('#__organizer_event_coordinators')
            ->where("eventID = $eventID");
        Database::setQuery($query);
        $this->value = Database::loadIntColumn();

        return parent::getInput();
    }

    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    public function getOptions(): array
    {
        $eventID = Input::getID();
        $event   = new Tables\Events();
        $options = [];

        if (!$event->load($eventID) or !$organizationID = $event->organizationID) {
            return $options;
        }

        $query = Database::getQuery();
        $query->select('DISTINCT p.id, p.forename, p.surname')
            ->from('#__organizer_persons AS p')
            ->innerJoin('#__organizer_associations AS a ON a.personID = p.id')
            ->where("a.organizationID = $organizationID")
            ->order('p.surname, p.forename');
        Database::setQuery($query);

        if (!$persons = Database::loadAssocList()) {
            return $options;
        }

        foreach ($persons as $person) {
            $name      = empty($person['forename']) ? $person['surname'] : "{$person['surname']}, {$person['forename']}";
            $options[] = HTML::option($person['id'], $name);
        }

        return $options;
    }
}
