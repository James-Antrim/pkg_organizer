<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Application, Document, HTML, Input, Text, Toolbar};
use THM\Organizer\Helpers\{Bookings as Helper, Can, Users};
use THM\Organizer\Tables;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class Booking extends Participants
{
    /**
     * @var Tables\Bookings
     */
    public $booking;

    /**
     * @var int
     */
    public int $bookingID;

    private bool $hasRegistered = false;

    protected array $rowStructure = [
        'checkbox' => '',
        'status'   => 'value',
        'fullName' => 'link',
        'event'    => 'value',
        'room'     => 'value',
        'seat'     => 'value',
        'complete' => 'value'
    ];

    /**
     * @inheritDoc
     */
    protected function addSubtitle(): void
    {
        $bookingID      = Input::getID();
        $subTitle       = Helper::getNames($bookingID);
        $subTitle[]     = Helper::getDateTimeDisplay($bookingID);
        $this->subtitle = '<h6 class="sub-title">' . implode('<br>', $subTitle) . '</h6>';
    }

    /**
     * @inheritDoc
     */
    protected function addSupplement(): void
    {
        $bookingDate = $this->booking->get('date');
        $expiredText = Text::_('ORGANIZER_BOOKING_CLOSED');
        $ongoingText = Text::_('ORGANIZER_BOOKING_ONGOING');
        $pendingText = Text::_('ORGANIZER_BOOKING_PENDING');
        $today       = date('Y-m-d');

        if ($today === $bookingDate) {
            $end   = $this->booking->endTime ?: $this->booking->get('defaultEndTime');
            $now   = date('H:i:s');
            $start = $this->booking->startTime ?: $this->booking->get('defaultStartTime');

            if ($now >= $start and $now < $end) {
                $statusColor = 'green';
                $texts[]     = $ongoingText;
            }
            elseif ($now < $start) {
                $statusColor = 'yellow';
                $texts[]     = $pendingText;
            }
            else {
                $statusColor = 'red';
                $texts[]     = $expiredText;
            }
        }
        elseif ($bookingDate > $today) {
            $statusColor = 'yellow';
            $texts[]     = $pendingText;
        }
        else {
            $statusColor = 'red';
            $texts[]     = $expiredText;
        }

        $count         = Helper::getParticipantCount($this->bookingID);
        $registrations = Helper::getRegistrations($this->bookingID);
        $capacity      = Helper::getCapacity($this->bookingID);
        $countText     = Text::sprintf('ORGANIZER_CHECKIN_COUNT', $count, $registrations, $capacity);

        if ($count and $roomID = $this->state->get('filter.roomID')) {
            $roomCount = Helper::getParticipantCount($this->bookingID, $roomID);
            $roomCount = Text::sprintf('ORGANIZER_CHECKIN_ROOM_COUNT', $roomCount);
            $countText .= " ($roomCount)";
        }

        $texts[] = $countText;

        $this->supplement = '<div class="tbox-' . $statusColor . '">' . implode('<br>', $texts) . '</div>';
    }

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $title = Text::_('ORGANIZER_EVENT_CODE') . ": {$this->booking->code}";

        $this->setTitle($title);

        $toolbar = Toolbar::getInstance();

        $icon = '<span class="icon-list-3"></span>';
        $text = Text::_('ORGANIZER_MY_INSTANCES');
        $url  = Uri::base() . "?option=com_organizer&view=instances&my=1";
        $link = HTML::link($url, $icon . $text, ['class' => 'btn']);
        $toolbar->appendButton('Custom', $link);

        $bookingDate = $this->booking->get('date');
        $today       = date('Y-m-d');
        $earlyStart  = false;
        $end         = $this->booking->get('defaultEndTime');
        $ended       = false;
        $expired     = $today > $bookingDate;
        $isToday     = $today === $bookingDate;
        $notOver     = true;
        $now         = date('H:i:s');
        $reOpen      = false;
        $start       = $this->booking->get('defaultStartTime');
        $started     = false;

        if ($isToday) {
            $earlyStart = date('H:i:s', strtotime('-60 minutes', strtotime($this->booking->get('defaultStartTime'))));
            $end        = $this->booking->endTime ?: $end;
            $ended      = $now >= $end;
            $start      = $this->booking->startTime ?: $start;
            $started    = $now > $start;

            $earlyStart = ($now > $earlyStart and $now < $start);
            $notOver    = $now < $this->booking->get('defaultEndTime');
            $reOpen     = ($ended and $notOver);
        }

        if (!$expired and !($isToday and $ended)) {
            $icon = '<span class="icon-grid-2"></span>';
            $text = Text::_('QR Code');
            $url  = Uri::getInstance()->toString() . "&layout=qrcode&tmpl=component";
            $link = HTML::link($url, $icon . $text, ['class' => 'btn', 'target' => 'qrcode']);
            $toolbar->appendButton('Custom', $link);
        }

        if (count($this->items)) {
            $toolbar->appendButton(
                'NewTab',
                'file-pdf',
                Text::_('ORGANIZER_ATTENDANCE_LIST'),
                'Bookings.pdf',
                false
            );

            if (($expired or ($isToday and $now >= $start)) and $this->hasRegistered) {
                $text = Text::_('ORGANIZER_CHECKIN');
                $toolbar->appendButton('Standard', 'user-check', $text, 'bookings.checkin', true);
            } // No easy removal at a later date
            elseif (!$expired) {
                $text = Text::_('ORGANIZER_DELETE');
                $toolbar->appendButton('Standard', 'user-minus', $text, 'bookings.removeParticipants', true);
            }
        }

        if ($isToday) {
            if ($earlyStart or $reOpen) {
                if ($earlyStart) {
                    $icon = 'play';
                    $text = Text::_('ORGANIZER_MANUALLY_OPEN');
                }
                else {
                    $icon = 'loop';
                    $text = Text::_('ORGANIZER_REOPEN');
                }

                $toolbar->appendButton('Standard', $icon, $text, 'bookings.open', false);
            }
            elseif ($started and !$this->booking->endTime) {
                $text = $notOver ? Text::_('ORGANIZER_MANUALLY_CLOSE_PRE') : Text::_('ORGANIZER_MANUALLY_CLOSE_POST');
                $toolbar->appendButton('Standard', 'stop', $text, 'bookings.close', false);
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (!Users::getID()) {
            Application::error(401);
        }

        if (!$this->bookingID = Input::getID()) {
            Application::error(400);
        }

        if (!Can::manage('booking', $this->bookingID)) {
            Application::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        $index = 0;

        $bookingDate = $this->booking->get('date');
        $link        = '';
        $now         = date('H:i:s');
        $start       = $this->booking->get('defaultStartTime');
        $today       = date('Y-m-d');

        if ($today >= $bookingDate) {
            $start = $this->booking->startTime ?: $start;

            if (!($today === $bookingDate) or $now > $start) {
                $link = "index.php?option=com_organizer&view=instance_participant_edit&bookingID=$this->bookingID&id=";
            }
        }

        $structuredItems = [];

        foreach ($this->items as $item) {
            $item->id       = $item->ipaID;
            $item->fullName = $item->forename ? $item->fullName : $item->surname;
            $thisLink       = ($link and $item->attended) ? $link . $item->ipaID : '';

            if ($item->attended and $item->registered) {
                $label = 'CHECKED_IN';
                $icon  = 'fa fa-user-check';
            }
            elseif ($item->attended) {
                $label = 'STOWAWAY';
                $icon  = 'fa fa-user-plus';
            }
            else {
                $this->hasRegistered = true;

                $label = 'REGISTERED';
                $icon  = 'fa fa-question';
            }

            $item->status = HTML::tip(HTML::icon($icon), "person-status-$item->id", $label);

            if ($item->complete) {
                $label = 'PROFILE_COMPLETE';
                $icon  = 'fa fa-check-square';
            }
            else {
                $label = 'PROFILE_INCOMPLETE';
                $icon  = 'fa fa-square';
            }

            $item->complete = HTML::tip(HTML::icon($icon), "profile-status-$item->id", $label);

            $structuredItems[$index] = $this->completeItem($index, $item, $thisLink);
            $index++;
        }

        $this->items = $structuredItems;
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        // Set batch template path
        $this->batch   = ['batch_participation', 'form_modal'];
        $this->booking = $this->getModel()->booking;
        $this->empty   = '';
        $this->sameTab = true;

        parent::display($tpl);
    }

    /**
     * @inheritDoc
     */
    protected function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => HTML::checkAll(),
            'status'   => Text::_('STATUS'),
            'fullName' => HTML::sort('NAME', 'fullName', $direction, $ordering),
            'event'    => Text::_('EVENT'),
            'room'     => Text::_('ROOM'),
            'seat'     => Text::_('SEAT'),
            'complete' => Text::_('PROFILE_COMPLETE')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        if ($this->layout === 'qrcode') {
            Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/qrcode.css');
        }
        else {
            parent::modifyDocument();
        }

    }
}
