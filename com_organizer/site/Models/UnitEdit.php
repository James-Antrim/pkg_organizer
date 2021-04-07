<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Joomla\CMS\Factory;
use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Tables;
use stdClass;

/**
 * Class loads a form for editing instance data.
 */
class UnitEdit extends EditModel
{
	/**
	 * Checks access to edit the resource.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		$instanceID = empty($this->item->instanceID) ? 0 : $this->item->instanceID;
		if (!Helpers\Can::manage('instance', $instanceID))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function getForm($data = [], $loadData = true)
	{
		$form = parent::getForm($data, $loadData);
		$item = $this->item;

		$previous = Helpers\Input::getFormItems();
		$session  = Factory::getSession();
		$instance = $session->get('organizer.instance', []);

		// Immutable once set
		if (!empty($item->organizationID))
		{
			$form->removeField('organizationID');
			$organizationID = $item->organizationID;
		}
		else
		{
			if ($organizationID = $previous->get('organizationID'))
			{
				$instance['organizationID'] = $organizationID;
			}
			else
			{
				// The authorize function would have blocked the object from getting this far if there was no value here.
				$organizations  = Helpers\Organizations::getResources('teach');
				$organizationID = reset($organizations)['id'];
			}

			$form->setValue('organizationID', null, $organizationID);
		}

		$item->organizationID = $organizationID;

		$this->setDate($item);
		$this->setGridID($item);
		$this->setBlockID($item);

		$instance['blockID'] = $item->blockID;
		$instance['date']    = $item->date;
		$instance['gridID']  = $item->gridID;

		$form->setValue('blockID', null, $item->blockID);
		$form->setValue('date', null, $item->date);
		$form->setValue('gridID', null, $item->gridID);

		$session->set('organizer.instance', $instance);

		return $form;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   int  $pk  The id of the primary key
	 *
	 * @return mixed Object on success, false on failure
	 */
	public function getItem($pk = 0)
	{
		$item = parent::getItem($pk);

		if ($item->id)
		{
			$block = new Tables\Blocks();
			$block->load($item->blockID);
			$item->date = $block->date;

			$unit = new Tables\Units();
			$unit->load($item->unitID);

			// Null default on grid deletion
			$item->gridID = (int) $unit->gridID;

			$item->organizationID = $unit->organizationID;
			$this->setInstances($item);
			//$instance = ['instanceID' => $item->id, 'instanceStatus' => $item->delta];

			//Helpers\Instances::setPersons($instance, ['delta' => '']);

			//$item->resources = $instance['resources'];
		}
		else
		{
			// if the date has been selected get the user's default unit if existent
			//$item->resources = [];
		}

		return $item;
	}

	/**
	 * Method to get a table object, load it if necessary.
	 *
	 * @param   string  $name     The table name. Optional.
	 * @param   string  $prefix   The class prefix. Optional.
	 * @param   array   $options  Configuration array for model. Optional.
	 *
	 * @return Tables\Instances A Table object
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getTable($name = '', $prefix = '', $options = []): Tables\Instances
	{
		return new Tables\Instances();
	}

	/**
	 * Attempts to determine the block to be used for planning the current instance.
	 *
	 * @param   object  $item
	 *
	 * @return void sets the item's block id
	 */
	private function setBlockID(object $item)
	{
		$block = new Tables\Blocks();

		// Selected > unit > organization default > 0
		if ($blockID = Helpers\Input::getFormItems()->get('blockID') and $block->load($blockID))
		{
			$item->blockID = $blockID;
		}
	}

	/**
	 * Attempts to determine the date to be used for planning the current instance.
	 *
	 * @param   object  $item
	 *
	 * @return void
	 */
	private function setDate(object $item)
	{
		if ($date = Helpers\Input::getFormItems()->get('date') and preg_match('/\d{4}-\d{2}-\d{2}/', $date))
		{
			$item->date = $date;
		}
		elseif (empty($item->date))
		{
			$item->date = date('Y-m-d');
		}
	}

	/**
	 * Attempts to determine the grid to be used for planning the current instance.
	 *
	 * @param   object  $item
	 *
	 * @return void sets the item's grid id
	 */
	private function setGridID(object $item)
	{
		$default = Helpers\Organizations::getDefaultGrid($item->organizationID);
		$grid    = new Tables\Grids();

		// Selected > unit > organization default > 0
		if ($gridID = Helpers\Input::getFormItems()->get('gridID') and $grid->load($gridID))
		{
			$item->gridID = $gridID;
		}
		elseif (empty($item->gridID))
		{
			$item->gridID = $default;
		}
	}

	private function setInstances(object $item)
	{
		$query = Database::getQuery();
		$query->select('id, eventID')
			->from('#__organizer_instances')
			->where("blockID = $item->blockID")
			->where("unitID = $item->unitID");
		Database::setQuery($query);
		$instances = Database::loadAssocList();
		echo "<pre>" . print_r($instances, true) . "</pre><br>";
	}
}