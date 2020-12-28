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
use Organizer\Views\Named;
use PHPExcel;
use PHPExcel_IOFactory;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
abstract class BaseView extends PHPExcel
{
	use Named;

	/**
	 * @var BaseLayout
	 */
	protected $layout;

	/**
	 * @var BaseModel
	 */
	protected $model;

	/**
	 * @inheritdoc
	 */
	public function __construct()
	{
		parent::__construct();

		$name = $this->getName();

		$layout = Helpers\Input::getCMD('layout', $name);
		$layout = Helpers\OrganizerHelper::classDecode($layout);
		$layout = "Organizer\\Layouts\\XLS\\$name\\$layout";
		$model  = "Organizer\\Models\\$name";

		$this->layout = new $layout($this);
		$this->model  = new $model();

		$properties = $this->getProperties();
		$properties->setCreator('Organizer');
		$properties->setLastModifiedBy(Helpers\Users::getName());
	}

	/**
	 * Sets context variables and renders the view.
	 *
	 * @return void
	 * @throws Exception
	 */
	abstract public function display();

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
