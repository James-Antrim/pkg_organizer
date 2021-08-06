<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Toolbar\Button\StandardButton;
use Organizer\Adapters\Toolbar;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class loads information about a given instance.
 */
class InstanceItem extends ListView
{
	use ListsInstances;

	/**
	 * @var object the data for the instance
	 */
	public $instance;

	protected $layout = 'instance-item';

	private $manages = false;

	private $teaches = false;

	private $userID;

	protected $rowStructure = [
		'checkbox' => '',
		'date'     => 'value',
		'time'     => 'value',
		'persons'  => 'value',
		'rooms'    => 'value'
	];

	/**
	 * @inheritdoc
	 */
	protected function addToolBar()
	{
		$instance = $this->instance;
		$method   = $instance->method ? " - $instance->method" : '';
		Helpers\HTML::setTitle($instance->name . $method, 'square');
		$this->setSubtitle();

		$toolbar = Toolbar::getInstance();

		if ($this->userID and $this->items)
		{
			$day           = Languages::_(strtoupper(date('D', strtotime($instance->date)))) . '.';
			$deRegThis     = false;
			$deRegBlock    = false;
			$deRegSelected = false;
			$deRegAll      = false;
			$regThis       = false;
			$regBlock      = false;
			$regSelected   = false;
			$regAll        = false;
			$thisDOW       = strtoupper(date('l', strtotime($instance->date)));

			foreach ($this->getModel()->getItems() as $item)
			{
				if ($item->participates)
				{
					$deRegAll      = true;
					$deRegSelected = true;
				}
				else
				{
					$regAll      = true;
					$regSelected = true;
				}

				$sameDOW = (strtoupper(date('l', strtotime($item->date))) === $thisDOW);
				$sameET  = $item->startTime === $instance->startTime;
				$sameST  = $item->startTime === $instance->startTime;

				$sameBlock = ($sameDOW and $sameET and $sameST);
				if ($sameBlock)
				{
					$identity = $item->instanceID === $instance->instanceID;

					if ($item->participates)
					{
						$deRegBlock = true;

						if ($identity)
						{
							$deRegThis = true;
						}
					}
					else
					{
						$regBlock = true;

						if ($identity)
						{
							$regThis = true;
						}
					}
				}
			}

			$registrations = [];

			if ($regThis)
			{
				$regThis         = new StandardButton();
				$registrations[] = $regThis->fetchButton(
					'Standard',
					'square',
					Languages::_('ORGANIZER_THIS_INSTANCE'),
					'InstanceParticipants.register',
					false
				);
			}

			if ($regBlock)
			{
				$regBlock        = new StandardButton();
				$registrations[] = $regBlock->fetchButton(
					'Standard',
					'menu',
					sprintf(Languages::_('ORGANIZER_BLOCK_INSTANCES'), $day, $instance->startTime, $instance->endTime),
					'InstanceParticipants.registerBlock',
					false
				);
			}

			if ($regSelected)
			{
				$regSelected     = new StandardButton();
				$registrations[] = $regSelected->fetchButton(
					'Standard',
					'checkbox',
					Languages::_('ORGANIZER_SELECTED_INSTANCES'),
					'InstanceParticipants.registerSelected',
					true
				);
			}

			if ($regAll)
			{
				$regAll          = new StandardButton();
				$registrations[] = $regAll->fetchButton(
					'Standard',
					'grid-2',
					Languages::_('ORGANIZER_ALL_INSTANCES'),
					'InstanceParticipants.registerAll',
					false
				);
			}

			if ($registrations)
			{
				$toolbar->appendButton('Buttons', 'buttons', Languages::_('ORGANIZER_REGISTER'), $registrations, 'enter');
			}

			$deregistrations = [];

			if ($deRegThis)
			{
				$deRegThis         = new StandardButton();
				$deregistrations[] = $deRegThis->fetchButton(
					'Standard',
					'square',
					Languages::_('ORGANIZER_THIS_INSTANCE'),
					'InstanceParticipants.deregister',
					false
				);
			}

			if ($deRegBlock)
			{
				$deRegBlock        = new StandardButton();
				$deregistrations[] = $deRegBlock->fetchButton(
					'Standard',
					'menu',
					sprintf(Languages::_('ORGANIZER_BLOCK_INSTANCES'), $day, $instance->startTime, $instance->endTime),
					'InstanceParticipants.deregisterBlock',
					false
				);
			}

			if ($deRegSelected)
			{
				$deRegSelected     = new StandardButton();
				$deregistrations[] = $deRegSelected->fetchButton(
					'Standard',
					'checkbox',
					Languages::_('ORGANIZER_SELECTED_INSTANCES'),
					'InstanceParticipants.deregisterSelected',
					true
				);
			}

			if ($deRegAll)
			{
				$deRegAll          = new StandardButton();
				$deregistrations[] = $deRegAll->fetchButton(
					'Standard',
					'grid-2',
					Languages::_('ORGANIZER_ALL_INSTANCES'),
					'InstanceParticipants.deregisterAll',
					false
				);
			}

			if ($deregistrations)
			{
				$toolbar->appendButton('Buttons', 'buttons', Languages::_('ORGANIZER_DEREGISTER'), $deregistrations, 'exit');
			}
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function authorize()
	{
		if (!$instanceID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		$iOrganizationIDs = Helpers\Instances::getOrganizationIDs($instanceID);
		$mOrganizationIDs = Helpers\Can::manageTheseOrganizations();
		$this->manages    = (bool) array_intersect($iOrganizationIDs, $mOrganizationIDs);
		$this->teaches    = Helpers\Instances::teaches();
		$this->userID     = Helpers\Users::getID();
	}

	/**
	 * @inheritDoc
	 */
	public function display($tpl = null)
	{
		$this->instance = $this->getModel()->instance;
		parent::display($tpl);
	}

	/**
	 * @inheritdoc
	 */
	public function setHeaders()
	{
		$this->headers = [
			'checkbox' => Helpers\HTML::_('grid.checkall'),
			'status'   => '',
			'times'    => Languages::_('ORGANIZER_DATETIME'),
			'persons'  => Languages::_('ORGANIZER_PERSONS'),
			'groups'   => Languages::_('ORGANIZER_GROUPS'),
			'rooms'    => Languages::_('ORGANIZER_ROOMS')
		];

		if (!$this->userID)
		{
			unset($this->headers['checkbox']);
		}
	}

	/**
	 * @inheritdoc
	 */
	protected function setSubtitle()
	{
		$instance       = $this->instance;
		$date           = Helpers\Dates::formatDate($instance->date);
		$this->subtitle = "<h4>$date $instance->startTime - $instance->endTime</h4>";
	}

	/**
	 * @inheritdoc
	 */
	protected function structureItems()
	{
		$index           = 0;
		$structuredItems = [];

		foreach ($this->items as $item)
		{

			$times = '<span class="date">' . Helpers\Dates::formatDate($item->date) . '</span><br>';
			$times .= '<span class="times">' . $item->startTime . ' - ' . $item->endTime . '</span>';

			$structuredItems[$index] = [];

			if ($this->userID)
			{
				$structuredItems[$index]['checkbox'] = Helpers\HTML::_('grid.id', $index, $item->instanceID);
			}

			$structuredItems[$index]['status']  = $this->getStatus($item);
			$structuredItems[$index]['times']   = $times;
			$structuredItems[$index]['persons'] = $this->getPersons($item);
			$structuredItems[$index]['groups']  = $this->getResource($item, 'group', 'fullName');
			$structuredItems[$index]['rooms']   = $this->getResource($item, 'room', 'room');

			$index++;
		}

		$this->items = $structuredItems;
	}
}
