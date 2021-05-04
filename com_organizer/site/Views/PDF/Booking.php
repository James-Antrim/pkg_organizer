<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\PDF;

use Organizer\Helpers;
use Organizer\Models\Booking as Model;
use Organizer\Tables;

/**
 * Class loads persistent information about a course into the display context.
 */
class Booking extends ListView
{
    /**
     * @var Tables\Bookings
     */
    public $booking;

    /**
     * The id of the booking.
     * @var int
     */
    public $bookingID;

    /**
     * The date and time of the booking.
     * @var string
     */
    public $dateTime;

    /**
     * The name of the events associated with the booking
     * @var string
     */
    public $events;

    /**
     * @var Model
     */
    protected $model;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->booking  = $this->model->getBooking();
        $this->events   = Helpers\Bookings::getName($this->bookingID);
        $this->dateTime = Helpers\Bookings::getDateTimeDisplay($this->bookingID);
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Users::getID()) {
            Helpers\OrganizerHelper::error(401);
        }

        if (!$this->bookingID = Helpers\Input::getID()) {
            Helpers\OrganizerHelper::error(400);
        }

        if (!Helpers\Can::manage('booking', $this->bookingID)) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    public function display($destination = self::INLINE)
    {
        parent::display($destination);
    }

    /**
     * Set header items.
     *
     * @return void
     */
    public function setOverhead()
    {
        $title    = Helpers\Languages::_('ORGANIZER_EVENT_CODE') . ': ' . $this->booking->code;
        $subTitle = Helpers\Bookings::getNames($this->bookingID);

        if (count($subTitle) > 2) {
            $subTitle = [Helpers\Languages::_('ORGANIZER_MULTIPLE_EVENTS')];
        }

        $subTitle[] = $this->dateTime;
        $subTitle   = implode("\n", $subTitle);

        $this->setHeaderData('pdf_logo.png', '55', $title, $subTitle, self::BLACK, self::WHITE);
        $this->setFooterData(self::BLACK, self::WHITE);

        parent::setHeader();
    }
}
