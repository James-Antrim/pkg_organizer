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
use PHPExcel_Worksheet;

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
	public $model;

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
		$properties->setDescription($this->layout->getDescription());
		$properties->setTitle($this->layout->getTitle());
	}

	/**
	 * Adds a range to the active sheet.
	 *
	 * @param   string      $start
	 * @param   string      $end
	 * @param   array       $style
	 * @param   int|string  $value
	 *
	 * @return void
	 * @throws Exception
	 */
	public function addRange(string $start, string $end, $style = [], $value = '')
	{
		$coords = "$start:$end";
		$sheet  = $this->getActiveSheet();
		$sheet->mergeCells($coords);

		if ($style)
		{
			$sheet->getStyle($coords)->applyFromArray($style);
		}

		if ($value)
		{
			$sheet->setCellValue($start, $value);
		}
	}

	/**
	 * Sets context variables and renders the view.
	 *
	 * @return void
	 * @throws Exception
	 */
	public function display()
	{
		$this->layout->fill();
		$this->render();
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
		exit();
	}

	/**
	 * Set active sheet index
	 *
	 * @param   int  $pIndex  Active sheet index
	 *
	 * @return PHPExcel_Worksheet
	 */
	public function setActiveSheetIndex($pIndex = 0): PHPExcel_Worksheet
	{
		try
		{
			return parent::setActiveSheetIndex($pIndex);
		}
		catch (Exception $exception)
		{
			return $this->setActiveSheetIndex($pIndex - 1);
		}
	}
}
