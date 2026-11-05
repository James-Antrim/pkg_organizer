<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 * @see         TCPDF (http://www.tcpdf.org) => Nicola Asuni
 * @see         FPDF (http://www.fpdf.org) => Olivier Plathey
 */

namespace THM\Organizer\Views\PDF\Library;

/**
 * Trait extracts constants and descriptive texts used by other classes.
 */
trait Constants
{
////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////COLORS

    public const BLACK = [0, 0, 0], WHITE = [255, 255, 255];

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////CONFIGURATION

    public const FONT_PATH = JPATH_ROOT . '/components/com_organizer/Views/PDF/Library/Fonts/';
    public const IMAGE_PATH = JPATH_ROOT . '/components/com_organizer/images/';

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DESCRIPTIONS

    public const LINE_CAP_DESCRIPTIONS = [
        'butt'   => 'flat line ending',
        'round'  => 'round',
        'square' => 'flat line ending projected past line end',
    ];

    public const LINE_JOIN_DESCRIPTIONS = [
        'bevel' => '?',
        'miter' => '?',
        'round' => '?',
    ];

    public const LINE_STYLE_DESCRIPTION = [
        'cap'   => 'cap style value @see self::LINE_CAP_DESCRIPTIONS (string)',
        'color' => '[GREY] or [R, G, B] or [C, M, Y, K] or [C, M, Y, K, SpotColorName](array)',
        'dash'  => '0 => no dash, string with length (comma-seperated) value(s) defining dash/space lengths (int 0 |string)',
        'join'  => 'join style value @see self::LINE_JOIN_DESCRIPTIONS (string)',
        'phase' => 'modifier on the dash pattern which is used to shift the point at which the pattern starts (integer)',
        'width' => 'width of the line in user units (float)',
    ];

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////FONTS

    public const DEFAULT_FONT = 'helvetica';
    public const SMALL_FONT_RATIO = 2 / 3;

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////FORMATTING

    public const LANDSCAPE = 'L', PORTRAIT = 'P';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////STYLES
}