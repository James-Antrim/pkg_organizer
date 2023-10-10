<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\ICS;

use DateTime;
use DateTimeZone;
use Exception;
use Joomla\CMS\Filter\OutputFilter;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\User\User;
use Joomla\Registry\Registry;
use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers;
use THM\Organizer\Models;
use SimpleXMLElement;

/**
 * Base class for a Joomla View
 * Class holding methods for displaying presentation data.
 */
class Instances
{
    /**
     * Component internal fixed roles.
     */
    private const SPEAKER = 4, TEACHER = 1, TUTOR = 3;

    /**
     * ICS format values.
     */
    private const INITIAL_RELEASE = 0, LINE_LIMIT = 75, MEDIUM = 5;

    /**
     * Time units in seconds; used for offset calculations.
     */
    private const HOUR = 3600, MINUTE = 60;

    /**
     * Hexidecimal byte values used for resolving character encoding.
     */
    private const ONE_BYTE_LOWER = 0x20, ONE_BYTE_UPPER = 0x7F, TWO_BYTE_MARK = 0xC0, THREE_BYTE_MARK = 0xE0,
        FOUR_BYTE_MARK = 0xF0, FIVE_BYTE_MARK = 0xF8, SIX_BYTE_MARK = 0xFC, SEVEN_BYTE_MARK = 0xFE;

    /**
     * The name of the generated file.
     * @var string
     */
    private $fileName;

    /**
     * The instances data.
     * @var array
     */
    private $instances;

    /**
     * The two character language code.
     * @var string
     */
    private $language;

    /**
     * This property specifies the date and time (UTC) that the instance of the iCalendar object was created.
     * @var string
     * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.7.2
     */
    private $stamp;

    /**
     * @var Registry
     */
    private $state;

    /**
     * The full name of the calendar.
     * @var string
     */
    private $title;

    /**
     * This property specifies the text value that uniquely identifies the "VTIMEZONE" calendar component in the scope
     * of an iCalendar object.
     * @var string
     * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.3.1
     */
    private $tzID;

    /**
     * The component version.
     * @var string
     */
    private $version;

    /**
     * The template for uIDs used in individual components.
     * @var string
     * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.4.7
     */
    private $uIDTemplate;

    /**
     * @var User
     */
    private $user;

    /**
     * Performs initial construction of the TCPDF Object.
     */
    public function __construct()
    {
        $model = new Models\Instances();
        $uri   = Uri::getInstance();

        $left  = date('Ymd') . 'T' . date('His') . date('T');
        $right = $uri->getHost() . $uri->getPath();

        $this->language    = strtoupper(Application::getTag());
        $this->instances   = $model->getItems();
        $this->state       = $model->getState();
        $this->tzID        = date_default_timezone_get();
        $this->user        = Helpers\Users::getUser();
        $this->uIDTemplate = "UID:$left-%s@$right";

        $this->setStamp();
        $this->setTitles();
        $this->setVersion();
    }

