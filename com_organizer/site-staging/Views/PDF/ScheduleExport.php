<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\PDF;

define('K_PATH_IMAGES', JPATH_ROOT . '/components/com_organizer/images/');

use Organizer\Helpers;
use Organizer\Views\BaseView;

jimport('tcpdf.tcpdf');

/**
 * Class creates a PDF file for the display of the filtered schedule information.
 */
class ScheduleExport extends BaseView
{
	/**
	 * @var bool
	 */
	private $compiler;

	public $document;

	/**
	 * Sets context variables and renders the view.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return void
	 *
	 * @SuppressWarnings(PHPMD.UnusedFormalParameter)
	 */
	public function display($tpl = null)
	{
		if (!$this->checkLibraries())
		{
			return;
		}

		$model      = $this->getModel();
		$parameters = $model->parameters;
		$grid       = empty($model->grid) ? null : $model->grid;
		$lessons    = $model->lessons;

		$fileName = "{$parameters['documentFormat']}_{$parameters['displayFormat']}_{$parameters['pdfWeekFormat']}";
		require_once __DIR__ . "/tmpl/$fileName.php";
		new OrganizerTemplateSchedule_Export_PDF($parameters, $lessons, $grid);
	}

	/**
	 * Imports libraries and sets library variables
	 *
	 * @return bool true if the tcpdf library is installed, otherwise false
	 */
	private function checkLibraries()
	{
		$this->compiler = jimport('tcpdf.tcpdf');

		if (!$this->compiler)
		{
			Helpers\OrganizerHelper::error(503);
		}

		return true;
	}
}
