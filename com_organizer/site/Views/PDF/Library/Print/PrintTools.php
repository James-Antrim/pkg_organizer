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
     * @see   cell()
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


    /**
     * Returns the string alias used right align page numbers.
     * If the current font is unicode type, the returned string wil contain an additional open curly brace.
     * @return string
     */
    public function getAliasRightShift()
    {
        // calculate aproximatively the ratio between widths of aliases and replacements.
        $ref  = '{' . self::RIGHT_SHIFT_ALIAS . '}{' . self::TOTAL_PAGES_ALIAS . '}{' . self::PAGE_NUMBER_ALIAS . '}';
        $rep  = str_repeat(' ', $this->GetNumChars($ref));
        $wrep = $this->GetStringWidth($rep);
        if ($wrep > 0) {
            $wdiff = max(1, ($this->GetStringWidth($ref) / $wrep));
        }
        else {
            $wdiff = 1;
        }
        $sdiff = sprintf('%F', $wdiff);
        $alias = self::RIGHT_SHIFT_ALIAS . $sdiff . '}';
        return $alias;
    }

#endregion
#region ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Text indentation value (used for text-indent CSS attribute).
     */
    protected $textindent = 0;

    /**
     * Text rendering mode: 0 = Fill text; 1 = Stroke text; 2 = Fill, then stroke text; 3 = Neither fill nor stroke text (invisible); 4 = Fill text and add to path for clipping; 5 = Stroke text and add to path for clipping; 6 = Fill, then stroke text and add to path for clipping; 7 = Add text to path for clipping.
     */
    protected $textrendermode = 0;

    /**
     * Text stroke width in doc units.
     */
    protected $textstrokewidth = 0;

    /**
     * Returns an array containing current margins:
     * <ul>
     * <li>$ret['left'] = left margin</li>
     * <li>$ret['right'] = right margin</li>
     * <li>$ret['top'] = top margin</li>
     * <li>$ret['bottom'] = bottom margin</li>
     * <li>$ret['header'] = header margin</li>
     * <li>$ret['footer'] = footer margin</li>
     * <li>$ret['cell'] = cell padding array</li>
     * <li>$ret['padding_left'] = cell left padding</li>
     * <li>$ret['padding_top'] = cell top padding</li>
     * <li>$ret['padding_right'] = cell right padding</li>
     * <li>$ret['padding_bottom'] = cell bottom padding</li>
     * </ul>
     * @return array containing all margins measures
     */
    public function getMargins()
    {
        $ret = [
            'left'           => $this->leftMargin,
            'right'          => $this->rightMargin,
            'top'            => $this->topMargin,
            'bottom'         => $this->bottomMargin,
            'header'         => $this->headerMargin,
            'footer'         => $this->footerMargin,
            'cell'           => $this->cellPaddings,
            'padding_left'   => $this->cellPaddings['L'],
            'padding_top'    => $this->cellPaddings['T'],
            'padding_right'  => $this->cellPaddings['R'],
            'padding_bottom' => $this->cellPaddings['B']
        ];
        return $ret;
    }

#endregion
}