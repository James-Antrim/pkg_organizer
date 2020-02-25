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

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of (scheduled subject) pools into the display context.
 */
class Groups extends ListView
{
	protected $rowStructure = [
		'checkbox' => '',
		'fullName' => 'link',
		'name'     => 'link',
		'grid'     => 'link',
		'code'     => 'link'
	];

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_GROUPS'), 'list-2');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'groups.edit', true);

		if (Helpers\Can::administrate())
		{
			$toolbar->appendButton(
				'Standard',
				'attachment',
				Helpers\Languages::_('ORGANIZER_MERGE'),
				'groups.mergeView',
				true
			);

			$toolbar->appendButton(
				'Standard',
				'eye-open',
				Helpers\Languages::_('ORGANIZER_PUBLISH_EXPIRED_TERMS'),
				'groups.publishPast',
				false
			);
		}

		$if          = "alert('" . Helpers\Languages::_('ORGANIZER_LIST_SELECTION_WARNING') . "');";
		$else        = "jQuery('#modal-publishing').modal('show'); return true;";
		$script      = 'onclick="if(document.adminForm.boxchecked.value==0){' . $if . '}else{' . $else . '}"';
		$batchButton = '<button id="group-publishing" data-toggle="modal" class="btn btn-small" ' . $script . '>';

		$title       = Helpers\Languages::_('ORGANIZER_BATCH');
		$batchButton .= '<span class="icon-stack" title="' . $title . '"></span>' . " $title";

		$batchButton .= '</button>';

		$toolbar->appendButton('Custom', $batchButton, 'batch');
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return bool true if the use may access the view, otherwise false
	 */
	protected function allowAccess()
	{
		return (bool) Helpers\Can::scheduleTheseOrganizations();
	}

	/**
	 * Method to get display
	 *
	 * @param   Object  $tpl  template  (default: null)
	 *
	 * @return void
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		// Set batch template path
		$this->batch = ['batch_group_publishing'];

		parent::display($tpl);
	}

	/**
	 * Modifies document variables and adds links to external files
	 *
	 * @return void
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Factory::getDocument()->addStyleSheet(Uri::root() . 'components/com_organizer/css/group_publishing.css');
		Factory::getDocument()->addStyleSheet(Uri::root() . 'components/com_organizer/css/modal.css');
	}

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	protected function setHeaders()
	{
		$ordering  = $this->state->get('list.ordering');
		$direction = $this->state->get('list.direction');
		$headers   = [
			'checkbox' => Helpers\HTML::_('grid.checkall'),
			'fullName' => Helpers\HTML::sort('FULL_NAME', 'gr.fullName', $direction, $ordering),
			'name'     => Helpers\HTML::sort('SELECT_BOX_DISPLAY', 'gr.name', $direction, $ordering),
			'grid'     => Helpers\Languages::_('ORGANIZER_GRID'),
			'code'     => Helpers\HTML::sort('UNTIS_ID', 'gr.code', $direction, $ordering)
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
		$link            = 'index.php?option=com_organizer&view=group_edit&id=';
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$item->grid              = Helpers\Grids::getName($item->gridID);
			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
