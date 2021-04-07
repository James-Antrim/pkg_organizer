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

use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\AdminModel;
use Organizer\Helpers;

/**
 * Class loads item form data to edit an entry.
 */
abstract class EditModel extends AdminModel
{
	use Named;

	protected $association;

	public $item = null;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->setContext();
	}

	/**
	 * Checks access to edit the resource.
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
	public function getForm($data = [], $loadData = true)
	{
		$name = $this->get('name');
		$form = $this->loadForm($this->context, $name, ['control' => 'jform', 'load_data' => $loadData]);

		return empty($form) ? false : $form;
	}

	/**
	 * Method to get a single record.
	 *
	 * @param   int  $pk  The id of the primary key.
	 *
	 * @return mixed    Object on success, false on failure.
	 */
	public function getItem($pk = 0)
	{
		$pk = empty($pk) ? Helpers\Input::getSelectedID() : $pk;

		// Prevents duplicate execution from getForm and getItem
		if (isset($this->item->id) and ($this->item->id === $pk or $pk === null))
		{
			return $this->item;
		}

		$this->item = parent::getItem($pk);

		$this->authorize();

		return $this->item;
	}

	/**
	 * @inheritDoc
	 */
	protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = '')
	{
		Form::addFormPath(JPATH_COMPONENT_SITE . '/Forms');
		Form::addFieldPath(JPATH_COMPONENT_SITE . '/Fields');

		return parent::loadForm($name, $source, $options, $clear, $xpath);
	}

	/**
	 * @inheritDoc
	 */
	protected function loadFormData(): ?object
	{
		$resourceIDs = Helpers\Input::getSelectedIDs();
		$resourceID  = empty($resourceIDs) ? 0 : $resourceIDs[0];

		return $this->item ? $this->item : $this->getItem($resourceID);
	}
}
