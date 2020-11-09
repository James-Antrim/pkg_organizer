<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\XLS;

require_once JPATH_ROOT . '/libraries/phpexcel/library/PHPExcel.php';

use Exception;
use Joomla\CMS\Application\ApplicationHelper;
use Organizer\Helpers;
use Organizer\Layouts\XLS\BaseLayout;
use Organizer\Models\BaseModel;
use PHPExcel;
use PHPExcel_IOFactory;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
abstract class BaseView extends PHPExcel
{
	/**
	 * @var BaseLayout
	 */
	protected $layout;

	/**
	 * @var BaseModel
	 */
	public $model;

	protected $name;

	/**
	 * BaseView constructor.
	 */
	public function __construct()
	{
		parent::__construct();

		$name = $this->getName();

		$layout       = Helpers\Input::getCMD('layout', $name);
		$layout       = Helpers\OrganizerHelper::classDecode($layout);
		$layout       = "Organizer\\Layouts\\XLS\\$name\\$layout";
		$this->layout = new $layout($this);

		$model       = "Organizer\\Models\\" . $this->getName();
		$this->model = new $model();

		$properties = $this->getProperties();
		$properties->setCreator('Organizer');
		$properties->setLastModifiedBy(Helpers\Users::getName());
	}

	/**
	 * Sets context variables and renders the view.
	 *
	 * @return void
	 */
	abstract public function display();

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
			$this->name = Helpers\OrganizerHelper::getClass($this);
		}

		return $this->name;
	}

	/**
	 * Renders the document.
	 *
	 * @return void
	 * @throws Exception
	 */
	protected function render()
	{
		$documentTitle = ApplicationHelper::stringURLSafe($this->getProperties()->getTitle());
		$objWriter     = PHPExcel_IOFactory::createWriter($this, 'Excel2007');
		ob_end_clean();
		header('Content-type: application/vnd.ms-excel');
		header("Content-Disposition: attachment;filename=$documentTitle.xlsx");
		$objWriter->save('php://output');
		ob_flush();
	}
}
