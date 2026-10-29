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

use Exception;

/** PHP color trait. */
trait Colors
{
    private const COLOR_CONTEXTS = ['draw', 'fill', 'text'];

    /**
     * Array of valid JavaScript color names
     */
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

    /**
     * Array of WEB safe colors
     */
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

    /** Whether fill and text colors are different. */
    private bool $differentColors = false;

    /** Commands for drawing color. */
    private string $drawColor = '0 G';

    /** Commands for filling color. */
    private string $fillColor = '0 g';

    /** User-defined spot colors. */
    private array $spotColors = [];

    /** Commands for text color. */
    private string $textColor = '0 g';

    /** Append the spot colors to the document. */
    protected function appendSpotColors(): void
    {
        foreach ($this->spotColors as $color => $configuration) {
            $this->_newobj();
            $this->spotColors[$color]['n'] = $this->objectNumber;

            $black   = $configuration['K'] / 100;
            $cyan    = $configuration['C'] / 100;
            $color   = str_replace(' ', '#20', $color);
            $magenta = $configuration['M'] / 100;
            $yellow  = $configuration['Y'] / 100;

            $out = '[/Separation /' . $color . ' /DeviceCMYK << /Range [0 1 0 1 0 1 0 1] /C0 [0 0 0 0]';
            $out .= ' ' . sprintf('/C1 [%F %F %F %F] ', $cyan, $magenta, $yellow, $black);
            $out .= ' /FunctionType 2 /Domain [0 1] /N 1>>]';
            $out .= "\n" . 'endobj';

            $this->append($out);
        }
    }

    /**
     * Returns an array (RGB or CMYK) from a html color name, or a six-digit (i.e. #3FE5AA), or three-digit (i.e. #7FF) hexadecimal color,
     * or a javascript color array, or javascript color name.
     * @param string $htmlColor  HTML color
     * @param array  $spotColors the defined spot colors.
     * @param array  $default    the default color array
     * @return array
     */
    private function colorFromHTML(string $htmlColor, array &$spotColors, array $default = ['R' => 128, 'G' => 128, 'B' => 128]): array
    {
        $color = preg_replace('/\s*/', '', $htmlColor); // remove extra spaces
        $color = strtolower($color);

        // javascript color array syntax
        if (str_contains($color, '[')) {
            $pattern = '/\[[\"\'](t|g|rgb|cmyk)[\"\'],?([0-9.]*),?([0-9.]*),?([0-9.]*),?([0-9.]*)]/';
            if (preg_match($pattern, $color, $m) > 0) {
                switch ($m[1]) {
                    case 'cmyk':
                    {
                        return [
                            'C' => max(0, min(100, floatval($m[2]) * 100)),
                            'M' => max(0, min(100, floatval($m[3]) * 100)),
                            'Y' => max(0, min(100, floatval($m[4]) * 100)),
                            'K' => max(0, min(100, floatval($m[5]) * 100)),
                        ];
                    }
                    case 'rgb':
                    {
                        return [
                            'R' => max(0, min(255, floatval($m[2]) * 255)),
                            'G' => max(0, min(255, floatval($m[3]) * 255)),
                            'B' => max(0, min(255, floatval($m[4]) * 255)),
                        ];
                    }
                    case 'g':
                    {
                        return ['G' => max(0, min(255, floatval($m[2]) * 255))];
                    }
                    case 't':
                    default:
                    {
                        return [];
                    }
                }
            }
        }

        $cmyk = str_starts_with($color, 'cmyk');
        $rbg  = str_starts_with($color, 'rbg');

        if (!$cmyk and !$rbg and ($position = strpos($color, '.')) !== false) {
            // remove class parent (i.e.: color.red)
            $color = substr($color, ($position + 1));
            if ($color == 'transparent') {
                // transparent (empty array)
                return [];
            }
        }

        if (!$color) {
            return $default;
        }

        if (str_starts_with($color, 'cmyk')) {
            $codes  = substr($color, 5);
            $codes  = str_replace(')', '', $codes);
            $return = explode(',', $codes);
            foreach ($return as $key => $val) {
                if (str_contains($val, '%')) {
                    // percentage
                    $return[$key] = (100 * intval($val) / 100);
                }
                else {
                    $return[$key] = intval($val);
                }
                // normalize value
                $return[$key] = max(0, min(100, $return[$key]));
            }
            return $return;
        }

        if (str_starts_with($color, 'rgb')) {
            $codes  = substr($color, 4);
            $codes  = str_replace(')', '', $codes);
            $return = explode(',', $codes);

            foreach ($return as $letter => $value) {
                // percentage
                if (str_contains($value, '%')) {
                    $return[$letter] = (255 * intval($value) / 100);
                }
                else {
                    $return[$letter] = intval($value);
                }
                // normalize value
                $return[$letter] = max(0, min(255, $return[$letter]));
            }

            return $return;
        }

        if (strlen($color) == 0) {
            return $default;
        }

        if ($color[0] === '#') {
            $colorCode = substr($color, 1);
        }
        else {
            if (!isset(self::WEB_COLORS[$color]) or !$colorCode = self::WEB_COLORS[$color]) {
                return $this->spotColor($htmlColor) ?: $default;
            }
        }

        // HEXADECIMAL REPRESENTATION
        switch (strlen($colorCode)) {
            // 3-digit RGB hexadecimal representation
            case 3:
                $red   = substr($colorCode, 0, 1);
                $green = substr($colorCode, 1, 1);
                $blue  = substr($colorCode, 2, 1);

                return [
                    'B' => max(0, min(255, hexdec($blue . $blue))),
                    'G' => max(0, min(255, hexdec($green . $green))),
                    'R' => max(0, min(255, hexdec($red . $red))),
                ];
            // 6-digit RGB hexadecimal representation
            case 6:
                return [
                    'B' => max(0, min(255, hexdec(substr($colorCode, 4, 2)))),
                    'G' => max(0, min(255, hexdec(substr($colorCode, 2, 2)))),
                    'R' => max(0, min(255, hexdec(substr($colorCode, 0, 2)))),
                ];
            // 8-digit CMYK hexadecimal representation
            case 8:
                return [
                    'C' => max(0, min(100, round(hexdec(substr($colorCode, 0, 2)) / 2.55))),
                    'K' => max(0, min(100, round(hexdec(substr($colorCode, 6, 2)) / 2.55))),
                    'M' => max(0, min(100, round(hexdec(substr($colorCode, 2, 2)) / 2.55))),
                    'Y' => max(0, min(100, round(hexdec(substr($colorCode, 4, 2)) / 2.55))),
                ];
            default:
                return $default;
        }
    }

