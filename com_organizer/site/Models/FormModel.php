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
use Joomla\CMS\MVC\Model\FormModel as ParentModel;
use Organizer\Helpers;

/**
 * Class loads non-item-specific form data.
 */
class FormModel extends ParentModel
{
	use Named;

	protected $association;

	public $mobile = false;

	/**
	 * Constructor.
	 *
	 * @param   array  $config  An optional associative array of configuration settings.
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$this->mobile = Helpers\OrganizerHelper::isSmartphone();
		$this->setContext();
	}

	/**
	 * Provides a strict access check which can be overwritten by extending classes.
	 *
	 * @return bool  true if the user can access the view, otherwise false
	 */
	protected function authorize()
	{
		if (!Helpers\Can::administrate())
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Method to get the form
	 *
	 * @param   array  $data      Data         (default: array)
	 * @param   bool   $loadData  Load data  (default: true)
	 *
	 * @return mixed Form object on success, False on error.
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function getForm($data = [], $loadData = false)
	{
		$this->authorize();

		$name = $this->get('name');
		$form = $this->loadForm($this->context, $name, ['control' => 'jform', 'load_data' => $loadData]);

		if (empty($form))
		{
			return false;
		}

		return $form;
	}

	/**
	 * Method to get a form object.
	 *
	 * @param   string  $name     The name of the form.
	 * @param   string  $source   The form source. Can be XML string if file flag is set to false.
	 * @param   array   $options  Optional array of options for the form creation.
	 * @param   bool    $clear    Optional argument to force load a new form.
	 * @param   string  $xpath    An optional xpath to search for the fields.
	 *
	 * @return  Form|bool  Form object on success, false on error.
	 */
	protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = '')
	{
		Form::addFormPath(JPATH_COMPONENT_SITE . '/Forms');
		Form::addFieldPath(JPATH_COMPONENT_SITE . '/Fields');

		return parent::loadForm($name, $source, $options, $clear, $xpath);
	}
}