    /**
     * Provide a grouping of component properties that describe an event.
     *
     * @param array   &$ics      the output container
     * @param object   $instance the instance being iterated
     *
     * @return void modifies $ics
     * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.6.1
     */
    private function addEvent(array &$ics, object $instance)
    {
        $date      = $instance->date;
        $endTime   = $instance->endTime;
        $method    = $instance->method ?: '';
        $startTime = $instance->startTime;

        $campuses     = [];
        $coordinates  = [];
        $lastModified = $instance->instanceStatusDate;
        $lastModified = $lastModified > $instance->unitStatusDate ? $lastModified : $instance->unitStatusDate;
        $locations    = [];
        $pattern      = '/^-?[\d]?[\d].[\d]{6}, ?-?[01]?[\d]{1,2}.[\d]{6}$/';
        $persons      = [];

        if (!empty($instance->resources)) {
            foreach ($instance->resources as $person) {
                $lastModified = $lastModified > $person['statusDate'] ? $lastModified : $person['statusDate'];

                if (in_array($person['roleID'], [self::SPEAKER, self::TEACHER])
                    or ($method === 'Tutorium' and $person['roleID'] === self::TUTOR)) {
                    $persons[$person['person']] = $person['person'];
                }

                if (!empty($person['groups'])) {
                    foreach ($person['groups'] as $group) {
                        $lastModified = $lastModified > $group['statusDate'] ? $lastModified : $group['statusDate'];
                    }
                }

                if (!empty($person['rooms'])) {
                    foreach ($person['rooms'] as $room) {
                        $lastModified             = $lastModified > $room['statusDate'] ? $lastModified : $room['statusDate'];
                        $locations[$room['room']] = $room['room'];

                        if (!empty($room['location'])) {
                            if (preg_match($pattern, $room['location'])) {
                                $coordinates[$room['location']] = $room['location'];
                            } else {
                                $coordinates['invalid'] = true;
                            }
                        }

                        if (!empty($room['campus'])) {
                            if (preg_match($pattern, $room['campus'])) {
                                $campuses[$room['campus']] = $room['campus'];
                            } else {
                                $campuses['invalid'] = true;
                            }
                        }
                    }
                }
            }
        }

        $ics[] = "BEGIN:VEVENT";
        $ics[] = $this->stamp;
        $ics[] = sprintf($this->uIDTemplate, $instance->instanceID);
        $ics[] = 'DTSTART' . $this->getDateTime("$date $startTime");
        $ics[] = 'DTEND' . $this->getDateTime("$date $endTime");

        //@url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.1.2
        if ($method) {
            $ics[] = "CATEGORIES:$method";
        }

        if ($persons) {
            if (count($persons) === 1) {
                $description = reset($persons);
            } else {
                ksort($persons);
                $last        = array_pop($persons);
                $description = implode(', ', $persons) . " & $last";
            }

            $description = sprintf(Helpers\Languages::_('ORGANIZER_ICS_DESCRIPTION'), $description);
            $description = $this->escape($description);

            $ics[] = "DESCRIPTION:$description";
        }

        //@url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.1.6
        $geo = '';

        if (count($coordinates) === 1 and empty($coordinates['invalid'])) {
            $geo = reset($coordinates);
        } elseif (count($campuses) === 1 and empty($campuses['invalid'])) {
            $geo = reset($campuses);
        }

        if ($geo) {
            // RFC 5545 calls for a semicolon in lieu of a comma
            $ics[] = "GEO:" . str_replace(',', ';', $geo);
        }

        // @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.7.3
        $lastModified = strtotime($lastModified);
        $date         = gmdate('Ymd', $lastModified);
        $time         = gmdate('His', $lastModified);
        $ics[]        = "LAST-MODIFIED:{$date}T{$time}Z";

        //@url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.1.7
        if ($locations) {
            if (count($locations) === 1) {
                $location = reset($locations);
            } else {
                ksort($locations);
                $last     = array_pop($locations);
                $location = implode(', ', $locations) . " & $last";
            }

            $location = $this->escape($location);

            $ics[] = "LOCATION:$location";
        }

        // @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.4.3
        if ($this->user->email) {
            $ics[] = 'ORGANIZER:mailto:' . $this->user->email;
        }

        // @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.1.9
        $ics[] = 'PRIORITY:' . self::MEDIUM;

        /**
         * @TODO add relations to events over the RELATED-TO property
         * @URL https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.4.5
         */

        /**
         * Since the calendar is always dynamically generated, as opposed to laying persistently in a web directory,
         * every calendar is an initial release.
         * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.7.4
         */
        $ics[] = 'SEQUENCE:' . self::INITIAL_RELEASE;

        // @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.1.12
        $summary = $instance->name;
        $summary .= $method ? " - $instance->method" : '';
        $summary = $this->escape($summary);
        $ics[]   = "SUMMARY:$summary";

        /**
         * url?
         * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.4.6
         * comment*
         * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.1.4
         */
        $this->setCommentnURL($ics, $instance->comment);

        /**
         * TODO: Add contact information for the site administration?
         * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.4.2
         */

        // @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.2.7
        $ics[] = "TRANSP:OPAQUE";

        $ics[] = "END:VEVENT";
    }

