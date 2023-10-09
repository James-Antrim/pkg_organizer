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

use THM\Organizer\Adapters\Database;
use THM\Organizer\Helpers;

/**
 * Class creates a select box for predefined colors.
 */
class InstanceEventsField extends OptionsField
{
    /**
     * Type
     * @var    String
     */
    protected $type = 'InstanceEvents';

    /**
     * Method to get the field options.
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        $personID   = Helpers\Persons::getIDByUserID();
        $plannedIDs = Helpers\Can::scheduleTheseOrganizations();

        if (empty($personID) and empty($plannedIDs)) {
            return $options;
        }

        $tag   = Helpers\Languages::getTag();
        $query = Database::getQuery();
        $query->select("DISTINCT e.id, e.name_$tag AS name, e.organizationID")
            ->from('#__organizer_events AS e');

        $wherray = [];

        if ($plannedIDs) {
            $plannedIDs = implode(',', $plannedIDs);
            $wherray[]  = "e.organizationID IN ($plannedIDs)";
        }

        if ($personID) {
            $query->leftJoin('#__organizer_instances AS i ON i.eventID = e.id')
                ->leftJoin('#__organizer_instance_persons AS ipe on ipe.instanceID = i.id');
            $wherray[] = "ipe.personID = $personID";
        }

        $where = implode(' OR ', $wherray);
        $query->where("($where)");
        Database::setQuery($query);
        $events = [];
        $free   = [];

        foreach (Database::loadAssocList() as $result) {
            // Prioritize the events associated with an organization in the first pass.
            if ($result['organizationID']) {
                // Associated events can overwrite each other.
                $events[$result['name']] = $result['id'];
            } else {
                $free[$result['name']] = $result['id'];
            }
        }

        foreach ($free as $name => $id) {
            if (!isset($events[$name])) {
                $events[$name] = $id;
            }
        }

        ksort($events);

        foreach ($events as $name => $id) {
            $options[] = Helpers\HTML::_('select.option', $id, $name);
        }

        return $options;
    }
}
