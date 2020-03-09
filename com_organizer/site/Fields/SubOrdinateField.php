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
class SubOrdinateField extends FormField
{
	use Translated;

	/**
	 * Type
	 *
	 * @var    String
	 */
	protected $type = 'SubOrdinate';

	/**
	 * Generates a text for the management of child elements
	 *
	 * @return string  the HTML for the input
	 */
	public function getInput()
	{
		$document = Factory::getDocument();
		$document->addStyleSheet(Uri::root() . 'components/com_organizer/css/children.css');
		$document->addScript(Uri::root() . 'components/com_organizer/js/children.js');

		$input = '<table id="subOrdinates" class="table-striped">';
		$input .= '<thead><tr>';
		$input .= '<th>' . Helpers\Languages::_('ORGANIZER_NAME') . '</th>';
		$input .= '<th>' . Helpers\Languages::_('ORGANIZER_ORDER') . '</th>';
		$input .= '</tr></thead>';
		$input .= '<tbody>';

		$input .= implode($this->getRows());

		$input .= '</tbody>';
		$input .= '</table>';
		$input .= '<div class="btn-toolbar" id="children-toolbar"></div>';

		return $input;
	}

	/**
	 * Retrieves resources subordinate to the resource being edited
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

		return Helpers\OrganizerHelper::executeQuery('loadAssocList', [], 'ordering');
	}

	/**
	 * Generates the HTML Output for the children field
	 *
	 * @return array the HTML strings for the subordinate resources
	 */
	private function getRows()
	{
		$rows = [];

		if (!$subOrdinates = $this->getSubordinateItems())
		{
			return $rows;
		}

		$maxOrdering     = max(array_keys($subOrdinates));
		$poolEditLink    = 'index.php?option=com_organizer&view=pool_edit&id=';
		$rowTemplate     = $this->getRowTemplate();
		$subjectEditLink = 'index.php?option=com_organizer&view=subject_edit&id=';

		for ($ordering = 1; $ordering <= $maxOrdering; $ordering++)
		{
			if (empty($subOrdinates[$ordering]))
			{
				$icon = $link = $name = $subID = '';
			}
			elseif (empty($subOrdinates[$ordering]['subjectID']))
			{
				$poolID = $subOrdinates[$ordering]['poolID'];
				$icon   = 'icon-list';
				$link   = Route::_($poolEditLink . $poolID, false);
				$name   = Helpers\Pools::getFullName($poolID);
				$subID  = $poolID . 'p';
			}
			else
			{
				$subjectID = $subOrdinates[$ordering]['subjectID'];
				$icon      = 'icon-book';
				$link      = Route::_($subjectEditLink . $subjectID, false);
				$name      = Helpers\Subjects::getName($subjectID, true);
				$subID     = $subjectID . 's';
			}

			$row = str_replace('XICONX', $icon, $rowTemplate);
			$row = str_replace('XLINKX', $link, $row);
			$row = str_replace('XNAMEX', $name, $row);
			$row = str_replace('XORDERINGX', $ordering, $row);
			$row = str_replace('XSUBIDX', $subID, $row);

			$rows[] = $row;
		}

		return $rows;
	}

	/**
	 * Generates a template to be used in the creation of rows.
	 *
	 * @return string the template to be used for row generation
	 */
	private function getRowTemplate()
	{
		$buttonTemplate = "<button onclick=\"XFUNCTIONX('XORDERINGX');\" title=\"XTEXTX\">XICONX</button>";

		$rowTemplate = '<tr id="childRowXORDERINGX">';

		$rowTemplate .= '<td class="child-name">';

		$rowTemplate .= '<a id="childXORDERINGXLink" href="XLINKX" target="_blank">';
		$rowTemplate .= '<span id="childXORDERINGXIcon" class="XICONX"></span>';
		$rowTemplate .= '<span id="childXORDERINGXName">XNAMEX</span>';
		$rowTemplate .= '</a>';

		$rowTemplate .= '<input type="hidden" name="childXORDERINGX" id="childXORDERINGX" value="XSUBIDX" />';

		$rowTemplate .= '</td>';
		$rowTemplate .= '<td class="child-order">';

		$firstTemplate = str_replace('XFUNCTIONX', 'setFirst', $buttonTemplate);
		$firstTemplate = str_replace('XICONX', '<span class="icon-first"></span>', $firstTemplate);
		$rowTemplate   .= str_replace('XTEXTX', Helpers\Languages::setScript('ORGANIZER_MAKE_FIRST'), $firstTemplate);

		$upTemplate  = str_replace('XFUNCTIONX', 'moveUp', $buttonTemplate);
		$upTemplate  = str_replace('XICONX', '<span class="icon-previous"></span>', $upTemplate);
		$rowTemplate .= str_replace('XTEXTX', Helpers\Languages::setScript('ORGANIZER_MOVE_UP'), $upTemplate);

		$orderTemplate = '<input type="text" title="Ordering" name="childXORDERINGXOrder" id="childXORDERINGXOrder" ';
		$orderTemplate .= 'value="XORDERINGX" class="text-area-order" onChange="moveChildToIndex(XORDERINGX);"/>';
		$rowTemplate   .= $orderTemplate;

		$blankTemplate = str_replace('XFUNCTIONX', 'insertBlank', $buttonTemplate);
		$blankTemplate = str_replace('XICONX', '<span class="icon-download"></span>', $blankTemplate);
		$rowTemplate   .= str_replace('XTEXTX', Helpers\Languages::setScript('ORGANIZER_ADD_EMPTY'), $blankTemplate);

		$trashTemplate = str_replace('XFUNCTIONX', 'trash', $buttonTemplate);
		$trashTemplate = str_replace('XICONX', '<span class="icon-trash"></span>', $trashTemplate);
		$rowTemplate   .= str_replace('XTEXTX', Helpers\Languages::setScript('ORGANIZER_DELETE'), $trashTemplate);

		$downTemplate = str_replace('XFUNCTIONX', 'moveDown', $buttonTemplate);
		$downTemplate = str_replace('XICONX', '<span class="icon-next"></span>', $downTemplate);
		$rowTemplate  .= str_replace('XTEXTX', Helpers\Languages::setScript('ORGANIZER_MOVE_DOWN'), $downTemplate);

		$downTemplate = str_replace('XFUNCTIONX', 'setLast', $buttonTemplate);
		$downTemplate = str_replace('XICONX', '<span class="icon-last"></span>', $downTemplate);
		$rowTemplate  .= str_replace('XTEXTX', Helpers\Languages::setScript('ORGANIZER_MAKE_LAST'), $downTemplate);

		$rowTemplate .= '</td>';
		$rowTemplate .= '</tr>';

		return $rowTemplate;
	}
}
