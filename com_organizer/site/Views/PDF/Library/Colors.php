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
        $color = in_array($color, self::JS_COLORS) ? $color: self::JS_COLORS[0];

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
     * Returns an array (RGB or CMYK) from an html color name, or a six-digit (i.e. #3FE5AA), or three-digit (i.e. #7FF) hexadecimal color, or a javascript color array, or javascript color name.
     * @param string $hcolor HTML color.
     * @param array  $spotc  Reference to an array of spot colors.
     * @param array  $defcol Color to return in case of error.
     * @return array|false RGB or CMYK color, or false in case of error.
     */
    public function convertHTMLColorToDec($hcolor, &$spotc, $defcol = array('R' => 128, 'G' => 128, 'B' => 128))
    {
        $color = preg_replace('/[\s]*/', '', $hcolor); // remove extra spaces
        $color = strtolower($color);
        // check for javascript color array syntax
        if (strpos($color, '[') !== false) {
            if (preg_match('/[\[][\"\'](t|g|rgb|cmyk)[\"\'][\,]?([0-9\.]*)[\,]?([0-9\.]*)[\,]?([0-9\.]*)[\,]?([0-9\.]*)[\]]/', $color, $m) > 0) {
                $returncolor = [];
                switch ($m[1]) {
                    case 'cmyk':
                    {
                        // RGB
                        $returncolor['C'] = max(0, min(100, (floatval($m[2]) * 100)));
                        $returncolor['M'] = max(0, min(100, (floatval($m[3]) * 100)));
                        $returncolor['Y'] = max(0, min(100, (floatval($m[4]) * 100)));
                        $returncolor['K'] = max(0, min(100, (floatval($m[5]) * 100)));
                        break;
                    }
                    case 'rgb':
                    {
                        // RGB
                        $returncolor['R'] = max(0, min(255, (floatval($m[2]) * 255)));
                        $returncolor['G'] = max(0, min(255, (floatval($m[3]) * 255)));
                        $returncolor['B'] = max(0, min(255, (floatval($m[4]) * 255)));
                        break;
                    }
                    case 'g':
                    {
                        // grayscale
                        $returncolor['G'] = max(0, min(255, (floatval($m[2]) * 255)));
                        break;
                    }
                    case 't':
                    default:
                    {
                        // transparent (empty array)
                        break;
                    }
                }
                return $returncolor;
            }
        }
        elseif ((substr($color, 0, 4) != 'cmyk') and (substr($color, 0, 3) != 'rgb') and (($dotpos = strpos($color, '.')) !== false)) {
            // remove class parent (i.e.: color.red)
            $color = substr($color, ($dotpos + 1));
            if ($color == 'transparent') {
                // transparent (empty array)
                return [];
            }
        }
        if (strlen($color) == 0) {
            return $defcol;
        }
        // RGB ARRAY
        if (substr($color, 0, 3) == 'rgb') {
            $codes       = substr($color, 4);
            $codes       = str_replace(')', '', $codes);
            $returncolor = explode(',', $codes);
            foreach ($returncolor as $key => $val) {
                if (strpos($val, '%') > 0) {
                    // percentage
                    $returncolor[$key] = (255 * intval($val) / 100);
                }
                else {
                    $returncolor[$key] = intval($val);
                }
                // normalize value
                $returncolor[$key] = max(0, min(255, $returncolor[$key]));
            }
            return $returncolor;
        }
        // CMYK ARRAY
        if (substr($color, 0, 4) == 'cmyk') {
            $codes       = substr($color, 5);
            $codes       = str_replace(')', '', $codes);
            $returncolor = explode(',', $codes);
            foreach ($returncolor as $key => $val) {
                if (strpos($val, '%') !== false) {
                    // percentage
                    $returncolor[$key] = (100 * intval($val) / 100);
                }
                else {
                    $returncolor[$key] = intval($val);
                }
                // normalize value
                $returncolor[$key] = max(0, min(100, $returncolor[$key]));
            }
            return $returncolor;
        }
        if ($color[0] != '#') {
            // COLOR NAME
            if (isset(self::WEB_COLORS[$color])) {
                // web color
                $color_code = self::WEB_COLORS[$color];
            }
            else {
                // spot color
                $returncolor = self::spotColor($hcolor);
                if ($returncolor === false) {
                    $returncolor = $defcol;
                }
                return $returncolor;
            }
        }
        else {
            $color_code = substr($color, 1);
        }
        // HEXADECIMAL REPRESENTATION
        switch (strlen($color_code)) {
            case 3:
            {
                // 3-digit RGB hexadecimal representation
                $r                = substr($color_code, 0, 1);
                $g                = substr($color_code, 1, 1);
                $b                = substr($color_code, 2, 1);
                $returncolor      = [];
                $returncolor['R'] = max(0, min(255, hexdec($r . $r)));
                $returncolor['G'] = max(0, min(255, hexdec($g . $g)));
                $returncolor['B'] = max(0, min(255, hexdec($b . $b)));
                break;
            }
            case 6:
            {
                // 6-digit RGB hexadecimal representation
                $returncolor      = [];
                $returncolor['R'] = max(0, min(255, hexdec(substr($color_code, 0, 2))));
                $returncolor['G'] = max(0, min(255, hexdec(substr($color_code, 2, 2))));
                $returncolor['B'] = max(0, min(255, hexdec(substr($color_code, 4, 2))));
                break;
            }
            case 8:
            {
                // 8-digit CMYK hexadecimal representation
                $returncolor      = [];
                $returncolor['C'] = max(0, min(100, round(hexdec(substr($color_code, 0, 2)) / 2.55)));
                $returncolor['M'] = max(0, min(100, round(hexdec(substr($color_code, 2, 2)) / 2.55)));
                $returncolor['Y'] = max(0, min(100, round(hexdec(substr($color_code, 4, 2)) / 2.55)));
                $returncolor['K'] = max(0, min(100, round(hexdec(substr($color_code, 6, 2)) / 2.55)));
                break;
            }
            default:
            {
                $returncolor = $defcol;
                break;
            }
        }
        return $returncolor;
    }

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