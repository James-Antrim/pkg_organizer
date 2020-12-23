<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;

/**
 * Class loads lesson statistic information into the display context.
 */
class LessonStatistics extends BaseView
{
	public $columns = [];

	public $form = null;

	public $lessons = [];

	public $rows = [];

	public $total = 0;

	/**
	 * Execute and display a template script.
	 *
	 * @param   string  $tpl  The name of the template file to parse; automatically searches through the template paths.
	 *
	 * @return void sets context variables and uses the parent's display method
	 */
	public function display($tpl = null)
	{
		// Use language_selection layout
		$this->state = $this->get('State');
		$this->form  = $this->get('Form');
		$this->form->setValue('termID', null, $this->state->get('termID'));
		$this->form->setValue('organizationID', null, $this->state->get('organizationID'));
		$this->form->setValue('categoryID', null, $this->state->get('categoryID'));

		$model         = $this->getModel();
		$this->columns = $model->columns;
		$this->rows    = $model->rows;
		$this->lessons = $model->lessons;
		$this->total   = $model->total;

		parent::display($tpl);
	}

	/**
	 * Modifies document variables and adds links to external files
	 *
	 * @return void
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/lesson_statistics.css');
	}
}