    /**
     * Enforces the 75 byte line limitation by 'folding'.
     *
     * @param string $buffer the input string used as a byte buffer during processing
     *
     * @return string
     * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.1
     */
    private function chunk(string $buffer): string
    {
        if (strlen($buffer) <= self::LINE_LIMIT) {
            return $buffer;
        }

        $currentLine = '';
        $lines       = [];
        $pos         = 0;
        $return      = '';

        while (true) {
            // All characters have been processed.
            if (!isset($buffer[$pos])) {
                $lines[] = $currentLine;
                break;
            }

            // The previous iteration ended the line.
            if (strlen($currentLine) === self::LINE_LIMIT) {
                $lines[]     = $currentLine;
                $currentLine = '';
            }

            // Remove processed characters from the buffer;
            $buffer = substr($buffer, $pos);
            $pos    = 0;

            // The unprocessed portion of the buffer is under limit with consideration of the space from the fold
            if (!$currentLine and strlen($buffer) <= 74) {
                $lines[] = " $buffer";
                break;
            }

            $current = $buffer[0];
            $next1   = $buffer[1] ?? '';
            $next2   = $buffer[2] ?? '';
            $next3   = $buffer[3] ?? '';
            $next4   = $buffer[4] ?? '';
            $next5   = $buffer[5] ?? '';

            // New line sequence would get cut.
            if (strlen($currentLine) === 74 and $current === '\\' and $next1 === 'n') {
                // End current line
                $lines[] = $currentLine;

                // New line would only have the new line sequence making it superfluous.
                if (!$next2) {
                    break;
                }

                // Start new line with the new line sequence and tell the buffer where to start
                $currentLine = ' \n';
                $pos         = 2;

                continue;
            }

            $byte        = ord($current);
            $currentLine .= $current;

            /**
             * After one byte encoding has been ruled out a bit-wise and is made with the next higher byte prefix to
             * suppress insignificant bytes for the comparison with the actual byte prefix to ensure character encoding.
             */

            // Characters U-00000000 - U-0000007F (same as ASCII)
            if ($byte >= self::ONE_BYTE_LOWER and $byte <= self::ONE_BYTE_UPPER) {
                $pos++;
                continue;
            }

            // Characters U-00000080 - U-000007FF, mark 110XXXXX
            if ($byte & self::THREE_BYTE_MARK === self::TWO_BYTE_MARK) {
                if (isset($next1)) {
                    $currentLine .= $next1;
                    $pos++;
                }
            } // Characters U-00000800 - U-0000FFFF, mark 1110XXXX
            elseif ($byte & self::FOUR_BYTE_MARK === self::THREE_BYTE_MARK) {
                if (isset($next2)) {
                    $return .= $next1 . $next2;
                    $pos    += 2;
                }
            } // Characters U-00010000 - U-001FFFFF, mark 11110XXX
            elseif ($byte & self::FIVE_BYTE_MARK === self::FOUR_BYTE_MARK) {
                if (isset($next3)) {
                    $return .= $next1 . $next2 . $next3;
                    $pos    += 3;
                }
            } // Characters U-00200000 - U-03FFFFFF, mark 111110XX
            elseif ($byte & self::SIX_BYTE_MARK === self::FIVE_BYTE_MARK) {
                if (isset($next4)) {
                    $return .= $next1 . $next2 . $next3 . $next4;
                    $pos    += 4;
                }
            } // Characters U-04000000 - U-7FFFFFFF, mark 1111110
            elseif ($byte & self::SEVEN_BYTE_MARK === self::SIX_BYTE_MARK) {
                if (isset($next5)) {
                    $return .= $next1 . $next2 . $next3 . $next4 . $next5;
                    $pos    += 5;
                }
            }

            // Move forward in the buffer
            $pos++;
        }

        return implode("\r\n", $lines);
    }