    /**
     * Convert a color array into a string representation.
     * @param array $color
     * @return string
     */
    public static function colorStringFromArray(array $color): string
    {
        $color = array_values($color);
        switch (count($color)) {
            case 4:
            {
                $cyan    = max(0, min(100, floatval($color[0]))) / 100;
                $magenta = max(0, min(100, floatval($color[1]))) / 100;
                $yellow  = max(0, min(100, floatval($color[2]))) / 100;
                $key     = max(0, min(100, floatval($color[3]))) / 100;
                //  Cyan, Magenta, Yellow (Gelb) und Key
                return '[' . sprintf('%F %F %F %F', $cyan, $magenta, $yellow, $key) . ']';
            }
            case 3:
            {
                $blue  = max(0, min(255, floatval($color[2]))) / 255;
                $green = max(0, min(255, floatval($color[1]))) / 255;
                $red   = max(0, min(255, floatval($color[0]))) / 255;
                return '[' . sprintf('%F %F %F', $red, $green, $blue) . ']';
            }
            case 1:
            {
                // grayscale
                return '[' . sprintf('%F', (max(0, min(255, floatval($color[0]))) / 255)) . ']';
            }
            default:
                return '[]';
        }
    }

    /**
     * Convert color to javascript color.
     * @param string $color color name or "#RRGGBB"
     */
    private function colorToJavascript(string $color): string
    {
        if (str_starts_with($color, '#')) {
            $blue  = hexdec(substr($color, 5, 2)) / 255;
            $green = hexdec(substr($color, 3, 2)) / 255;
            $red   = hexdec(substr($color, 1, 2)) / 255;
            return sprintf("['RGB',%F,%F,%F]", $red, $green, $blue);
        }

        if (!in_array($color, self::JS_COLORS)) {
            // default transparent color
            $color = self::JS_COLORS[0];
        }

        return 'color.' . $color;
    }

