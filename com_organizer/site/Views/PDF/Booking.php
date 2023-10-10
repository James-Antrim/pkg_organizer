<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\PDF;

use THM\Organizer\Adapters\{Application, Input, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Bookings as Helper;
use THM\Organizer\Models\Booking as Model;
use THM\Organizer\Tables;
use TCPDF_FONTS;

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
     * The number of lines used by the header texts.
     * @var int
     */
    public $overhead;

    /**
     * Constructor
     */
    public function __construct()
    {
        parent::__construct();

        $this->booking  = $this->model->getBooking();
        $this->events   = Helper::getName($this->bookingID);
        $this->dateTime = Helper::getDateTimeDisplay($this->bookingID);
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Users::getID()) {
            Application::error(401);
        }

        if (!$this->bookingID = Input::getID()) {
            Application::error(400);
        }

        if (!Helpers\Can::manage('booking', $this->bookingID)) {
            Application::error(403);
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
     * @inheritDoc
     */
    public function Footer()
    {
        if ($this->formState->get('filter.status') === Helper::ALL) {
            //set style for cell border
            $pageWidth = (0.85 / $this->k);
            $this->SetLineStyle(['width' => $pageWidth, 'color' => $this->footer_line_color]);
            $this->SetX($this->original_lMargin);

            $pageFont = $this->getFontFamily();
            $this->SetFont('zapfdingbats');
            $this->renderCell(3, 0, TCPDF_FONTS::unichr(51), self::LEFT, self::TOP);
            $this->SetFont($pageFont);
            $this->renderCell(25, 0, Text::_('ORGANIZER_CHECKED_IN'), self::LEFT, self::TOP);
            $this->SetFont('zapfdingbats');
            $this->renderCell(3, 0, TCPDF_FONTS::unichr(46), self::LEFT, self::TOP);
            $this->SetFont($pageFont);
            $this->renderCell(25, 0, Text::_('ORGANIZER_REGISTERED'), self::LEFT, self::TOP);

            $pnText = $this->getAliasNumPage() . ' / ' . $this->getAliasNbPages();
            $this->Cell(0, 0, $this->getAliasRightShift() . $pnText, self::TOP, 0, self::RIGHT);

            return;
        }

        parent::Footer();
    }

    /**
     * Set header items.
     * @return void
     */
    public function setOverhead()
    {
        $title    = Text::_('ORGANIZER_EVENT_CODE') . ': ' . $this->booking->code;
        $subTitle = Helpers\Bookings::getNames($this->bookingID);

        if (count($subTitle) > 2) {
            $subTitle = [Text::_('ORGANIZER_MULTIPLE_EVENTS')];
        }

        $room = '';

        if ($roomID = $this->formState->get('filter.roomID')) {
            $room = ', ' . Text::_('ORGANIZER_ROOM') . ' ' . Helpers\Rooms::getName($roomID);
        }

        $subTitle[] = $this->dateTime . $room;

        switch ($this->formState->get('filter.status')) {
            case Helper::ALL:
                $subTitle[] = Text::_('ORGANIZER_CHECKED_IN_OR_REGISTERED_PARTICIPANTS');
                break;
            case Helper::ATTENDEES:
                $subTitle[] = Text::_('ORGANIZER_CHECKED_IN_PARTICIPANTS');
                break;
            case Helper::IMPROPER:
                $subTitle[] = Text::_('ORGANIZER_CHECKED_IN_NOT_REGISTERED_PARTICIPANTS');
                break;
            case Helper::ONLY_REGISTERED:
                $subTitle[] = Text::_('ORGANIZER_REGISTERED_NOT_CHECKED_IN_PARTICIPANTS');
                break;
            case Helper::PROPER:
                $subTitle[] = Text::_('ORGANIZER_CHECKED_IN_AND_REGISTERED_PARTICIPANTS');
                break;
        }

        $this->overhead = max(4, count($subTitle));
        $subTitle       = implode("\n", $subTitle);

        $this->setHeaderData('pdf_logo.png', '55', $title, $subTitle, self::BLACK, self::WHITE);
        $this->setFooterData(self::BLACK, self::WHITE);

        parent::setHeader();
    }
}
