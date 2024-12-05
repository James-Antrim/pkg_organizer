<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use Joomla\Database\ParameterType;
use stdClass;
use THM\Organizer\Adapters\{Database as DB, HTML};
use THM\Organizer\Tables\{Bookings as Table, Blocks, Instances as ITable, Rooms as RTable};

/**
 * Provides functions for XML instance validation and modeling.
 */
class Bookings extends ResourceHelper
{
    public const ALL = '', ATTENDEES = 1, IMPROPER = 3, ONLY_REGISTERED = -1, PROPER = 2;

    public const ATTENDED = 1, REGISTERED = 0;

    /**
     * Retrieves the number of current registrations for the booking.
     *
     * @param   int  $bookingID  the id of the booking
     *
     * @return int
     */
    public static function capacity(int $bookingID): int
    {
        if (!$instanceIDs = self::instanceIDs($bookingID)) {
            return 0;
        }

        return Instances::capacity($instanceIDs[0]);
    }

    /**
     * Creates a display of formatted times for a booking.
     *
     * @param   int  $bookingID  the id of the booking entry
     *
     * @return string
     */
    public static function dateTimeDisplay(int $bookingID): string
    {
        $booking = new Table();
        if (!$booking->load($bookingID)) {
            return '';
        }

        $block = new Blocks();
        if (!$block->load($booking->blockID)) {
            return '';
        }

        // It is enough to load a single one, because if the instance does not have an event, there is only one.
        $instance = new ITable();
        if (!$instance->load(['blockID' => $booking->blockID, 'unitID' => $booking->unitID])) {
            return '';
        }

        $endTime   = $booking->endTime ?: $block->endTime;
        $startTime = $booking->startTime ?: $block->startTime;
        $date      = Dates::formatDate($block->date);
        $endTime   = $instance->eventID ? Dates::formatEndTime($endTime) : Dates::formatTime($endTime);
        $startTime = Dates::formatTime($startTime);

        return "$date $startTime - $endTime";
    }

    /**
     * Retrieves a list of instance IDs associated with the booking.
     *
     * @param   int  $bookingID  the id of the booking entry
     *
     * @return int[]
     */
    public static function instanceIDs(int $bookingID): array
    {
        $query = DB::query();
        $query->select('DISTINCT ' . DB::qn('i.id'))
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_bookings', 'b'), DB::qcs([['b.blockID', 'i.blockID'], ['b.unitID', 'i.unitID']]))
            ->where("b.id = $bookingID")
            ->order('i.id');
        DB::set($query);

        return DB::integers();
    }

    /**
     * Gets instance options for the booking entry.
     *
     * @param   int  $bookingID  the id of the booking to get instance options for
     *
     * @return stdClass[]
     */
    public static function instanceOptions(int $bookingID): array
    {
        $options = [];

        foreach (self::instanceIDs($bookingID) as $instanceID) {
            if ($name = Instances::name($instanceID)) {
                $options[$name] = HTML::option($instanceID, $name);
            }
        }

        ksort($options);

        return $options;
    }

    /**
     * Gets the localized name of the events associated with the booking and the name of the booking's method.
     *
     * @param   int  $resourceID  the id of the booking entry
     *
     * @return string
     */
    public static function name(int $resourceID): string
    {
        $method = '';
        $names  = [];

        foreach (self::instanceIDs($resourceID) as $instanceID) {
            if ($name = Instances::name($instanceID, false)) {
                $names[] = $name;

                if (empty($method)) {
                    $method = Instances::methodName($instanceID);
                }
            }
        }

        $names = array_unique($names);
        asort($names);
        $names = implode(', ', $names);

        // Removes potentially redundant methods which are also a part of the instance event name.
        $names .= ($method and !str_contains($names, $method)) ? " - $method" : '';

        return $names;
    }

    /**
     * Gets the localized name of the events associated with the booking and the name of the booking's method.
     *
     * @param   int  $bookingID  the id of the booking entry
     *
     * @return string[]
     */
    public static function names(int $bookingID): array
    {
        $names = [];

        foreach (self::instanceIDs($bookingID) as $instanceID) {
            if ($name = Instances::name($instanceID)) {
                $names[] = $name;
            }
        }

        $names = array_unique($names);
        asort($names);

        return $names;
    }

    /**
     * Gets the count of participants who attended the booking.
     *
     * @param   int  $bookingID  the id of the booking entry
     * @param   int  $roomID     the optional id of the room
     *
     * @return int
     */
    public static function participantCount(int $bookingID, int $roomID = 0): int
    {
        $query = DB::query();
        $query->select('SUM(' . DB::qn('i.attended') . ')')
            ->from(DB::qn('#__organizer_instances', 'i'))
            ->innerJoin(DB::qn('#__organizer_bookings', 'b'), DB::qcs([['b.unitID', 'i.unitID'], ['b.blockID', 'i.blockID']]))
            ->where(DB::qn('b.id') . ' = :bookingID')->bind(':bookingID', $bookingID, ParameterType::INTEGER);

        if ($roomID) {
            $query->leftJoin(DB::qn('#__organizer_instance_participants', 'ip'), DB::qc('ip.instanceID', 'i.id'))
                ->where(DB::qn('ip.roomID') . ' = :roomID')->bind(':roomID', $roomID, ParameterType::INTEGER);
        }

        DB::set($query);

        return DB::integer();
    }

    /**
     * Gets the count of registrations for the booking.
     *
     * @param   int  $bookingID
     *
     * @return int
     */
    public static function registrationCount(int $bookingID): int
    {
        if (!$instanceIDs = self::instanceIDs($bookingID)) {
            return 0;
        }

        return Instances::currentCapacity($instanceIDs[0]);
    }

    /**
     * Builds list of room ids => room name pairs, sorted by their names.
     *
     * @param   int  $bookingID  the id of the booking to get instance options for
     *
     * @return string[]
     */
    public static function rooms(int $bookingID): array
    {
        $rooms = [];

        foreach (self::instanceIDs($bookingID) as $instanceID) {
            foreach (Instances::getRoomIDs($instanceID) as $roomID) {
                $room = new RTable();
                $room->load($roomID);

                if ($room->virtual) {
                    continue;
                }

                $rooms[$roomID] = $room->name;
            }
        }

        asort($rooms);

        return $rooms;
    }

    /**
     * Gets instance options for the booking entry.
     *
     * @param   int  $bookingID  the id of the booking to get instance options for
     *
     * @return stdClass[]
     */
    public static function roomOptions(int $bookingID): array
    {
        $options = [];

        foreach (self::instanceIDs($bookingID) as $instanceID) {
            foreach (Instances::getRoomIDs($instanceID) as $roomID) {
                $name           = Rooms::name($roomID);
                $options[$name] = HTML::option($roomID, $name);
            }
        }

        ksort($options);

        return $options;
    }
}
