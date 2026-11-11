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
///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////GENERAL

    private const FINAL = 3, INITIAL = 0, NEUTRAL = 1, READY = 2;
    private const STATES = [self::FINAL, self::INITIAL, self::NEUTRAL, self::READY];


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////ENCRYPTION

    public const ENCRYPTION_PADDING = "\x28\xBF\x4E\x5E\x4E\x75\x8A\x41\x64\x00\x4E\x56\xFF\xFA\x01\x08\x2E\x2E\x00\xB6\xD0\x68\x3E\x80\x2F\x0C\xA9\xFE\x64\x53\x69\x7A";

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////COLORS

    public const BLACK = [0, 0, 0], BLUE = [0, 0, 255], WHITE = [255, 255, 255];
    private const COLOR_CONTEXTS = ['draw', 'fill', 'text'];

    public const GRAYSCALE = 0, RGB = 2, CMYK = 3;
    public const COLOR_SCHEMES = [self::GRAYSCALE => 'DeviceGray', self::RGB => 'DeviceRGB', self::CMYK => 'Indexed'];

    /** Array of valid JavaScript color names. */
    private const JS_COLORS = ['transparent', 'black', 'white', 'red', 'green', 'blue', 'cyan', 'magenta', 'yellow', 'dkGray', 'gray', 'ltGray'];

    /**
     * Array of spot colors (C,M,Y,K,name). Color keys must be in lowercase and without spaces.
     * Common industry standard spot colors are: ANPA-COLOR, DIC, FOCOLTONE, GCMI, HKS, PANTONE, TOYO, TRUMATCH.
     */
    private const SPOT_COLORS = [
        // special registration colors
        'none'    => [0, 0, 0, 0, 'None'],
        'all'     => [100, 100, 100, 100, 'All'],
        // standard CMYK colors
        'cyan'    => [100, 0, 0, 0, 'Cyan'],
        'magenta' => [0, 100, 0, 0, 'Magenta'],
        'yellow'  => [0, 0, 100, 0, 'Yellow'],
        'key'     => [0, 0, 0, 100, 'Key'],
        // alias
        'white'   => [0, 0, 0, 0, 'White'],
        'black'   => [0, 0, 0, 100, 'Black'],
        // standard RGB colors
        'red'     => [0, 100, 100, 0, 'Red'],
        'green'   => [100, 0, 100, 0, 'Green'],
        'blue'    => [100, 100, 0, 0, 'Blue'],
    ];

    /** Array associating web safe color names to their corresponding hexadecimal values. */
    private const WEB_COLORS = [
        'aliceblue'            => 'f0f8ff',
        'antiquewhite'         => 'faebd7',
        'aqua'                 => '00ffff',
        'aquamarine'           => '7fffd4',
        'azure'                => 'f0ffff',
        'beige'                => 'f5f5dc',
        'bisque'               => 'ffe4c4',
        'black'                => '000000',
        'blanchedalmond'       => 'ffebcd',
        'blue'                 => '0000ff',
        'blueviolet'           => '8a2be2',
        'brown'                => 'a52a2a',
        'burlywood'            => 'deb887',
        'cadetblue'            => '5f9ea0',
        'chartreuse'           => '7fff00',
        'chocolate'            => 'd2691e',
        'coral'                => 'ff7f50',
        'cornflowerblue'       => '6495ed',
        'cornsilk'             => 'fff8dc',
        'crimson'              => 'dc143c',
        'cyan'                 => '00ffff',
        'darkblue'             => '00008b',
        'darkcyan'             => '008b8b',
        'darkgoldenrod'        => 'b8860b',
        'dkgray'               => 'a9a9a9',
        'darkgray'             => 'a9a9a9',
        'darkgrey'             => 'a9a9a9',
        'darkgreen'            => '006400',
        'darkkhaki'            => 'bdb76b',
        'darkmagenta'          => '8b008b',
        'darkolivegreen'       => '556b2f',
        'darkorange'           => 'ff8c00',
        'darkorchid'           => '9932cc',
        'darkred'              => '8b0000',
        'darksalmon'           => 'e9967a',
        'darkseagreen'         => '8fbc8f',
        'darkslateblue'        => '483d8b',
        'darkslategray'        => '2f4f4f',
        'darkslategrey'        => '2f4f4f',
        'darkturquoise'        => '00ced1',
        'darkviolet'           => '9400d3',
        'deeppink'             => 'ff1493',
        'deepskyblue'          => '00bfff',
        'dimgray'              => '696969',
        'dimgrey'              => '696969',
        'dodgerblue'           => '1e90ff',
        'firebrick'            => 'b22222',
        'floralwhite'          => 'fffaf0',
        'forestgreen'          => '228b22',
        'fuchsia'              => 'ff00ff',
        'gainsboro'            => 'dcdcdc',
        'ghostwhite'           => 'f8f8ff',
        'gold'                 => 'ffd700',
        'goldenrod'            => 'daa520',
        'gray'                 => '808080',
        'grey'                 => '808080',
        'green'                => '008000',
        'greenyellow'          => 'adff2f',
        'honeydew'             => 'f0fff0',
        'hotpink'              => 'ff69b4',
        'indianred'            => 'cd5c5c',
        'indigo'               => '4b0082',
        'ivory'                => 'fffff0',
        'khaki'                => 'f0e68c',
        'lavender'             => 'e6e6fa',
        'lavenderblush'        => 'fff0f5',
        'lawngreen'            => '7cfc00',
        'lemonchiffon'         => 'fffacd',
        'lightblue'            => 'add8e6',
        'lightcoral'           => 'f08080',
        'lightcyan'            => 'e0ffff',
        'lightgoldenrodyellow' => 'fafad2',
        'ltgray'               => 'd3d3d3',
        'lightgray'            => 'd3d3d3',
        'lightgrey'            => 'd3d3d3',
        'lightgreen'           => '90ee90',
        'lightpink'            => 'ffb6c1',
        'lightsalmon'          => 'ffa07a',
        'lightseagreen'        => '20b2aa',
        'lightskyblue'         => '87cefa',
        'lightslategray'       => '778899',
        'lightslategrey'       => '778899',
        'lightsteelblue'       => 'b0c4de',
        'lightyellow'          => 'ffffe0',
        'lime'                 => '00ff00',
        'limegreen'            => '32cd32',
        'linen'                => 'faf0e6',
        'magenta'              => 'ff00ff',
        'maroon'               => '800000',
        'mediumaquamarine'     => '66cdaa',
        'mediumblue'           => '0000cd',
        'mediumorchid'         => 'ba55d3',
        'mediumpurple'         => '9370d8',
        'mediumseagreen'       => '3cb371',
        'mediumslateblue'      => '7b68ee',
        'mediumspringgreen'    => '00fa9a',
        'mediumturquoise'      => '48d1cc',
        'mediumvioletred'      => 'c71585',
        'midnightblue'         => '191970',
        'mintcream'            => 'f5fffa',
        'mistyrose'            => 'ffe4e1',
        'moccasin'             => 'ffe4b5',
        'navajowhite'          => 'ffdead',
        'navy'                 => '000080',
        'oldlace'              => 'fdf5e6',
        'olive'                => '808000',
        'olivedrab'            => '6b8e23',
        'orange'               => 'ffa500',
        'orangered'            => 'ff4500',
        'orchid'               => 'da70d6',
        'palegoldenrod'        => 'eee8aa',
        'palegreen'            => '98fb98',
        'paleturquoise'        => 'afeeee',
        'palevioletred'        => 'd87093',
        'papayawhip'           => 'ffefd5',
        'peachpuff'            => 'ffdab9',
        'peru'                 => 'cd853f',
        'pink'                 => 'ffc0cb',
        'plum'                 => 'dda0dd',
        'powderblue'           => 'b0e0e6',
        'purple'               => '800080',
        'red'                  => 'ff0000',
        'rosybrown'            => 'bc8f8f',
        'royalblue'            => '4169e1',
        'saddlebrown'          => '8b4513',
        'salmon'               => 'fa8072',
        'sandybrown'           => 'f4a460',
        'seagreen'             => '2e8b57',
        'seashell'             => 'fff5ee',
        'sienna'               => 'a0522d',
        'silver'               => 'c0c0c0',
        'skyblue'              => '87ceeb',
        'slateblue'            => '6a5acd',
        'slategray'            => '708090',
        'slategrey'            => '708090',
        'snow'                 => 'fffafa',
        'springgreen'          => '00ff7f',
        'steelblue'            => '4682b4',
        'tan'                  => 'd2b48c',
        'teal'                 => '008080',
        'thistle'              => 'd8bfd8',
        'tomato'               => 'ff6347',
        'turquoise'            => '40e0d0',
        'violet'               => 'ee82ee',
        'wheat'                => 'f5deb3',
        'white'                => 'ffffff',
        'whitesmoke'           => 'f5f5f5',
        'yellow'               => 'ffff00',
        'yellowgreen'          => '9acd32'
    ];

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


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////IMAGES

    public const SVG_INHERITED = [
        'clip-rule', 'color', 'color-interpolation', 'color-interpolation-filters', 'color-profile', 'color-rendering', 'cursor', 'direction',
        'display', 'fill', 'fill-opacity', 'fill-rule', 'font', 'font-family', 'font-size', 'font-size-adjust', 'font-stretch', 'font-style',
        'font-variant', 'font-weight', 'glyph-orientation-horizontal', 'glyph-orientation-vertical', 'image-rendering', 'kerning', 'letter-spacing',
        'marker', 'marker-end', 'marker-mid', 'marker-start', 'pointer-events', 'shape-rendering', 'stroke', 'stroke-dasharray', 'stroke-dashoffset',
        'stroke-linecap', 'stroke-linejoin', 'stroke-miterlimit', 'stroke-opacity', 'stroke-width', 'text-anchor', 'text-rendering', 'visibility',
        'word-spacing', 'writing-mode'
    ];


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PRINTING

    public const BYTE_RANGE_STRING = '/ByteRange[0 ********** ********** **********]';
    public const GROUP_PAGE_NUMBER_ALIAS = '{:png:}', PAGE_NUMBER_ALIAS = '{:pnp:}', PAGES_IN_GROUP_ALIAS = '{:ptg:}', TOTAL_PAGES_ALIAS = '{:ptp:}';
    public const PAGE_BOXES = ['MediaBox', 'CropBox', 'BleedBox', 'TrimBox', 'ArtBox'];
    /** Alias for right shift compensation used to correctly align page numbers on the right. */
    public const RIGHT_SHIFT_ALIAS = '{rsc:';

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////STYLES


}