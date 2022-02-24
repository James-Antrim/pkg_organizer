<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Layouts\PDF\Instances;

use Organizer\Views\PDF\Instances;

/**
 * Class generates a PDF file in A3 format.
 */
class GridA3 extends GridLayout
{
	protected const DATA_WIDTH = 66, FONT_SIZE = 6, LINE_HEIGHT = 3.5, LINE_LENGTH = 40;

	public function __construct(Instances $view)
	{
		parent::__construct($view);
		$view->setFormat('A3');
	}
}
