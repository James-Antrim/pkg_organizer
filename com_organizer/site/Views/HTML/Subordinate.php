<?php
/**
 * @category    Joomla component
 * @package     THM_Organizer
 * @subpackage  com_thm_organizer.<subdirectory>
 * @name        Subordinate
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;


use Joomla\CMS\Factory;
use Joomla\CMS\Uri\Uri;

trait Subordinate
{
	/**
	 * Adds styles and scripts to the document
	 *
	 * @return void  modifies the document
	 */
	protected function modifyDocument()
	{
		parent::modifyDocument();

		Factory::getDocument()->addScript(Uri::root() . 'components/com_organizer/js/curricula.js');
	}
}