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
use Organizer\Helpers\Languages;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class Participants extends ListView
{
	protected $rowStructure = [
		'checkbox'    => '',
		'fullName'    => 'value',
		'email'       => 'value',
		'programName' => 'value',
		'status'      => 'value',
		'paid'        => 'value',
		'attended'    => 'value'
	];

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Languages::_('ORGANIZER_PARTICIPANTS'), 'users');

		if (Helpers\Can::administrate())
		{
			$toolbar = Toolbar::getInstance();
			$toolbar->appendButton(
				'Standard',
				'edit',
				Languages::_('ORGANIZER_EDIT'),
				'participants.edit',
				true
			);
			/*$toolbar->appendButton(
				'Standard',
				'attachment',
				Languages::_('ORGANIZER_MERGE'),
				'participants.mergeView',
				true
			);*/
		}
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return bool true if the use may access the view, otherwise false
	 */
	protected function allowAccess()
	{
		if ($courseID = Helpers\Input::getFilterID('course'))
		{
			return Helpers\Can::manage('course', $courseID);
		}

		return Helpers\Can::administrate();
	}

	/**
	 * Method to create a list output
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function display($tpl = null)
	{
		// Set batch template path
		$this->batch = ['batch_circular'];

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
			'checkbox'    => Helpers\HTML::_('grid.checkall'),
			'fullName'    => Helpers\HTML::sort('NAME', 'fullName', $direction, $ordering),
			'email'       => Helpers\HTML::sort('EMAIL', 'email', $direction, $ordering),
			'programName' => Helpers\HTML::sort('PROGRAM', 'programName', $direction, $ordering),
		];

		if ($courseID = Helpers\Input::getFilterID('course') and $courseID !== -1)
		{
			$headers['status']   = Helpers\HTML::sort('STATUS', 'status', $direction, $ordering);
			$headers['paid']     = Helpers\HTML::sort('PAID', 'paid', $direction, $ordering);
			$headers['attended'] = Helpers\HTML::sort('ATTENDED', 'attended', $direction, $ordering);
		}

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
		$link            = 'index.php?option=com_organizer&view=participant_edit&id=';
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$item->programName       = Helpers\Programs::getName($item->programID);
			$structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
