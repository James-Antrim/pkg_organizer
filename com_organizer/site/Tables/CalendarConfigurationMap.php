<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

/**
 * Models the thm_organizer_calendar_configuration_map table.
 */
class CalendarConfigurationMap extends BaseTable
{
    /**
     * The id of the calendar entry referenced.
     * INT(11) UNSIGNED NOT NULL
     *
     * @var int
     */
    public $calendarID;

    /**
     * The id of the configuration entry referenced.
     * INT(11) UNSIGNED NOT NULL
     *
     * @var int
     */
    public $configurationID;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__thm_organizer_calendar_configuration_map');
    }
}
