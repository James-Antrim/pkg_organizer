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
}