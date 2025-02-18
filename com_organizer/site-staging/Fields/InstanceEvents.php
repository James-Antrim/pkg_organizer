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
use THM\Organizer\Adapters\{Application, Database, HTML};
use THM\Organizer\Helpers;

/** @inheritDoc */
class InstanceEvents extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $options = parent::getOptions();

        $personID   = Helpers\Persons::getIDByUserID();
        $plannedIDs = Helpers\Organizations::schedulableIDs();

        if (empty($personID) and empty($plannedIDs)) {
            return $options;
        }

        $tag   = Application::tag();
        $query = Database::query();
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
        Database::set($query);
        $events = [];
        $free   = [];

        foreach (Database::arrays() as $result) {
            // Prioritize the events associated with an organization in the first pass.
            if ($result['organizationID']) {
                // Associated events can overwrite each other.
                $events[$result['name']] = $result['id'];
            }
            else {
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
            $options[] = HTML::option($id, $name);
        }

        return $options;
    }
}
