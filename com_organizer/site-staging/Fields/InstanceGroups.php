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

/** @inheritDoc */
class InstanceGroups extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        return parent::getOptions();

        /*$tag   = Helpers\Languages::getTag();
        $query = Database::getQuery();
        $query->select("DISTINCT e.id, e.name_$tag AS name, e.organizationID")
            ->from('#__organizer_events AS e');

        $wherray = ['e.organizationID IS NULL'];

        if ($plannedIDs = Helpers\Can::scheduleTheseOrganizations())
        {
            $plannedIDs = implode(',', $plannedIDs);
            $wherray[]  = "e.organizationID IN ($plannedIDs)";
        }

        if ($personID = Helpers\Persons::getIDByUserID())
        {
            $query->leftJoin('#__organizer_instances AS i ON i.eventID = e.id')
                ->leftJoin('#__organizer_instance_persons AS ipe on ipe.instanceID = i.id');
            $wherray[] = "ipe.personID = $personID";
        }

        $where = implode(' OR ', $wherray);
        $query->where("($where)");
        Database::setQuery($query);
        $events = [];
        $free   = [];

        foreach (Database::loadAssocList() as $result)
        {
            // Prioritize the events associated with an organization in the first pass.
            if ($result['organizationID'])
            {
                // Associated events can overwrite each other.
                $events[$result['name']] = $result['id'];
            }
            else
            {
                $free[$result['name']] = $result['id'];
            }
        }

        foreach ($free as $name => $id)
        {
            if (!isset($events[$name]))
            {
                $events[$name] = $id;
            }
        }

        ksort($events);

        foreach ($events as $name => $id)
        {
            $options[] = HTML::option($id, $name);
        }*/
    }
}
