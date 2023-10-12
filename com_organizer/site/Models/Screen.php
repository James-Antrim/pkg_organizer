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

use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Application, Database, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;

/**
 * Class retrieves information about daily events for display on monitors.
 */
class Screen extends BaseModel
{
    private const UPCOMING = 0, CURRENT = 1, ALTERNATING = 2, IMAGE = 3;

    public array $grid = [];

    public array $instances = [];

    public string $image = '';

    public string $layout = 'upcoming_instances';

    public array $room = [];

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $imagePath = JPATH_ROOT . '/images/organizer/';
        $ipData    = ['ip' => Input::getInput()->server->getString('REMOTE_ADDR', '')];
        $layout    = 'upcoming_instances';
        $monitor   = new Tables\Monitors();
        $roomID    = 0;

        if ($monitor->load($ipData)) {
            $roomID = $monitor->roomID;
            $name   = Helpers\Rooms::getName($roomID);

            switch ($monitor->display) {
                case self::CURRENT:
                    $layout = 'current_instances';
                    break;
                case self::ALTERNATING:
                    if (empty($monitor->content) or !file_exists($imagePath . $monitor->content)) {
                        $layout = 'current_instances';
                        break;
                    }

                    $session    = Factory::getSession();
                    $lastLayout = $session->get('layout');
                    if ($lastLayout and $lastLayout === 'current_instances') {
                        $layout      = 'image';
                        $this->image = $monitor->content;
                        $session->set('layout', 'image');
                    } else {
                        $layout = 'current_instances';
                        $session->set('layout', 'current_instances');
                    }

                    break;
                case self::IMAGE:
                    if (empty($monitor->content) or !file_exists($imagePath . $monitor->content)) {
                        break;
                    }
                    $this->image = $monitor->content;
                    $layout      = 'image';
                    break;
                case self::UPCOMING:
                default:
                    break;
            }
        } elseif ($name = Input::getCMD('room') and $roomID = Helpers\Rooms::getID($name)) {
            if (Application::mobile()) {
                $layout = 'current_instances';
            } else {
                $layouts = ['current_instances', 'image', 'upcoming_instances'];
                $layout  = Input::getCMD('layout', 'upcoming_instances');
                $layout  = in_array($layout, $layouts) ? $layout : 'upcoming_instances';
            }
        } else {
            Application::getApplication()->redirect('index.php', 400);
        }

        if (Input::getCMD('tmpl') !== 'component') {
            $query = Input::getInput()->server->get('QUERY_STRING', '', 'raw') . '&tmpl=component';
            Application::getApplication()->redirect(Uri::root() . "?$query");
        }

        $gridLayouts  = ['current_instances', 'upcoming_instances'];
        $this->room   = ['id' => $roomID, 'name' => $name];
        $this->layout = $layout;

