<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\PDF\Booking;

use Organizer\Helpers\Bookings as Helper;
use Organizer\Helpers\Languages;
use Organizer\Layouts\PDF\ListLayout;
use Organizer\Views\PDF\Booking as View;

/**
 * Class loads persistent information about a course into the display context.
 */
class Booking extends ListLayout
{
    /**
     * @var View
     */
    protected $view;

    protected $widths = [];

    /**
     * @inheritdoc
     */
    public function fill(array $data)
    {
        $itemNo = 1;
        $view   = $this->view;
        $height = 7.5 * $view->overhead;
        $view->margins(10, $height, -1, 0, 8);
        $this->setColumns();
        $this->addListPage();

        foreach ($data as $participant) {
            // Get the starting coordinates for later use with borders
            $maxLength = 0;
            $startX    = $view->GetX();
            $startY    = $view->GetY();

            foreach (array_keys($this->headers) as $columnName) {
                switch ($columnName) {
                    case 'index':
                        $value = $itemNo;
                        break;
                    case 'name':
                        $value = empty($participant->forename) ?
                            $participant->surname : "$participant->surname,  $participant->forename";
                        break;
                    case 'attended':
                    case 'registered':
                        $value = empty($participant->$columnName) ? '' : 'X';
                        break;
                    default:
                        $value = empty($participant->$columnName) ? '' : $participant->$columnName;
                        break;
                }

                $length = $view->renderMultiCell($this->widths[$columnName], 5, $value);

                if ($length > $maxLength) {
                    $maxLength = $length;
                }
            }

            $this->addLineBorders($startX, $startY, $maxLength);
            $this->addLine();
            $itemNo++;
        }
    }

    /**
     * Sets the names and widths of columns dependent upon booking properties and selected filters.
     * @return void
     */
    private function setColumns()
    {
        $this->headers = [
            'checkbox' => '',
            'index' => '#',
            'name' => Languages::_('ORGANIZER_NAME'),
            'attended' => '51',
            'registered' => '46',
            'event' => Languages::_('ORGANIZER_EVENT'),
            'room' => Languages::_('ORGANIZER_ROOM'),
            'seat' => Languages::_('ORGANIZER_SEAT')
        ];

        $view     = $this->view;
        $showCR   = $view->formState->get('filter.status') === Helper::ALL;
        $showRoom = (count(Helper::getRooms($view->bookingID)) > 1 and !$view->formState->get('filter.roomID'));

        if ($showCR and $showRoom) {
            $this->widths = [
                'checkbox' => 5,
                'index' => 10,
                'name' => 50,
                'attended' => 7,
                'registered' => 7,
                'event' => 76,
                'room' => 15,
                'seat' => 10
            ];
        } elseif ($showCR) {
            unset($this->headers['room']);

            $this->widths = [
                'checkbox' => 5,
                'index' => 10,
                'name' => 55,
                'attended' => 8,
                'registered' => 8,
                'event' => 90,
                'seat' => 10
            ];
        } elseif ($showRoom) {
            unset($this->headers['attended'], $this->headers['registered']);

            $this->widths = [
                'checkbox' => 5,
                'index' => 10,
                'name' => 55,
                'event' => 85,
                'room' => 15,
                'seat' => 10
            ];
        } else {
            unset($this->headers['attended'], $this->headers['registered'], $this->headers['room']);

            $this->widths = [
                'checkbox' => 5,
                'index' => 10,
                'name' => 60,
                'event' => 90,
                'seat' => 15
            ];
        }
    }

    /**
     * Generates the title and sets name related properties.
     */
    public function setTitle()
    {
        $view = $this->view;
        $name = Languages::_('ORGANIZER_EVENT') . '-' . $view->booking->code . '-' . Languages::_('ORGANIZER_PARTICIPANTS');
        $view->setNames($name);
    }
}
