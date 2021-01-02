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

use Organizer\Adapters\Toolbar;
use Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of colors into the display context.
 */
class ContactTracking extends ListView
{
	protected $rowStructure = ['person' => 'value', 'data' => 'value', 'dates' => 'value', 'length' => 'value'];

	/**
	 * @inheritdoc
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_("ORGANIZER_CONTACT_TRACKING"), 'list-2');

		if (($this->state->get('participantID') or $this->state->get('personID')) and count($this->items))
		{
			$toolbar = Toolbar::getInstance();
			//$toolbar->appendButton('Standard', 'envelope', Helpers\Languages::_('ORGANIZER_NOTIFY'), '', false);
			$toolbar->appendButton('NewTab', 'file-pdf', Helpers\Languages::_('Download as PDF'), 'ContactTracking.pdf', false);
		}
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

		if (!Helpers\Can::traceContacts())
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
			'data'   => Helpers\Languages::_('ORGANIZER_CONTACT_INFORMATION'),
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

			$data         = [$item->telephone, $item->email, $item->address, "$item->zipCode $item->city"];
			$data         = array_filter($data);
			$item->data   = implode('<br>', $data);
			$item->dates  = implode('<br>', $dates);
			$item->length = implode('<br>', $lengths);
			$item->person = "$item->person ($item->username)";

			$structuredItems[$index] = $this->structureItem($index, $item, $link);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
