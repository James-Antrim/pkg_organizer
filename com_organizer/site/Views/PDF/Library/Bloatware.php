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


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////TRANSFORMATIONS-PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////TRANSFORMATIONS-UNPROCESSED

    /**
     * Horizontal Mirroring.
     * @param float|null $x abscissa of the point. Default is current x position
     * @see   StartTransform(), StopTransform()
     */
    public function MirrorH($x = null)
    {
        $this->Scale(-100, 100, $x);
    }

    /**
     * Verical Mirroring.
     * @param float|null $y ordinate of the point. Default is current y position
     * @see   StartTransform(), StopTransform()
     */
    public function MirrorV($y = null)
    {
        $this->Scale(100, -100, null, $y);
    }

    /**
     * Point reflection mirroring.
     * @param float|null $x abscissa of the point. Default is current x position
     * @param float|null $y ordinate of the point. Default is current y position
     * @see   StartTransform(), StopTransform()
     */
    public function MirrorP($x = null, $y = null)
    {
        $this->Scale(-100, -100, $x, $y);
    }

    /**
     * Reflection against a straight line through point (x, y) with the gradient angle (angle).
     * @param float      $angle gradient angle of the straight line. Default is 0 (horizontal line).
     * @param float|null $x     abscissa of the point. Default is current x position
     * @param float|null $y     ordinate of the point. Default is current y position
     * @see   StartTransform(), StopTransform()
     */
    public function MirrorL($angle = 0, $x = null, $y = null)
    {
        $this->Scale(-100, 100, $x, $y);
        $this->Rotate(-2 * ($angle - 90), $x, $y);
    }

    /**
     * Rotate object.
     * @param float      $angle angle in degrees for counter-clockwise rotation
     * @param float|null $x     abscissa of the rotation center. Default is current x position
     * @param float|null $y     ordinate of the rotation center. Default is current y position
     * @see   StartTransform(), StopTransform()
     */
    public function Rotate($angle, $x = null, $y = null)
    {
        if (StaticMethods::empty_string($x)) {
            $x = $this->x;
        }
        if (StaticMethods::empty_string($y)) {
            $y = $this->y;
        }
        $y = ($this->height - $y) * self::FACTOR;
        $x *= self::FACTOR;
        //calculate elements of transformation matrix
        $tm    = [];
        $tm[0] = cos(deg2rad($angle));
        $tm[1] = sin(deg2rad($angle));
        $tm[2] = -$tm[1];
        $tm[3] = $tm[0];
        $tm[4] = $x + ($tm[1] * $y) - ($tm[0] * $x);
        $tm[5] = $y - ($tm[0] * $y) - ($tm[1] * $x);
        //rotate the coordinate system around ($x,$y)
        $this->transform($tm);
    }

    /**
     * Vertical and horizontal non-proportional Scaling.
     * @param float      $s_x scaling factor for width as percent. 0 is not allowed.
     * @param float      $s_y scaling factor for height as percent. 0 is not allowed.
     * @param float|null $x   abscissa of the scaling center. Default is current x position
     * @param float|null $y   ordinate of the scaling center. Default is current y position
     * @see   StartTransform(), StopTransform()
     */
    public function Scale($s_x, $s_y, $x = null, $y = null)
    {
        if (StaticMethods::empty_string($x)) {
            $x = $this->x;
        }
        if (StaticMethods::empty_string($y)) {
            $y = $this->y;
        }
        if (($s_x == 0) or ($s_y == 0)) {
            $this->Error('Please do not use values equal to zero for scaling');
        }
        $y = ($this->height - $y) * self::FACTOR;
        $x *= self::FACTOR;
        //calculate elements of transformation matrix
        $s_x   /= 100;
        $s_y   /= 100;
        $tm    = [];
        $tm[0] = $s_x;
        $tm[1] = 0;
        $tm[2] = 0;
        $tm[3] = $s_y;
        $tm[4] = $x * (1 - $s_x);
        $tm[5] = $y * (1 - $s_y);
        //scale the coordinate system
        $this->transform($tm);
    }

    /**
     * Horizontal Scaling.
     * @param float $s_x scaling factor for width as percent. 0 is not allowed.
     * @param int   $x   abscissa of the scaling center. Default is current x position
     * @param int   $y   ordinate of the scaling center. Default is current y position
     * @see   StartTransform(), StopTransform()
     */
    public function ScaleX($s_x, $x = '', $y = '')
    {
        $this->Scale($s_x, 100, $x, $y);
    }

    /**
     * Vertical and horizontal proportional Scaling.
     * @param float $s scaling factor for width and height as percent. 0 is not allowed.
     * @param int   $x abscissa of the scaling center. Default is current x position
     * @param int   $y ordinate of the scaling center. Default is current y position
     * @see   StartTransform(), StopTransform()
     */
    public function ScaleXY($s, $x = '', $y = '')
    {
        $this->Scale($s, $s, $x, $y);
    }

    /**
     * Vertical Scaling.
     * @param float $s_y scaling factor for height as percent. 0 is not allowed.
     * @param int   $x   abscissa of the scaling center. Default is current x position
     * @param int   $y   ordinate of the scaling center. Default is current y position
     * @see   StartTransform(), StopTransform()
     */
    public function ScaleY($s_y, $x = '', $y = '')
    {
        $this->Scale(100, $s_y, $x, $y);
    }

    /**
     * Skew.
     * @param float      $angle_x angle in degrees between -90 (skew to the left) and 90 (skew to the right)
     * @param float      $angle_y angle in degrees between -90 (skew to the bottom) and 90 (skew to the top)
     * @param float|null $x       abscissa of the skewing center. default is current x position
     * @param float|null $y       ordinate of the skewing center. default is current y position
     * @see   StartTransform(), StopTransform()
     */
    public function Skew($angle_x, $angle_y, $x = null, $y = null)
    {
        if (StaticMethods::empty_string($x)) {
            $x = $this->x;
        }
        if (StaticMethods::empty_string($y)) {
            $y = $this->y;
        }
        if (($angle_x <= -90) or ($angle_x >= 90) or ($angle_y <= -90) or ($angle_y >= 90)) {
            $this->Error('Please use values between -90 and +90 degrees for Skewing.');
        }
        $x *= self::FACTOR;
        $y = ($this->height - $y) * self::FACTOR;
        //calculate elements of transformation matrix
        $tm    = [];
        $tm[0] = 1;
        $tm[1] = tan(deg2rad($angle_y));
        $tm[2] = tan(deg2rad($angle_x));
        $tm[3] = 1;
        $tm[4] = -$tm[2] * $y;
        $tm[5] = -$tm[1] * $x;
        //skew the coordinate system
        $this->transform($tm);
    }

    /**
     * Skew horizontally.
     * @param float      $angle_x angle in degrees between -90 (skew to the left) and 90 (skew to the right)
     * @param float|null $x       abscissa of the skewing center. default is current x position
     * @param float|null $y       ordinate of the skewing center. default is current y position
     * @see   StartTransform(), StopTransform()
     */
    public function SkewX($angle_x, $x = null, $y = null)
    {
        $this->Skew($angle_x, 0, $x, $y);
    }

    /**
     * Skew vertically.
     * @param float      $angle_y angle in degrees between -90 (skew to the bottom) and 90 (skew to the top)
     * @param float|null $x       abscissa of the skewing center. default is current x position
     * @param float|null $y       ordinate of the skewing center. default is current y position
     * @see   StartTransform(), StopTransform()
     */
    public function SkewY($angle_y, $x = null, $y = null)
    {
        $this->Skew(0, $angle_y, $x, $y);
    }

    /**
     * Translate graphic object horizontally and vertically.
     * @param int $t_x movement to the right
     * @param int $t_y movement to the bottom
     * @see   StartTransform(), StopTransform()
     */
    public function Translate($t_x, $t_y)
    {
        //calculate elements of transformation matrix
        $tm    = [];
        $tm[0] = 1;
        $tm[1] = 0;
        $tm[2] = 0;
        $tm[3] = 1;
        $tm[4] = $t_x * self::FACTOR;
        $tm[5] = -$t_y * self::FACTOR;
        //translate the coordinate system
        $this->transform($tm);
    }

    /**
     * Translate graphic object horizontally.
     * @param int $t_x movement to the right
     * @see   StartTransform(), StopTransform()
     */
    public function TranslateX($t_x)
    {
        $this->Translate($t_x, 0);
    }

    /**
     * Translate graphic object vertically.
     * @param int $t_y movement to the bottom
     * @see   StartTransform(), StopTransform()
     */
    public function TranslateY($t_y)
    {
        $this->Translate(0, $t_y);
    }
}