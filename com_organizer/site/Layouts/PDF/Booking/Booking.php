<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\PDF\Booking;

use THM\Organizer\Adapters\Text;
use THM\Organizer\Helpers\Bookings as Helper;
use THM\Organizer\Layouts\PDF\ListLayout;
use THM\Organizer\Views\PDF\Booking as View;

/**
 * Class loads persistent information about a course into the display context.
 */
class Booking extends ListLayout
{
    /** @inheritDoc */
    public function fill(array $data): void
    {
        /** @var View $view */
        $view = $this->view;

        $itemNo = 1;
        $height = 7.5 * $view->headerLines;
        $view->margins(10, $height, -1, 0, 8);
        $this->setColumns();
        $this->page();

        foreach ($data as $participant) {
            // Get the starting coordinates for later use with borders
            $maxLength = 0;
            $startX    = $view->GetX();
            $startY    = $view->GetY();

            foreach (array_keys($this->headers) as $columnName) {
                $value = match ($columnName) {
                    'index' => $itemNo,
                    'name' => empty($participant->forename) ?
                        $participant->surname : "$participant->surname,  $participant->forename",
                    'attended', 'registered' => empty($participant->$columnName) ? '' : 'X',
                    default => empty($participant->$columnName) ? '' : $participant->$columnName,
                };

                $length = $view->renderMultiCell($this->widths[$columnName], 5, $value);

                if ($length > $maxLength) {
                    $maxLength = $length;
                }
            }

            $this->borders($startX, $startY, $maxLength);
            $this->line();
            $itemNo++;
        }
    }

    /**
     * Sets the names and widths of columns dependent upon booking properties and selected filters.
     * @return void
     */
    private function setColumns(): void
    {
        $this->headers = [
            'checkbox'   => '',
            'index'      => '#',
            'name'       => Text::_('ORGANIZER_NAME'),
            'attended'   => '51',
            'registered' => '46',
            'event'      => Text::_('ORGANIZER_EVENT'),
            'room'       => Text::_('ORGANIZER_ROOM'),
            'seat'       => Text::_('ORGANIZER_SEAT')
        ];

        /** @var View $view */
        $view = $this->view;

        $showCR   = $view->formState->get('filter.status') === Helper::ALL;
        $showRoom = (count(Helper::rooms($view->bookingID)) > 1 and !$view->formState->get('filter.roomID'));

        if ($showCR and $showRoom) {
            $this->widths = [
                'checkbox'   => 5,
                'index'      => 10,
                'name'       => 50,
                'attended'   => 7,
                'registered' => 7,
                'event'      => 76,
                'room'       => 15,
                'seat'       => 10
            ];
        }
        elseif ($showCR) {
            unset($this->headers['room']);

            $this->widths = [
                'checkbox'   => 5,
                'index'      => 10,
                'name'       => 55,
                'attended'   => 8,
                'registered' => 8,
                'event'      => 90,
                'seat'       => 10
            ];
        }
        elseif ($showRoom) {
            unset($this->headers['attended'], $this->headers['registered']);

            $this->widths = [
                'checkbox' => 5,
                'index'    => 10,
                'name'     => 55,
                'event'    => 85,
                'room'     => 15,
                'seat'     => 10
            ];
        }
        else {
            unset($this->headers['attended'], $this->headers['registered'], $this->headers['room']);

            $this->widths = [
                'checkbox' => 5,
                'index'    => 10,
                'name'     => 60,
                'event'    => 90,
                'seat'     => 15
            ];
        }
    }

    /** @inheritDoc */
    public function title(): void
    {
        /** @var View $view */
        $view = $this->view;
        $name = Text::_('ORGANIZER_EVENT') . '-' . $view->booking->code . '-' . Text::_('ORGANIZER_PARTICIPANTS');
        $view->titles($name);
    }
}
