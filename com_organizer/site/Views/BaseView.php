<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views;

use Exception;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\View\HtmlView;
use Organizer\Helpers;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
abstract class BaseView extends HtmlView
{
	const BACKEND = true, FRONTEND = false;

	public $clientContext;

	/**
	 * Constructor
	 *
	 * @param   array  $config  A named configuration array for object construction.
	 *
	 * @since   3.0
	 */
	public function __construct($config = array())
	{
		parent::__construct($config);
		$this->clientContext = Helpers\OrganizerHelper::getApplication()->isClient('administrator');
	}

	/**
	 * Method to get the object name
	 *
	 * The model name by default parsed using the classname, or it can be set
	 * by passing a $config['name'] in the class constructor
	 *
	 * @return  string  The name of the model
	 */
	public function getName()
	{
		if (empty($this->_name))
		{
			$this->_name = Helpers\OrganizerHelper::getClass($this);
		}

		return $this->_name;
	}

	/**
	 * Sets the layout name to use
	 *
	 * @param   string  $layout  The layout name or a string in format <template>:<layout file>
	 *
	 * @return  string  Previous value.
	 */
	public function setLayout($layout)
	{
		// I have no idea what this does but don't want to break it.
		$joomlaValid = strpos($layout, ':') === false;

		// This was explicitly set
		$nonStandard = $layout !== 'default';
		if ($joomlaValid and $nonStandard)
		{
			$this->_layout = $layout;
		}
		else
		{
			// Default is not an option anymore.
			$replace = $this->_layout === 'default';
			if ($replace)
			{
				$layoutName = strtolower($this->getName());
				$exists     = false;
				foreach ($this->_path['template'] as $path)
				{
					$exists = file_exists("$path$layoutName.php");
					if ($exists)
					{
						break;
					}
				}
				if (!$exists)
				{
					Helpers\OrganizerHelper::error(501);
				}
				$this->_layout = strtolower($this->getName());
			}
		}

		return $this->_layout;
	}

	/**
	 * Method to add a model to the view.
	 *
	 * @param   BaseDatabaseModel  $model    The model to add to the view.
	 * @param   boolean            $default  Is this the default model?
	 *
	 * @return  BaseDatabaseModel  The added model.
	 */
	public function setModel($model, $default = false)
	{
		$name                 = strtolower($this->getName());
		$this->_models[$name] = $model;

		if ($default)
		{
			$this->_defaultModel = $name;
		}

		return $model;
	}
}
