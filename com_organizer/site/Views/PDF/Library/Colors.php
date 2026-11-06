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

/** PHP color trait. */
trait Colors
{
//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

    /** Current background color. */
    protected array $bgColor = ['R' => 255, 'G' => 255, 'B' => 255];

    /** Indicates whether fill and text colors are different. */
    protected bool $differentColors = false;

    /** Commands for drawing color. */
    protected string $drawColor = '0 G';

    /** Current foreground color. */
    protected array $fgColor = ['R' => 0, 'G' => 0, 'B' => 0];

    /** Commands for filling color. */
    protected string $fillColor = '0 g';

    /** @var int[] RGB */
    protected array $footerLineColor = self::BLACK;

    /** @var int[] RGB */
    protected array $footerTextColor = self::BLACK;

    /** @var int[] RGB */
    protected array $headerLineColor = self::BLACK;

    /** @var int[] RGB */
    protected array $headerTextColor = self::BLACK;

    /** @var int[] RGB */
    protected array $linkColor = self::BLUE;

    /** @var array User defined [CMYK + name + objectNumber] color configurations. */
    protected array $spotColors = [];

    /** Current stroke color. */
    protected array $strokeColor = ['R' => 0, 'G' => 0, 'B' => 0];

    /** Commands for text color. */
    protected string $textColor = '0 g';

    /** Append the spot colors to the document. */
    protected function appendSpotColors(): void
    {
        foreach ($this->spotColors as $color => $configuration) {
            $this->_newobj();
            $this->spotColors[$color]['n'] = $this->n;

            $black   = $configuration['K'] / 100;
            $color   = str_replace(' ', '#20', $color);
            $cyan    = $configuration['C'] / 100;
            $magenta = $configuration['M'] / 100;
            $yellow  = $configuration['Y'] / 100;

            $out = '[/Separation /' . $color . ' /DeviceCMYK << /Range [0 1 0 1 0 1 0 1] /C0 [0 0 0 0]';
            $out .= ' ' . sprintf('/C1 [%F %F %F %F] ', $cyan, $magenta, $yellow, $black);
            $out .= ' /FunctionType 2 /Domain [0 1] /N 1>>]';
            $out .= "\n" . 'endobj';

            $this->_out($out);
        }
    }

    /**
     * Convert a color array into its string representation.
     * @param array $color
     * @return string
     */
    public function colorArrayToString(array $color): string
    {
        $color = array_values($color);
        switch (count($color)) {
            // grayscale
            case 1:
                $gray = max(0, min(255, (float) $color[0]) / 255);
                return "[$gray]";
            // RGB
            case 3:
                $blue  = max(0, min(255, floatval($color[2]))) / 255;
                $green = max(0, min(255, floatval($color[1]))) / 255;
                $red   = max(0, min(255, floatval($color[0]))) / 255;
                return "[$red $blue $green]";
            // CMYK
            case 4:
                $cyan    = max(0, min(100, floatval($color[0]))) / 100;
                $key     = max(0, min(100, floatval($color[3]))) / 100;
                $magenta = max(0, min(100, floatval($color[1]))) / 100;
                $yellow  = max(0, min(100, floatval($color[2]))) / 100;
                return "[$cyan $magenta $yellow $key]";
            default:
                return '[]';
        }
    }

    /**
     * Convert color to javascript color.
     * @param string $color color name or "#RRGGBB"
     */
    public function colorToJavascript(string $color): string
    {
        // Hexadecimal values get an entirely different format.
        if (str_starts_with($color, '#')) {
            $blue  = hexdec(substr($color, 5, 2)) / 255;
            $green = hexdec(substr($color, 3, 2)) / 255;
            $red   = hexdec(substr($color, 1, 2)) / 255;
            return sprintf("['RGB',%F,%F,%F]", $red, $green, $blue);
        }

        // Default to transparent if name is not associated with a predefined value set.
        $color = in_array($color, self::JS_COLORS) ? $color : self::JS_COLORS[0];

        return 'color.' . $color;
    }

