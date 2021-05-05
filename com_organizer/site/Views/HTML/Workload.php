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

		if (Helpers\Input::getInt('personID'))
		{
			$toolbar->appendButton(
				'NewTab',
				'file-xls',
				Helpers\Languages::_('ORGANIZER_DOWNLOAD'),
				'Workloads.xls',
				false
			);
		}
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
