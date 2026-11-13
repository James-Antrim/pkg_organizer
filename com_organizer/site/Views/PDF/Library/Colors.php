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

            $this->append($out);
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
     * Applies the color to the context, returns the color command, empty if not appended.
     * @param string $context
     * @param float  $value1 Grayscale gray / RGB red (0-255), CMYK cyan (0-100)
     * @param float  $value2 RGB green (0-255), CMYK magenta (0-100)
     * @param float  $value3 RGB blue (0-255), CMYK yellow (0-100)
     * @param float  $value4 CMYK key/black (0-100)
     * @param bool   $append whether to append the command to the document
     * @param string $scName spot color name
     * @return string
     * @see self::COLOR_CONTEXTS
     */
    public function contextColor(string $context, float $value1 = 0, float $value2 = -1, float $value3 = -1, float $value4 = -1, bool $append = true, string $scName = ''): string
    {
        // set color by case
        $suffix = '';
        if ($value4 === -1.0) {
            $value1 = max(0, min(255, $value1));

            // Grayscale
            if ($value2 === -1.0 and $value3 === -1.0) {
                $color   = ['G' => $value1];
                $command = sprintf('%F ', ($value1 / 255));
                $suffix  = 'g';
            }
            // RGB
            else {
                $value2 = max(0, min(255, $value2));
                $value3 = max(0, min(255, $value3));

                $color   = ['R' => $value1, 'G' => $value2, 'B' => $value3];
                $command = sprintf('%F %F %F ', ($value1 / 255), ($value2 / 255), ($value3 / 255));
                $suffix  = 'rg';
            }

        }
        // CMYK
        else {
            $value1 = max(0, min(100, $value1));
            $value2 = max(0, min(100, $value2));
            $value3 = max(0, min(100, $value3));
            $value4 = max(0, min(100, $value4));

            // SPOT COLOR
            if ($scName) {
                $color = ['C' => $value1, 'M' => $value2, 'Y' => $value3, 'K' => $value4, 'name' => $scName];
                $this->spotColor($scName, [$value1, $value2, $value3, $value4]);
                $command = $this->contextSpotColor($scName, $context);
            }
            else {
                $color   = ['C' => $value1, 'M' => $value2, 'Y' => $value3, 'K' => $value4];
                $command = sprintf('%F %F %F %F ', ($value1 / 100), ($value2 / 100), ($value3 / 100), ($value4 / 100));
                $suffix  = 'k';
            }
        }

        switch ($context) {
            case 'draw':
                $command .= strtoupper($suffix);

                $this->drawColor   = $command;
                $this->strokeColor = $color;
                break;
            case 'fill':
                $command .= $suffix;

                $this->bgColor   = $color;
                $this->fillColor = $command;
                break;
            case 'text':
                $command .= $suffix;

                $this->fgColor   = $color;
                $this->textColor = $command;
                break;
        }

        $this->differentColors = ($this->fillColor != $this->textColor);

        if ($context === 'text' or $this->state !== self::READY) {
            return '';
        }

        if ($append) {
            $this->append($command);
        }
        return $command;
    }

    /**
     * Applies the color array to the context, returns the color command, empty if not appended.
     * @param string $context
     * @param array  $color  color configuration array: 1 => grayscale, 3 => RGB, 4 => CMYK, 5 => spotColor (CMYK & name values)
     * @param bool   $append appends the command to the document, defaults to true
     * @return string
     * @see self::COLOR_CONTEXTS
     */
    public function contextColorArray(string $context, array $color, bool $append = true): string
    {
        $color = array_values($color);

        // Necessary for grayscale, RGB and CMYK resolution
        $value1 = $color[0] ?? -1;
        if ($value1 < 0) {
            return '';
        }

        return $this->contextColor($context, $value1, $color[1] ?? -1, $color[2] ?? -1, $color[3] ?? -1, $append, $color[4] ?? '');
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
            $this->append($command);
        }

        if ($this->inXObject) {
            // we are inside an XObject template
            $this->xObjects[$this->xObjectID]['spot_colors'][$color] = $this->spotColors[$color];
        }

        return $command;
    }

    /**
     * Applies the color to all drawing operations (lines, rectangles and cell borders), returns the color command, empty if not appended.
     * @param float  $value1 gray level for single color, red color for RGB (0-255), or cyan color for CMYK (0-100)
     * @param float  $value2 green color for RGB (0-255) or magenta color for CMYK (0-100)
     * @param float  $value3 blue color for RGB (0-255) or yellow color for CMYK (0-100)
     * @param float  $value4 black (key) color for CMYK (0-100)
     * @param bool   $append appends the command to the document, defaults to true
     * @param string $name   spot color name (if any)
     * @return string the PDF command
     * @see   contextColor()
     */
    public function drawColor(float $value1 = 0, float $value2 = -1, float $value3 = -1, float $value4 = -1, bool $append = true, string $name = ''): string
    {
        return $this->contextColor('draw', $value1, $value2, $value3, $value4, $append, $name);
    }

    /**
     * Applies the color array to all drawing operations (lines, rectangles and cell borders), returns the color command, empty if not appended.
     * @param array $color  color configuration array: 1 => grayscale, 3 => RGB, 4 => CMYK, 5 => spotColor (CMYK & name values)
     * @param bool  $append appends the command to the document, defaults to true
     * @return string
     * @see   contextColorArray(), drawColor())
     */
    public function drawColorArray(array $color, bool $append = true): string
    {
        return $this->contextColorArray('draw', $color, $append);
    }

    /**
     * Sets the spot color for drawing operations (lines, rectangles and cell borders) and generates a PDF command for the same.
     * @param string $color
     * @param float  $tint intensity of the color 0 to 100, where 100 is full intensity (default)
     * @return string
     * @see contextSpotColor(), spotColor()
     */
    public function drawSpotColor(string $color, float $tint = 100): string
    {
        return $this->contextSpotColor($color, 'draw', $tint);
    }

    /**
     * Applies the color to all filling operations (filled rectangles and cell backgrounds), returns the color command, empty if not appended.
     * @param float  $value1 gray level for single color, red color for RGB (0-255), or cyan color for CMYK (0-100)
     * @param float  $value2 green color for RGB (0-255) or magenta color for CMYK (0-100)
     * @param float  $value3 blue color for RGB (0-255) or yellow color for CMYK (0-100)
     * @param float  $value4 black (key) color for CMYK (0-100)
     * @param bool   $append appends the command to the document, defaults to true
     * @param string $name   spot color name (if any)
     * @return string The PDF command.
     * @see contextColor()
     */
    public function fillColor(float $value1 = 0, float $value2 = -1, float $value3 = -1, float $value4 = -1, bool $append = true, string $name = ''): string
    {
        return $this->contextColor('fill', $value1, $value2, $value3, $value4, $append, $name);
    }

    /**
     * Applies the color array to all filling operations (filled rectangles and cell backgrounds), returns the color command, empty if not appended.
     * @param array $color  color configuration array: 1 => grayscale, 3 => RGB, 4 => CMYK, 5 => spotColor (CMYK & name values)
     * @param bool  $append appends the command to the document, defaults to true
     * @return string
     * @see   contextColorArray(), fillColor()
     */
    public function fillColorArray(array $color, bool $append = true): string
    {
        return $this->contextColorArray('fill', $color, $append);
    }

    /**
     * Sets the spot color for filling operations (filled rectangles and cell backgrounds) and generates a PDF command for the same.
     * @param string $color
     * @param float  $tint intensity of the color 0 to 100, where 100 is full intensity (default)
     * @return string
     * @see contextSpotColor(), spotColor()
     */
    public function fillSpotColor(string $color, float $tint = 100): string
    {
        return $this->contextSpotColor($color, 'fill', $tint);
    }

    /**
     * Converts an HTML color string into an array (CMYK, RGB, Grayscale or Transparent).
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
     * Applies the color to text, returns the color command, empty if not appended.
     * @param float  $value1 gray level for single color, red color for RGB (0-255), or cyan color for CMYK (0-100)
     * @param float  $value2 green color for RGB (0-255) or magenta color for CMYK (0-100)
     * @param float  $value3 blue color for RGB (0-255) or yellow color for CMYK (0-100)
     * @param float  $value4 black (key) color for CMYK (0-100)
     * @param bool   $append appends the command to the document, defaults to true
     * @param string $name   spot color name (if any)
     * @return string
     * @see   contextColor()
     */
    public function textColor(float $value1 = 0, float $value2 = -1, float $value3 = -1, float $value4 = -1, bool $append = true, string $name = ''): string
    {
        return $this->contextColor('text', $value1, $value2, $value3, $value4, $append, $name);
    }

    /**
     * Applies the color array to text, returns the color command, empty if not appended.
     * @param array $color  color configuration array: 1 => grayscale, 3 => RGB, 4 => CMYK, 5 => spotColor (CMYK & name values)
     * @param bool  $append appends the command to the document, defaults to true
     * @return string
     * @see   contextColorArray(), textColor()
     */
    public function textColorArray(array $color, bool $append = true): string
    {
        return $this->contextColorArray('text', $color, $append);
    }

    /**
     * Sets the spot color for text and generates a PDF command for the same.
     * @param string $color
     * @param float  $tint intensity of the color 0 to 100, where 100 is full intensity (default)
     * @return string
     * @see contextSpotColor(), spotColor()
     */
    public function textSpotColor(string $color, float $tint = 100): string
    {
        return $this->contextSpotColor($color, 'text', $tint);
    }
}