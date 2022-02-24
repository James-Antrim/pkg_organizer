<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Controllers;

use Joomla\CMS\Router\Route;
use Organizer\Helpers;

trait Activated
{
	/**
	 * Activates resources.
	 *
	 * @return void
	 */
	public function activate()
	{
		$fqName = 'Organizer\\Models\\' . ucfirst($this->resource);
		$model  = new $fqName();

		if ($model->activate())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_DEACTIVATION_SUCCESS', 'success');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_DEACTIVATION_FAIL', 'error');
		}

		$url = Helpers\Routing::getRedirectBase() . '&view=' . $this->listView;
		$this->setRedirect(Route::_($url, false));
	}

	/**
	 * Deactivates resources.
	 *
	 * @return void
	 */
	public function deactivate()
	{
		$fqName = 'Organizer\\Models\\' . ucfirst($this->resource);
		$model  = new $fqName();

		if ($model->deactivate())
		{
			Helpers\OrganizerHelper::message('ORGANIZER_DEACTIVATION_SUCCESS', 'success');
		}
		else
		{
			Helpers\OrganizerHelper::message('ORGANIZER_DEACTIVATION_FAIL', 'error');
		}

		$url = Helpers\Routing::getRedirectBase() . '&view=' . $this->listView;
		$this->setRedirect(Route::_($url, false));
	}
}