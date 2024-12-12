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
use Joomla\Database\{DatabaseQuery, ParameterType};
use THM\Organizer\Adapters\{Application, Database as DB, Input};
use THM\Organizer\Helpers\Can;

/** @inheritDoc */
class Rooms extends ListModel
{
    use Activated;

    protected string $defaultOrdering = 'roomName';

    protected $filter_fields = ['buildingID', 'campusID', 'cleaningID', 'keyID', 'roomtypeID', 'virtual'];

    /** @inheritDoc */
    protected function filterFilterForm(Form $form): void
    {
        if (Input::getParams()->get('campusID')) {
            $form->removeField('campusID', 'filter');

            // No virtual rooms in a physical area
            $form->removeField('virtual', 'filter');
            unset($this->filter_fields['campusID'], $this->filter_fields['virtual']);
        }

        if (!Application::backend()) {
            $form->removeField('active', 'filter');
        }
    }

    /** @inheritDoc */
    protected function getListQuery(): DatabaseQuery
    {
        $query = DB::query();
        $tag   = Application::tag();
        $url   = 'index.php?option=com_organizer&view=room&id=';

        $access  = [DB::quote((int) Can::fm()) . ' AS ' . DB::qn('access')];
        $aliased = DB::qn(
            ['b.id', 'b.name', "c1.name_$tag", "c2.name_$tag", 'r.name', 't.id', "t.name_$tag"],
            ['buildingID', 'buildingName', 'campus', 'parent', 'roomName', 'roomtypeID', 'roomType']
        );
        $select  = DB::qn(['r.id', 'r.code', 'r.active', 'r.effCapacity', 'b.address', 'b.location', 'b.propertyType']);
        $url     = [$query->concatenate([DB::quote($url), DB::qn('r.id')], '') . ' AS ' . DB::qn('url')];

        $query->select(array_merge($select, $access, $aliased, $url))
            ->from(DB::qn('#__organizer_rooms', 'r'))
            ->leftJoin(DB::qn('#__organizer_roomtypes', 't'), DB::qc('t.id', 'r.roomtypeID'))
            ->leftJoin(DB::qn('#__organizer_use_codes', 'uc'), DB::qc('uc.id', 't.usecode'))
            ->leftJoin(DB::qn('#__organizer_roomkeys', 'rk'), DB::qc('rk.id', 'uc.keyID'));

        $campusID = (int) $this->state->get('filter.campusID');
        if ($campusID and $campusID !== self::NONE) {
            $query->innerJoin(DB::qn('#__organizer_buildings', 'b'), DB::qc('b.id', 'r.buildingID'))
                ->innerJoin(DB::qn('#__organizer_campuses', 'c1'), DB::qc('c1.id', 'b.campusID'))
                ->where('(' . DB::qn('c1.id') . ' = :campusID OR ' . DB::qn('c1.parentID') . ' = :pCampusID)')
                ->bind(':campusID', $campusID, ParameterType::INTEGER)
                ->bind(':pCampusID', $campusID, ParameterType::INTEGER);
        }
        else {
            $query->leftJoin(DB::qn('#__organizer_buildings', 'b'), DB::qc('b.id', 'r.buildingID'))
                ->leftJoin(DB::qn('#__organizer_campuses', 'c1'), DB::qc('c1.id', 'b.campusID'));

            if ($campusID) {
                $query->where('r.buildingID IS NULL');
            }
        }

        $query->leftJoin('#__organizer_campuses AS c2 ON c2.id = c1.parentID');

        $this->activeFilter($query, 'r');
        $this->filterByKey($query, 'rk.id', 'keyID');
        $this->filterByKey($query, 'rk.cleaningID', 'cleaningID');
        $this->filterSearch($query, ['r.name', 'b.name', 't.name_de', 't.name_en', 'uc.code']);
        $this->filterValues($query, ['buildingID', 'roomtypeID', 'virtual']);

        $this->orderBy($query);

        return $query;
    }

    /** @inheritDoc */
    protected function populateState($ordering = null, $direction = null): void
    {
        parent::populateState($ordering, $direction);

        // GET
        if ($format = Input::getCMD('format') and in_array($format, ['pdf', 'xls'])) {
            $this->setState('list.limit', 0);
        }

        if ($campusID = Input::getInt('campusID')) {
            $this->setState('filter.campusID', $campusID);
        }
    }
}
