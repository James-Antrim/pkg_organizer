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

use Joomla\CMS\Uri\Uri;
use Organizer\Helpers\Bookings;
use Organizer\Helpers\Languages;
use Organizer\Layouts\PDF\BaseLayout;
use Organizer\Views\PDF\Booking as View;

/**
 * Class loads persistent information about a course into the display context.
 */
class QRCode extends BaseLayout
{
	/**
	 * @var View
	 */
	protected $view;

	/**
	 * @inheritDoc
	 */
	public function __construct(View $view)
	{
		parent::__construct($view);

		$view->margins(10, 10, -1, 10);
		$view->showPrintOverhead(false);
	}

	/**
	 * @inheritdoc
	 */
	public function fill(array $data)
	{
		$view = $this->view;
		$view->AddPage();
		$view->SetFillColor(57, 74, 89);

		$bookingID = $view->bookingID;
		$code      = $view->booking->code;

		$view->setColor('text', 255, 255, 255);
		$view->changeFont($view::BOLD, 50);
		$view->renderCell(190, 30, $code, $view::CENTER, $view::NONE, 1);

		$style = ['module_width' => 5, 'module_height' => 5];
		$URL   = Uri::base() . "?option=com_organizer&view=checkin&code=$code";
		$view->write2DBarcode($URL, 'QRCODE,L', 55, 55, 100, 100, $style);

		$view->changeFont($view::REGULAR, 20);
		$view->setColor('text', 0, 0, 0);
		$y = 160;

		foreach (Bookings::getNames($bookingID) as $name)
		{
			$view->changePosition(10, $y);
			$view->renderCell(190, 20, $name, $view::CENTER, $view::NONE);
			$y = $y + 10;
		}

		// 25 total here
		$y     = $y + 15;
		$rooms = implode(' ', Bookings::getRooms($bookingID));
		$view->changePosition(10, $y);
		$view->renderCell(190, 20, $rooms, $view::CENTER, $view::NONE);

		$y = $y + 25;
		$view->changePosition(10, $y);
		$view->renderCell(190, 20, Bookings::getDateTimeDisplay($bookingID), $view::CENTER, $view::NONE);

		$view->changePosition(10, 255);
		$view->setColor('text', 255, 255, 255);
		$view->changeFont($view::BOLD, 50);
		$view->renderCell(190, 30, 'go.thm.de/checkin', $view::CENTER, $view::NONE, 1);
	}

	/**
	 * Generates the title and sets name related properties.
	 */
	public function setTitle()
	{
		$view = $this->view;
		$name = Languages::_('ORGANIZER_EVENT') . '-' . $view->booking->code . '-' . Languages::_('ORGANIZER_CODE');
		$view->setNames($name);
	}
}
