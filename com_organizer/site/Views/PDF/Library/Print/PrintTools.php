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

trait PrintTools
{
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

    use Bookmarks;
    use Cells;
    use Columns;
    use Fonts;
    use Footers;
    use Headers;
    use Pages;
    use TOCs;

    /**
     * Performs a line break. The current abscissa goes back to the left margin and the ordinate increases by the amount passed in parameter.
     * @param float|null $height The height of the break. By default, the value equals the height of the last printed cell.
     * @param bool       $cell   if true add the current left padding to the X coordinate
     * @see   Cell()
     */
    public function lineBreak(float|null $height = null, bool $cell = false): void
    {
        if ($this->columnCount > 1) {
            $column = $this->columns[$this->currentColumn];
            // remove vertical space from the top of the column
            if ($this->y === $column['y'] and isset($column['x']) and ($this->x == $column['x'])) {
                return;
            }
        }

        $cellPadding = $cell ? $this->cellPaddings['L'] : 0;

        $this->x = $this->leftMargin + $cellPadding;
        $this->y += is_null($height) ? $this->lastPrintedCellHeight : $height;

        $this->newLine = true;
    }

#endregion
#region /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


#endregion
#region ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED

#endregion
}