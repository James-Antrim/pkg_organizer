<?php

namespace THM\Organizer\Views\PDF\Library;

/** Storage space for currently unused functions. */
trait Bloatware
{

////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////BITS-PROCESSED


//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////BITS-UNPROCESSED


    /**
     * Update the CIDToGIDMap string with a new value.
     * @param string $map CIDToGIDMap.
     * @param int    $cid CID value.
     * @param int    $gid GID value.
     * @return string CIDToGIDMap.
     * @author Nicola Asuni
     */
    public static function updateCIDtoGIDmap($map, $cid, $gid)
    {
        if (($cid >= 0) and ($cid <= 0xFFFF) and ($gid >= 0)) {
            if ($gid > 0xFFFF) {
                $gid -= 0x10000;
            }
            $map[($cid * 2)]       = chr($gid >> 8);
            $map[(($cid * 2) + 1)] = chr($gid & 0xFF);
        }
        return $map;
    }

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////COLORS-PROCESSED


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////COLORS-UNPROCESSED


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
                    $col_a = self::WHITE;
                    $col_b = [255, 0, 0];
                    break;
                }
                case 'G':
                { // GREEN (RGB)
                    $col_a = self::WHITE;
                    $col_b = [0, 255, 0];
                    break;
                }
                case 'B':
                { // BLUE (RGB)
                    $col_a = self::WHITE;
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
                    $col_a = self::WHITE;
                    $col_b = self::BLACK;
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
                    $this->fillColorArray($col_b);
                    // colored rectangle
                    $this->Rect($xb, $yb, $wb, $hb, 'F', []);
                }
                $xb += $xd;
                $yb += $yd;
            }
        }
    }


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////FONTS-PROCESSED


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////FONTS-UNPROCESSED

}