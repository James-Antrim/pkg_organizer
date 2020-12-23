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

use Joomla\CMS\MVC\View\HtmlView;
use Organizer\Helpers;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
abstract class BaseView extends HtmlView
{
	public $adminContext;

	public $mobile = false;

	/**
	 * @inheritdoc
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);
		$this->adminContext = Helpers\OrganizerHelper::getApplication()->isClient('administrator');
		$this->mobile       = Helpers\OrganizerHelper::isSmartphone();
	}

	/**
	 * @inheritdoc
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
	 * @inheritdoc
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