    /**
     * Applies the color to the specified context and generates a PDF command for the same.
     * @param string $color
     * @param string $context
     * @param float  $tint intensity of the color 0 to 100, where 100 is full intensity (default)
     * @return string
     * @see self::COLOR_CONTEXTS
     */
    public function contextSpotColor(string $color, string $context, float $tint = 100): string
    {
        if ($spotColor = $this->spotColor($color) or !in_array($context, self::COLOR_CONTEXTS)) {
            return '';
        }

        $command = sprintf('/CS%d ', $this->spotColors[$color]['i']);
        $tint    = max(0, min(100, $tint)) / 100;

        switch ($context) {
            case 'draw':
                $command .= sprintf('CS %F SCN', $tint);

                $this->drawColor   = $command;
                $this->strokeColor = $spotColor;
                break;
            case 'fill':
                $command .= sprintf('cs %F scn', $tint);

                $this->bgColor   = $spotColor;
                $this->fillColor = $command;
                break;
            case 'text':
                $command .= sprintf('cs %F scn', $tint);

                $this->fgColor   = $spotColor;
                $this->textColor = $command;
                break;
        }

        $this->differentColors = ($this->fillColor != $this->textColor);

        if ($this->state === self::READY) {
            $this->_out($command);
        }

        if ($this->inxobj) {
            // we are inside an XObject template
            $this->xobjects[$this->xobjid]['spot_colors'][$color] = $this->spotColors[$color];
        }

        return $command;
    }

    /**
     * Sets the spot color for drawing operations (lines, rectangles and cell borders) and generates a PDF command for the same.
     * @param string $color
     * @param float  $tint intensity of the color 0 to 100, where 100 is full intensity (default)
     * @return string
     * @see   spotColor(), fillSpotColor(), textSpotColor()
     */
    public function drawSpotColor(string $color, float $tint = 100): string
    {
        return $this->contextSpotColor($color, 'draw', $tint);
    }

    /**
     * Sets the spot color for filling operations (filled rectangles and cell backgrounds) and generates a PDF command for the same.
     * @param string $color
     * @param float  $tint intensity of the color 0 to 100, where 100 is full intensity (default)
     * @return string
     * @see   spotColor(), drawSpotColor(), textSpotColor()
     */
    public function fillSpotColor(string $color, float $tint = 100): string
    {
        return $this->contextSpotColor($color, 'fill', $tint);
    }

