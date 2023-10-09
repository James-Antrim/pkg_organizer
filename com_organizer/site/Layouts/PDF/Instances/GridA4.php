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

/**
 * Class generates a PDF file in A4 format.
 */
class GridA4 extends GridLayout
{
    protected const DATA_WIDTH = 45, FONT_SIZE = 8, LINE_HEIGHT = 4.5, LINE_LENGTH = 32;
}
