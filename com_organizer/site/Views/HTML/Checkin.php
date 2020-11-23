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

use Joomla\CMS\Factory;
use Joomla\CMS\Toolbar\Toolbar;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;

class Checkin extends FormView
{
	public $complete = true;

	public $instances = [];

	/**
	 * @var \Organizer\Tables\Participants
	 */
	public $participant;

	/**
	 * @inheritDoc
	 */
	protected function addToolBar()
	{
		if ($this->instances)
		{
			if ($this->complete)
			{
				$title = count($this->instances) > 1 ?
					Helpers\Languages::_('ORGANIZER_CONFIRM_ATTENDANCE') : Helpers\Languages::_('ORGANIZER_CHECKIN');
			}
			else
			{
				$title = Helpers\Languages::_('ORGANIZER_CONTACT_INFORMATION');
			}
		}
		else
		{
			$title = Helpers\Languages::_('ORGANIZER_CHECKIN');
		}

		Helpers\HTML::setTitle($title);

		if (Helpers\Input::getCMD('tmpl') !== 'component' and !count($this->instances))
		{
			$toolbar = Toolbar::getInstance();
			$toolbar->appendButton(
				'Standard',
				'enter',
				Helpers\Languages::_('ORGANIZER_CHECKIN'),
				'checkin.checkin',
				false
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function display($tpl = null)
	{
		$this->instances   = $this->get('Instances');
		$this->participant = $this->get('Participant');
		$this->_layout     = 'checkin-wrapper';

		$this->complete = true;
		if ($this->participant->id)
		{
			$requiredColumns = ['address', 'city', 'forename', 'surname', 'zipCode'];
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

		Factory::getDocument()->addStyleSheet(Uri::root() . 'components/com_organizer/css/checkin.css');
	}
}