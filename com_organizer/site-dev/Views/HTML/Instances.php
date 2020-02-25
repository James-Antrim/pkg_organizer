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
use Organizer\Helpers;
use Organizer\Helpers\Languages; // Exception for frequency of use

/**
 * Class loads persistent information a filtered set of instances into the display context.
 */
class Instances extends ListView
{
	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	public function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox' => Helpers\HTML::_('grid.checkall'),
			'name'     => Helpers\HTML::sort('NAME', 'name', $direction, $ordering),
			'term'     => Languages::_('ORGANIZER_TERM'),
			'status'   => Languages::_('ORGANIZER_STATUS')
		];

		$this->headers = $headers;
	}

	/**
	 * Processes the items in a manner specific to the view, so that a generalized  output in the layout can occur.
	 *
	 * @return void processes the class items property
	 */
	protected function structureItems()
	{
		$index           = 0;
		$link            = 'index.php?option=com_organizer&view=instance_edit&id=';
		$structuredItems = [];

		foreach ($this->items as $item)
		{

			$today = date('Y-m-d');
			if ($item->date < $today)
			{
				$status = Languages::_('ORGANIZER_EXPIRED');
			}
			elseif ($item->date > $today)
			{
				$status = Languages::_('ORGANIZER_PENDING');
			}
			else
			{
				$status = Languages::_('ORGANIZER_CURRENT');
			}

			$thisLink                            = $link . $item->id;
			$structuredItems[$index]             = [];
			$structuredItems[$index]['checkbox'] = Helpers\HTML::_('grid.id', $index, $item->id);
			$structuredItems[$index]['name']     = Helpers\HTML::_('link', $thisLink, $item->name);
			$structuredItems[$index]['term']     = Helpers\HTML::_('link', $thisLink, $item->term);
			$structuredItems[$index]['status']   = Helpers\HTML::_('link', $thisLink, $status);

			$index++;
		}

		$this->items = $structuredItems;
	}
}