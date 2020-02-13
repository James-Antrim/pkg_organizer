<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Joomla\CMS\Factory;
use Joomla\CMS\Form\FormField;
use Joomla\CMS\Uri\Uri;
use Joomla\CMS\Router\Route;
use Organizer\Helpers;

/**
 * Class creates a box for managing subordinated curriculum elements. Change order, remove, add empty element.
 */
class ChildrenField extends FormField
{
	use Translated;

	/**
	 * Type
	 *
	 * @var    String
	 */
	protected $type = 'Children';

	/**
	 * Generates a text for the management of child elements
	 *
	 * @return string  the HTML for the input
	 */
	public function getInput()
	{
		$children = $this->getSubordinateItems();

		$document = Factory::getDocument();
		$document->addStyleSheet(Uri::root() . 'components/com_organizer/css/children.css');
		$document->addScript(Uri::root() . 'components/com_organizer/js/children.js');

		return $this->getHTML($children);
	}

	/**
	 * Retrieves child mappings for the resource being edited
	 *
	 * @return array  empty if no child data exists
	 */
	private function getSubordinateItems()
	{
		$resourceID   = $this->form->getValue('id');
		$contextParts = explode('.', $this->form->getName());
		$resource     = Helpers\OrganizerHelper::getResource($contextParts[1]);

		$dbo     = Factory::getDbo();
		$idQuery = $dbo->getQuery(true);
		$idQuery->select('id')
			->from('#__organizer_curricula')
			->where("{$resource}ID = '$resourceID'")
			->group('id');

		$dbo->setQuery($idQuery);

		if (!$parentID = Helpers\OrganizerHelper::executeQuery('loadResult'))
		{
			return [];
		}

		$subordinateQuery = $dbo->getQuery(true);
		$subordinateQuery->select('*')
			->from('#__organizer_curricula')
			->where("parentID = $parentID")
			->order('lft ASC');
		$dbo->setQuery($subordinateQuery);

		if (!$subordinateItems = Helpers\OrganizerHelper::executeQuery('loadAssocList', [], 'ordering'))
		{
			return [];
		}

		$this->setTypeData($subordinateItems);

		return $subordinateItems;
	}

	/**
	 * Sets attributes of subordinate items according to their type.
	 *
	 * @param   array  &$items  the subordinate resource items
	 *
	 * @return void  adds data to the &$children array
	 */
	private function setTypeData(&$items)
	{
		$poolEditLink    = 'index.php?option=com_organizer&view=pool_edit&id=';
		$subjectEditLink = 'index.php?option=com_organizer&view=subject_edit&id=';
		foreach ($items as $key => $range)
		{
			if ($range['poolID'])
			{
				$items[$key]['id']   = $range['poolID'] . 'p';
				$items[$key]['name'] = Helpers\Pools::getName($range['poolID']);
				$items[$key]['link'] = $poolEditLink . $range['poolID'];
			}
			else
			{
				$items[$key]['id']   = $range['subjectID'] . 's';
				$items[$key]['name'] = Helpers\Subjects::getName($range['subjectID']);
				$items[$key]['link'] = $subjectEditLink . $range['subjectID'];
			}
		}
	}

	/**
	 * Generates the HTML Output for the children field
	 *
	 * @param   array &$children  the children of the resource being edited
	 *
	 * @return string  the HTML string for the children field
	 */
	private function getHTML(&$children)
	{
		$html = '<table id="childList" class="table table-striped">';
		$html .= '<thead><tr>';
		$html .= '<th>' . Helpers\Languages::_('ORGANIZER_NAME') . '</th>';
		$html .= '<th class="organizer_pools_ordering">' . Helpers\Languages::_('ORGANIZER_ORDER') . '</th>';
		$html .= '</tr></thead>';
		$html .= '<tbody>';

		$addSpace      = Helpers\Languages::setScript('ORGANIZER_ADD_EMPTY');
		$delete        = Helpers\Languages::setScript('ORGANIZER_DELETE');
		$makeFirst     = Helpers\Languages::setScript('ORGANIZER_MAKE_FIRST');
		$makeLast      = Helpers\Languages::setScript('ORGANIZER_MAKE_LAST');
		$moveChildUp   = Helpers\Languages::setScript('ORGANIZER_MOVE_UP');
		$moveChildDown = Helpers\Languages::setScript('ORGANIZER_MOVE_DOWN');

		$rowClass = 'row0';
		if (!empty($children))
		{
			$maxOrdering = max(array_keys($children));
			for ($ordering = 1; $ordering <= $maxOrdering; $ordering++)
			{
				if (isset($children[$ordering]))
				{
					$childID = $children[$ordering]['id'];
					$name    = $children[$ordering]['name'];
					$link    = Route::_($children[$ordering]['link'], false);
				}
				else
				{
					$link = $name = $childID = '';
				}

				$icon = '';
				if (!empty($children[$ordering]))
				{
					$icon = empty($children[$ordering]['subjectID']) ? 'icon-list' : 'icon-book';
				}

				$html .= '<tr id="childRow' . $ordering . '" class="' . $rowClass . '">';

				$visualDetails = '<td class="child-name">';
				$visualDetails .= '<a id="child' . $ordering . 'Link" href="' . $link . '" target="_blank">';
				$visualDetails .= '<span id="child' . $ordering . 'Icon" class="' . $icon . '"></span>';
				$visualDetails .= '<span id="child' . $ordering . 'Name">' . $name . '</span></a>';
				$visualDetails .= '<input type="hidden" name="child' . $ordering . '" id="child' . $ordering . '" ';
				$visualDetails .= 'value="' . $childID . '" /></td>';

				$orderingToolbar = '<td class="child-order">';

				$first = '<button class="btn btn-small" onclick="setFirst(\'' . $ordering . '\');" ';
				$first .= 'title="' . $makeFirst . '"><span class="icon-first"></span></button>';

				$previous = '<button class="btn btn-small" onclick="moveChildUp(\'' . $ordering . '\');" ';
				$previous .= 'title="' . $moveChildUp . '"><span class="icon-previous"></span></button>';

				$order = '<input type="text" title="Ordering" name="child' . $ordering . 'Order" ';
				$order .= 'id="child' . $ordering . 'Order" size="2" value="' . $ordering . '" ';
				$order .= 'class="text-area-order" onChange="moveChildToIndex(' . $ordering . ');"/>';

				$blank = '<button class="btn btn-small" onclick="addBlankChild(\'' . $ordering . '\');" ';
				$blank .= 'title="' . $addSpace . '"><span class="icon-download"></span></button>';

				$trash = '<button class="btn btn-small" onClick="trash(' . $ordering . ');" ';
				$trash .= 'title="' . $delete . '" ><span class="icon-trash"></span>';
				$trash .= '</button>';

				$next = '<button class="btn btn-small" onclick="moveChildDown(\'' . $ordering . '\');" ';
				$next .= 'title="' . $moveChildDown . '"><span class="icon-next"></span></button>';

				$last = '<button class="btn btn-small" onclick="setLast(\'' . $ordering . '\');" ';
				$last .= 'title="' . $makeLast . '"><span class="icon-last"></span></button>';

				$orderingToolbar .= $first . $previous . $order . $blank . $trash . $next . $last . '</td>';

				$html     .= $visualDetails . $orderingToolbar . '</tr>';
				$rowClass = $rowClass == 'row0' ? 'row1' : 'row0';
			}
		}
		$html .= '</tbody>';
		$html .= '</table>';
		$html .= '<div class="btn-toolbar" id="children-toolbar"></div>';

		return $html;
	}
}