    /**
     * Converts a HTML color string into an array (CMYK, RGB, Grayscale or Transparent).
     * @param string $htmlColor
     * @param array  $defaultColor
     * @return array
     */
    public function htmlToColor(string $htmlColor, array $defaultColor = ['R' => 128, 'G' => 128, 'B' => 128]): array
    {
        $color = preg_replace('/\s*/', '', $htmlColor);
        $color = strtolower($color);

        $cmyk = str_starts_with($color, 'cmyk');
        $rbg  = str_starts_with($color, 'rbg');

        // check for javascript color array syntax
        if (str_contains($color, '[')) {
            $pattern = '/\[[\"\'](t|g|rgb|cmyk)[\"\'],?([0-9.]*),?([0-9.]*),?([0-9.]*),?([0-9.]*)]/';
            if (preg_match($pattern, $color, $matches) > 0) {
                return match ($matches[1]) {
                    'cmyk' => [
                        'C' => max(0, min(100, floatval($matches[2]) * 100)),
                        'M' => max(0, min(100, floatval($matches[3]) * 100)),
                        'Y' => max(0, min(100, floatval($matches[4]) * 100)),
                        'K' => max(0, min(100, floatval($matches[5]) * 100)),
                    ],
                    'rgb' => [
                        'R' => max(0, min(255, floatval($matches[2]) * 255)),
                        'G' => max(0, min(255, floatval($matches[3]) * 255)),
                        'B' => max(0, min(255, floatval($matches[4]) * 255)),
                    ],
                    'g' => ['G' => max(0, min(255, floatval($matches[2]) * 255))],
                    default => [],
                };
            }
        }

        // Javascript formatted: 'color.<color name>' => reduce to <color name>
        if (!$cmyk and !$rbg and ($dotPosition = strpos($color, '.')) !== false) {
            $color = substr($color, $dotPosition + 1);
            // transparent doesn't need value resolution
            if ($color === 'transparent') {
                return [];
            }
        }

        if (!$color) {
            return $defaultColor;
        }

        if ($cmyk) {
            $codes  = substr($color, 5);
            $codes  = str_replace(')', '', $codes);
            $return = explode(',', $codes);
            foreach ($return as $key => $value) {
                $return[$key] = max(0, min(100, (int) $value));
            }
            return $return;
        }

        if ($rbg) {
            $codes  = substr($color, 4);
            $codes  = str_replace(')', '', $codes);
            $return = explode(',', $codes);
            foreach ($return as $key => $value) {
                $integer      = (int) $value;
                $return[$key] = strpos($value, '%') > 0 ? (255 * $integer / 100) : $integer;
                $return[$key] = max(0, min(255, $return[$key]));
            }
            return $return;
        }

        // CMYK ARRAY
        if (str_starts_with($color, '#')) {
            $colorCode = substr($color, 1);
        }
        else {
            $colorCode = self::WEB_COLORS[$color] ?: false;
            if (!$colorCode) {
                return self::spotColor($htmlColor) ?: $defaultColor;
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
                return $defaultColor;
        }
    }

    /**
     * Getter / setter for spot colors.
     * @param string  $name          name of the spot color
     * @param float[] $configuration the CMYK color definition to add
     * @return array
     */
    public function spotColor(string $name, array $configuration = []): array
    {
        if (isset($this->spotColors[$name])) {
            return $this->spotColors[$name];
        }

        // Turn the given name into a lc index for referencing pre-defined colors and storing new ones.
        $color = preg_replace('/\s*/', '', $name);
        $color = strtolower($color);
        $new   = count($this->spotColors) + 1;

        if (isset(self::SPOT_COLORS[$color])) {
            $predefined               = self::SPOT_COLORS[$color];
            $this->spotColors[$color] = [
                'C'    => $predefined[0],
                'M'    => $predefined[1],
                'Y'    => $predefined[2],
                'K'    => $predefined[3],
                'name' => $predefined[4],
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
     * @see   spotColor(), drawSpotColor(), fillSpotColor()
     */
    public function textSpotColor(string $color, float $tint = 100): string
    {
        return $this->contextSpotColor($color, 'text', $tint);
    }


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


// waiting on append value default value inversion
    /**
     * Applies the color to the context, returns the color command, empty if not appended.
     * @param string $context
     * @param float  $value1 gray level for single color, red color for RGB (0-255), or cyan color for CMYK (0-100)
     * @param float  $value2 green color for RGB (0-255) or magenta color for CMYK (0-100)
     * @param float  $value3 blue color for RGB (0-255) or yellow color for CMYK (0-100)
     * @param float  $value4 black (key) color for CMYK (0-100)
     * @param bool   $append appends the command to the document, defaults to true
     * @param string $name   spot color name (if any)
     * @return string
     * @see self::COLOR_CONTEXTS
     */
    public function setColor(string $context, float $value1 = 0, float $value2 = -1, float $value3 = -1, float $value4 = -1, bool $append = false, string $name = ''): string
    {
        if (!in_array($context, self::COLOR_CONTEXTS)) {
            return '';
        }

        // set color by case
        $suffix = '';

        if ($value4 === -1) {
            if ($value2 === -1 and $value3 === -1) {
                // Grey scale
                $value1   = max(0, min(255, $value1));
                $intcolor = ['G' => $value1];
                $pdfcolor = sprintf('%F ', ($value1 / 255));
                $suffix   = 'g';
            }
            // RGB
            else {
                $value1   = max(0, min(255, $value1));
                $value2   = max(0, min(255, $value2));
                $value3   = max(0, min(255, $value3));
                $intcolor = ['R' => $value1, 'G' => $value2, 'B' => $value3];
                $pdfcolor = sprintf('%F %F %F ', ($value1 / 255), ($value2 / 255), ($value3 / 255));
                $suffix   = 'rg';
            }
        }
        else {
            $value1 = max(0, min(100, $value1));
            $value2 = max(0, min(100, $value2));
            $value3 = max(0, min(100, $value3));
            $value4 = max(0, min(100, $value4));
            if (empty($name)) {
                // CMYK
                $intcolor = ['C' => $value1, 'M' => $value2, 'Y' => $value3, 'K' => $value4];
                $pdfcolor = sprintf('%F %F %F %F ', ($value1 / 100), ($value2 / 100), ($value3 / 100), ($value4 / 100));
                $suffix   = 'k';
            }
            else {
                // SPOT COLOR
                $intcolor = ['C' => $value1, 'M' => $value2, 'Y' => $value3, 'K' => $value4, 'name' => $name];
                $this->spotColor($name, [$value1, $value2, $value3, $value4]);
                $pdfcolor = $this->contextSpotColor($name, $context);
            }
        }
        switch ($context) {
            case 'draw':
            {
                $pdfcolor          .= strtoupper($suffix);
                $this->drawColor   = $pdfcolor;
                $this->strokeColor = $intcolor;
                break;
            }
            case 'fill':
            {
                $pdfcolor        .= $suffix;
                $this->fillColor = $pdfcolor;
                $this->bgColor   = $intcolor;
                break;
            }
            case 'text':
            {
                $pdfcolor        .= $suffix;
                $this->textColor = $pdfcolor;
                $this->fgColor   = $intcolor;
                break;
            }
        }
        $this->differentColors = ($this->fillColor != $this->textColor);
        if (($context != 'text') and ($this->state == 2) and $context !== 0) {
            if (!$append) {
                $this->_out($pdfcolor);
            }
            return $pdfcolor;
        }
        return '';
    }


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Set the color array for the specified type ('draw', 'fill', 'text').
     * It can be expressed in RGB, CMYK or GRAY SCALE components.
     * The method can be called before the first page is created and the value is retained from page to page.
     * @param string $type  Type of object affected by this color: ('draw', 'fill', 'text').
     * @param array  $color Array of colors (1=gray, 3=RGB, 4=CMYK or 5=spotcolor=CMYK+name values).
     * @param bool   $ret   If true do not send the PDF command.
     * @return string The PDF command or empty string.
     */
    public function setColorArray($type, $color, $ret = false)
    {
        if (is_array($color)) {
            $color = array_values($color);
            // component: grey, RGB red or CMYK cyan
            $c = isset($color[0]) ? $color[0] : -1;
            // component: RGB green or CMYK magenta
            $m = isset($color[1]) ? $color[1] : -1;
            // component: RGB blue or CMYK yellow
            $y = isset($color[2]) ? $color[2] : -1;
            // component: CMYK black
            $k = isset($color[3]) ? $color[3] : -1;
            // color name
            $name = isset($color[4]) ? $color[4] : '';
            if ($c >= 0) {
                return $this->setColor($type, $c, $m, $y, $k, $ret, $name);
            }
        }
        return '';
    }

    /**
     * Defines the color used for all drawing operations (lines, rectangles and cell borders).
     * It can be expressed in RGB, CMYK or GRAY SCALE components.
     * The method can be called before the first page is created and the value is retained from page to page.
     * @param array $color Array of colors (1, 3 or 4 values).
     * @param bool  $ret   If true do not send the PDF command.
     * @return string the PDF command
     * @see   SetDrawColor()
     */
    public function setDrawColorArray($color, $ret = false)
    {
        return $this->setColorArray('draw', $color, $ret);
    }

    /**
     * Defines the color used for all filling operations (filled rectangles and cell backgrounds).
     * It can be expressed in RGB, CMYK or GRAY SCALE components.
     * The method can be called before the first page is created and the value is retained from page to page.
     * @param array $color Array of colors (1, 3 or 4 values).
     * @param bool  $ret   If true do not send the PDF command.
     * @see   SetFillColor()
     */
    public function setFillColorArray($color, $ret = false)
    {
        return $this->setColorArray('fill', $color, $ret);
    }

    /**
     * Defines the color used for text. It can be expressed in RGB components or gray scale.
     * The method can be called before the first page is created and the value is retained from page to page.
     * @param array $color Array of colors (1, 3 or 4 values).
     * @param bool  $ret   If true do not send the PDF command.
     * @see   SetFillColor()
     */
    public function setTextColorArray($color, $ret = false)
    {
        return $this->setColorArray('text', $color, $ret);
    }

    /**
     * Defines the color used for all drawing operations (lines, rectangles and cell borders). It can be expressed in RGB components or gray scale. The method can be called before the first page is created and the value is retained from page to page.
     * @param float  $col1 GRAY level for single color, or Red color for RGB (0-255), or CYAN color for CMYK (0-100).
     * @param float  $col2 GREEN color for RGB (0-255), or MAGENTA color for CMYK (0-100).
     * @param float  $col3 BLUE color for RGB (0-255), or YELLOW color for CMYK (0-100).
     * @param float  $col4 KEY (BLACK) color for CMYK (0-100).
     * @param bool   $ret  If true do not send the command.
     * @param string $name spot color name (if any)
     * @return string the PDF command
     * @see   SetDrawColorArray(), SetFillColor(), SetTextColor(), Line(), Rect(), Cell(), MultiCell()
     */
    public function setDrawColor($col1 = 0, $col2 = -1, $col3 = -1, $col4 = -1, $ret = false, $name = '')
    {
        return $this->setColor('draw', $col1, $col2, $col3, $col4, $ret, $name);
    }

    /**
     * Defines the color used for all filling operations (filled rectangles and cell backgrounds). It can be expressed in RGB components or gray scale. The method can be called before the first page is created and the value is retained from page to page.
     * @param float  $col1 GRAY level for single color, or Red color for RGB (0-255), or CYAN color for CMYK (0-100).
     * @param float  $col2 GREEN color for RGB (0-255), or MAGENTA color for CMYK (0-100).
     * @param float  $col3 BLUE color for RGB (0-255), or YELLOW color for CMYK (0-100).
     * @param float  $col4 KEY (BLACK) color for CMYK (0-100).
     * @param bool   $ret  If true do not send the command.
     * @param string $name Spot color name (if any).
     * @return string The PDF command.
     * @see   SetFillColorArray(), SetDrawColor(), SetTextColor(), Rect(), Cell(), MultiCell()
     */
    public function setFillColor($col1 = 0, $col2 = -1, $col3 = -1, $col4 = -1, $ret = false, $name = '')
    {
        return $this->setColor('fill', $col1, $col2, $col3, $col4, $ret, $name);
    }

    /**
     * Defines the color used for text. It can be expressed in RGB components or gray scale. The method can be called before the first page is created and the value is retained from page to page.
     * @param float  $col1 GRAY level for single color, or Red color for RGB (0-255), or CYAN color for CMYK (0-100).
     * @param float  $col2 GREEN color for RGB (0-255), or MAGENTA color for CMYK (0-100).
     * @param float  $col3 BLUE color for RGB (0-255), or YELLOW color for CMYK (0-100).
     * @param float  $col4 KEY (BLACK) color for CMYK (0-100).
     * @param bool   $ret  If true do not send the command.
     * @param string $name Spot color name (if any).
     * @return string Empty string.
     * @see   SetTextColorArray(), SetDrawColor(), SetFillColor(), Text(), Cell(), MultiCell()
     */
    public function setTextColor($col1 = 0, $col2 = -1, $col3 = -1, $col4 = -1, $ret = false, $name = '')
    {
        return $this->setColor('text', $col1, $col2, $col3, $col4, $ret, $name);
    }

    /**
     * Paints color transition registration bars
     * @param float  $x          abscissa of the top left corner of the rectangle.
     * @param float  $y          ordinate of the top left corner of the rectangle.
     * @param float  $w          width of the rectangle.
     * @param float  $h          height of the rectangle.
     * @param bool   $transition if true prints tcolor transitions to white.
     * @param bool   $vertical   if true prints bar vertically.
     * @param string $colors     colors to print separated by comma. Valid values are: A,W,R,G,B,C,M,Y,K,RGB,CMYK,ALL,ALLSPOT,<SPOT_COLOR_NAME>. Where: A = grayscale black, W = grayscale white, R = RGB red, G RGB green, B RGB blue, C = CMYK cyan, M = CMYK magenta, Y = CMYK yellow, K = CMYK key/black, RGB = RGB registration color, CMYK = CMYK registration color, ALL = Spot registration color, ALLSPOT = print all defined spot colors, <SPOT_COLOR_NAME> = name of the spot color to print.
     * @author Nicola Asuni
     */
    public function colorRegistrationBar($x, $y, $w, $h, $transition = true, $vertical = false, $colors = 'A,R,G,B,C,M,Y,K')
    {
        if (strpos($colors, 'ALLSPOT') !== false) {
            // expand spot colors
            $spot_colors = '';
            foreach ($this->spotColors as $spot_color_name => $v) {
                $spot_colors .= ',' . $spot_color_name;
            }
            if (!empty($spot_colors)) {
                $spot_colors = substr($spot_colors, 1);
                $colors      = str_replace('ALLSPOT', $spot_colors, $colors);
            }
            else {
                $colors = str_replace('ALLSPOT', 'NONE', $colors);
            }
        }
        $bars    = explode(',', $colors);
        $numbars = count($bars); // number of bars to print
        if ($numbars <= 0) {
            return;
        }
        // set bar measures
        if ($vertical) {
            $coords = [0, 0, 0, 1];
            $wb     = $w / $numbars; // bar width
            $hb     = $h; // bar height
            $xd     = $wb; // delta x
            $yd     = 0; // delta y
        }
        else {
            $coords = [1, 0, 0, 0];
            $wb     = $w; // bar width
            $hb     = $h / $numbars; // bar height
            $xd     = 0; // delta x
            $yd     = $hb; // delta y
        }
        $xb = $x;
        $yb = $y;
        foreach ($bars as $col) {
            switch ($col) {
                // set transition colors
                case 'A':
                { // BLACK (GRAYSCALE)
                    $col_a = [255];
                    $col_b = [0];
                    break;
                }
                case 'W':
                { // WHITE (GRAYSCALE)
                    $col_a = [0];
                    $col_b = [255];
                    break;
                }
                case 'R':
                { // RED (RGB)
                    $col_a = [255, 255, 255];
                    $col_b = [255, 0, 0];
                    break;
                }
                case 'G':
                { // GREEN (RGB)
                    $col_a = [255, 255, 255];
                    $col_b = [0, 255, 0];
                    break;
                }
                case 'B':
                { // BLUE (RGB)
                    $col_a = [255, 255, 255];
                    $col_b = [0, 0, 255];
                    break;
                }
                case 'C':
                { // CYAN (CMYK)
                    $col_a = [0, 0, 0, 0];
                    $col_b = [100, 0, 0, 0];
                    break;
                }
                case 'M':
                { // MAGENTA (CMYK)
                    $col_a = [0, 0, 0, 0];
                    $col_b = [0, 100, 0, 0];
                    break;
                }
                case 'Y':
                { // YELLOW (CMYK)
                    $col_a = [0, 0, 0, 0];
                    $col_b = [0, 0, 100, 0];
                    break;
                }
                case 'K':
                { // KEY - BLACK (CMYK)
                    $col_a = [0, 0, 0, 0];
                    $col_b = [0, 0, 0, 100];
                    break;
                }
                case 'RGB':
                { // BLACK REGISTRATION (RGB)
                    $col_a = [255, 255, 255];
                    $col_b = [0, 0, 0];
                    break;
                }
                case 'CMYK':
                { // BLACK REGISTRATION (CMYK)
                    $col_a = [0, 0, 0, 0];
                    $col_b = [100, 100, 100, 100];
                    break;
                }
                case 'ALL':
                { // SPOT COLOR REGISTRATION
                    $col_a = [0, 0, 0, 0, 'None'];
                    $col_b = [100, 100, 100, 100, 'All'];
                    break;
                }
                case 'NONE':
                { // SKIP THIS COLOR
                    $col_a = [0, 0, 0, 0, 'None'];
                    $col_b = [0, 0, 0, 0, 'None'];
                    break;
                }
                default:
                { // SPECIFIC SPOT COLOR NAME
                    $col_a = [0, 0, 0, 0, 'None'];
                    $col_b = $this->spotColor($col);
                    if ($col_b === false) {
                        // in case of error defaults to the registration color
                        $col_b = [100, 100, 100, 100, 'All'];
                    }
                    break;
                }
            }
            if ($col != 'NONE') {
                if ($transition) {
                    // color gradient
                    $this->LinearGradient($xb, $yb, $wb, $hb, $col_a, $col_b, $coords);
                }
                else {
                    $this->setFillColorArray($col_b);
                    // colored rectangle
                    $this->Rect($xb, $yb, $wb, $hb, 'F', []);
                }
                $xb += $xd;
                $yb += $yd;
            }
        }
    }

}