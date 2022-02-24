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

use Organizer\Adapters\Toolbar;
use Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of runs into the display context.
 */
class Runs extends ListView
{
	protected $rowStructure = [
		'checkbox'  => '',
		'name'      => 'link',
		'term'      => 'link',
		'startDate' => 'link',
		'endDate'   => 'link',
		'sections'  => 'value'
	];

	/**
	 * Checks user authorization and initiates redirects accordingly.
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
	 * @inheritDoc
	 */
	protected function addToolBar(bool $delete = true)
	{
		$this->setTitle('ORGANIZER_RUNS');
		$toolbar = Toolbar::getInstance();
		$toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), "runs.add", false);

		if (Helpers\Can::administrate() and count($this->items))
		{
			$toolbar->appendButton('Standard', 'edit', Helpers\Languages::_('ORGANIZER_EDIT'), "runs.edit", true);
			$toolbar->appendButton(
				'Confirm',
				Helpers\Languages::_('ORGANIZER_DELETE_CONFIRM'),
				'delete',
				Helpers\Languages::_('ORGANIZER_DELETE'),
				"runs.delete",
				true
			);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function setHeaders()
	{
		$headers = [
			'checkbox'  => '',
			'name'      => Helpers\Languages::_('ORGANIZER_NAME'),
			'term'      => Helpers\Languages::_('ORGANIZER_TERM'),
			'startDate' => Helpers\Languages::_('ORGANIZER_START_DATE'),
			'endDate'   => Helpers\Languages::_('ORGANIZER_END_DATE'),
			'sections'  => Helpers\Languages::_('ORGANIZER_SECTIONS')
		];

		$this->headers = $headers;
	}

	/**
	 * @inheritDoc
	 */
	protected function structureItems()
	{
		$link            = "index.php?option=com_organizer&view=run_edit&id=";
		$index           = 0;
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$thisLink = "$link$item->id";
			$run      = json_decode($item->run, true);

			if (empty($run) or empty($run['runs']))
			{
				$item->endDate   = '';
				$item->sections  = '';
				$item->startDate = '';
			}
			else
			{
				$runs      = $run['runs'];
				$sections  = [];
				$startDate = '';

				foreach ($runs as $run)
				{
					$startDate = (!$startDate or $startDate > $run['startDate']) ? $run['startDate'] : $startDate;
				}

				ksort($sections);

				$item->endDate   = Helpers\Dates::formatDate($item->endDate);
				$item->sections  = count($runs);
				$item->startDate = Helpers\Dates::formatDate($startDate);
			}

			$structuredItems[$index] = $this->structureItem($index, $item, $thisLink);
			$index++;
		}

		$this->items = $structuredItems;
	}
}
