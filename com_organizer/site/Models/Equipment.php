<?php
namespace Organizer\Models;
use Organizer\Helpers;
use Organizer\Tables;
class Equipment extends ListModel {

    protected $filter_fields = ['code'];
    protected $defaultOrdering = 'code';
    protected function getListQuery()
    {
        $query = $this->_db->getQuery(true);
        $query->select('*')->from('#__organizer_equipment');
        $this->setSearchFilter($query, ['code', 'name_de', 'name_en']);
        $this->setValueFilters($query, ['code']);
        //$this->setOrdering();
        return $query;
    }
    function save(){
        $data = array();
        $data = empty($data) ? Helpers\Input::getFormItems()->toArray() : $data;
        $table = new Tables\Equipment();
        $table->load($data['id']);
        $table->bind($data);
        $table->store();
        return $table->id;
    }

    function delete() {

        if (!$EquipmentIDs = Helpers\Input::getSelectedIDs())
		{
			Helpers\OrganizerHelper::message('organizer_equipment_400', 'warning');

			return;
		}
        
		foreach ($EquipmentIDs as $EquipmentID)
		{
            $table = new Tables\Equipment();
            if (!$table->load($EquipmentID))
            {
                Helpers\OrganizerHelper::message('organizer_equipment_412', 'notice');

                return;
            }

            if (!$table->delete())
            {
                Helpers\OrganizerHelper::message('organizer_equipment_NOT_REMOVED', 'error');

                return;
            }
        }
       // Helpers\OrganizerHelper::message('organizer_equipment_REMOVED', 'success');
		return true;
    }

    private function cleanAlpha(string $name)
    {
        $name = preg_replace('/[^A-ZÀ-ÖØ-Þa-zß-ÿ\p{N}_.\-\']/', ' ', $name);

        return self::cleanSpaces($name);
    }
}