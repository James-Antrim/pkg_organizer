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

	/**
	 * @var int
	 */
	public $bookingID;

	protected $rowStructure = [
		'checkbox' => '',
		'fullName' => 'link',
		'event'    => 'link',
		'room'     => 'link',
		'seat'     => 'link',
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
		$today       = date('Y-m-d');

		if ($today === $bookingDate)
		{
			$end   = $this->booking->endTime ?: $this->booking->get('defaultEndTime');
			$now   = date('H:i:s');
			$start = $this->booking->startTime ?: $this->booking->get('defaultStartTime');

			if ($now >= $start and $now < $end)
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

		$toolbar = Adapters\Toolbar::getInstance();

		$icon = '<span class="icon-list-3"></span>';
		$text = Languages::_('ORGANIZER_MY_INSTANCES');
		$url  = Uri::base() . "?option=com_organizer&view=instances&my=1";
		$link = Helpers\HTML::link($url, $icon . $text, ['class' => 'btn']);
		$toolbar->appendButton('Custom', $link);

		$icon = '<span class="icon-grid-2"></span>';
		$text = Languages::_('QR Code');
		$url  = Uri::getInstance()->toString() . "&layout=qrcode&tmpl=component";
		$link = Helpers\HTML::link($url, $icon . $text, ['class' => 'btn', 'target' => 'qrcode']);
		$toolbar->appendButton('Custom', $link);

		$bookingDate = $this->booking->get('date');
		$today       = date('Y-m-d');

		if (count($this->items))
		{
			$toolbar->appendButton(
				'NewTab',
				'file-pdf',
				Languages::_('ORGANIZER_ATTENDANCE_LIST'),
				'Bookings.pdf',
				false
			);

			// No easy removal at a later date
			if ($today <= $bookingDate)
			{
				$text = Languages::_('ORGANIZER_DELETE');
				$toolbar->appendButton('Standard', 'user-minus', $text, 'bookings.removeParticipants', true);
			}
		}

		if ($today === $bookingDate)
		{
			$defaultEnd   = $this->booking->get('defaultEndTime');
			$defaultStart = $this->booking->get('defaultStartTime');
			$end          = $this->booking->endTime ?: $defaultEnd;
			$now          = date('H:i:s');
			$start        = $this->booking->startTime ?: $defaultStart;
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

				$toolbar->appendButton('Standard', $icon, $text, 'bookings.open', false);
			}
			elseif ($now > $defaultStart and !$this->booking->endTime)
			{
				$text = $now < $defaultEnd ? Languages::_('ORGANIZER_MANUALLY_CLOSE_PRE') : Languages::_('ORGANIZER_MANUALLY_CLOSE_POST');
				$toolbar->appendButton('Standard', 'stop', $text, 'bookings.close', false);
			}
		}

		$url = Uri::base() . "index.php?Itemid=4908";
		$toolbar->appendButton('Link', 'help', Languages::_('ORGANIZER_HELP'), $url, true);

	}

	/**
	 * @inheritDoc
	 */
	protected function authorize()
	{
		if (!Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}

		if (!$this->bookingID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		if (!Helpers\Can::manage('booking', $this->bookingID))
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
		$this->batch   = ['batch_participation', 'form_modal'];
		$this->booking = $this->get('Booking');
		$this->empty   = '';
		$this->refresh = 60;
		$this->sameTab = true;

		parent::display($tpl);
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyDocument()
	{
		if ($this->layout === 'qrcode')
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
			'event'    => Languages::_('ORGANIZER_EVENT'),
			'room'     => Languages::_('ORGANIZER_ROOM'),
			'seat'     => Languages::_('ORGANIZER_SEAT'),
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
		$link  = "index.php?option=com_organizer&view=instance_participant_edit&bookingID=$this->bookingID&id=";

		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$item->id       = $item->ipaID;
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

			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->ipaID);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
