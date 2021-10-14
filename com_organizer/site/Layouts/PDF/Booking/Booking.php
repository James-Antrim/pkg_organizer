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

use Organizer\Helpers\Languages;
use Organizer\Layouts\PDF\ListLayout;
use Organizer\Views\PDF\Booking as View;
use Organizer\Views\PDF\ListView;

/**
 * Class loads persistent information about a course into the display context.
 */
class Booking extends ListLayout
{
	/**
	 * @var View
	 */
	protected $view;

	protected $widths = [
		'index' => 10,
		'name'  => 50,
		'event' => 80,
		'room'  => 20,
		'seat'  => 20
	];

	/**
	 * @inheritDoc
	 */
	public function __construct(ListView $view)
	{
		parent::__construct($view);

		$this->headers = [
			'index' => '#',
			'name'  => Languages::_('ORGANIZER_NAME'),
			'event' => Languages::_('ORGANIZER_EVENT'),
			'room'  => Languages::_('ORGANIZER_ROOM'),
			'seat'  => Languages::_('ORGANIZER_SEAT')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function fill(array $data)
	{
		$itemNo = 1;
		$view   = $this->view;
		$height = 7.5 * $view->overhead;
		$view->margins(10, $height, -1, 0, 8);

		$this->addListPage();

		foreach ($data as $participant)
		{
			// Get the starting coordinates for later use with borders
			$maxLength = 0;
			$startX    = $view->GetX();
			$startY    = $view->GetY();

			foreach (array_keys($this->headers) as $columnName)
			{
				switch ($columnName)
				{
					case 'index':
						$value = $itemNo;
						break;
					case 'name':
						$value = empty($participant->forename) ?
							$participant->surname : "$participant->surname,  $participant->forename";
						break;
					default:
						$value = empty($participant->$columnName) ? '' : $participant->$columnName;
						break;
				}

				$length = $view->renderMultiCell($this->widths[$columnName], 5, $value);

				if ($length > $maxLength)
				{
					$maxLength = $length;
				}
			}

			$this->addLineBorders($startX, $startY, $maxLength);
			$this->addLine();
			$itemNo++;
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
