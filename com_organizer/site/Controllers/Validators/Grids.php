<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers\Validators;

use SimpleXMLElement;
use stdClass;
use THM\Organizer\Adapters\Text;
use THM\Organizer\Controllers\ImportSchedule as Schedule;
use THM\Organizer\Tables\Grids as Table;

/**
 * Class provides general functions for retrieving building data.
 */
class Grids implements UntisXMLValidator
{
    /**
     * Retrieves the table id if existent.
     *
     * @param   string  $code  the grid name in untis
     *
     * @return int id on success, otherwise 0
     */
    public static function getID(string $code): int
    {
        $table = new Table();

        return $table->load(['code' => $code]) ? $table->id : 0;
    }

    /**
     * @inheritDoc
     */
    public static function setID(Schedule $controller, string $code): void
    {
        if (empty($controller->grids->$code)) {
            return;
        }

        $grid       = $controller->grids->$code;
        $grid->grid = json_encode($grid, JSON_UNESCAPED_UNICODE);
        $table      = new Table();

        // No overwrites for global resources
        if (!$table->load(['code' => $code])) {
            $controller->errors[] = Text::sprintf('GRID_INVALID', $code);

            return;
        }

        $grid->id = $table->id;
    }

    /**
     * Sets IDs for the collection of grids.
     *
     * @param   Schedule  $controller  the model for the schedule being validated
     *
     * @return void modifies &$model
     */
    public static function setIDs(Schedule $controller): void
    {
        foreach (array_keys((array) $controller->grids) as $gridName) {
            self::setID($controller, $gridName);
        }
    }

    /**
     * @inheritDoc
     */
    public static function validate(Schedule $controller, SimpleXMLElement $node): void
    {
        // Untis didn't output the name, because in the local context it's redundant. In the global context we assume the default.
        if (!property_exists($node, 'timegrid')) {
            $controller->grids = null;
            return;
        }

        // Not actually referenced but evinces data inconsistencies in Untis
        $exportKey = trim((string) $node[0]['id']);
        $gridName  = (string) $node->timegrid;
        $day       = (int) $node->day;
        $periodNo  = (int) $node->period;
        $startTime = trim((string) $node->starttime);
        $endTime   = trim((string) $node->endtime);

        $invalidKeys   = (empty($exportKey) or empty($gridName) or empty($periodNo));
        $invalidTimes  = (empty($day) or empty($startTime) or empty($endTime));
        $invalidPeriod = ($invalidKeys or $invalidTimes);

        if ($invalidPeriod) {
            $controller->errors['PI'] = Text::_('PERIODS_INCONSISTENT');

            return;
        }

        // Set the grid if not already existent
        if (empty($controller->grids->$gridName)) {
            $controller->grids->$gridName          = new stdClass();
            $controller->grids->$gridName->periods = new stdClass();
        }

        $grid = $controller->grids->$gridName;

        if (!isset($grid->startDay) or $grid->startDay > $day) {
            $grid->startDay = $day;
        }

        if (!isset($grid->endDay) or $grid->endDay < $day) {
            $grid->endDay = $day;
        }

        $periods = $grid->periods;

        $periods->$periodNo            = new stdClass();
        $periods->$periodNo->startTime = $startTime;
        $periods->$periodNo->endTime   = $endTime;

        $label = (string) $node->label;
        if ($label and preg_match("/[A-ZÀ-ÖØ-Þa-zß-ÿ]+/", $label)) {
            $periods->$periodNo->label_de = $label;
            $periods->$periodNo->label_en = $label;

            // This is an assumption, which can later be rectified as necessary.
            $periods->$periodNo->type = 'break';
        }
    }
}
