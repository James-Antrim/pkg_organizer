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

    /**
     * Set position at a given column
     * @param int|null $column column number (from 0 to getNumberOfColumns()-1); empty string = current column.
     */
    public function selectColumn(int|null $column = null)
    {
        $column = $column ?? $this->currentColumn;
        $column = $column >= $this->columnCount ? 0 : $column;
        $xShift = ['x' => 0, 's' => ['H' => 0, 'V' => 0], 'p' => ['L' => 0, 'T' => 0, 'R' => 0, 'B' => 0]];

        $printHeader = false;
        if ($this->columnCount > 1) {
            if ($column != $this->currentColumn) {
                // move Y pointer at the top of the column
                if ($this->column_start_page == $this->page) {
                    $this->y = $this->columns[$column]['y'];
                }
                else {
                    $this->y = $this->topMargin;
                }
                // Avoid to write table headers more than once
                if (($this->page > $this->maxselcol['page']) or (($this->page == $this->maxselcol['page']) and ($column > $this->maxselcol['column']))) {
                    $printHeader               = true;
                    $this->maxselcol['page']   = $this->page;
                    $this->maxselcol['column'] = $column;
                }
            }
            $xShift = $this->colxshift;
            // set X position of the current column by case
            $listIndent = ($this->listindentlevel * $this->listindent);
            // calculate column X position
            $colpos = 0;
            for ($i = 0; $i < $column; ++$i) {
                $colpos += ($this->columns[$i]['w'] + $this->columns[$i]['s']);
            }
            $x                           = $this->originalLeftMargin + $colpos;
            $this->leftMargin            = ($x + $listIndent);
            $this->rightMargin           = ($this->width - $x - $this->columns[$column]['w']);
            $this->x                     = $x + $listIndent;
            $this->columns[$column]['x'] = $x;
        }
        $this->currentColumn = $column;
        // fix for HTML mode
        $this->newLine = true;

        if ($this->tableHeader and !$this->inTableHeader) {
            if ($printHeader) {
                // print table header
                $this->writeHTML($this->tableHeader, false, false, false, false, '');
                $this->y += $xShift['s']['V'];
                // store end of header position
                if (!isset($this->columns[$column]['th'])) {
                    $this->columns[$column]['th'] = [];
                }
                $this->columns[$column]['th']['\'' . $this->page . '\''] = $this->y;
                $this->lastPrintedCellHeight                             = 0;
            }
            elseif (isset($this->columns[$column]['th']['\'' . $this->page . '\''])) {
                $this->y = $this->columns[$column]['th']['\'' . $this->page . '\''];
            }
        }
        // Account for html table cells over multiple columns
        $this->leftMargin += $xShift['x'];
        $this->x          += $xShift['x'] + $xShift['p']['L'];
    }

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