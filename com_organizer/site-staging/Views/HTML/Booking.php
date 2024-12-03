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
use THM\Organizer\Adapters\{Application, Document, HTML, Input, Text, Toolbar, User};
use THM\Organizer\Buttons\FormTarget;
use THM\Organizer\Helpers\{Bookings as Helper, Can};
use THM\Organizer\Models\Booking as Model;
use THM\Organizer\Tables\Bookings as Table;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class Booking extends Participants
{
    public Table $booking;

    public int $bookingID;

    private bool $hasRegistered = false;

    /** @inheritDoc */
    protected function addToolBar(bool $delete = true): void
    {
        $this->title(Text::_('EVENT_CODE') . ": {$this->booking->code}");

        $toolbar = Toolbar::getInstance();

        $url = Uri::base() . "?option=com_organizer&view=instances&my=1";
        $toolbar->linkButton('my', Text::_('MY_INSTANCES'))->url($url)->icon('fa fa-th-list');

        $bookingDate = $this->booking->get('date');
        $earlyStart  = $ended = $reOpen = $started = false;
        $end         = $this->booking->get('defaultEndTime');
        $notOver     = true;
        $now         = date('H:i:s');
        $start       = $this->booking->get('defaultStartTime');
        $today       = date('Y-m-d');

        $expired = $today > $bookingDate;
        $isToday = $today === $bookingDate;

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
            $url = Uri::getInstance()->toString() . "&layout=qrcode&tmpl=component";
            $toolbar->linkButton('qrcode', 'QR Code')->target('qrcode')->url($url)->icon('fa fa-qrcode');
        }

        if (count($this->items)) {
            $button = new FormTarget('attendance', Text::_('ATTENDANCE_LIST'));
            $button->icon('fa fa-file-pdf')->task('Bookings.pdf');
            $toolbar->appendButton($button);

            if (($expired or ($isToday and $now >= $start)) and $this->hasRegistered) {
                $toolbar->standardButton('checkin', Text::_('CHECKIN'), 'Booking.checkin')
                    ->icon('fa fa-user-check')->listCheck(true);
            } // No easy removal at a later date
            elseif (!$expired) {
                $toolbar->standardButton('remove', Text::_('DELETE'), 'Booking.removeParticipants')
                    ->icon('fa fa-user-minus')->listCheck(true);
            }
        }

        if ($isToday) {
            if ($earlyStart or $reOpen) {
                if ($earlyStart) {
                    $icon = 'fa fa-play';
                    $text = Text::_('MANUALLY_OPEN');
                }
                else {
                    $icon = 'fa fa-sync';
                    $text = Text::_('REOPEN');
                }

                $toolbar->standardButton('open', $text, 'Booking.open')->icon($icon);
            }
            elseif ($started and !$this->booking->endTime) {
                $text = $notOver ? Text::_('MANUALLY_CLOSE_PRE') : Text::_('MANUALLY_CLOSE_POST');
                $toolbar->standardButton('close', $text, 'Booking.close')->icon('fa fa-stop');
            }
        }
    }

    /** @inheritDoc */
    protected function authorize(): void
    {
        if (!User::id()) {
            Application::error(401);
        }

        if (!$this->bookingID = Input::getID()) {
            Application::error(400);
        }

        if (!Can::manage('booking', $this->bookingID)) {
            Application::error(403);
        }
    }

    /** @inheritDoc */
    protected function completeItems(array $options = []): void
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

    /** @inheritDoc */
    public function display($tpl = null): void
    {
        // Set batch template path
        $this->batch = ['batch_participation', 'form_modal'];
        $this->empty = '';

        /** @var Model $model */
        $model = $this->getModel();

        $this->booking = $model->booking;

        parent::display($tpl);
    }

    /** @inheritDoc */
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

    /** @inheritDoc */
    protected function modifyDocument(): void
    {
        if ($this->layout === 'qrcode') {
            Document::style('qrcode');

            return;
        }

        parent::modifyDocument();
    }

    /** @inheritDoc */
    protected function subTitle(): void
    {
        $bookingID      = Input::getID();
        $subTitle       = Helper::names($bookingID);
        $subTitle[]     = Helper::dateTimeDisplay($bookingID);
        $this->subtitle = '<h6 class="sub-title">' . implode('<br>', $subTitle) . '</h6>';
    }

    /** @inheritDoc */
    protected function supplement(): void
    {
        $bookingDate = $this->booking->get('date');
        $expiredText = Text::_('BOOKING_CLOSED');
        $ongoingText = Text::_('BOOKING_ONGOING');
        $pendingText = Text::_('BOOKING_PENDING');
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

        $count         = Helper::participantCount($this->bookingID);
        $registrations = Helper::registrationCount($this->bookingID);
        $capacity      = Helper::capacity($this->bookingID);
        $countText     = Text::sprintf('CHECKIN_COUNT', $count, $registrations, $capacity);

        if ($count and $roomID = $this->state->get('filter.roomID')) {
            $roomCount = Helper::participantCount($this->bookingID, $roomID);
            $roomCount = Text::sprintf('CHECKIN_ROOM_COUNT', $roomCount);
            $countText .= " ($roomCount)";
        }

        $texts[] = $countText;

        $this->supplement = '<div class="tbox-' . $statusColor . '">' . implode('<br>', $texts) . '</div>';
    }
}
