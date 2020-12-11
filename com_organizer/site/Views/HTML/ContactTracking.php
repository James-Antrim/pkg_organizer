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

//use Joomla\CMS\Toolbar\Toolbar;
use Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of colors into the display context.
 */
class ContactTracking extends ListView
{
	protected $rowStructure = ['person' => 'value', 'dates' => 'value', 'length' => 'value'];

	/**
	 * @inheritdoc
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_("ORGANIZER_CONTACT_TRACKING"), 'list-2');
		//$toolbar = Toolbar::getInstance();
		//$toolbar->appendButton('Standard', 'envelope', Helpers\Languages::_('ORGANIZER_NOTIFY'), "", false);
	}

	/**
	 * @inheritdoc
	 */
	protected function authorize()
	{
		if (!Helpers\Can::manageTheseOrganizations())
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function setHeaders()
	{
		$headers = [
			'person' => Helpers\Languages::_('ORGANIZER_PERSON'),
			'dates'  => Helpers\Languages::_('ORGANIZER_DATES'),
			'length' => Helpers\Languages::_('ORGANIZER_CONTACT_LENGTH')
		];

		$this->headers = $headers;
	}

	/**
	 * @inheritdoc
	 */
	protected function structureItems()
	{
		$index           = 0;
		$link            = '';
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$dates   = [];
			$lengths = [];

			foreach ($item->dates as $date => $length)
			{
				$dates[]   = Helpers\Dates::formatDate($date);
				$lengths[] = "$length " . Helpers\Languages::_('ORGANIZER_MINUTES');
			}

			$item->dates  = implode('<br>', $dates);
			$item->length = implode('<br>', $lengths);

			$structuredItems[$index] = $this->structureItem($index, $item, $link);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
