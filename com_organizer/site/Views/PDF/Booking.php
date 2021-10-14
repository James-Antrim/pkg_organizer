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
use Organizer\Helpers\Bookings as Helper;
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
	 * The number of lines used by the header texts.
	 *
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

		if (count($subTitle) > 2)
		{
			$subTitle = [Helpers\Languages::_('ORGANIZER_MULTIPLE_EVENTS')];
		}

		$room = '';

		if ($roomID = $this->formState->get('filter.roomID'))
		{
			$room = ', ' . Helpers\Languages::_('ORGANIZER_ROOM') . ' ' . Helpers\Rooms::getName($roomID);
		}

		$subTitle[] = $this->dateTime . $room;

		switch ($this->formState->get('filter.status'))
		{
			case Helper::ALL:
				$subTitle[] = Helpers\Languages::_('ORGANIZER_CHECKED_IN_OR_REGISTERED_PARTICIPANTS');
				break;
			case Helper::ATTENDEES:
				$subTitle[] = Helpers\Languages::_('ORGANIZER_CHECKED_IN_PARTICIPANTS');
				break;
			case Helper::IMPROPER:
				$subTitle[] = Helpers\Languages::_('ORGANIZER_CHECKED_IN_NOT_REGISTERED_PARTICIPANTS');
				break;
			case Helper::ONLY_REGISTERED:
				$subTitle[] = Helpers\Languages::_('ORGANIZER_REGISTERED_NOT_CHECKED_IN_PARTICIPANTS');
				break;
			case Helper::PROPER:
				$subTitle[] = Helpers\Languages::_('ORGANIZER_CHECKED_IN_AND_REGISTERED_PARTICIPANTS');
				break;
		}

		$this->overhead = max(4, count($subTitle));
		$subTitle       = implode("\n", $subTitle);

		$this->setHeaderData('pdf_logo.png', '55', $title, $subTitle, self::BLACK, self::WHITE);
		$this->setFooterData(self::BLACK, self::WHITE);

		parent::setHeader();
	}
}
