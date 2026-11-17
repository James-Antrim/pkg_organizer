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

namespace THM\Organizer\Views\PDF\Library;

trait Cells
{
    protected float $cellHeightRatio = 1.25;
    protected array $cellMargins = ['T' => 0, 'R' => 0, 'B' => 0, 'L' => 0];
    protected array $cellPaddings = ['T' => 0, 'R' => 0, 'B' => 0, 'L' => 0];

    /**
     * Set the internal Cell margins.
     * @param float|null $left   left margin
     * @param float|null $top    top margin
     * @param float|null $right  right margin
     * @param float|null $bottom bottom margin
     */
    public function cellMargins(float|null $left = null, float|null $top = null, float|null $right = null, float|null $bottom = null): array
    {
        if (is_float($left) and ($left >= 0)) {
            $this->cellMargins['L'] = $left;
        }
        if (is_float($top) and ($top >= 0)) {
            $this->cellMargins['T'] = $top;
        }
        if (is_float($right) and ($right >= 0)) {
            $this->cellMargins['R'] = $right;
        }
        if (is_float($bottom) and ($bottom >= 0)) {
            $this->cellMargins['B'] = $bottom;
        }

        return $this->cellMargins;
    }

    /**
     * Sets a uniform padding for all cell paddings.
     * @param float $padding
     * @see   cellPaddings()
     */
    public function cellPadding(float $padding): void
    {
        if ($padding >= 0) {
            $this->cellPaddings['L'] = $padding;
            $this->cellPaddings['T'] = $padding;
            $this->cellPaddings['R'] = $padding;
            $this->cellPaddings['B'] = $padding;
        }
    }

    /**
     * Getter / setter for specific cell paddings.
     * @param float|null $left   left padding
     * @param float|null $top    top padding
     * @param float|null $right  right padding
     * @param float|null $bottom bottom padding
     * @see   cellPadding()
     */
    public function cellPaddings(float|null $left = null, float|null $top = null, float|null $right = null, float|null $bottom = null): array
    {
        if (is_float($left) and ($left >= 0)) {
            $this->cellPaddings['L'] = $left;
        }
        if (is_float($top) and ($top >= 0)) {
            $this->cellPaddings['T'] = $top;
        }
        if (is_float($right) and ($right >= 0)) {
            $this->cellPaddings['R'] = $right;
        }
        if (is_float($bottom) and ($bottom >= 0)) {
            $this->cellPaddings['B'] = $bottom;
        }

        return $this->cellPaddings;
    }


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Cell left margin (used by regions).
     */
    protected $clMargin;

    /**
     * Cell right margin (used by regions).
     */
    protected $crMargin;

    /**
     * Adjust the internal Cell padding array to take account of the line width.
     * @param string|array|int|bool $brd Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: ['LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK]]
     * @return void|array array of adjustments
     */
    protected function adjustCellPadding($brd = 0)
    {
        if (empty($brd)) {
            return;
        }
        if (is_string($brd)) {
            // convert string to array
            $slen   = strlen($brd);
            $newbrd = [];
            for ($i = 0; $i < $slen; ++$i) {
                $newbrd[$brd[$i]] = true;
            }
            $brd = $newbrd;
        }
        elseif (
            ($brd === 1)
            || ($brd === true)
            || (is_numeric($brd) && ((int) $brd > 0))
        ) {
            $brd = ['LRTB' => true];
        }
        if (!is_array($brd)) {
            return;
        }
        // store current cell padding
        $cp = $this->cellPaddings;
        // select border mode
        if (isset($brd['mode'])) {
            $mode = $brd['mode'];
            unset($brd['mode']);
        }
        else {
            $mode = 'normal';
        }
        // process borders
        foreach ($brd as $border => $style) {
            $line_width = $this->lineWidth;
            if (is_array($style) && isset($style['width'])) {
                // get border width
                $line_width = $style['width'];
            }
            $adj = 0; // line width inside the cell
            switch ($mode) {
                case 'ext':
                {
                    $adj = 0;
                    break;
                }
                case 'int':
                {
                    $adj = $line_width;
                    break;
                }
                case 'normal':
                default:
                {
                    $adj = ($line_width / 2);
                    break;
                }
            }
            // correct internal cell padding if required to avoid overlap between text and lines
            if (
                is_numeric($this->cellPaddings['T'])
                && ($this->cellPaddings['T'] < $adj)
                && (strpos($border, 'T') !== false)
            ) {
                $this->cellPaddings['T'] = $adj;
            }
            if (
                is_numeric($this->cellPaddings['R'])
                && ($this->cellPaddings['R'] < $adj)
                && (strpos($border, 'R') !== false)
            ) {
                $this->cellPaddings['R'] = $adj;
            }
            if (
                is_numeric($this->cellPaddings['B'])
                && ($this->cellPaddings['B'] < $adj)
                && (strpos($border, 'B') !== false)
            ) {
                $this->cellPaddings['B'] = $adj;
            }
            if (
                is_numeric($this->cellPaddings['L'])
                && ($this->cellPaddings['L'] < $adj)
                && (strpos($border, 'L') !== false)
            ) {
                $this->cellPaddings['L'] = $adj;
            }

        }

        return [
            'T' => $this->cellPaddings['T'] - $cp['T'],
            'R' => $this->cellPaddings['R'] - $cp['R'],
            'B' => $this->cellPaddings['B'] - $cp['B'],
            'L' => $this->cellPaddings['L'] - $cp['L'],
        ];
    }
}