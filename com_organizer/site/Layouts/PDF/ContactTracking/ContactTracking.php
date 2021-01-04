<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\PDF\ContactTracking;

use Organizer\Helpers;
use Organizer\Layouts\PDF\ListLayout;
use Organizer\Views\PDF\ContactTracking as View;

class ContactTracking extends ListLayout
{
	/**
	 * @var View
	 */
	protected $view;

	protected $widths = [
		'index'  => 10,
		'person' => 50,
		'data'   => 70,
		'dates'  => 20,
		'length' => 25
	];

	/**
	 * @inheritDoc
	 */
	public function __construct(View $view)
	{
		parent::__construct($view);
		$view->margins(10, 30, -1, 0, 8);

		$this->headers = [
			'index'  => '#',
			'person' => Helpers\Languages::_('ORGANIZER_PERSON'),
			'data'   => Helpers\Languages::_('ORGANIZER_CONTACT_INFORMATION'),
			'dates'  => Helpers\Languages::_('ORGANIZER_DATES'),
			'length' => Helpers\Languages::_('ORGANIZER_CONTACT_LENGTH')
		];
	}

	/**
	 * @inheritdoc
	 */
	public function fill(array $data)
	{
		$itemNo = 1;
		$view   = $this->view;
		$this->addListPage();

		foreach ($data as $person)
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
					case 'person':
						$value = $person->person;
						$value .= $person->username ? " ($person->username)" : '';
						break;
					case 'data' :
						$values = [$person->telephone, $person->email, $person->address, "$person->zipCode $person->city"];
						foreach ($values as $index => $dataPoint)
						{
							$values[$index] = trim($dataPoint);
						}
						$values = array_filter($values);
						$value  = implode("\n", $values);
						break;
					case 'dates' :
						$values = array_keys($person->dates);
						$value  = implode("\n", $values);
						break;
					case 'length':
						$values = [];
						foreach ($person->dates as $minutes)
						{
							$values[] = $minutes . ' ' . Helpers\Languages::_('ORGANIZER_MINUTES');
						}
						$value = implode("\n", $values);
						break;
					default:
						$value = '';
						break;
				}

				$length = $view->renderMultiCell($this->widths[$columnName], 5, $value);

				if ($length > $maxLength)
				{
					$maxLength = $length;
				}
			}

			// Reset for borders
			$view->changePosition($startX, $startY);

			foreach ($this->widths as $index => $width)
			{
				$border = $index === 'index' ? ['BLR' => $view->border] : ['BR' => $view->border];
				$view->renderMultiCell($width, $maxLength * 5, '', $view::LEFT, $border);
			}

			$this->addLine();

			$itemNo++;
		}
	}

	/**
	 * Generates the title and sets name related properties.
	 */
	public function setTitle()
	{
		$documentName = Helpers\Languages::_('ORGANIZER_CONTACTS') . ': ' . $this->view->participantName;
		$this->view->setNames($documentName);
	}
}
