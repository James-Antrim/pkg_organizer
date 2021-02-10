<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\XLS;

use Joomla\Registry\Registry;
use Organizer\Helpers;

abstract class ListView extends BaseView
{
	/**
	 * The header information to display indexed by the referenced attribute.
	 * @var array
	 */
	public $headers = [];

	public $items = null;

	protected $rowStructure = [];

	/**
	 * @var Registry
	 */
	public $state = null;

	/**
	 * Checks user authorization and initiates redirects accordingly.
	 *
	 * @return void
	 */
	protected function authorize()
	{
		if (!Helpers\Can::administrate())
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * @inheritDoc
	 */
	public function display()
	{
		$this->authorize();
		$this->state = $this->model->getState();
		$this->setHeaders();
		$this->items = $this->model->getItems();

		if ($this->items)
		{
			$this->structureItems();
		}

		parent::display();
	}

	/**
	 * Function to set the object's headers property
	 *
	 * @return void sets the object headers property
	 */
	abstract protected function setHeaders();

	/**
	 * Processes an individual list item resolving it to an array of table data values.
	 *
	 * @param   object      $item   the item to be displayed in a table row
	 *
	 * @return array an array of property columns with their values
	 */
	protected function structureItem(object $item) : array
	{
		$processedItem = [];

		foreach ($this->rowStructure as $property => $propertyType)
		{
			if (!property_exists($item, $property))
			{
				continue;
			}

			// Individual code will be added to index later
			if ($propertyType === '')
			{
				$processedItem[$property] = $propertyType;
				continue;
			}

			if ($propertyType === 'list' and is_array($item->$property))
			{
				$processedItem[$property] = implode("\n", $item->$property);
				continue;
			}

			if ($propertyType === 'value')
			{
				$processedItem[$property] = $item->$property;
				continue;
			}
		}

		return $processedItem;
	}

	/**
	 * Processes the items in a manner specific to the view, so that a generalized  output in the layout can occur.
	 *
	 * @return void processes the class items property
	 */
	protected function structureItems()
	{
		$index           = 0;
		$structuredItems = [];

		foreach ($this->items as $item)
		{
			$structuredItems[$index] = $this->structureItem($item);
			$index++;
		}

		$this->items = $structuredItems;
	}
}