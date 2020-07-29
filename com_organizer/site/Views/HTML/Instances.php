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
use Organizer\Helpers\Languages;

/**
 * Class loads persistent information a filtered set of instances into the display context.
 */
class Instances extends ListView
{
	const MONDAY = 1, TUESDAY = 2, WEDNESDAY = 3, THURSDAY = 4, FRIDAY = 5, SATURDAY = 6, SUNDAY = 7;

	/**
	 * Sets Joomla view title and action buttons
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		$params = Helpers\Input::getParams();

		if (!$params->get('show_page_heading') or !$title = $params->get('page_title'))
		{
			$title  = Helpers\Languages::_("ORGANIZER_INSTANCES");
			$suffix = '';

			if ($dow = $params->get('dow'))
			{
				switch ($dow)
				{
					case self::MONDAY:
						$title = Helpers\Languages::_("ORGANIZER_MONDAY_INSTANCES");
						break;
					case self::TUESDAY:
						$title = Helpers\Languages::_("ORGANIZER_TUESDAY_INSTANCES");
						break;
					case self::WEDNESDAY:
						$title = Helpers\Languages::_("ORGANIZER_WEDNESDAY_INSTANCES");
						break;
					case self::THURSDAY:
						$title = Helpers\Languages::_("ORGANIZER_THURSDAY_INSTANCES");
						break;
					case self::FRIDAY:
						$title = Helpers\Languages::_("ORGANIZER_FRIDAY_INSTANCES");
						break;
					case self::SATURDAY:
						$title = Helpers\Languages::_("ORGANIZER_SATURDAY_INSTANCES");
						break;
					case self::SUNDAY:
						$title = Helpers\Languages::_("ORGANIZER_SUNDAY_INSTANCES");
						break;
				}
			}
			elseif ($methodID = $params->get('methodID'))
			{
				$title = Helpers\Methods::getPlural($methodID);
			}

			if ($organizationID = $params->get('organizationID'))
			{
				$suffix .= ': ' . Helpers\Organizations::getFullName($organizationID);
			}
			elseif ($campusID = $params->get('campusID'))
			{
				$suffix .= ': ' . Helpers\Languages::_("ORGANIZER_CAMPUS") . ' ' . Helpers\Campuses::getName($campusID);
			}
		}

		// Add menu title support, both direct and via selected filters
		Helpers\HTML::setTitle($title . $suffix, 'list-2');

		$toolbar = Toolbar::getInstance();
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return bool true if the use may access the view, otherwise false
	 */
	protected function allowAccess()
	{
		return true;
	}

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	public function setHeaders()
	{
		$this->headers = [
			//'checkbox'     => Helpers\HTML::_('grid.checkall'),
			'status'       => Languages::_('ORGANIZER_STATUS'),
			'name'         => Languages::_('ORGANIZER_NAME'),
			'times'        => Languages::_('ORGANIZER_DATETIME'),
			'organization' => Languages::_('ORGANIZER_ORGANIZATION')
		];
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
		$template        = '<span class="icon-XICONX hasToolTip" title="XTITLEX"></span>';

		foreach ($this->items as $item)
		{
			$icon  = '';
			$title = '';

			if ($item->unitStatus)
			{
				$date = Helpers\Dates::formatDate($item->unitStatusDate);

				if ($item->unitStatus === 'new')
				{
					$icon  = 'sun';
					$title = sprintf(Languages::_('ORGANIZER_UNIT_ADDED_ON'), $date);
				}
				elseif ($item->unitStatus === 'removed')
				{
					$icon  = 'cancel-circle';
					$title = sprintf(Languages::_('ORGANIZER_UNIT_REMOVED_ON'), $date);
				}
			}
			elseif ($item->instanceStatus)
			{
				$date = Helpers\Dates::formatDate($item->instanceStatusDate);

				if ($item->instanceStatus === 'new')
				{
					$icon  = 'plus-circle';
					$title = sprintf(Languages::_('ORGANIZER_INSTANCE_ADDED_ON'), $date);
				}
				elseif ($item->instanceStatus === 'removed')
				{
					$icon  = 'minus-circle';
					$title = sprintf(Languages::_('ORGANIZER_INSTANCE_REMOVED_ON'), $date);
				}
			}

			$status = $title ? str_replace('XICONX', $icon, str_replace('XTITLEX', $title, $template)) : '';

			$name = '<span class="event">' . $item->name . '</span>';
			$name .= empty($item->method) ? '' : "<br><span class=\"method\">$item->method</span>";
			$name .= empty($item->comment) ? '' : "<br><span class=\"comment\">$item->comment</span>";

			$times = '<span class="date">' . Helpers\Dates::formatDate($item->date) . '</span><br>';
			$times .= '<span class="times">' . Helpers\Dates::formatTime($item->startTime) . ' - ';
			$times .= Helpers\Dates::formatTime($item->endTime) . '</span>';

			$structuredItems[$index]                 = [];
			//$structuredItems[$index]['checkbox']     = Helpers\HTML::_('grid.id', $index, $item->instanceID);
			$structuredItems[$index]['status']       = $status;
			$structuredItems[$index]['name']         = $name;
			$structuredItems[$index]['times']        = $times;
			$structuredItems[$index]['organization'] = $item->organization;

			$index++;
		}

		$this->items = $structuredItems;
	}
}