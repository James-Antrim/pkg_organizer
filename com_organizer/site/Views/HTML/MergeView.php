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

use Joomla\CMS\Toolbar\Toolbar;
use Organizer\Helpers;

/**
 * Class loads the resource form into display context. Specific resource determined by extending class.
 */
abstract class MergeView extends FormView
{
	/**
	 * Adds a toolbar and title to the view.
	 *
	 * @return void  adds toolbar items to the view
	 */
	protected function addToolBar()
	{
		$name = Helpers\OrganizerHelper::getClass($this);
		Helpers\HTML::setTitle(Helpers\Languages::_(Helpers\Languages::getConstant($name)));
		$resource   = str_replace('merge', '', strtolower($name));
		$controller = Helpers\OrganizerHelper::getPlural($resource);
		$toolbar    = Toolbar::getInstance();
		$toolbar->appendButton(
			'Standard',
			'attachment',
			Helpers\Languages::_('ORGANIZER_MERGE'),
			$controller . '.merge',
			false
		);
		$toolbar->appendButton(
			'Standard',
			'cancel',
			Helpers\Languages::_('ORGANIZER_CANCEL'),
			$controller . '.cancel',
			false
		);
	}
}
