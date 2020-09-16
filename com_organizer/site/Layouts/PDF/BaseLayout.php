<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @author      Florian Fenzl, <florian.fenzl@mni.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\PDF;

use Joomla\CMS\Application\ApplicationHelper;
use Joomla\CMS\Factory;
use TCPDF;

/**
 * Base PDF export class used for the generation of various course exports.
 */
abstract class BaseLayout extends TCPDF
{

	/**
	 * Adds the contents of the document to it.
	 *
	 * @param   mixed  $data  the data used to fill the contents of the document
	 *
	 * @return void modifies the document
	 */
	abstract public function fill($data);

	/**
	 * Renders the document.
	 *
	 * @return void renders the document and closes the application
	 */
	public function render()
	{
	}
}
