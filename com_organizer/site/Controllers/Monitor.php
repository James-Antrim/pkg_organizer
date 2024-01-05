<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Joomla\Filesystem\Folder;
use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers\Monitors as Helper;
use THM\Organizer\Tables\Rooms as Room;

/**
 * @inheritDoc
 */
class Monitor extends FormController
{
    use FluMoxed;

    protected string $list = 'Monitors';

    /**
     * @inheritDoc
     */
    protected function prepareData(): array
    {
        $data = parent::prepareData();

        /**
         * '' is the value of the filelist field 'use standard' option
         * '-1' is the value of the filelist field 'nothing selected' option
         * These have the same effect in this context.
         */
        if (empty($data['content']) or $data['content'] === '-1') {
            $data['content'] = '';
        }

        $this->validate($data);

        return $data;
    }

    /**
     * @inheritDoc
     */
    protected function validate(array &$data, array $required = []): void
    {
        $required = ['display', 'ip', 'roomID'];
        parent::validate($data, $required);

        $room = new Room();
        if (!$room->load($data['roomID'])) {
            Application::error(400);
        }

        if (!preg_match('/^([0-2]?[\d]?[\d].){3}[0-2]?[\d]?[\d]$/', $data['ip'])) {
            Application::error(400);
        }

        if (!in_array($data['useDefaults'], array_keys(Helper::CONFIGURATIONS))) {
            Application::error(400);
        }

        if (!in_array($data['display'], Helper::DISPLAYS)) {
            Application::error(400);
        }

        // Steps are set to 10 with a minimum of 10 and maximum of 990 for these fields
        $cRefresh = $data['contentRefresh'];
        if ($cRefresh % 10 or $cRefresh < 10 or $cRefresh > 990) {
            Application::error(400);
        }

        $sRefresh = $data['scheduleRefresh'];
        if ($sRefresh % 10 or $sRefresh < 10 or $sRefresh > 990) {
            Application::error(400);
        }

        if (!empty($data['content'])) {
            $path = JPATH_ROOT . '/' . 'images/organizer/';

            // Directory was wiped, file was deleted, or x
            if (!$files = Folder::files($path) or !in_array($data['content'], $files)) {
                Application::message('404', Application::NOTICE);
                $data['content'] = '';
            }
        }
    }
}
