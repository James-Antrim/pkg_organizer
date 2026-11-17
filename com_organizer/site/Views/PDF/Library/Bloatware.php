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
                    $this->rectangle($xb, $yb, $wb, $hb, 'F', []);
                }
                $xb += $xd;
                $yb += $yd;
            }
        }
    }


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////FONTS-PROCESSED


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////FONTS-UNPROCESSED


////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////GEOMETRY-PROCESSED

    public const ARMS = 0, CLOSED = 1, CLOSED_FILLED = 2, FILLED = 3;
    public const ARROW_HEAD_STYLES = [self::ARMS, self::CLOSED, self::CLOSED_FILLED, self::FILLED];

    /**
     * Draws a graphic arrow.
     * @param float $x0            abscissa of first point.
     * @param float $y0            ordinate of first point.
     * @param float $x1            abscissa of second point.
     * @param float $y1            ordinate of second point.
     * @param int   $headStyle     (0 = draw only arrowhead arms, 1 = draw closed arrowhead, but no fill, 2 = closed and filled arrowhead, 3 = filled arrowhead)
     * @param float $armSize       length of arrowhead arms
     * @param int   $armShaftAngle angle between an arm and the shaft
     */
    public function arrow(float $x0, float $y0, float $x1, float $y1, int $headStyle = self::ARMS, float $armSize = 5, int $armShaftAngle = 15): void
    {
        $armShaftAngle  = deg2rad($armShaftAngle);
        $directionAngle = atan2($y0 - $y1, $x0 - $x1);
        if ($directionAngle < 0) {
            $directionAngle += (2 * M_PI);
        }

        $sx1 = $x1;
        $sy1 = $y1;

        if ($headStyle > 0) {
            // calculate the stopping point for the arrow shaft
            $sx1 = $x1 + (($armSize - $this->lineWidth) * cos($directionAngle));
            $sy1 = $y1 + (($armSize - $this->lineWidth) * sin($directionAngle));
        }

        // main arrow line / shaft
        $this->line($x0, $y0, $sx1, $sy1);

        // left arrowhead arm tip
        $x2L = $x1 + ($armSize * cos($directionAngle + $armShaftAngle));
        $y2L = $y1 + ($armSize * sin($directionAngle + $armShaftAngle));

        // right arrowhead arm tip
        $x2R = $x1 + ($armSize * cos($directionAngle - $armShaftAngle));
        $y2R = $y1 + ($armSize * sin($directionAngle - $armShaftAngle));

        $headStyle = in_array($headStyle, self::ARROW_HEAD_STYLES) ?: self::ARMS;

        $mode  = 'D';
        $style = [];
        switch ($headStyle) {
            case self::ARMS:
                $style = [1, 1, 0];
                break;
            case self::CLOSED_FILLED:
                $mode = self::DRAW_FILL;
                break;
            case self::FILLED:
                $mode = self::FILL;
                break;
        }

        $this->Polygon([$x2L, $y2L, $x1, $y1, $x2R, $y2R], $mode, $style, []);
    }

