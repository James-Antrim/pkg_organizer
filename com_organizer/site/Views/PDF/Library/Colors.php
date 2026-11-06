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
    public function contextColor(string $color, string $context, float $tint = 100): string
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
        return $this->contextColor($color, 'draw', $tint);
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
        return $this->contextColor($color, 'fill', $tint);
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
        return $this->contextColor($color, 'text', $tint);
    }


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Convert a color array into a string representation.
     * @param array $c Array of colors.
     * @return string The color array representation.
     */
    public function getColorStringFromArray($c)
    {
        $c     = array_values($c);
        $color = '[';
        switch (count($c)) {
            case 4:
            {
                // CMYK
                $color .= sprintf('%F %F %F %F', (max(0, min(100, floatval($c[0]))) / 100), (max(0, min(100, floatval($c[1]))) / 100), (max(0, min(100, floatval($c[2]))) / 100), (max(0, min(100, floatval($c[3]))) / 100));
                break;
            }
            case 3:
            {
                // RGB
                $color .= sprintf('%F %F %F', (max(0, min(255, floatval($c[0]))) / 255), (max(0, min(255, floatval($c[1]))) / 255), (max(0, min(255, floatval($c[2]))) / 255));
                break;
            }
            case 1:
            {
                // grayscale
                $color .= sprintf('%F', (max(0, min(255, floatval($c[0]))) / 255));
                break;
            }
        }
        $color .= ']';
        return $color;
    }
}