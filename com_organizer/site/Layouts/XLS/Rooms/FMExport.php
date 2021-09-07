<?php
/**
 * @package     Organizer\Layouts\XLS\Rooms
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\XLS\Rooms;

use Exception;
use Joomla\CMS\Factory;
use Organizer\Helpers;
use Organizer\Layouts\XLS\BaseLayout;
use PHPExcel_Style_Border as BorderStyle;

class FMExport extends BaseLayout
{
    /**
     * Adds the column headers to the document.
     *
     * @return void
     */
    private function addColumnHeaders()
    {
        $sheet = $this->view->getActiveSheet();

        $sheet->getColumnDimension()->setWidth(35.43);
        //campus
        $sheet->setCellValue('A1', Helpers\Languages::_('ORGANIZER_FM_CAMPUS_PLACE'));
        //campus address
        $sheet->setCellValue('B1', Helpers\Languages::_('ORGANIZER_FM_CAMPUS_ADDRESS'));
        //Ownership / Property type
        $sheet->setCellValue('C1', Helpers\Languages::_('ORGANIZER_FM_OWNERSHIP'));
        //Building name
        $sheet->setCellValue('D1', Helpers\Languages::_('ORGANIZER_FM_BUILDING_NAME'));
        //Room name
        $sheet->setCellValue('E1', Helpers\Languages::_('ORGANIZER_FM_ROOM_NAME'));
        //Room floor
        $sheet->setCellValue('F1', Helpers\Languages::_('ORGANIZER_FM_ROOM_FLOOR'));
        //Room description /Room usage
        $sheet->setCellValue('G1', Helpers\Languages::_('ORGANIZER_FM_ROOM_DESCRIPTION'));
        //effective capacity
        $sheet->setCellValue('H1', Helpers\Languages::_('ORGANIZER_FM_ROOM_EFFECTIVE_CAPACITY'));
        //Archetypes
        $sheet->setCellValue('I1', Helpers\Languages::_('ORGANIZER_FM_ROOM_ARCHETYPE'));
        //DIN
        $sheet->setCellValue('J1', Helpers\Languages::_('ORGANIZER_FM_ROOM_DIN'));
        //DIN Types
        $sheet->setCellValue('K1', Helpers\Languages::_('ORGANIZER_FM_ROOM_DINTYPES'));
        //Room Type equipment
        $sheet->setCellValue('L1', Helpers\Languages::_('ORGANIZER_FM_ROOM_TYPE_EQUIPMENT'));
        //Room Equipment
        $sheet->setCellValue('M1', Helpers\Languages::_('ORGANIZER_FM_ROOM_EQUIPMENT'));
    }

    private function addRows()
    {
        // The first row is used by the header
        $index = 2;
        $sheet = $this->view->getActiveSheet();
        foreach ($this->view->model->getItems() as $room)
        {
            for ($column = 'A'; $column <= 'M'; $column++)
            {
                $coordinates = "$column$index";
                $value       = '';

                switch ($column)
                {
                    // Generated externally
                    case 'A':
                        if ($room->campus)
                        {
                            $value = $room->parent ? "$room->parent / $room->campus" : $room->campus;
                        }
                        else
                        {
                            $value = '';
                        }
                        break;
                    case 'B':
                        //$value = $room->campus_address ? $room->campus_address: '-';
                        $value = $room->address ? $room->address: '-';
                        break;
                    case 'C':
                        $value = '';
                        if($room->propertyType == 1){
                            $value = "New";
                        }elseif($room->propertyType == 2){
                            $value = "Owned";
                        }elseif($room->propertyType == 3){
                            $value = "rented/leased";
                        }
                        break;
                    case 'D':
                        $value = $room->buildingName ?: 'Unbekannt';
                        break;
                    case 'E':
                        $value = $room->roomName;
                        break;
                    case 'F':
                        if (preg_match("/^[A-Z]\d+.(U?\d+).\d+[A-Za-z]?$/", $room->roomName, $matches))
                        {
                            $symbols = str_split($matches[1]);
                            if ($symbols[0] === '0')
                            {
                                $value = 'Erdgeschoss';
                            }
                            elseif ($symbols[0] === 'U')
                            {
                                $level = implode('', array_splice($symbols, 1));
                                $value = "$level. Untergeschoss";
                            }
                            else
                            {
                                $level = implode('', $symbols);
                                $value = "$level. Etage";
                            }
                        }
                        else
                        {
                            $value = '';
                        }
                        break;
                    case 'G':
                        $value = $room->roomType ?: '';
                        break;
                    case 'H':
                        $value = $room->effCapacity;
                        break;
                    case 'I':
                        $value = '';
                        if($room->room_archetypeID > 0){
                            try{
                                $tag   = Helpers\Languages::getTag();
                                $db = Factory::getDbo();
                                $query = $db->getQuery(true);
                                $query->select("name_$tag as arche_name")->from('#__organizer_room_archetypes')
                                    ->where('id='.$room->room_archetypeID);
                                $db->setQuery($query);
                                $arche_object = $db->loadObject();
                                if(!empty($arche_object->arche_name)){
                                    $value = $arche_object->arche_name;
                                }
                            }catch (Exception $e){

                            }
                        }
                        break;
                    case 'J':
                        $value = $room->din_name;
                        break;
                    case 'K':
                        $value = $room->din_code;
                        break;
                    case 'L':
                        $value = '';
                        if(isset($room->roomtypeID) && !empty($room->roomtypeID)){
                            try{
                                $tag   = Helpers\Languages::getTag();
                                $db = Factory::getDbo();
                                $query = $db->getQuery(true);
                                $query->select("name_$tag as equipment_name")->from('#__organizer_roomtype_equipment')
                                    ->where('roomtypeID='.$room->roomtypeID);
                                $db->setQuery($query);
                                $saved_roome_type_quipment = $db->loadObjectList();
                                $room_type_equipment = '';
                                foreach ($saved_roome_type_quipment as $equipment){
                                    $room_type_equipment .= $equipment->equipment_name.',';
                                }
                                $value = !empty($room_type_equipment) ? trim($room_type_equipment,','): '';
                            }catch (Exception $e){

                            }

                        }
                        break;
                    case 'M':
                        $value = '';
                        if(isset($room->id) && !empty($room->id)){
                            try{
                                $tag   = Helpers\Languages::getTag();
                                $db = Factory::getDbo();
                                $query = $db->getQuery(true);
                                $query->select("name_$tag as equipment_name")->from('#__organizer_room_equipment')
                                    ->where('roomID='.$room->id);
                                $db->setQuery($query);
                                $saved_room_quipments = $db->loadObjectList();
                                $room_equipment = '';
                                foreach ($saved_room_quipments as $equipment){
                                    $room_equipment .= $equipment->equipment_name.',';
                                }
                                $value = !empty($room_equipment) ? trim($room_equipment,','): '';
                            }catch (Exception $e){

                            }

                        }
                        break;
                }
                $sheet->setCellValue($coordinates, $value);
            }
            $index++;
        }
    }

    /**
     * @inheritDoc
     */
    public function fill()
    {
        $this->setPageFormatting();
        $this->addColumnHeaders();
        $this->addRows();
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return 'FM Export';
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return 'FM Export';
    }

    /**
     * Sets the formatting for the page.
     *
     * @return void modifies the page formatting
     * @throws Exception
     */
    private function setPageFormatting()
    {
        $sheet = $this->view->getActiveSheet();

        $sheet->getDefaultStyle()->applyFromArray(['borders' => ['outline' => ['style' => BorderStyle::BORDER_NONE]]]);

        $sheet->getColumnDimension('A')->setWidth();
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(10);
        $sheet->getColumnDimension('D')->setWidth(15);
        $sheet->getColumnDimension('E')->setWidth(15);
        $sheet->getColumnDimension('F')->setWidth(20);
        $sheet->getColumnDimension('G')->setWidth(23);
        $sheet->getColumnDimension('H')->setWidth(20);
        $sheet->getColumnDimension('I')->setWidth(35);
        $sheet->getColumnDimension('J')->setWidth(25);
        $sheet->getColumnDimension('K')->setWidth(15);
        $sheet->getColumnDimension('L')->setWidth(35);
        $sheet->getColumnDimension('M')->setWidth(25);
    }
}