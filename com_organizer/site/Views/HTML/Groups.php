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

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;
use Organizer\Tables;

/**
 * Class loads persistent information a filtered set of (scheduled subject) pools into the display context.
 */
class Groups extends ListView
{
	protected $rowStructure = [
		'checkbox' => '',
		'fullName' => 'link',
		'this'     => 'value',
		'next'     => 'value',
		'name'     => 'link',
		'active'   => 'value',
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

		$if          = "alert('" . Helpers\Languages::_('ORGANIZER_LIST_SELECTION_WARNING') . "');";
		$else        = "jQuery('#modal-publishing').modal('show'); return true;";
		$script      = 'onclick="if(document.adminForm.boxchecked.value==0){' . $if . '}else{' . $else . '}"';
		$batchButton = '<button id="group-publishing" data-toggle="modal" class="btn btn-small" ' . $script . '>';

		$title       = Helpers\Languages::_('ORGANIZER_BATCH');
		$batchButton .= '<span class="icon-stack" title="' . $title . '"></span>' . " $title";

		$batchButton .= '</button>';

		$toolbar->appendButton('Custom', $batchButton, 'batch');
		$toolbar->appendButton(
			'Standard',
			'eye-open',
			Helpers\Languages::_('ORGANIZER_ACTIVATE'),
			'groups.activate',
			false
		);
		$toolbar->appendButton(
			'Standard',
			'eye-close',
			Helpers\Languages::_('ORGANIZER_DEACTIVATE'),
			'groups.deactivate',
			false
		);

		if (Helpers\Can::administrate())
		{
			/*$toolbar->appendButton(
				'Standard',
				'attachment',
				Helpers\Languages::_('ORGANIZER_MERGE'),
				'groups.mergeView',
				true
			);*/

			$toolbar->appendButton(
				'Standard',
				'eye-open',
				Helpers\Languages::_('ORGANIZER_PUBLISH_EXPIRED_TERMS'),
				'groups.publishPast',
				false
			);
		}
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!Helpers\Can::scheduleTheseOrganizations())
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Method to get display
	 *
	 * @param   Object  $tpl  template  (default: null)
	 *
	 * @return void
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
			'this'     => Helpers\Terms::getName(Helpers\Terms::getCurrentID()),
			'next'     => Helpers\Terms::getName(Helpers\Terms::getNextID()),
			'name'     => Helpers\HTML::sort('SELECT_BOX_DISPLAY', 'gr.name', $direction, $ordering),
			'active'   => Helpers\Languages::_('ORGANIZER_ACTIVE'),
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
		$currentTerm     = Helpers\Terms::getCurrentID();
		$index           = 0;
		$link            = 'index.php?option=com_organizer&view=group_edit&id=';
		$nextTerm        = Helpers\Terms::getNextID();
		$publishing      = new Tables\GroupPublishing();
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$tip          = $item->active ? 'ORGANIZER_CLICK_TO_DEACTIVATE' : 'ORGANIZER_CLICK_TO_ACTIVATE';
			$item->active = $this->getToggle('groups', $item->id, $item->active, $tip, 'active');

			$termData   = ['groupID' => $item->id, 'termID' => $currentTerm];
			$item->grid = Helpers\Grids::getName($item->gridID);

			$thisValue  = $publishing->load($termData) ? $publishing->published : 1;
			$tip        = $thisValue ? 'ORGANIZER_CLICK_TO_UNPUBLISH' : 'ORGANIZER_CLICK_TO_PUBLISH';
			$item->this = $this->getToggle('groups', $item->id, $thisValue, $tip, $currentTerm);

			$termData['termID'] = $nextTerm;
			$nextValue          = $publishing->load($termData) ? $publishing->published : 1;
			$tip                = $nextValue ? 'ORGANIZER_CLICK_TO_UNPUBLISH' : 'ORGANIZER_CLICK_TO_PUBLISH';
			$item->next         = $this->getToggle('groups', $item->id, $nextValue, $tip, $nextTerm);

			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