    /**
     * Sets the spot color for the specified context and generates a PDF command for the same.
     * @param string $color
     * @param string $context
     * @param float  $tint intensity of the color 0 to 100, where 100 is full intensity (default)
     * @return string
     * @throws Exception if the color or context are invalid
     * @see self::COLOR_CONTEXTS
     */
    public function contextColor(string $color, string $context, float $tint = 100): string
    {
        if (!$spotColor = $this->spotColor($color)) {
            $this->Error('Undefined spot color: ' . $color . ', you must add it using the spotColor() method.');
        }
        elseif (!in_array($context, self::COLOR_CONTEXTS)) {
            $this->Error('Undefined color context: ' . $context . ', @see Colors::COLOR_CONTEXTS.');
        }

        $tint = max(0, min(100, $tint)) / 100;

        $command = sprintf('/CS%d ', $this->spotColors[$color]['i']);

        switch ($context) {
            case 'draw':
                $command           .= sprintf('CS %F SCN', $tint);
                $this->drawColor   = $command;
                $this->strokecolor = $spotColor;
                break;
            case 'fill':
                $command         .= sprintf('cs %F scn', $tint);
                $this->fillColor = $command;
                $this->bgcolor   = $spotColor;
                break;
            case 'text':
                $command         .= sprintf('cs %F scn', $tint);
                $this->textColor = $command;
                $this->fgcolor   = $spotColor;
                break;
        }

        $this->differentColors = ($this->fillColor != $this->textColor);

        if ($this->state === self::READY) {
            $this->append($command);
        }

        if ($this->xObject) {
            $this->xObjects[$this->xObjectID]['spotColors'][$color] = $this->spotColors[$color];
        }

        return $command;
    }

    /**
     * Sets the spot color for drawing operations (lines, rectangles and cell borders) and generates a PDF command for the same.
     * @param string $color
     * @param float  $tint intensity of the color 0 to 100, where 100 is full intensity (default)
     * @return string
     * @throws Exception if the color or context are invalid
     * @see   spotColor(), fillColor(), textColor()
     */
    public function drawColor(string $color, float $tint = 100): string
    {
        return $this->contextColor($color, 'draw', $tint);
    }

    /**
     * Sets the spot color for filling operations (filled rectangles and cell backgrounds) and generates a PDF command for the same.
     * @param string $color
     * @param float  $tint intensity of the color 0 to 100, where 100 is full intensity (default)
     * @return string
     * @throws Exception if the color or context are invalid
     * @see   spotColor(), drawColor(), textColor()
     */
    public function fillColor(string $color, float $tint = 100): string
    {
        return $this->contextColor($color, 'fill');
    }

    /**
     * Getter / setter for spot colors.
     * @param string  $name          name of the spot color
     * @param float[] $configuration the CMYK color definition to add
     * @return array
     */
    public function spotColor(string $name, array $configuration = []): array
    {
        // Previously defined colors
        if (isset($this->spotColors[$name])) {
            return $this->spotColors[$name];
        }

        // Turn the given name into a lc index for referencing pre-defined colors and storing new ones.
        $color = preg_replace('/\s*/', '', $name);
        $color = strtolower($color);
        $new   = count($this->spotColors) + 1;

        // Translates a pre-defined color => into a user-defined one
        if (isset(self::SPOT_COLORS[$color])) {
            $this->spotColors[$color] = [
                'C'    => self::SPOT_COLORS[$color][0],
                'M'    => self::SPOT_COLORS[$color][1],
                'Y'    => self::SPOT_COLORS[$color][2],
                'K'    => self::SPOT_COLORS[$color][3],
                'name' => self::SPOT_COLORS[$color][4],
                'i'    => $new
            ];

            return $this->spotColors[$color];
        }

        // CMYK values are the minimum
        if (!$configuration or count($configuration) < 4) {
            return $configuration;
        }

        $configuration = array_map('floatval', $configuration);

        $this->spotColors[$color] = [
            'C'    => $configuration[0],
            'M'    => $configuration[1],
            'Y'    => $configuration[2],
            'K'    => $configuration[3],
            'name' => $name,
            'i'    => $new
        ];

        return $this->spotColors[$color];
    }

    /**
     * Returns the array of user-defined spot colors.
     * @return array
     */
    public function spotColors(): array
    {
        return $this->spotColors;
    }

    /**
     * Sets the spot color for text and generates a PDF command for the same.
     * @param string $color
     * @param float  $tint intensity of the color 0 to 100, where 100 is full intensity (default)
     * @return string
     * @throws Exception if the color or context are invalid
     * @see   spotColor(), drawColor(), fillColor()
     */
    public function textColor(string $color, float $tint = 100): string
    {
        return $this->contextColor('text', $color, $tint);
    }
}