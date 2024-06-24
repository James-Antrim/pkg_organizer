<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use Joomla\CMS\Form\Form;
use Joomla\Database\DatabaseQuery;
use THM\Organizer\Adapters\{Application, Database, Input};
use THM\Organizer\Helpers;

/**
 * Retrieves lesson and event data for a filtered set of rooms.
 */
class RoomOverview extends ListModel
{
    private const DAY = 1;

    protected int $defaultLimit = 25;

    protected string $defaultOrdering = 'r.name';

    protected $filter_fields = ['campusID', 'buildingID', 'effCapacity', 'roomtypeID'];

    /** @inheritDoc */
    protected function filterFilterForm(Form $form): void
    {
        parent::filterFilterForm($form);

        if (Input::getParams()->get('campusID', 0)) {
            $form->removeField('campusID', 'filter');
            unset($this->filter_fields[array_search('campusID', $this->filter_fields)]);
        }
    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $tag   = Application::getTag();
        $query = Database::getQuery();

        $query->select('r.id, r.name AS name, r.effCapacity')
            ->select("t.id AS roomtypeID, t.name_$tag AS typeName, t.description_$tag AS typeDesc")
            ->from('#__organizer_rooms AS r')
            ->leftJoin('#__organizer_roomtypes AS t ON t.id = r.roomtypeID')
            ->leftJoin('#__organizer_buildings AS b ON b.id = r.buildingID')
            ->where('r.active = 1')
            ->where('t.suppress = 0');

        // Only display public room types, i.e. no offices or toilets...
        $query->where('t.suppress = 0');

        $this->filterSearch($query, ['r.name']);
        $this->filterValues($query, ['buildingID', 'roomtypeID']);
        $this->filterByCampus($query, 'b');

        if ($capacity = $this->state->get('filter.effCapacity')) {
            $query->where("r.effCapacity >= $capacity");
        }

        $query->order($this->defaultOrdering);

        return $query;
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        $list = Input::getListItems();

        $date = Application::getUserRequestState("$this->context.list.date", "list_date", '', 'string');
        $date = (string) $list->get('date', $date);
        $date = Helpers\Dates::standardize($date);

        $defaultGrid = Helpers\Grids::getDefault();

        if ($campusID = Input::getParams()->get('campusID')) {
            $defaultGrid = Helpers\Campuses::gridID($campusID);
            $this->setState('filter.campusID', $campusID);
        }

        $gridID = Application::getUserRequestState("$this->context.list.gridID", "list_gridID", $defaultGrid, 'int');
        $gridID = (int) $list->get('gridID', $gridID);

        $template = Application::getUserRequestState("$this->context.list.template", "list_template", self::DAY, 'int');
        $template = (int) $list->get('template', $template);

        $this->setState('list.date', $date);
        $this->setState('list.gridID', $gridID);
        $this->setState('list.template', $template);
    }
}
