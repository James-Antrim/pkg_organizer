<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\PDF\CourseParticipants;

use stdClass;
use THM\Organizer\Adapters\Text;
use THM\Organizer\Layouts\PDF\BadgeLayout;
use THM\Organizer\Views\PDF\CourseParticipants;

/**
 * Class loads persistent information about a course into the display context.
 */
class Badges extends BadgeLayout
{
    /** @inheritDoc */
    public function fill(array $data): void
    {
        $view = $this->view;

        $count            = count($data);
        $buffer           = intval($count * .2);
        $count            = $count + 6 - ($count % 6);
        $buffer           = $buffer + 6 - ($buffer % 6);
        $badgeCount       = $count + $buffer;
        $emptyParticipant = new stdClass();
        $xOffset          = 10;
        $yOffset          = 0;

        $view->AddPage();

        for ($index = 0; $index < $badgeCount; $index++) {
            $participant = empty($data[$index]) ? $emptyParticipant : $data[$index];
            $badgeNumber = $index + 1;
            $this->front($participant, $xOffset, $yOffset);

            // End of the sheet
            if ($badgeNumber % 6 == 0) {

                $view->AddPage('L');

                $xOffset = 14;

                for ($boxNo = 0; $boxNo < 3; $boxNo++) {
                    for ($level = 0; $level < 2; $level++) {
                        // The next item should be 82 to the right
                        $yOffset = $level * 82;

                        $this->back($xOffset, $yOffset);
                    }

                    // The next row should be 92 lower
                    $xOffset += 92;
                }

                $xOffset = 10;
                $yOffset = 0;

                if ($badgeNumber < $badgeCount) {
                    $view->AddPage($view::LANDSCAPE);
                }

            } // End of the first row on a sheet
            elseif ($badgeNumber % 3 == 0) {
                $xOffset = 10;
                $yOffset = 82;
            } // Next item
            else {
                $xOffset += 92;
            }
        }
    }

    /** @inheritDoc */
    public function title(): void
    {
        /* @var CourseParticipants $view */
        $view = $this->view;

        $documentName = "$view->course - $view->campus - $view->startDate - " . Text::_('ORGANIZER_BADGES');
        $view->titles($documentName);
    }
}
