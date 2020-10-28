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
use Organizer\Helpers\Languages;
use Organizer\Tables;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class InstanceParticipants extends Participants
{
	private $manages = false;

	protected $rowStructure = [
		'checkbox' => '',
		'fullName' => 'value',
		'email'    => 'value',
		'program'  => 'value',
		'attended' => 'value'
	];

	private $teaches = false;

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		$instanceID = Helpers\Input::getID();
		$instance   = new Tables\Instances();
		$instance->load($instanceID);
		$title = Languages::_('ORGANIZER_PARTICIPANTS');

		Helpers\HTML::setTitle($title, 'users');

		$toolbar = Toolbar::getInstance();

		$script      = "onclick=\"jQuery('#modal-mail').modal('show'); return true;\"";
		$batchButton = "<button id=\"participant-mail\" data-toggle=\"modal\" class=\"btn btn-small\" $script>";

		$title       = Languages::_('ORGANIZER_NOTIFY');
		$batchButton .= '<span class="icon-envelope" title="' . $title . '"></span>' . " $title";

		$batchButton .= '</button>';

		$toolbar->appendButton('Custom', $batchButton, 'batch');
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!$instanceID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		$this->manages = Helpers\Can::manageTheseOrganizations();
		$this->teaches = Helpers\Instances::teaches($instanceID);

		if (!$this->manages and !$this->teaches)
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Method to create a list output
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		// Set batch template path
		$this->batch = ['batch_participant_notify'];

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
			'checkbox' => Helpers\HTML::_('grid.checkall'),
			'fullName' => Helpers\HTML::sort('NAME', 'fullName', $direction, $ordering),
			'email'    => Helpers\HTML::sort('EMAIL', 'email', $direction, $ordering),
			'program'  => Helpers\HTML::sort('PROGRAM', 'program', $direction, $ordering)
		];

		$this->headers = $headers;
	}

	/**
	 * Creates a subtitle element from the term name and the start and end dates of the course.
	 *
	 * @return void modifies the course
	 */
	protected function setSubtitle()
	{
		$instanceID = Helpers\Input::getID();

		$subTitle   = [];
		$subTitle[] = Helpers\Instances::getName($instanceID);

		$subTitle[] = Helpers\Instances::getDateDisplay($instanceID);

		$this->subtitle = '<h6 class="sub-title">' . implode('<br>', $subTitle) . '</h6>';
	}
}
