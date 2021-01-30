<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

class XLC
{
	// General
	public const NONE = 'none', CONTAINSTEXT = 'containsText';

	// Alignment
	public const BOTTOM = 'bottom', CENTER = 'center', CENTER_CONTINUOUS = 'centerContinuous', FILL = 'fill',
		GENERAL = 'general', JUSTIFY = 'justify', LEFT = 'left', RIGHT = 'right', TOP = 'top';

	// Borders
	public const DASHDOT = 'dashDot', DASHDOTDOT = 'dashDotDot', DASHED = 'dashed', DOTTED = 'dotted', DOUBLE = 'double',
		HAIR = 'hair', MEDIUM = 'medium', MEDIUMDASHDOT = 'mediumDashDot', MEDIUMDASHDOTDOT = 'mediumDashDotDot',
		MEDIUMDASHED = 'mediumDashed', SLANTDASHDOT = 'slantDashDot', THICK = 'thick', THIN = 'thin';

	// Colors
	public const BLACK = 'FF000000', BLUE = 'FF0000FF', DARKRED = 'FF800000', DARKBLUE = 'FF000080',
		DARKGREEN = 'FF008000', DARKYELLOW = 'FF808000', GREEN = 'FF00FF00', RED = 'FFFF0000', WHITE = 'FFFFFFFF',
		YELLOW = 'FFFFFF00';

	// Conditions
	public const CELLIS = 'cellIs', EXPRESSION = 'expression';

	// Fills
	public const GRADIENT_LINEAR = 'linear', GRADIENT_PATH = 'path', PATTERN_DARKDOWN = 'darkDown',
		PATTERN_DARKGRAY = 'darkGray', PATTERN_DARKGRID = 'darkGrid', PATTERN_DARKHORIZONTAL = 'darkHorizontal',
		PATTERN_DARKTRELLIS = 'darkTrellis', PATTERN_DARKUP = 'darkUp', PATTERN_DARKVERTICAL = 'darkVertical',
		PATTERN_GRAY0625 = 'gray0625', PATTERN_GRAY125 = 'gray125', PATTERN_LIGHTDOWN = 'lightDown',
		PATTERN_LIGHTGRAY = 'lightGray', PATTERN_LIGHTGRID = 'lightGrid', PATTERN_LIGHTHORIZONTAL = 'lightHorizontal',
		PATTERN_LIGHTTRELLIS = 'lightTrellis', PATTERN_LIGHTUP = 'lightUp', PATTERN_LIGHTVERTICAL = 'lightVertical',
		PATTERN_MEDIUMGRAY = 'mediumGray', SOLID = 'solid';

	// Operators
	public const BEGINSWITH = 'beginsWith', BETWEEN = 'between', ENDSWITH = 'endsWith', EQUAL = 'equal',
		GREATERTHAN = 'greaterThan', GREATERTHANOREQUAL = 'greaterThanOrEqual', LESSTHAN = 'lessThan',
		LESSTHANOREQUAL = 'lessThanOrEqual', OPERATOR_NONE = '', NOTEQUAL = 'notEqual', NOTCONTAINS = 'notContains';

	// Paper sizes
	public const A2_PAPER = 64, A3 = 8, A3_EXTRA_PAPER = 61, A3_EXTRA_TRANSVERSE_PAPER = 66, A3_TRANSVERSE_PAPER = 65,
		A4 = 9, A4_EXTRA_PAPER = 51, A4_PLUS_PAPER = 58, A4_SMALL = 10, A4_TRANSVERSE_PAPER = 53, A5 = 11,
		A5_EXTRA_PAPER = 62, A5_TRANSVERSE_PAPER = 59, B4 = 12, B4_ENVELOPE = 33, B5 = 13, B5_ENVELOPE = 34,
		B6_ENVELOPE = 35, C3_ENVELOPE = 29, C4_ENVELOPE = 30, C5_ENVELOPE = 28, C6_ENVELOPE = 31, C65_ENVELOPE = 32,
		C_PAPER = 24, D_PAPER = 25, DL_ENVELOPE = 27, E_PAPER = 26, EXECUTIVE = 7, FOLIO = 14,
		GERMAN_LEGAL_FANFOLD = 41, GERMAN_STANDARD_FANFOLD = 40, INVITE_ENVELOPE = 47, ISO_B4 = 42,
		ISO_B5_EXTRA_PAPER = 63, ITALY_ENVELOPE = 36, JAPANESE_DOUBLE_POSTCARD = 43, JIS_B5_TRANSVERSE_PAPER = 60,
		LEDGER = 4, LEGAL = 5, LEGAL_EXTRA_PAPER = 49, LETTER = 1, LETTER_EXTRA_PAPER = 48,
		LETTER_EXTRA_TRANSVERSE_PAPER = 54, LETTER_PLUS_PAPER = 57, LETTER_SMALL = 2, LETTER_TRANSVERSE_PAPER = 52,
		MONARCH_ENVELOPE = 37, NO9_ENVELOPE = 19, NO10_ENVELOPE = 20, NO11_ENVELOPE = 21, NO12_ENVELOPE = 22,
		NO14_ENVELOPE = 23, NOTE = 18, PAPERSIZE_634_ENVELOPE = 38, PAPERSIZE_STATEMENT = 6, QUARTO = 15,
		STANDARD_1 = 16, STANDARD_2 = 17, STANDARD_PAPER_1 = 44, STANDARD_PAPER_2 = 45, STANDARD_PAPER_3 = 46,
		SUPERA_A4_PAPER = 55, SUPERB_A3_PAPER = 56, TABLOID = 3, TABLOID_EXTRA_PAPER = 50, US_STANDARD_FANFOLD = 39;

	// Orientation
	public const DEFAULT = 'default', LANDSCAPE = 'landscape', PORTRAIT = 'portrait';

	// Print Range Method */
	public const SETPRINTRANGE_OVERWRITE = 'O', SETPRINTRANGE_INSERT = 'I';
}