//////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////GEOMETRY-UNPROCESSED

    /**
     * Append a cubic Bézier curve to the current path. The curve shall extend from the current point to the point (x3, y3), using the current point and (x2, y2) as the Bezier control points.
     * The new current point shall be (x3, y3).
     * @param float $x2 Abscissa of control point 2.
     * @param float $y2 Ordinate of control point 2.
     * @param float $x3 Abscissa of end point.
     * @param float $y3 Ordinate of end point.
     */
    protected function _outCurveV($x2, $y2, $x3, $y3)
    {
        if ($this->state === self::READY) {
            $this->append(sprintf('%F %F %F %F v', ($x2 * self::FACTOR), (($this->height - $y2) * self::FACTOR), ($x3 * self::FACTOR), (($this->height - $y3) * self::FACTOR)));
        }
    }

    /**
     * Append a cubic Bézier curve to the current path. The curve shall extend from the current point to the point (x3, y3), using (x1, y1) and (x3, y3) as the Bezier control points.
     * The new current point shall be (x3, y3).
     * @param float $x1 Abscissa of control point 1.
     * @param float $y1 Ordinate of control point 1.
     * @param float $x3 Abscissa of end point.
     * @param float $y3 Ordinate of end point.
     */
    protected function _outCurveY($x1, $y1, $x3, $y3)
    {
        if ($this->state === self::READY) {
            $this->append(sprintf('%F %F %F %F y', ($x1 * self::FACTOR), (($this->height - $y1) * self::FACTOR), ($x3 * self::FACTOR), (($this->height - $y3) * self::FACTOR)));
        }
    }

    /**
     * Draws a Bezier curve.
     * The Bezier curve is a tangent to the line between the control points at
     * either end of the curve.
     * @param float  $x0         Abscissa of start point.
     * @param float  $y0         Ordinate of start point.
     * @param float  $x1         abscissa of control point 1.
     * @param float  $y1         ordinate of control point 1.
     * @param float  $x2         abscissa of control point 2.
     * @param float  $y2         ordinate of control point 2.
     * @param float  $x3         abscissa of end point.
     * @param float  $y3         ordinate of end point.
     * @param string $style
     * @param array  $line_style Line style of curve. Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array  $fill_color Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array).
     * @see self::RENDERING_STYLES
     */
    public function Curve($x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3, $style = '', $line_style = [], $fill_color = [])
    {
        if ($this->state !== self::READY) {
            return;
        }
        if (!(false === strpos($style, 'F')) and is_array($fill_color)) {
            $this->fillColorArray($fill_color);
        }
        $op = $this->pathPaintingOperators($style);
        if ($line_style) {
            $this->lineStyle($line_style);
        }
        $this->point($x0, $y0);
        $this->appendCurve($x1, $y1, $x2, $y2, $x3, $y3);
        $this->append($op);
    }

    /**
     * Draws a poly-Bezier curve.
     * Each Bezier curve segment is a tangent to the line between the control points at
     * either end of the curve.
     * @param float   $x0         Abscissa of start point.
     * @param float   $y0         Ordinate of start point.
     * @param float[] $segments   An array of bezier descriptions. Format: [x1, y1, x2, y2, x3, y3].
     * @param string  $style
     * @param array   $line_style Line style of curve. Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array   $fill_color Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array).
     * @see self::RENDERING_STYLES
     */
    public function Polycurve($x0, $y0, $segments, $style = '', $line_style = [], $fill_color = [])
    {
        if ($this->state !== self::READY) {
            return;
        }
        if (!(false === strpos($style, 'F')) and is_array($fill_color)) {
            $this->fillColorArray($fill_color);
        }
        $op = $this->pathPaintingOperators($style);
        if ($op == 'f') {
            $line_style = [];
        }
        if ($line_style) {
            $this->lineStyle($line_style);
        }
        $this->point($x0, $y0);
        foreach ($segments as $segment) {
            list($x1, $y1, $x2, $y2, $x3, $y3) = $segment;
            $this->appendCurve($x1, $y1, $x2, $y2, $x3, $y3);
        }
        $this->append($op);
    }

    /**
     * Draws a regular polygon.
     * @param float  $x0                    Abscissa of center point.
     * @param float  $y0                    Ordinate of center point.
     * @param float  $r                     Radius of inscribed circle.
     * @param int    $ns                    Number of sides.
     * @param float  $angle                 Angle oriented (anti-clockwise). Default value: 0.
     * @param bool   $draw_circle           Draw inscribed circle or not. Default value: false.
     * @param string $style
     * @param array  $line_style            Line style of polygon sides. Array with keys among the following:
     *                                      <ul>
     *                                      <li>all: Line style of all sides. Array like for SetLineStyle().</li>
     *                                      <li>0 to ($ns - 1): Line style of each side. Array like for SetLineStyle().</li>
     *                                      </ul>
     *                                      If a key is not present or is null, not draws the side. Default value is default line style (empty array).
     * @param array  $fillColor             Fill color. Format: [red, green, blue]. Default value: default color (empty array).
     * @param string $circle_style          Style of rendering of inscribed circle (if draws). Possible values are:
     *                                      <ul>
     *                                      <li>D or empty string: Draw (default).</li>
     *                                      <li>F: Fill.</li>
     *                                      <li>DF or FD: Draw and fill.</li>
     *                                      <li>CNZ: Clipping mode (using the even-odd rule to determine which regions lie inside the clipping path).</li>
     *                                      <li>CEO: Clipping mode (using the nonzero winding number rule to determine which regions lie inside the clipping path).</li>
     *                                      </ul>
     * @param array  $circle_outLine_style  Line style of inscribed circle (if draws). Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array  $circle_fill_color     Fill color of inscribed circle (if draws). Format: [red, green, blue]. Default value: default color (empty array).
     * @see self::RENDERING_STYLES
     */
    public function RegularPolygon($x0, $y0, $r, $ns, $angle = 0, $draw_circle = false, $style = '', $line_style = [], $fillColor = [], $circle_style = '', $circle_outLine_style = [], $circle_fill_color = [])
    {
        if (3 > $ns) {
            $ns = 3;
        }
        if ($draw_circle) {
            $this->circle($x0, $y0, $r, 0, 360, $circle_style, $circle_outLine_style, $circle_fill_color);
        }
        $p = [];
        for ($i = 0; $i < $ns; ++$i) {
            $a     = $angle + ($i * 360 / $ns);
            $a_rad = deg2rad((float) $a);
            $p[]   = $x0 + ($r * sin($a_rad));
            $p[]   = $y0 + ($r * cos($a_rad));
        }
        $this->polygon($p, $style, $line_style, $fillColor);
    }

    /**
     * Draws a rounded rectangle.
     * @param float  $x            Abscissa of upper-left corner.
     * @param float  $y            Ordinate of upper-left corner.
     * @param float  $w            Width.
     * @param float  $h            Height.
     * @param float  $r            the radius of the circle used to round off the corners of the rectangle.
     * @param string $round_corner Draws rounded corner or not. String with a 0 (not rounded i-corner) or 1 (rounded i-corner) in i-position. Positions are, in order and begin to 0: top right, bottom right, bottom left and top left. Default value: all rounded corner ("1111").
     * @param string $style
     * @param array  $border_style Border style of rectangle. Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array  $fill_color   Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array).
     * @see self::RENDERING_STYLES
     */
    public function RoundedRect($x, $y, $w, $h, $r, $round_corner = '1111', $style = '', $border_style = [], $fill_color = [])
    {
        $this->RoundedRectXY($x, $y, $w, $h, $r, $r, $round_corner, $style, $border_style, $fill_color);
    }

    /**
     * Draws a star polygon
     * @param float  $x0                    Abscissa of center point.
     * @param float  $y0                    Ordinate of center point.
     * @param float  $r                     Radius of inscribed circle.
     * @param int    $nv                    Number of vertices.
     * @param int    $ng                    Number of gap (if ($ng % $nv = 1) then is a regular polygon).
     * @param float  $angle                 Angle oriented (anti-clockwise). Default value: 0.
     * @param bool   $draw_circle           Draw inscribed circle or not. Default value is false.
     * @param string $style
     * @param array  $lineStyle             Line style of polygon sides. Array with keys among the following:
     *                                      <ul>
     *                                      <li>all: Line style of all sides. Array like for
     *                                      SetLineStyle().</li>
     *                                      <li>0 to (n - 1): Line style of each side. Array like for SetLineStyle().</li>
     *                                      </ul>
     *                                      If a key is not present or is null, not draws the side. Default value is default line style (empty array).
     * @param array  $fillColor             Fill color. Format: [red, green, blue]. Default value: default color (empty array).
     * @param string $circleStyle           Style of rendering of inscribed circle (if draws). Possible values are:
     *                                      <ul>
     *                                      <li>D or empty string: Draw (default).</li>
     *                                      <li>F: Fill.</li>
     *                                      <li>DF or FD: Draw and fill.</li>
     *                                      <li>CNZ: Clipping mode (using the even-odd rule to determine which regions lie inside the clipping path).</li>
     *                                      <li>CEO: Clipping mode (using the nonzero winding number rule to determine which regions lie inside the clipping path).</li>
     *                                      </ul>
     * @param array  $circle_outLine_style  Line style of inscribed circle (if draws). Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array  $circle_fill_color     Fill color of inscribed circle (if draws). Format: [red, green, blue]. Default value: default color (empty array).
     * @see self::RENDERING_STYLES
     */
    public function StarPolygon($x0, $y0, $r, $nv, $ng, $angle = 0, $draw_circle = false, $style = '', $lineStyle = [], $fillColor = [], $circleStyle = '', $circle_outLine_style = [], $circle_fill_color = [])
    {
        if ($nv < 2) {
            $nv = 2;
        }
        if ($draw_circle) {
            $this->circle($x0, $y0, $r, 0, 360, $circleStyle, $circle_outLine_style, $circle_fill_color);
        }
        $p2      = [];
        $visited = [];
        for ($i = 0; $i < $nv; ++$i) {
            $a         = $angle + ($i * 360 / $nv);
            $a_rad     = deg2rad((float) $a);
            $p2[]      = $x0 + ($r * sin($a_rad));
            $p2[]      = $y0 + ($r * cos($a_rad));
            $visited[] = false;
        }
        $p = [];
        $i = 0;
        do {
            $p[]         = $p2[$i * 2];
            $p[]         = $p2[($i * 2) + 1];
            $visited[$i] = true;
            $i           += $ng;
            $i           %= $nv;
        } while (!$visited[$i]);
        $this->polygon($p, $style, $lineStyle, $fillColor);
    }

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