    /**
     * Method to generate output/vcalendar. Overwriting functions should place class specific code before the parent
     * call.
     * @return void
     * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.4
     * @throws Exception
     */
    public function display()
    {
        $ics   = [];
        $ics[] = 'BEGIN:VCALENDAR';
        $ics[] = "VERSION:2.0";
        $ics[] = "PRODID:-//TH Mittelhessen//NONSGML Organizer $this->version//$this->language";
        $ics[] = 'METHOD:PUBLISH';
        $ics[] = "X-WR-CALNAME:$this->title";
        $ics[] = "X-WR-TIMEZONE:$this->tzID";
        $ics[] = 'BEGIN:VTIMEZONE';
        $ics[] = "TZID:$this->tzID";

        $year         = date('Y');
        $januaryOne   = "$year-01-01";
        $winter       = $this->getDTObject($januaryOne);
        $winterAbbr   = $winter->format('T');
        $winterOffset = $this->getOffset($winter);
        $julyOne      = "$year-07-01";
        $summer       = $this->getDTObject($julyOne);
        $summerAbbr   = $summer->format('T');
        $summerOffset = $this->getOffset($summer);

        $ics[] = 'BEGIN:STANDARD';
        $ics[] = "TZNAME:$winterAbbr";
        $ics[] = 'DTSTART:19701025T030000';
        $ics[] = 'RRULE:FREQ=YEARLY;BYMONTH=10;BYDAY=-1SU';
        $ics[] = "TZOFFSETFROM:$summerOffset";
        $ics[] = "TZOFFSETTO:$winterOffset";
        $ics[] = "END:STANDARD";

        $ics[] = 'BEGIN:DAYLIGHT';
        $ics[] = "TZNAME:$summerAbbr";
        $ics[] = 'DTSTART:19700329T020000';
        $ics[] = 'RRULE:FREQ=YEARLY;BYMONTH=3;BYDAY=-1SU';
        $ics[] = "TZOFFSETFROM:$winterOffset";
        $ics[] = "TZOFFSETTO:$summerOffset";
        $ics[] = "END:DAYLIGHT";
        $ics[] = "END:VTIMEZONE";


        foreach ($this->instances as $instance) {
            $this->addEvent($ics, $instance);
        }

        $ics[] = "END:VCALENDAR";

        foreach ($ics as $index => $line) {
            $ics[$index] = $this->chunk($line);
        }

        $fsize  = 0;
        $output = implode("\r\n", $ics) . "\r\n";

        if ($temp = tempnam(sys_get_temp_dir(), 'ics')) {
            if (file_put_contents($temp, $output)) {
                $fsize = filesize($temp);
            }

            unlink($temp);
        }

        if ($fsize) {
            header("Content-Length: $fsize");
        }

        header('Content-Type: text/calendar; charset=utf-8');
        header('Content-Disposition: attachment; filename="' . $this->fileName . '"');
        header('Cache-Control: max-age=10');
        echo $output;
        ob_flush();
    }

    /**
     * Escape special character use in text values.
     *
     * @param string $text
     *
     * @return string
     */
    private function escape(string $text): string
    {
        $text = preg_replace('/\\\\([^nNr,;])/', '\\\\\\\\$1', $text);

        $map = ['"' => "'", ',' => '\,', ';' => '\;', "\r\n" => '\n', "\r" => '\n', "\n" => '\n', '\N' => '\n'];

        foreach ($map as $from => $to) {
            $text = str_replace($from, $to, $text);
        }

        return $text;
    }

    /**
     * Formats a given date time string (Y-m-d H:i) to a timezone qualified DATE-TIME.
     *
     * @param string $dateTime
     *
     * @return string
     * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.3.5
     */
    private function getDateTime(string $dateTime = ''): string
    {
        $dateTime = preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}$/', $dateTime) ? $dateTime : date('Y-m-d H:i:s');
        $stamp    = strtotime($dateTime);
        $value    = date('Ymd', $stamp) . 'T' . date('His', $stamp);