        if (in_array($layout, $gridLayouts)) {
            $this->setInstances();
        }
    }

    /**
     * Gets the grid to be used in the screen display.
     *
     * @param   int  $gridID  the id of the grid
     *
     * @return void  sets the object grid variable
     */
    private function setGrid(int $gridID)
    {
        $grid = new Tables\Grids();
        if (($gridID and $grid->load($gridID)) or $grid->load(Helpers\Grids::getDefault())) {
            $grid = json_decode($grid->grid, true);
        } else {
            $grid = [];
        }

        $grid = empty($grid['periods']) ? [] : $grid['periods'];

        foreach ($grid as &$period) {
            $period['comment'] = '';
            $period['events']  = [];
            $period['method']  = '';
            $period['persons'] = [];
        }

        $this->grid = $grid;
    }

    /**
     * Gets the raw events from the database
     * @return void sets the object variable events
     */
    private function setInstances()
    {
        $query = Database::getQuery();
        $tag   = Application::getTag();
        $query->select('DISTINCT i.id')
            ->select('b.date, b.endTime, b.startTime')
            ->select("e.id AS eventID, e.name_$tag AS event")
            ->select("m.abbreviation_$tag AS method")
            ->select('u.id AS unitID, u.gridID, u.comment')
            ->select('a.organizationID')
            ->from('#__organizer_instances AS i')
            ->innerJoin('#__organizer_blocks AS b ON b.id = i.blockID')
            ->innerJoin('#__organizer_events AS e ON e.id = i.eventID')
            ->innerJoin('#__organizer_units AS u ON u.id = i.unitID')
            ->leftJoin('#__organizer_methods AS m ON m.id = i.methodID')
            ->innerJoin('#__organizer_instance_persons AS ip ON ip.instanceID = i.id')
            ->innerJoin('#__organizer_instance_groups AS ig ON ig.assocID = ip.id')
            ->innerJoin('#__organizer_instance_rooms AS ir ON ir.assocID = ip.id')
            ->leftJoin('#__organizer_group_publishing AS gp ON gp.groupID = ig.groupID AND gp.termID = u.termID')
            ->innerJoin('#__organizer_groups AS g ON g.id = ig.groupID')
            ->innerJoin('#__organizer_categories AS c ON c.id = g.categoryID')
            ->innerJoin('#__organizer_associations AS a ON a.categoryID = c.id')
            ->where("ir.roomID = {$this->room['id']}")
            ->where("i.delta != 'removed'")
            ->where("ip.delta != 'removed'")
            ->where("ig.delta != 'removed'")
            ->where("ir.delta != 'removed'")
            ->where("u.delta != 'removed'")
            ->where("(gp.published IS NULL OR gp.published = 1)")
            ->order("b.date, b.startTime, b.endTime, e.name_$tag, m.abbreviation_$tag")
            ->group('i.id');

        $today = date('Y-m-d');
        $time  = date('H:i:s');

        switch ($this->layout) {
            case 'current_instances':
                $query->where("b.date = '$today'");
                break;
            case 'upcoming_instances':
            default:
                $endDate = date('Y-m-d H:i:s', strtotime('+3 months', strtotime($today)));
                $query->where("(b.date > '$today' OR (b.date = '$today' and b.endTime > '$time'))")
                    ->where("b.date < '$endDate'");
                break;
        }

        Database::setQuery($query);

        if (!$instances = Database::loadAssocList('id')) {
            return;
        }

        if ($this->layout === 'upcoming_instances') {
            foreach ($instances as $instanceID => $instance) {
                $persons = [];
                foreach (Helpers\Instances::getPersonIDs($instanceID) as $personID) {
                    $persons[$personID] = Helpers\Persons::getLNFName($personID, true);
                }

                asort($persons);
                $instances[$instanceID]['persons'] = $persons;
                $instances[$instanceID]['rooms']   = [$this->room['id'] => $this->room['name']];
            }

            $this->instances = $instances;

            return;
        }

        $defaultGrid = Helpers\Grids::getDefault();
        $grids       = [];
        foreach ($instances as $instance) {
            $gridID         = empty($instance['gridID']) ? $defaultGrid : $instance['gridID'];
            $grids[$gridID] = empty($grids[$gridID]) ? 1 : $grids[$gridID] + 1;
        }

        $gridID = array_search(max($grids), $grids);
        $this->setGrid($gridID);

        foreach ($this->grid as &$period) {
            $endTime   = Helpers\Dates::formatEndTime($period['endTime']);
            $startTime = Helpers\Dates::formatTime($period['startTime']);

            $period['comment'] = '';
            $period['events']  = [];
            $period['method']  = '';
            $period['persons'] = [];

            foreach ($instances as $instanceID => $instance) {
                $endsBefore  = $instance['endTime'] < $startTime;
                $startsAfter = $instance['startTime'] > $endTime;

                if ($endsBefore or $startsAfter) {
                    continue;
                }

                $period['events'][$instance['eventID']] = $instance['event'];

                foreach (Helpers\Instances::getPersonIDs($instanceID) as $personID) {
                    $period['persons'][$personID] = Helpers\Persons::getLNFName($personID, true);
                }

                $period['comment'] = $period['comment'] ?: $instance['comment'];
                $period['method']  = $period['method'] ?: $instance['method'];
            }
        }
    }
}
