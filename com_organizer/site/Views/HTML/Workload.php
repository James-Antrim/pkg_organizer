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
use Organizer\Helpers;

/**
 * Class loads person workload statistics into the display context.
 */
class Workload extends FormView
{

	/**
	 * Adds supplemental information about constants used for calculation to the page.
	 *
	 * @return void modifies the object property supplement
	 */
	protected function addSupplement()
	{
		$params           = Helpers\Input::getParams();
		$texts            = [
			Helpers\Languages::_('ORGANIZER_WORKLOAD_CALCULATION_SETTINGS'),
			Helpers\Languages::_('ORGANIZER_WORKLOAD_WEEKS') . ': ' . $params->get('workloadWeeks', 13),
			Helpers\Languages::_('ORGANIZER_BACHELOR_VALUE') . ': ' . $params->get('bachelorValue', 25) . '%',
			Helpers\Languages::_('ORGANIZER_MASTER_VALUE') . ': ' . $params->get('masterValue', 50) . '%',
		];
		$this->supplement = '<div class="tbox-blue">' . implode('<br>', $texts) . '</div>';
	}

	/**
	 * Adds a toolbar and title to the view.
	 *
	 * @return void  sets context variables
	 */
	protected function addToolBar()
	{
		$params      = Helpers\Input::getParams();
		$showHeading = (bool) $params->get('show_page_heading', false);
		$pageTitle   = $params->get('page_title', '');
		$title       = ($pageTitle and $showHeading) ? $pageTitle : Helpers\Languages::_('ORGANIZER_WORKLOAD');
		Helpers\HTML::setTitle($title, 'list-2');
		$toolbar = Adapters\Toolbar::getInstance();

		// TODO Hard refresh / inclusion of calculated workload

		$toolbar->appendButton(
			'NewTab',
			'file-xls',
			Helpers\Languages::_('ORGANIZER_XLS_SPREADSHEET'),
			'Workloads.xls',
			false
		);
	}

	/**
	 * @inheritDoc
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/list.css');
	}
}
