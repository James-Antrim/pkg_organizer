<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\JSON;

use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
abstract class BaseView extends CMSObject
{
	/**
	 * The base path of the view
	 *
	 * @var    string
	 */
	protected $_basePath = null;

	/**
	 * The base path of the site itself
	 *
	 * @var string
	 */
	private $baseURL;

	/**
	 * The class name
	 *
	 * @var string the name of the class
	 */
	protected $name;

	/**
	 * Constructor
	 *
	 * @param   array  $config  A named configuration array for object construction.
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		// Set the view name
		if (empty($this->name))
		{
			$this->name = Helpers\OrganizerHelper::getClass($this);
		}

		// Set a base path for use by the view
		if (array_key_exists('base_path', $config))
		{
			$this->_basePath = $config['base_path'];
		}
		else
		{
			$this->_basePath = JPATH_COMPONENT;
		}

		$this->baseURL = Uri::base(true);
	}

	/**
	 * Display the view output
	 */
	abstract public function display();
}
