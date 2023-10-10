<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Validators;

use THM\Organizer\Adapters\Text;
use THM\Organizer\Tables;
use SimpleXMLElement;
use stdClass;

/**
 * Class provides general functions for retrieving building data.
 */
class Grids implements UntisXMLValidator
{
    /**
     * Retrieves the table id if existent.
     *
     * @param string $code the grid name in untis
     *
     * @return int id on success, otherwise 0
     */
    public static function getID(string $code): int
    {
        $table = new Tables\Grids();

        return $table->load(['code' => $code]) ? $table->id : 0;
    }

    /**
     * @inheritDoc
     */
    public static function setID(Schedule $model, string $code)
    {
        if (empty($model->grids->$code)) {
            return;
        }

        $grid       = $model->grids->$code;
        $grid->grid = json_encode($grid, JSON_UNESCAPED_UNICODE);
        $table      = new Tables\Grids();

        // No overwrites for global resources
        if (!$table->load(['code' => $code])) {
            $model->errors[] = Text::sprintf('ORGANIZER_GRID_INVALID', $code);

            return;
        }

        $grid->id = $table->id;
    }

    /**
     * Sets IDs for the collection of grids.
     *
     * @param Schedule $model the model for the schedule being validated
     *
     * @return void modifies &$model
     */
    public static function setIDs(Schedule $model)
    {
        foreach (array_keys((array) $model->grids) as $gridName) {
            self::setID($model, $gridName);
        }
    }

    /**
     * @inheritDoc
     */
    public static function validate(Schedule $model, SimpleXMLElement $node)
    {
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
            if (!in_array(Text::_('ORGANIZER_PERIODS_INCONSISTENT'), $model->errors)) {
                $model->errors[] = Text::_('ORGANIZER_PERIODS_INCONSISTENT');
            }

            return;
        }

        // Set the grid if not already existent
        if (empty($model->grids->$gridName)) {
            $model->grids->$gridName          = new stdClass();
            $model->grids->$gridName->periods = new stdClass();
        }

        $grid = $model->grids->$gridName;

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
