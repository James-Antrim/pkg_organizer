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
use Joomla\Utilities\ArrayHelper;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;
use THM\Organizer\Tables\Instances as Table;

/**
 * Class loads a form for editing instance data.
 */
class InstanceEdit extends EditModel
{
    // Default role
    private const NONE = -1;

    private $personID;

    /**
     * Checks access to edit the resource.
     * @return void
     */
    protected function authorize()
    {
        if (!Helpers\Users::getID()) {
            Application::error(401);
        }

        if (!$this->personID = Helpers\Persons::getIDByUserID()) {
            Application::error(403);
        }

        if ($instanceID = Input::getID() and !Helpers\Can::manage('instance', $instanceID)) {
            Application::error(403);
        }
    }

    /**
     * Checks whether the contents of a request field item with a string value are permissible.
     *
     * @param string $field   the name of the field
     * @param string $pattern the pattern to match
     *
     * @return string
     */
    private function checkString(string $field, string $pattern): string
    {
        $value = Input::getString($field);

        return preg_match($pattern, $value) ? $value : '';
    }

    /**
     * Gets the selection for a given field with a
     *
     * @param string $field
     *
     * @return array|int[]
     */
    private function getSelection(string $field): array
    {
        $request = Input::getFormItems();

        if ($selection = $request->get($field)) {
            $selection = ArrayHelper::toInteger($selection);

            if ($position = array_search(-1, $selection) !== false) {
                unset($selection[$position]);
            }

            return count($selection) ? $selection : [-1];
        }

        return [];
    }

    /**
     * @inheritDoc
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = parent::getForm($data, $loadData);

        $item     = $this->item;
        $request  = Input::getFormItems();
        $session  = Factory::getSession();
        $instance = $session->get('organizer.instance', []);

        // The user did not cancel out and has chosen to edit a different instance => hard reset session instance.
        if ($this->item->id and $instance and $this->item->id !== $instance['id']) {
            $instance = [];
        }

        // Immutable once set
        if (empty($instance['referrer'])) {
            $instance['referrer'] = Input::getInput()->server->getString('HTTP_REFERER');
        }

        $instance['id'] = $this->item->id;

        // Get defaults from session > item
        $dBlockID   = empty($instance['blockID']) ? $item->blockID : $instance['blockID'];
        $dDate      = empty($instance['date']) ? $item->date : $instance['date'];
        $dEndTime   = empty($instance['endTime']) ? $item->endTime : $instance['endTime'];
        $dEventIDs  = empty($instance['eventIDs']) ? $item->eventIDs : $instance['eventIDs'];
        $dGridID    = empty($instance['gridID']) ? $item->gridID : $instance['gridID'];
        $dGroupIDs  = empty($instance['groupIDs']) ? $item->groupIDs : $instance['groupIDs'];
        $dRoleID    = empty($instance['roleID']) ? $item->roleID : $instance['roleID'];
        $dRoomIDs   = empty($instance['roomIDs']) ? $item->roomIDs : $instance['roomIDs'];
        $dStartTime = empty($instance['startTime']) ? $item->startTime : $instance['startTime'];

        // Interpret request items
        $rDate      = $this->checkString('date', '/^\d{4}-\d{2}-\d{2}$/');
        $rEndTime   = $this->checkString('endTime', '/^(([01]?[0-9]|2[0-3]):?[0-5][0-9])$/');
        $rEventIDs  = $this->getSelection('eventIDs');
        $rGroupIDs  = $this->getSelection('groupIDs');
        $rStartTime = $this->checkString('startTime', '/^(([01]?[0-9]|2[0-3]):?[0-5][0-9])$/');
        $rRoomIDs   = $this->getSelection('roomIDs');
        /* Interpret nested structures from the advanced form. */

        // Set new values from selection > defaults
        $instance['blockID']   = empty($request->get('blockID')) ? $dBlockID : (int) $request->get('blockID');
        $instance['date']      = $rDate ?: $dDate;
        $instance['endTime']   = $rEndTime ?: $dEndTime;
        $instance['eventIDs']  = $rEventIDs ?: $dEventIDs;
        $instance['gridID']    = empty($request->get('gridID')) ? $dGridID : (int) $request->get('gridID');
        $instance['groupIDs']  = $rGroupIDs ?: $dGroupIDs;
        $instance['layout']    = Input::getCMD('layout', 'appointment');
        $instance['personID']  = $this->personID;
        $instance['roleID']    = empty($request->get('roleID')) ? $dRoleID : (int) $request->get('roleID');
        $instance['roomIDs']   = $rRoomIDs ?: $dRoomIDs;
        $instance['startTime'] = $rStartTime ?: $dStartTime;

        $today            = date('Y-m-d');
        $instance['date'] = $instance['date'] < $today ? $today : $instance['date'];

        $form->bind($instance);

        // Suppress conditional fields
        if ($instance['gridID'] === -1) {
            $form->removeField('blockID');
        } else {
            $form->removeField('startTime');
            $form->removeField('endTime');
        }

        $session->set('organizer.instance', $instance);

        return $form;
    }

    /**
     * @inheritDoc
     */
    public function getItem($pk = 0)
    {
        $item = parent::getItem($pk);

        // block ID, eventID, methodID & unitID are directly accessible/null from the table
        if ($item->id) {
            $gridID       = Helpers\Units::getGridID($item->unitID);
            $item->gridID = $gridID ?: self::NONE;

            $block = new Tables\Blocks();
            $block->load($item->blockID);
            $item->date      = $block->date;
            $item->endTime   = $block->endTime;
            $item->startTime = $block->startTime;

            // simple construction: one person, one role with their event/group/room assignments
            $item->eventIDs = Helpers\Units::getEventIDs($item->unitID, $item->id);
            $item->groupIDs = Helpers\Units::getGroupIDs($item->unitID, $item->id);
            $item->roleID   = Helpers\Instances::getRoleID($item->id, $this->personID);
            $item->roomIDs  = Helpers\Units::getRoomIDs($item->unitID, $item->id);

            /**
             * separate construction for advanced with the potential for:
             * individual event/group/person/role/room configurations
             */

        } else {
            $item->date      = date('Y-m-d');
            $item->endTime   = date('H:i', strtotime('+1 hour'));
            $item->eventIDs  = [self::NONE];
            $item->gridID    = self::NONE;
            $item->groupIDs  = [self::NONE];
            $item->roleID    = self::NONE;
            $item->roomIDs   = [self::NONE];
            $item->startTime = date('H:i');
        }

        /**
         * Set the default grid for an organization for which the person teaches. Will later be ignored the grid id
         * field is omitted or if 'none' option is selected.
         */
        if (empty($item->gridID)) {
            $organizations  = Helpers\Organizations::getResources('teach');
            $organizationID = reset($organizations)['id'];
            $item->gridID   = Helpers\Organizations::getDefaultGrid($organizationID);
        }

        $this->item = $item;

        return $item;

    }

    /**
     * @inheritDoc
     */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        return new Table();
    }
}