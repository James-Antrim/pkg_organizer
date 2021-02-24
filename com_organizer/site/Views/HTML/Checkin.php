<?php
/**
 * @package     Organizer\Views\HTML
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use Organizer\Adapters\Document;
use Organizer\Helpers;
use Organizer\Tables\Participants as Table;

class Checkin extends FormView
{
	public $complete = true;

	public $edit = false;

	/**
	 * @var array
	 */
	public $instances;

	/**
	 * @var Table
	 */
	public $participant;

	/**
	 * @var int|null
	 */
	public $roomID;

	/**
	 * @inheritDoc
	 */
	protected function addToolBar()
	{
		if ($this->edit or !$this->complete)
		{
			$title = Helpers\Languages::_('ORGANIZER_CONTACT_INFORMATION');
		}
		elseif ($this->instances)
		{
			if (count($this->instances) > 1)
			{
				$title = Helpers\Languages::_('ORGANIZER_CONFIRM_EVENT');
			}
			elseif (!$this->roomID)
			{
				$title = Helpers\Languages::_('ORGANIZER_CONFIRM_SEATING');
			}
			else
			{
				$title = Helpers\Languages::_('ORGANIZER_CHECKED_IN');
			}
		}
		else
		{
			$title = Helpers\Languages::_('ORGANIZER_CHECKIN');
		}

		Helpers\HTML::setTitle($title);
	}

	/**
	 * @inheritDoc
	 */
	public function display($tpl = null)
	{
		$this->edit        = Helpers\Input::getCMD('layout') === 'profile';
		$this->instances   = $this->get('Instances');
		$this->participant = $this->get('Participant');
		$this->roomID      = $this->get('RoomID');
		$this->_layout     = 'checkin-wrapper';

		$this->complete = true;
		if ($this->participant->id)
		{
			$requiredColumns = ['address', 'city', 'forename', 'surname', 'telephone', 'zipCode'];
			foreach ($requiredColumns as $column)
			{
				$this->complete = ($this->complete and !empty($this->participant->$column));
			}
		}

		parent::display($tpl);
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Document::addScript(Uri::root() . 'components/com_organizer/js/checkin.js');
		Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/checkin.css');
	}
}