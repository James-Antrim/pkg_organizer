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

/**
 * Class loads persistent information a filtered set of degree programs into the display context.
 */
class Programs extends ListView
{
	protected $rowStructure = [
		'checkbox'     => '',
		'programName'  => 'link',
		'degree'       => 'link',
		'accredited'   => 'link',
		'organization' => 'link'
	];

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_PROGRAMS'), 'list');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), 'programs.add', false);
		$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), 'programs.edit', true);

		$toolbar->appendButton(
			'Standard',
			'upload',
			Helpers\Languages::_('ORGANIZER_IMPORT_LSF'),
			'programs.import',
			true
		);

		$toolbar->appendButton(
			'Standard',
			'loop',
			Helpers\Languages::_('ORGANIZER_UPDATE_SUBJECTS'),
			'programs.update',
			true
		);

		if (Helpers\Can::administrate())
		{
			$toolbar->appendButton(
				'Confirm',
				Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
				'delete',
				Helpers\Languages::_('ORGANIZER_DELETE'),
				'programs.delete',
				true
			);
		}
	}

	/**
	 * Function determines whether the user may access the view.
	 *
	 * @return bool true if the use may access the view, otherwise false
	 */
	protected function allowAccess()
	{
		return (bool) Helpers\Can::documentTheseOrganizations();
	}

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
			'checkbox'     => '',
			'programName'  => Helpers\HTML::sort('NAME', 'programName', $direction, $ordering),
			'degree'       => Helpers\HTML::sort('DEGREE', 'degree', $direction, $ordering),
			'accredited'   => Helpers\HTML::sort('ACCREDITED', 'accredited', $direction, $ordering),
			'organization' => Helpers\HTML::sort('ORGANIZATION', 'organization', $direction, $ordering)
		];

		$this->headers = $headers;
	}
}
