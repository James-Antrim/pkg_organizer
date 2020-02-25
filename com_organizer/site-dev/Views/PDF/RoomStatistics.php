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

use Organizer\Views\BaseView;

jimport('tcpdf.tcpdf');

/**
 * Class loads room statistic information into the display context.
 */
class RoomStatistics extends BaseView
{
	public $fields = [];

	public $date;

	public $timePeriods;

	public $terms;

	public $organizations;

	public $programs;

	public $roomIDs;

	/**
	 * Sets context variables and renders the view.
	 *
	 * @param   string  $tpl  template
	 *
	 * @return void
	 */
	public function display($tpl = null)
	{
		$this->modifyDocument();

		$this->model = $this->getModel();

		parent::display($tpl);
	}
}
