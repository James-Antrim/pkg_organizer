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
	private $documentAccess = false;

	/**
	 * Method to generate buttons for user interaction
	 *
	 * @return void
	 */
	protected function addToolBar()
	{
		Helpers\HTML::setTitle(Helpers\Languages::_('ORGANIZER_PROGRAMS'), 'list');

		if ($this->documentAccess)
		{
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

			$toolbar->appendButton(
				'Standard',
				'eye-open',
				Helpers\Languages::_('ORGANIZER_ACTIVATE'),
				'programs.activate',
				true
			);

			$toolbar->appendButton(
				'Standard',
				'eye-close',
				Helpers\Languages::_('ORGANIZER_DEACTIVATE'),
				'programs.deactivate',
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
		$this->documentAccess = (bool) Helpers\Can::documentTheseOrganizations();

		return $this->clientContext === self::BACKEND ? $this->documentAccess : true;
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

		$headers = [
			'checkbox' => '',
			'name'     => Helpers\HTML::sort('NAME', 'name', $direction, $ordering)
		];

		if ($this->clientContext === self::FRONTEND)
		{
			$headers['links'] = '';
		}
		else
		{
			$headers['active'] = Helpers\Languages::_('ORGANIZER_ACTIVE');
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
		$backend  = $this->clientContext === self::BACKEND;
		$editLink = 'index.php?option=com_organizer&view=program_edit&id=';
		$itemLink = 'index.php?option=com_organizer&view=program_item&id=';
		$links    = '';

		if (!$backend)
		{
			$template = "<a class=\"hasTooltip\" href=\"URL\" target=\"_blank\" title=\"TIP\">ICON</a>";

			$icon  = "<span class=\"icon-grid-2\"></span>";
			$tip   = Helpers\Languages::_('ORGANIZER_CURRICULUM');
			$url   = 'index.php?option=com_organizer&view=curriculum&programID=XXXX';
			$links .= str_replace('URL', $url, str_replace('TIP', $tip, str_replace('ICON', $icon, $template)));

			$icon  = "<span class=\"icon-list\"></span>";
			$tip   = Helpers\Languages::_('ORGANIZER_SUBJECTS');
			$url   = 'index.php?option=com_organizer&view=subjects&programID=XXXX';
			$links .= str_replace('URL', $url, str_replace('TIP', $tip, str_replace('ICON', $icon, $template)));
		}

		$index           = 0;
		$structuredItems = [];
		foreach ($this->items as $program)
		{
			// The backend entries have been prefiltered for access
			if ($backend)
			{
				$checkbox = Helpers\HTML::_('grid.id', $index, $program->id);
				$thisLink = $editLink . $program->id;
			}
			else
			{
				$access   = Helpers\Can::document('program', $program->id);
				$checkbox = $access ? Helpers\HTML::_('grid.id', $index, $program->id) : '';
				$thisLink = $itemLink . $program->id;
			}


			$structuredItems[$index]             = [];
			$structuredItems[$index]['checkbox'] = $checkbox;
			$structuredItems[$index]['name']     = Helpers\HTML::_('link', $thisLink, $program->name);

			if ($backend)
			{
				$tip    = $program->active ? 'ORGANIZER_CLICK_TO_DEACTIVATE' : 'ORGANIZER_CLICK_TO_ACTIVATE';
				$toggle = $this->getToggle('programs', $program->id, $program->active, $tip, 'active');

				$structuredItems[$index]['active'] = $toggle;
			}

			if ($links)
			{
				$structuredItems[$index]['links'] = str_replace('XXXX', $program->id, $links);
			}

			$index++;
		}

		$this->items = $structuredItems;
	}
}
