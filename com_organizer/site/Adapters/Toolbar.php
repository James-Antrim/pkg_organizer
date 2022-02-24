<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Adapters;

use Joomla\CMS\Toolbar\Toolbar as ParentClass;
use Joomla\CMS\Toolbar\ToolbarButton;
use Organizer\Helpers\OrganizerHelper;

class Toolbar extends ParentClass
{
	/**
	 * @inheritDoc
	 */
	public function __construct($name = 'toolbar')
	{
		parent::__construct($name);

		$this->_buttonPath[] = JPATH_COMPONENT_SITE . '/Buttons';
	}

	/**
	 * Returns the global Toolbar object, only creating it if it
	 * doesn't already exist.
	 *
	 * @param   string  $name  The name of the toolbar.
	 *
	 * @return  Toolbar  The Toolbar object.
	 *
	 * @since   1.5
	 */
	public static function getInstance($name = 'toolbar'): Toolbar
	{
		if (empty(self::$instances[$name]))
		{
			self::$instances[$name] = new Toolbar($name);
		}

		return self::$instances[$name];
	}

	/**
	 * Load the button class including the deprecated ones.
	 *
	 * @param   string  $type  Button Type
	 *
	 * @return  string|null
	 */
	private function loadButtonClass(string $type): ?string
	{
		$buttonClasses = [
			'Organizer\\Buttons\\' . OrganizerHelper::classDecode($type),
			'Joomla\\CMS\\Toolbar\\Button\\' . ucfirst($type) . 'Button'
		];

		foreach ($buttonClasses as $buttonClass)
		{
			if (!class_exists($buttonClass))
			{
				continue;
			}

			return $buttonClass;
		}

		return null;
	}

	/**
	 * @inheritDoc
	 * @return ToolbarButton|bool
	 */
	public function loadButtonType($type, $new = false)
	{
		$signature = md5($type);

		if ($new === false && isset($this->_buttons[$signature]))
		{
			return $this->_buttons[$signature];
		}

		if (!class_exists('Joomla\\CMS\\Toolbar\\ToolbarButton'))
		{
			\JLog::add(\JText::_('JLIB_HTML_BUTTON_BASE_CLASS'), \JLog::WARNING, 'jerror');

			return false;
		}

		$buttonClass = $this->loadButtonClass($type);

		if (!$buttonClass)
		{
			if (isset($this->_buttonPath))
			{
				$dirs = $this->_buttonPath;
			}
			else
			{
				$dirs = [];
			}

			$file = \JFilterInput::getInstance()->clean(str_replace('_', DIRECTORY_SEPARATOR, strtolower($type)) . '.php', 'path');

			\JLoader::import('joomla.filesystem.path');

			if ($buttonFile = \JPath::find($dirs, $file))
			{
				include_once $buttonFile;
			}
			else
			{
				\JLog::add(\JText::sprintf('JLIB_HTML_BUTTON_NO_LOAD', $buttonClass, $buttonFile), \JLog::WARNING, 'jerror');

				return false;
			}

			$buttonClass = $this->loadButtonClass($type);

			if (!$buttonClass)
			{
				return false;
			}
		}

		$this->_buttons[$signature] = new $buttonClass($this);

		return $this->_buttons[$signature];
	}
}