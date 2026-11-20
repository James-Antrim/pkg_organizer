<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 * @see         PDF (http://www.tcpdf.org) => Nicola Asuni
 * @see         FPDF (http://www.fpdf.org) => Olivier Plathey
 */

namespace THM\Organizer\Views\PDF\Library\Print;

trait Columns
{
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

    protected int $columnCount = 1;
    protected array $columns = [];
    protected int $currentColumn = 0;

#endregion
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED

#endregion
#region ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED

    /**
     * Starting page for columns.
     */
    protected $column_start_page = 0;

    /**
     * Maximum page and column selected.
     */
    protected $maxselcol = ['page' => 0, 'column' => 0];

    /**
     * Array of: X difference between table cell x start and starting page margin, cellspacing, cellpadding.
     */
    protected $colxshift = ['x' => 0, 's' => ['H' => 0, 'V' => 0], 'p' => ['L' => 0, 'T' => 0, 'R' => 0, 'B' => 0]];
#endregion

}