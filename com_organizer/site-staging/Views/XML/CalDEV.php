<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\XML;

use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Helpers\OrganizerHelper;
use SimpleXMLElement;

/**
 * Base class for a Joomla View
 * Class holding methods for displaying presentation data.
 */
class CalDEV extends BaseView
{
    private $method;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->method = 'REPORT';//Input::getInput()->server->getString('REQUEST_METHOD');
    }

    /**
     * Display the view output.
     */
    public function display(string $response = '')
    {
        $allowedMethods = ['GET', 'OPTIONS', 'PROPFIND', 'REPORT'];

        if (!in_array($this->method, $allowedMethods)) {
            OrganizerHelper::error(501);
        }

        $response = '';

        $app = OrganizerHelper::getApplication();
        switch ($this->method) {
            case 'GET':
                $get = new SimpleXMLElement('<response/>');
                $get->addChild('text', 'Getting Something!');
                $response = $get->asXML();
                break;
            case 'OPTIONS':
                $app->setHeader('Allow', implode(' ', $allowedMethods));
                $options = new SimpleXMLElement('<response/>');
                $options->addChild('AllowedMethods', 'Allowed Methods: ' . implode(', ', $allowedMethods)) . '.';
                $response = $options->asXML();
                break;
            case 'PROPFIND':
                $propFind = new SimpleXMLElement('<response/>');
                $propFind->addChild('text', 'Finding Something!');
                $response = $propFind->asXML();
                break;
            case 'REPORT':
                $response = $this->getReport();
                break;
        }

        $response = '<xml version="1.0" encoding="utf-8"/>' . $response;

        parent::display($response);
        //https://www.thm.de/dev/organizer/?option=com_organizer&format=caldev&roomID=1
    }

    /**
     * Gets the report for the particular resource.
     * @return string
     */
    private function getReport(): string
    {
        $accessibleResources = [
            'categoryID' => "\\Organizer\\Tables\\Categories",
            'eventID' => "\\Organizer\\Tables\\Events",
            'groupID' => "\\Organizer\\Tables\\Groups",
            'roomID' => "\\Organizer\\Tables\\Rooms"
        ];

        $response = '';

        foreach ($accessibleResources as $key => $table) {
            if (!$id = Helpers\Input::getInt($key)) {
                continue;
            }

            $table = new $table();
            if (!$table->load($id)) {
                OrganizerHelper::error(404);
            }

            switch ($key) {
                case 'categoryID':

                    if ($table->suppress or !$table->active) {
                        OrganizerHelper::error(404);
                    }

                    $category = new SimpleXMLElement('<category/>');
                    $category->addAttribute('id', $table->id);
                    $code = htmlspecialchars($table->code);
                    $category->addChild('code', $code);
                    $nameColumn = 'name_' . Languages::getTag();
                    $name       = htmlspecialchars($table->$nameColumn);
                    $category->addChild('name', $name);
                    $response = $category->asXML();

                    break 2;

                case 'groupID':

                    if ($table->suppress or !$table->active) {
                        OrganizerHelper::error(404);
                    }

                    $group = new SimpleXMLElement('<group/>');
                    $group->addAttribute('id', $table->id);
                    $code = htmlspecialchars($table->code);
                    $group->addChild('code', $code);
                    $nameColumn = 'fullName_' . Languages::getTag();
                    $name       = htmlspecialchars($table->$nameColumn);
                    $group->addChild('name', $name);
                    $response = $group->asXML();

                    break 2;

                case 'roomID':

                    if (!$table->active) {
                        OrganizerHelper::error(404);
                    }

                    if (!$table->roomtypeID) {
                        OrganizerHelper::error(412);
                    }

                    if (Helpers\Roomtypes::getSuppressed($table->roomtypeID)) {
                        OrganizerHelper::error(404);
                    }

                    $room = new SimpleXMLElement('<room/>');
                    $room->addAttribute('id', $table->id);
                    $room->addAttribute('virtual', $table->virtual);
                    $name = $this->amp($table->name);
                    $room->addChild('name', $name);
                    $type = $this->amp(Helpers\Roomtypes::getName($table->roomtypeID));
                    $room->addChild('type', $type);
                    $building = $table->buildingID ? $this->amp(Helpers\Buildings::getName($table->buildingID)) : '';
                    $room->addChild('building', $building);
                    $capacity = $table->maxCapacity ?: '';
                    $room->addChild('capacity', $capacity);
                    $response = $room->asXML();

                    break 2;
            }

            $report = new SimpleXMLElement('<report/>');
            $report->addChild('report', 'Nothing to report');
            $response = $report->asXML();
        }

        return $response;
    }
}