        return ";TZID=$this->tzID:$value";
    }

    /**
     * @param string $dateTime
     *
     * @return DateTime|null
     * @throws Exception
     */
    private function getDTObject(string $dateTime): DateTime
    {
        return new DateTime($dateTime, new DateTimeZone($this->tzID));
    }

    /**
     * Gets the offset to UTC as a string.
     *
     * @param DateTime $dateTime the date at which the UTC offset is to be measured.
     *
     * @return string the formatted offset
     * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.3.3
     * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.3.4
     */
    private function getOffset(DateTime $dateTime): string
    {
        $offset = $dateTime->getOffset();

        if ($offset < 0) {
            $sign = '-';

            // Have to get rid of the signage for the sprintf formatting later
            $offset *= -1;
        } else {
            $sign = '+';
        }

        $hours   = (int) floor($offset / self::HOUR);
        $hours   = sprintf("%02d", $hours);
        $offset  = ($offset % self::HOUR);
        $minutes = (int) floor($offset / self::MINUTE);
        $minutes = sprintf("%02d", $minutes);

        return "$sign$hours$minutes";
    }

    /**
     * Sets the vevent comment and url, which would otherwise be in the comment. Removes all regexed URLs from the
     * comment. As per RFC only one URL is added, even if multiple were present.
     *
     * @param array   &$ics     the output container
     * @param string   $comment the commentary for the vevent
     *
     * @return void
     * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.1.4
     * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.4.6
     */
    private function setCommentnURL(array &$ics, string $comment = '')
    {
        if (!$comment) {
            return;
        }

        $url = '';

        $moodleCourse = 'URL:https://moodle.thm.de/course/view.php?id=PID';

        // Moodle Course 1
        if (preg_match('/(((https?):\/\/)moodle.thm.de\/course\/view.php\?id=(\d+))/', $comment, $matches)) {
            $comment = trim(str_replace($matches[0], '', $comment));
            $url     = str_replace('PID', $matches[4], $moodleCourse);
        }

        // Moodle Course 2
        if (preg_match('/moodle=(\d+)/', $comment, $matches)) {
            $comment = trim(str_replace($matches[0], '', $comment));

            if (!$url) {
                $url = str_replace('PID', $matches[1], $moodleCourse);
            }
        }

        $moodleCategory = 'URL:https://moodle.thm.de/course/index.php?categoryid=PID';

        // Moodle Category
        if (preg_match('/(((https?):\/\/)moodle\.thm\.de\/course\/index\.php\\?categoryid=(\\d+))/', $comment,
            $matches)) {
            $comment = trim(str_replace($matches[0], '', $comment));

            if (!$url) {
                $url = str_replace('PID', $matches[4], $moodleCategory);
            }
        }

        // NetAcademy
        if (preg_match('/(((https?):\/\/)\d+.netacad.com\/courses\/\d+)/', $comment, $matches)) {
            $thisURL = $matches[0];
            $comment = trim(str_replace($thisURL, '', $comment));

            if (!$url) {
                $url = "URL:$thisURL";
            }
        }

        $panopto = 'URL:https://panopto.thm.de/Panopto/Pages/Viewer.aspx?id=PID';

        // Panopto 1
        if (preg_match('/(((https?):\/\/)panopto.thm.de\/Panopto\/Pages\/Viewer.aspx\?id=[\d\w\-]+)/', $comment,
            $matches)) {
            $comment = trim(str_replace($matches[0], '', $comment));

            if (!$url) {
                $url = str_replace('PID', $matches[4], $panopto);
            }
        }

        // Panopto 2
        if (preg_match('/panopto=([\d\w\-]+)/', $comment, $matches)) {
            $comment = trim(str_replace($matches[0], '', $comment));

            if (!$url) {
                $url = str_replace('PID', $matches[1], $panopto);
            }
        }

        // Pilos
        if (preg_match('/(((https?):\/\/)(\d+|roxy).pilos-thm.de\/(b\/)?[\d\w]{3}-[\d\w]{3}-[\d\w]{3})/', $comment,
            $matches)) {
            $thisURL = $matches[0];
            $comment = trim(str_replace($thisURL, '', $comment));

            if (!$url) {
                $url = "URL:$thisURL";
            }
        }

        if ($url) {
            $ics[] = $url;
        }

        if ($comment) {
            $comment = $this->escape($comment);
            $ics[]   = "COMMENT:$comment";
        }
    }

    /**
     * Sets the stamp (UTC generation time) to be used for all components.
     * @return void
     */
    private function setStamp()
    {
        $date = gmdate('Ymd');
        $time = gmdate('His');

        $this->stamp = "DTSTAMP:{$date}T{$time}Z";
    }

    /**
     * Sets the globally unique id used as a property in every vcomponent as well as the
     * @return void
     */
    private function setTitles()
    {
        $state = $this->state;
        $user  = $this->user;
        $title = Helpers\Languages::_('ORGANIZER_INSTANCES');

        $suffix        = ".ics";
        $preTemplate   = "$title: %s";
        $postTemplate  = "%s $title";
        $postTemplate2 = "%s - Organizer $title";

        if ($state->get('filter.my') and $user->username) {
            $this->title    = Helpers\Languages::_('ORGANIZER_INSTANCES_ICS');
            $this->fileName = OutputFilter::stringURLSafe($this->title) . $suffix;

            return;
        }

        if ($thisID = (int) $state->get('filter.eventID')) {
            $this->title    = sprintf($postTemplate, Helpers\Events::getName($thisID));
            $this->fileName = OutputFilter::stringURLSafe($this->title) . $suffix;

            return;
        }

        if ($thisID = (int) $state->get('filter.personID')) {
            $this->title    = sprintf($postTemplate2, Helpers\Persons::getDefaultName($thisID, true));
            $this->fileName = OutputFilter::stringURLSafe($this->title) . $suffix;

            return;
        }

        if ($thisID = (int) $state->get('filter.groupID')) {
            $this->title    = sprintf($postTemplate, Helpers\Groups::getFullName($thisID));
            $this->fileName = OutputFilter::stringURLSafe($this->title) . $suffix;

            return;
        }

        if ($thisID = (int) $state->get('filter.roomID')) {
            $this->title    = sprintf($preTemplate, Helpers\Rooms::getName($thisID));
            $this->fileName = OutputFilter::stringURLSafe($this->title) . $suffix;

            return;
        }

        if ($thisID = (int) $state->get('filter.categoryID')) {
            $this->title    = sprintf($postTemplate, Helpers\Categories::getName($thisID));
            $this->fileName = OutputFilter::stringURLSafe($this->title) . $suffix;

            return;
        }

        if ($thisID = (int) $state->get('filter.organizationID')) {
            $this->title    = sprintf($postTemplate, Helpers\Organizations::getName($thisID));
            $this->fileName = OutputFilter::stringURLSafe($this->title) . $suffix;

            return;
        }

        if ($thisID = (int) $state->get('filter.campusID')) {
            $campus         = Helpers\Campuses::getName($thisID);
            $this->title    = sprintf($postTemplate, $campus);
            $this->fileName = OutputFilter::stringURLSafe($this->title) . $suffix;

            return;
        }

        $this->title    = $title;
        $this->fileName = OutputFilter::stringURLSafe($this->title) . $suffix;
    }

    /**
     * Sets the class $version property from the component manifest.
     * @return void
     */
    private function setVersion()
    {
        $manifest = JPATH_ADMINISTRATOR . '/components/com_organizer/com_organizer.xml';

        try {
            $manifest      = new SimpleXMLElement(file_get_contents($manifest));
            $this->version = (string) $manifest->version;
        } catch (Exception $exception) {
            $this->version = "X.X.X";
        }
    }
}
