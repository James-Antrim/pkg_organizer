<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Tables;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class Booking extends Participants
{
	/**
	 * @var Tables\Bookings
	 */
	public $booking;

	protected $rowStructure = [
		'checkbox' => '',
		'fullName' => 'value',
		'event'    => 'value',
		'complete' => 'value'
	];

	/**
	 * @inheritDoc
	 */
	protected function addSupplement()
	{
		$bookingDate = $this->booking->get('date');
		$expiredText = Languages::_('ORGANIZER_BOOKING_CLOSED');
		$ongoingText = Languages::_('ORGANIZER_BOOKING_ONGOING');
		$pendingText = Languages::_('ORGANIZER_BOOKING_PENDING');
		$statusColor = '';
		$today       = date('Y-m-d');

		if ($today === $bookingDate)
		{
			$end   = $this->booking->endTime ? $this->booking->endTime : $this->booking->get('defaultEndTime');
			$now   = date('H:i:s');
			$start = $this->booking->startTime ? $this->booking->startTime : $this->booking->get('defaultStartTime');

			if ($now >= $start and $now <= $end)
			{
				$statusColor = 'green';
				$texts[]     = $ongoingText;
			}
			elseif ($now < $start)
			{
				$statusColor = 'yellow';
				$texts[]     = $pendingText;
			}
			else
			{
				$statusColor = 'red';
				$texts[]     = $expiredText;
			}
		}
		elseif ($bookingDate > $today)
		{
			$statusColor = 'yellow';
			$texts[]     = $pendingText;
		}
		else
		{
			$statusColor = 'red';
			$texts[]     = $expiredText;
		}

		$count   = Helpers\Bookings::getParticipantCount(Helpers\Input::getID());
		$texts[] = sprintf(Helpers\Languages::_('ORGANIZER_CHECKIN_COUNT'), $count);

		$this->supplement = '<div class="tbox-' . $statusColor . '">' . implode('<br>', $texts) . '</div>';
	}

	/**
	 * @inheritDoc
	 */
	protected function addToolBar()
	{
		$title = Languages::_('ORGANIZER_EVENT_CODE') . ": {$this->booking->code}";

		Helpers\HTML::setTitle($title, 'users');

		$toolbar = Toolbar::getInstance();

		$icon = '<span class="icon-list-3"></span>';
		$text = Languages::_('ORGANIZER_MY_INSTANCES');
		$URL  = Uri::base() . "?option=com_organizer&view=instances&my=1";
		$link = Helpers\HTML::link($URL, $icon . $text, ['class' => 'btn']);
		$toolbar->appendButton('Custom', $link);

		$icon = '<span class="icon-grid-2"></span>';
		$text = Languages::_('QR Code');
		$URL  = Uri::getInstance()->toString() . "&layout=qrcode&tmpl=component";
		$link = Helpers\HTML::link($URL, $icon . $text, ['class' => 'btn', 'target' => 'qrcode']);
		$toolbar->appendButton('Custom', $link);

		$script      = "onclick=\"jQuery('#form-modal').modal('show'); return true;\"";
		$batchButton = "<button id=\"booking-notes\" data-toggle=\"modal\" class=\"btn btn-small\" $script>";
		$title       = Languages::_('ORGANIZER_NOTES');
		$batchButton .= '<span class="icon-pencil-2" title="' . $title . '"></span>' . " $title";
		$batchButton .= '</button>';
		$toolbar->appendButton('Custom', $batchButton, 'batch');

		// TODO add function to batch assign participants to the correct event
		// TODO add filter for participant events, should the booking be associated with more than one.
		// TODO add filter for incomplete profiles
		// TODO ajax refresh??

		$bookingDate = $this->booking->get('date');
		$today       = date('Y-m-d');

		if ($today <= $bookingDate)
		{
			$text = Languages::_('ORGANIZER_REMOVE_PARTICIPANTS');
			$toolbar->appendButton('Standard', 'user-minus', $text, 'booking.removeParticipants', true);
		}

		if ($today === $bookingDate)
		{
			$defaultEnd   = $this->booking->get('defaultEndTime');
			$defaultStart = $this->booking->get('defaultStartTime');
			$end          = $this->booking->endTime ? $this->booking->endTime : $defaultEnd;
			$now          = date('H:i:s');
			$start        = $this->booking->startTime ? $this->booking->startTime : $defaultStart;
			$then         = date('H:i:s', strtotime('-60 minutes', strtotime($defaultStart)));
			$earlyStart   = ($now > $then and $now < $start);
			$reOpen       = ($now >= $end and $now < $defaultEnd);

			if ($earlyStart or $reOpen)
			{
				if ($earlyStart)
				{
					$icon = 'play';
					$text = Languages::_('ORGANIZER_MANUALLY_OPEN');
				}
				else
				{
					$icon = 'loop';
					$text = Languages::_('ORGANIZER_REOPEN');
				}

				$toolbar->appendButton('Standard', $icon, $text, 'booking.open', false);
			}
			elseif ($now > $defaultStart and !$this->booking->endTime)
			{
				$text = Languages::_('ORGANIZER_MANUALLY_CLOSE');
				$toolbar->appendButton('Standard', 'stop', $text, 'booking.close', false);
			}
		}

	}

	/**
	 * @inheritDoc
	 */
	protected function authorize()
	{
		if (!$bookingID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		if (!Helpers\Can::manage('booking', $bookingID))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function display($tpl = null)
	{
		// Set batch template path
		$this->batch   = ['form_modal'];
		$this->booking = $this->get('Booking');
		$this->empty   = '';
		$this->refresh = 60;

		parent::display($tpl);
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyDocument()
	{
		if ($this->_layout === 'qrcode')
		{
			Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/qrcode.css');
		}
		else
		{
			parent::modifyDocument();
		}

	}

	/**
	 * @inheritDoc
	 */
	protected function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox' => Helpers\HTML::_('grid.checkall'),
			'fullName' => Helpers\HTML::sort('NAME', 'fullName', $direction, $ordering),
			'event'    => Helpers\HTML::sort('EVENT', 'event', $direction, $ordering),
			'complete' => Languages::_('ORGANIZER_PROFILE_COMPLETE')
		];

		$this->headers = $headers;
	}

	/**
	 * @inheritDoc
	 */
	protected function setSubtitle()
	{
		$bookingID      = Helpers\Input::getID();
		$subTitle       = Helpers\Bookings::getNames($bookingID);
		$subTitle[]     = Helpers\Bookings::getDateTimeDisplay($bookingID);
		$this->subtitle = '<h6 class="sub-title">' . implode('<br>', $subTitle) . '</h6>';
	}

	/**
	 * @inheritdoc
	 */
	protected function structureItems()
	{
		$index = 0;
		$link  = 'index.php?option=com_organizer&view=participant_edit&id=';

		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$item->fullName = $item->forename ? $item->fullName : $item->surname;

			if ($item->complete)
			{
				$label = Languages::_('ORGANIZER_PROFILE_COMPLETE');
				$icon  = 'checked';
			}
			else
			{
				$label = Languages::_('ORGANIZER_PROFILE_INCOMPLETE');
				$icon  = 'unchecked';
			}

			$item->complete = Helpers\HTML::icon("checkbox-$icon", $label, true);

			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
