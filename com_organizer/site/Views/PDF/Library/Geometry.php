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

/** Extracted geometric functions. Based on code provided to the TCPDF library by David Hernandez Sanz. */
trait Geometry
{
    public const ARMS = 0, CLOSED = 1, CLOSED_FILLED = 2, FILLED = 3;
    public const ARROW_HEAD_STYLES = [];

    private float $lineWidth;

    /**
     * Append a cubic Bézier curve to the current path.
     * The curve shall extend from the current point to the point (x3, y3), using (x1, y1) and (x2, y2) as the Bézier control points.
     * The new current point shall be (x3, y3).
     * @param float $x1 abscissa of control point 1.
     * @param float $y1 ordinate of control point 1.
     * @param float $x2 abscissa of control point 2.
     * @param float $y2 ordinate of control point 2.
     * @param float $x3 abscissa of end point.
     * @param float $y3 ordinate of end point.
     */
    private function appendCurve(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $x1Points = $x1 * $this->scaleFactor;
        $x2Points = $x2 * $this->scaleFactor;
        $x3Points = $x3 * $this->scaleFactor;
        $y1Points = ($this->h - $y1) * $this->scaleFactor;
        $y2Points = ($this->h - $y2) * $this->scaleFactor;
        $y3Points = ($this->h - $y3) * $this->scaleFactor;

        $this->append(sprintf('%F %F %F %F %F %F c', $x1Points, $y1Points, $x2Points, $y2Points, $x3Points, $y3Points));

    }

    /**
     * Append a straight line segment from the current point to the point (x, y).
     * The new current point shall be (x, y).
     * @param float $x abscissa of end point.
     * @param float $y ordinate of end point.
     * @return void
     */
    private function appendLine(float $x, float $y): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $this->append(sprintf('%F %F l', $x * $this->scaleFactor, ($this->h - $y) * $this->scaleFactor));
    }

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
        $this->Line($x0, $y0, $sx1, $sy1);

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
                $mode = 'DF';
                break;
            case self::FILLED:
                $mode = 'F';
                break;
        }

        $this->Polygon([$x2L, $y2L, $x1, $y1, $x2R, $y2R], $mode, $style, []);
    }

    /**
     * Begin a new subpath by moving the current point to coordinates (x, y), omitting any connecting line segment.
     * @param float $x abscissa of point.
     * @param float $y ordinate of point.
     */
    private function point(float $x, float $y): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $this->append(sprintf('%F %F m', $x * $this->scaleFactor, ($this->h - $y) * $this->scaleFactor));
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////TODO

    /**
     * Append an elliptical arc to the current path.
     * An ellipse is formed from n Bézier curves.
     * @param float   $xc         abscissa of center point.
     * @param float   $yc         ordinate of center point.
     * @param float   $rx         Horizontal radius.
     * @param float   $ry         Vertical radius (if ry = 0 then is a circle, see Circle()). Default value: 0.
     * @param float   $xang       Angle between the X-axis and the major axis of the ellipse. Default value: 0.
     * @param float   $angs       Angle start of draw line. Default value: 0.
     * @param float   $angf       Angle finish of draw line. Default value: 360.
     * @param boolean $pie        if true do not mark the border point (used to draw pie sectors).
     * @param integer $nc         Number of curves used to draw a 90 degrees portion of ellipse.
     * @param boolean $startpoint if true output a starting point.
     * @param boolean $ccw        if true draws in counter-clockwise.
     * @param boolean $svg        if true the angles are in svg mode (already calculated).
     * @return array bounding box coordinates (x min, y min, x max, y max)
     */
    protected function _outellipticalarc($xc, $yc, $rx, $ry, $xang = 0, $angs = 0, $angf = 360, $pie = false, $nc = 2, $startpoint = true, $ccw = true, $svg = false)
    {
        if (($rx <= 0) or ($ry < 0)) {
            return;
        }
        $k = $this->scaleFactor;
        if ($nc < 2) {
            $nc = 2;
        }
        $xmin = 2147483647;
        $ymin = 2147483647;
        $xmax = 0;
        $ymax = 0;
        if ($pie) {
            // center of the arc
            $this->point($xc, $yc);
        }
        $xang = deg2rad((float) $xang);
        $angs = deg2rad((float) $angs);
        $angf = deg2rad((float) $angf);
        if ($svg) {
            $as = $angs;
            $af = $angf;
        }
        else {
            $as = atan2((sin($angs) / $ry), (cos($angs) / $rx));
            $af = atan2((sin($angf) / $ry), (cos($angf) / $rx));
        }
        if ($as < 0) {
            $as += (2 * M_PI);
        }
        if ($af < 0) {
            $af += (2 * M_PI);
        }
        if ($ccw and ($as > $af)) {
            // reverse rotation
            $as -= (2 * M_PI);
        }
        elseif (!$ccw and ($as < $af)) {
            // reverse rotation
            $af -= (2 * M_PI);
        }
        $total_angle = ($af - $as);
        if ($nc < 2) {
            $nc = 2;
        }
        // total arcs to draw
        $nc *= (2 * abs($total_angle) / M_PI);
        $nc = round($nc) + 1;
        // angle of each arc
        $arcang = ($total_angle / $nc);
        // center point in PDF coordinates
        $x0 = $xc;
        $y0 = ($this->h - $yc);
        // starting angle
        $ang      = $as;
        $alpha    = sin($arcang) * ((sqrt(4 + (3 * pow(tan(($arcang) / 2), 2))) - 1) / 3);
        $cos_xang = cos($xang);
        $sin_xang = sin($xang);
        $cos_ang  = cos($ang);
        $sin_ang  = sin($ang);
        // first arc point
        $px1 = $x0 + ($rx * $cos_xang * $cos_ang) - ($ry * $sin_xang * $sin_ang);
        $py1 = $y0 + ($rx * $sin_xang * $cos_ang) + ($ry * $cos_xang * $sin_ang);
        // first Bézier control point
        $qx1 = ($alpha * ((-$rx * $cos_xang * $sin_ang) - ($ry * $sin_xang * $cos_ang)));
        $qy1 = ($alpha * ((-$rx * $sin_xang * $sin_ang) + ($ry * $cos_xang * $cos_ang)));
        if ($pie) {
            // line from center to arc starting point
            $this->appendLine($px1, $this->h - $py1);
        }
        elseif ($startpoint) {
            // arc starting point
            $this->point($px1, $this->h - $py1);
        }
        // draw arcs
        for ($i = 1; $i <= $nc; ++$i) {
            // starting angle
            $ang = $as + ($i * $arcang);
            if ($i == $nc) {
                $ang = $af;
            }
            $cos_ang = cos($ang);
            $sin_ang = sin($ang);
            // second arc point
            $px2 = $x0 + ($rx * $cos_xang * $cos_ang) - ($ry * $sin_xang * $sin_ang);
            $py2 = $y0 + ($rx * $sin_xang * $cos_ang) + ($ry * $cos_xang * $sin_ang);
            // second Bézier control point
            $qx2 = ($alpha * ((-$rx * $cos_xang * $sin_ang) - ($ry * $sin_xang * $cos_ang)));
            $qy2 = ($alpha * ((-$rx * $sin_xang * $sin_ang) + ($ry * $cos_xang * $cos_ang)));
            // draw arc
            $cx1 = ($px1 + $qx1);
            $cy1 = ($this->h - ($py1 + $qy1));
            $cx2 = ($px2 - $qx2);
            $cy2 = ($this->h - ($py2 - $qy2));
            $cx3 = $px2;
            $cy3 = ($this->h - $py2);
            $this->appendCurve($cx1, $cy1, $cx2, $cy2, $cx3, $cy3);
            // get bounding box coordinates
            $xmin = min($xmin, $cx1, $cx2, $cx3);
            $ymin = min($ymin, $cy1, $cy2, $cy3);
            $xmax = max($xmax, $cx1, $cx2, $cx3);
            $ymax = max($ymax, $cy1, $cy2, $cy3);
            // move to next point
            $px1 = $px2;
            $py1 = $py2;
            $qx1 = $qx2;
            $qy1 = $qy2;
        }
        if ($pie) {
            $this->appendLine($xc, $yc);
            // get bounding box coordinates
            $xmin = min($xmin, $xc);
            $ymin = min($ymin, $yc);
            $xmax = max($xmax, $xc);
            $ymax = max($ymax, $yc);
        }
        return [$xmin, $ymin, $xmax, $ymax];
    }

    /**
     * Append a rectangle to the current path as a complete subpath, with lower-left corner (x, y) and dimensions widthand height in user space.
     * @param float  $x  abscissa of upper-left corner.
     * @param float  $y  ordinate of upper-left corner.
     * @param float  $w  Width.
     * @param float  $h  Height.
     * @param string $op options
     */
    protected function _outRect($x, $y, $w, $h, $op)
    {
        if ($this->state !== self::READY) {
            return;
        }

        $this->append(sprintf('%F %F %F %F re %s', ($x * $this->scaleFactor), (($this->h - $y) * $this->scaleFactor), ($w * $this->scaleFactor), (-$h * $this->scaleFactor), $op));
    }

    /**
     * Draws a circle.
     * A circle is formed from n Bézier curves.
     * @param float   $x0         abscissa of center point.
     * @param float   $y0         ordinate of center point.
     * @param float   $r          Radius.
     * @param float   $angstr     Angle start of draw line. Default value: 0.
     * @param float   $angend     Angle finish of draw line. Default value: 360.
     * @param string  $style      Style of rendering. See the getPathPaintOperator() function for more information.
     * @param array   $line_style Line style of circle. Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array   $fill_color Fill color. Format: [red, green, blue]. Default value: default color (empty array).
     * @param integer $nc         Number of curves used to draw a 90 degrees portion of circle.
     */
    public function Circle($x0, $y0, $r, $angstr = 0, $angend = 360, $style = '', $line_style = [], $fill_color = [], $nc = 2)
    {
        $this->Ellipse($x0, $y0, $r, $r, 0, $angstr, $angend, $style, $line_style, $fill_color, $nc);
    }

    /**
     * Draws a Bézier curve.
     * The Bézier curve is a tangent to the line between the control points at
     * either end of the curve.
     * @param float  $x0         abscissa of start point.
     * @param float  $y0         ordinate of start point.
     * @param float  $x1         abscissa of control point 1.
     * @param float  $y1         ordinate of control point 1.
     * @param float  $x2         abscissa of control point 2.
     * @param float  $y2         ordinate of control point 2.
     * @param float  $x3         abscissa of end point.
     * @param float  $y3         ordinate of end point.
     * @param string $style      Style of rendering. See the getPathPaintOperator() function for more information.
     * @param array  $line_style Line style of curve. Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array  $fill_color Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array).
     * @see   SetLineStyle()
     */
    public function Curve($x0, $y0, $x1, $y1, $x2, $y2, $x3, $y3, $style = '', $line_style = [], $fill_color = [])
    {
        if ($this->state !== self::READY) {
            return;
        }

        if (!(false === strpos($style, 'F')) and is_array($fill_color)) {
            $this->setFillColorArray($fill_color);
        }
        $op = StaticMethods::getPathPaintOperator($style);
        if ($line_style) {
            $this->setLineStyle($line_style);
        }
        $this->point($x0, $y0);
        $this->appendCurve($x1, $y1, $x2, $y2, $x3, $y3);
        $this->append($op);
    }

    /**
     * Draws an ellipse.
     * An ellipse is formed from n Bézier curves.
     * @param float   $x0         abscissa of center point.
     * @param float   $y0         ordinate of center point.
     * @param float   $rx         Horizontal radius.
     * @param float   $ry         Vertical radius (if ry = 0 then is a circle, see Circle()). Default value: 0.
     * @param float   $angle      Angle oriented (anti-clockwise). Default value: 0.
     * @param float   $astart     Angle start of draw line. Default value: 0.
     * @param float   $afinish    Angle finish of draw line. Default value: 360.
     * @param string  $style      Style of rendering. See the getPathPaintOperator() function for more information.
     * @param array   $line_style Line style of ellipse. Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array   $fill_color Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array).
     * @param integer $nc         Number of curves used to draw a 90 degrees portion of ellipse.
     */
    public function Ellipse($x0, $y0, $rx, $ry = 0, $angle = 0, $astart = 0, $afinish = 360, $style = '', $line_style = [], $fill_color = [], $nc = 2)
    {
        if ($this->state !== self::READY) {
            return;
        }

        if (StaticMethods::empty_string($ry) or ($ry == 0)) {
            $ry = $rx;
        }
        if (!(false === strpos($style, 'F')) and is_array($fill_color)) {
            $this->setFillColorArray($fill_color);
        }
        $op = StaticMethods::getPathPaintOperator($style);
        if ($op == 'f') {
            $line_style = [];
        }
        if ($line_style) {
            $this->setLineStyle($line_style);
        }
        $this->_outellipticalarc($x0, $y0, $rx, $ry, $angle, $astart, $afinish, false, $nc, true, true, false);
        $this->append($op);
    }

    /**
     * Returns the current the line width.
     * @return int Line width
     * @see   Line(), SetLineWidth()
     */
    public function GetLineWidth()
    {
        return $this->lineWidth;
    }

    /**
     * Draws a line between two points.
     * @param float $x1    abscissa of first point.
     * @param float $y1    ordinate of first point.
     * @param float $x2    abscissa of second point.
     * @param float $y2    ordinate of second point.
     * @param array $style Line style. Array like for SetLineStyle(). Default value: default line style (empty array).
     * @see   SetLineWidth(), SetDrawColor(), SetLineStyle()
     */
    public function Line($x1, $y1, $x2, $y2, $style = [])
    {
        if ($this->state !== self::READY) {
            return;
        }

        if (is_array($style)) {
            $this->setLineStyle($style);
        }
        $this->point($x1, $y1);
        $this->appendLine($x2, $y2);
        $this->append('S');
    }

    /**
     * Draws a poly-Bézier curve.
     * Each Bézier curve segment is a tangent to the line between the control points at
     * either end of the curve.
     * @param float   $x0         abscissa of start point.
     * @param float   $y0         ordinate of start point.
     * @param float[] $segments   An array of Bézier descriptions. Format: [x1, y1, x2, y2, x3, y3].
     * @param string  $style      Style of rendering. See the getPathPaintOperator() function for more information.
     * @param array   $line_style Line style of curve. Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array   $fill_color Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array).
     * @see   SetLineStyle()
     */
    public function Polycurve($x0, $y0, $segments, $style = '', $line_style = [], $fill_color = [])
    {
        if ($this->state !== self::READY) {
            return;
        }

        if (!(false === strpos($style, 'F')) and is_array($fill_color)) {
            $this->setFillColorArray($fill_color);
        }
        $op = StaticMethods::getPathPaintOperator($style);
        if ($op == 'f') {
            $line_style = [];
        }
        if ($line_style) {
            $this->setLineStyle($line_style);
        }
        $this->point($x0, $y0);
        foreach ($segments as $segment) {
            list($x1, $y1, $x2, $y2, $x3, $y3) = $segment;
            $this->appendCurve($x1, $y1, $x2, $y2, $x3, $y3);
        }
        $this->append($op);
    }

    /**
     * Draws a polygon.
     * @param array   $p          Points 0 to ($np - 1). Array with values (x0, y0, x1, y1,..., x(np-1), y(np - 1))
     * @param string  $style      Style of rendering. See the getPathPaintOperator() function for more information.
     * @param array   $line_style Line style of polygon. Array with keys among the following:
     *                            <ul>
     *                            <li>all: Line style of all lines. Array like for SetLineStyle().</li>
     *                            <li>0 to ($np - 1): Line style of each line. Array like for SetLineStyle().</li>
     *                            </ul>
     *                            If a key is not present or is null, not draws the line. Default value is default line style (empty array).
     * @param array   $fill_color Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array).
     * @param boolean $closed     if true the polygon is closes, otherwise will remain open
     */
    public function Polygon($p, $style = '', $line_style = [], $fill_color = [], $closed = true)
    {
        if ($this->state !== self::READY) {
            return;
        }

        $nc = count($p); // number of coordinates
        $np = $nc / 2; // number of points
        if ($closed) {
            // close polygon by adding the first 2 points at the end (one line)
            for ($i = 0; $i < 4; ++$i) {
                $p[$nc + $i] = $p[$i];
            }
            // copy style for the last added line
            if (isset($line_style[0])) {
                $line_style[$np] = $line_style[0];
            }
            $nc += 4;
        }
        if (!(false === strpos($style, 'F')) and is_array($fill_color)) {
            $this->setFillColorArray($fill_color);
        }
        $op = StaticMethods::getPathPaintOperator($style);
        if ($op == 'f') {
            $line_style = [];
        }
        $draw = true;
        if ($line_style) {
            if (isset($line_style['all'])) {
                $this->setLineStyle($line_style['all']);
            }
            else {
                $draw = false;
                if ($op == 'B') {
                    // draw fill
                    $op = 'f';
                    $this->point($p[0], $p[1]);
                    for ($i = 2; $i < $nc; $i = $i + 2) {
                        $this->appendLine($p[$i], $p[$i + 1]);
                    }
                    $this->append($op);
                }
                // draw outline
                $this->point($p[0], $p[1]);
                for ($i = 2; $i < $nc; $i = $i + 2) {
                    $line_num = ($i / 2) - 1;
                    if (isset($line_style[$line_num])) {
                        if ($line_style[$line_num] != 0) {
                            if (is_array($line_style[$line_num])) {
                                $this->append('S');
                                $this->setLineStyle($line_style[$line_num]);
                                $this->point($p[$i - 2], $p[$i - 1]);
                                $this->appendLine($p[$i], $p[$i + 1]);
                                $this->append('S');
                                $this->point($p[$i], $p[$i + 1]);
                            }
                            else {
                                $this->appendLine($p[$i], $p[$i + 1]);
                            }
                        }
                    }
                    else {
                        $this->appendLine($p[$i], $p[$i + 1]);
                    }
                }
                $this->append($op);
            }
        }
        if ($draw) {
            $this->point($p[0], $p[1]);
            for ($i = 2; $i < $nc; $i = $i + 2) {
                $this->appendLine($p[$i], $p[$i + 1]);
            }
            $this->append($op);
        }
    }

    /**
     * Draws a polygonal line
     * @param array  $p          Points 0 to ($np - 1). Array with values (x0, y0, x1, y1,..., x(np-1), y(np - 1))
     * @param string $style      Style of rendering. See the getPathPaintOperator() function for more information.
     * @param array  $line_style Line style of polygon. Array with keys among the following:
     *                           <ul>
     *                           <li>all: Line style of all lines. Array like for SetLineStyle().</li>
     *                           <li>0 to ($np - 1): Line style of each line. Array like for SetLineStyle().</li>
     *                           </ul>
     *                           If a key is not present or is null, not draws the line. Default value is default line style (empty array).
     * @param array  $fill_color Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array).
     */
    public function PolyLine($p, $style = '', $line_style = [], $fill_color = [])
    {
        $this->Polygon($p, $style, $line_style, $fill_color, false);
    }

    /**
     * Draws a rectangle.
     * @param float  $x            abscissa of upper-left corner.
     * @param float  $y            ordinate of upper-left corner.
     * @param float  $w            Width.
     * @param float  $h            Height.
     * @param string $style        Style of rendering. See the getPathPaintOperator() function for more information.
     * @param array  $border_style Border style of rectangle. Array with keys among the following:
     *                             <ul>
     *                             <li>all: Line style of all borders. Array like for SetLineStyle().</li>
     *                             <li>L, T, R, B or combinations: Line style of left, top, right or bottom border. Array like for SetLineStyle().</li>
     *                             </ul>
     *                             If a key is not present or is null, the correspondent border is not drawn. Default value: default line style (empty array).
     * @param array  $fill_color   Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array).
     * @see   SetLineStyle()
     */
    public function Rect($x, $y, $w, $h, $style = '', $border_style = [], $fill_color = [])
    {
        if ($this->state !== self::READY) {
            return;
        }

        if (empty($style)) {
            $style = 'S';
        }
        if (!(strpos($style, 'F') === false) and !empty($fill_color)) {
            // set background color
            $this->setFillColorArray($fill_color);
        }
        if (!empty($border_style)) {
            if (isset($border_style['all']) and !empty($border_style['all'])) {
                //set global style for border
                $this->setLineStyle($border_style['all']);
                $border_style = [];
            }
            else {
                // remove stroke operator from style
                $opnostroke = ['S' => '', 'D' => '', 's' => '', 'd' => '', 'B' => 'F', 'FD' => 'F', 'DF' => 'F', 'B*' => 'F*', 'F*D' => 'F*', 'DF*' => 'F*', 'b' => 'f', 'fd' => 'f', 'df' => 'f', 'b*' => 'f*', 'f*d' => 'f*', 'df*' => 'f*'];
                if (isset($opnostroke[$style])) {
                    $style = $opnostroke[$style];
                }
            }
        }
        if (!empty($style)) {
            $op = StaticMethods::getPathPaintOperator($style);
            $this->_outRect($x, $y, $w, $h, $op);
        }
        if (!empty($border_style)) {
            $border_style2 = [];
            foreach ($border_style as $line => $value) {
                $length = strlen($line);
                for ($i = 0; $i < $length; ++$i) {
                    $border_style2[$line[$i]] = $value;
                }
            }
            $border_style = $border_style2;
            if (isset($border_style['L']) and $border_style['L']) {
                $this->Line($x, $y, $x, $y + $h, $border_style['L']);
            }
            if (isset($border_style['T']) and $border_style['T']) {
                $this->Line($x, $y, $x + $w, $y, $border_style['T']);
            }
            if (isset($border_style['R']) and $border_style['R']) {
                $this->Line($x + $w, $y, $x + $w, $y + $h, $border_style['R']);
            }
            if (isset($border_style['B']) and $border_style['B']) {
                $this->Line($x, $y + $h, $x + $w, $y + $h, $border_style['B']);
            }
        }
    }

    /**
     * Draws a regular polygon.
     * @param float   $x0                   abscissa of center point.
     * @param float   $y0                   ordinate of center point.
     * @param float   $r                    Radius of inscribed circle.
     * @param integer $ns                   Number of sides.
     * @param float   $angle                Angle oriented (anti-clockwise). Default value: 0.
     * @param boolean $draw_circle          Draw inscribed circle or not. Default value: false.
     * @param string  $style                Style of rendering. See the getPathPaintOperator() function for more information.
     * @param array   $line_style           Line style of polygon sides. Array with keys among the following:
     *                                      <ul>
     *                                      <li>all: Line style of all sides. Array like for SetLineStyle().</li>
     *                                      <li>0 to ($ns - 1): Line style of each side. Array like for SetLineStyle().</li>
     *                                      </ul>
     *                                      If a key is not present or is null, not draws the side. Default value is default line style (empty array).
     * @param array   $fill_color           Fill color. Format: [red, green, blue]. Default value: default color (empty array).
     * @param string  $circle_style         Style of rendering of inscribed circle (if draws). Possible values are:
     *                                      <ul>
     *                                      <li>D or empty string: Draw (default).</li>
     *                                      <li>F: Fill.</li>
     *                                      <li>DF or FD: Draw and fill.</li>
     *                                      <li>CNZ: Clipping mode (using the even-odd rule to determine which regions lie inside the clipping path).</li>
     *                                      <li>CEO: Clipping mode (using the nonzero winding number rule to determine which regions lie inside the clipping path).</li>
     *                                      </ul>
     * @param array   $circle_outLine_style Line style of inscribed circle (if draws). Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array   $circle_fill_color    Fill color of inscribed circle (if draws). Format: [red, green, blue]. Default value: default color (empty array).
     */
    public function RegularPolygon($x0, $y0, $r, $ns, $angle = 0, $draw_circle = false, $style = '', $line_style = [], $fill_color = [], $circle_style = '', $circle_outLine_style = [], $circle_fill_color = [])
    {
        if (3 > $ns) {
            $ns = 3;
        }
        if ($draw_circle) {
            $this->Circle($x0, $y0, $r, 0, 360, $circle_style, $circle_outLine_style, $circle_fill_color);
        }
        $p = [];
        for ($i = 0; $i < $ns; ++$i) {
            $a     = $angle + ($i * 360 / $ns);
            $a_rad = deg2rad((float) $a);
            $p[]   = $x0 + ($r * sin($a_rad));
            $p[]   = $y0 + ($r * cos($a_rad));
        }
        $this->Polygon($p, $style, $line_style, $fill_color);
    }

    /**
     * Set line style.
     * @param array   $style Line style. Array with keys among the following:
     *                       <ul>
     *                       <li>width (float): Width of the line in user units.</li>
     *                       <li>cap (string): Type of cap to put on the line. Possible values are:
     *                       butt, round, square. The difference between "square" and "butt" is that
     *                       "square" projects a flat end past the end of the line.</li>
     *                       <li>join (string): Type of join. Possible values are: miter, round,
     *                       bevel.</li>
     *                       <li>dash (mixed): Dash pattern. Is 0 (without dash) or string with
     *                       series of length values, which are the lengths of the on and off dashes.
     *                       For example: "2" represents 2 on, 2 off, 2 on, 2 off, ...; "2,1" is 2 on,
     *                       1 off, 2 on, 1 off, ...</li>
     *                       <li>phase (integer): Modifier on the dash pattern which is used to shift
     *                       the point at which the pattern starts.</li>
     *                       <li>color (array): Draw color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName].</li>
     *                       </ul>
     * @param boolean $ret   if true do not send the command.
     * @return string the PDF command
     */
    public function setLineStyle($style, $ret = false)
    {
        $s = ''; // string to be returned
        if (!is_array($style)) {
            return $s;
        }
        if (isset($style['width'])) {
            $this->lineWidth      = $style['width'];
            $this->linestyleWidth = sprintf('%F w', ($style['width'] * $this->scaleFactor));
            $s                    .= $this->linestyleWidth . ' ';
        }
        if (isset($style['cap'])) {
            $ca = ['butt' => 0, 'round' => 1, 'square' => 2];
            if (isset($ca[$style['cap']])) {
                $this->linestyleCap = $ca[$style['cap']] . ' J';
                $s                  .= $this->linestyleCap . ' ';
            }
        }
        if (isset($style['join'])) {
            $ja = ['miter' => 0, 'round' => 1, 'bevel' => 2];
            if (isset($ja[$style['join']])) {
                $this->linestyleJoin = $ja[$style['join']] . ' j';
                $s                   .= $this->linestyleJoin . ' ';
            }
        }
        if (isset($style['dash'])) {
            $dash_string = '';
            if ($style['dash']) {
                if (preg_match('/^.+,/', $style['dash']) > 0) {
                    $tab = explode(',', $style['dash']);
                }
                else {
                    $tab = [$style['dash']];
                }
                $dash_string = '';
                foreach ($tab as $i => $v) {
                    if ($i) {
                        $dash_string .= ' ';
                    }
                    $dash_string .= sprintf('%F', $v);
                }
            }
            if (!isset($style['phase']) or !$style['dash']) {
                $style['phase'] = 0;
            }
            $this->linestyleDash = sprintf('[%s] %F d', $dash_string, $style['phase']);
            $s                   .= $this->linestyleDash . ' ';
        }
        if (isset($style['color'])) {
            $s .= $this->setDrawColorArray($style['color'], true) . ' ';
        }
        if (!$ret and ($this->state === self::READY)) {
            $this->append($s);
        }
        return $s;
    }

    /**
     * Defines the line width. By default, the value equals 0.2 mm. The method can be called before the first page is created and the value is retained from page to page.
     * @param float $width The width.
     * @see   Line(), Rect(), Cell(), MultiCell()
     */
    public function setLineWidth($width)
    {
        //Set line width
        $this->lineWidth      = $width;
        $this->linestyleWidth = sprintf('%F w', ($width * $this->scaleFactor));
        if ($this->state === self::READY) {
            $this->append($this->linestyleWidth);
        }
    }

    /**
     * Draws a star polygon
     * @param float   $x0                   abscissa of center point.
     * @param float   $y0                   ordinate of center point.
     * @param float   $r                    Radius of inscribed circle.
     * @param integer $nv                   Number of vertices.
     * @param integer $ng                   Number of gap (if ($ng % $nv = 1) then is a regular polygon).
     * @param float   $angle                Angle oriented (anti-clockwise). Default value: 0.
     * @param boolean $draw_circle          Draw inscribed circle or not. Default value is false.
     * @param string  $style                Style of rendering. See the getPathPaintOperator() function for more information.
     * @param array   $line_style           Line style of polygon sides. Array with keys among the following:
     *                                      <ul>
     *                                      <li>all: Line style of all sides. Array like for
     *                                      SetLineStyle().</li>
     *                                      <li>0 to (n - 1): Line style of each side. Array like for SetLineStyle().</li>
     *                                      </ul>
     *                                      If a key is not present or is null, not draws the side. Default value is default line style (empty array).
     * @param array   $fill_color           Fill color. Format: [red, green, blue]. Default value: default color (empty array).
     * @param string  $circle_style         Style of rendering of inscribed circle (if draws). Possible values are:
     *                                      <ul>
     *                                      <li>D or empty string: Draw (default).</li>
     *                                      <li>F: Fill.</li>
     *                                      <li>DF or FD: Draw and fill.</li>
     *                                      <li>CNZ: Clipping mode (using the even-odd rule to determine which regions lie inside the clipping path).</li>
     *                                      <li>CEO: Clipping mode (using the nonzero winding number rule to determine which regions lie inside the clipping path).</li>
     *                                      </ul>
     * @param array   $circle_outLine_style Line style of inscribed circle (if draws). Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array   $circle_fill_color    Fill color of inscribed circle (if draws). Format: [red, green, blue]. Default value: default color (empty array).
     */
    public function StarPolygon($x0, $y0, $r, $nv, $ng, $angle = 0, $draw_circle = false, $style = '', $line_style = [], $fill_color = [], $circle_style = '', $circle_outLine_style = [], $circle_fill_color = [])
    {
        if ($nv < 2) {
            $nv = 2;
        }
        if ($draw_circle) {
            $this->Circle($x0, $y0, $r, 0, 360, $circle_style, $circle_outLine_style, $circle_fill_color);
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
        $this->Polygon($p, $style, $line_style, $fill_color);
    }

    /**
     * Draws a rounded rectangle.
     * @param float  $x            abscissa of upper-left corner.
     * @param float  $y            ordinate of upper-left corner.
     * @param float  $w            Width.
     * @param float  $h            Height.
     * @param float  $r            the radius of the circle used to round off the corners of the rectangle.
     * @param string $round_corner Draws rounded corner or not. String with a 0 (not rounded i-corner) or 1 (rounded i-corner) in i-position. Positions are, in order and begin to 0: top right, bottom right, bottom left and top left. Default value: all rounded corner ("1111").
     * @param string $style        Style of rendering. See the getPathPaintOperator() function for more information.
     * @param array  $border_style Border style of rectangle. Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array  $fill_color   Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array).
     */
    public function RoundedRect($x, $y, $w, $h, $r, $round_corner = '1111', $style = '', $border_style = [], $fill_color = [])
    {
        $this->RoundedRectXY($x, $y, $w, $h, $r, $r, $round_corner, $style, $border_style, $fill_color);
    }

    /**
     * Draws a rounded rectangle.
     * @param float  $x            abscissa of upper-left corner.
     * @param float  $y            ordinate of upper-left corner.
     * @param float  $w            Width.
     * @param float  $h            Height.
     * @param float  $rx           the x-axis radius of the ellipse used to round off the corners of the rectangle.
     * @param float  $ry           the y-axis radius of the ellipse used to round off the corners of the rectangle.
     * @param string $round_corner Draws rounded corner or not. String with a 0 (not rounded i-corner) or 1 (rounded i-corner) in i-position. Positions are, in order and begin to 0: top right, bottom right, bottom left and top left. Default value: all rounded corner ("1111").
     * @param string $style        Style of rendering. See the getPathPaintOperator() function for more information.
     * @param array  $border_style Border style of rectangle. Array like for SetLineStyle(). Default value: default line style (empty array).
     * @param array  $fill_color   Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array).
     */
    public function RoundedRectXY($x, $y, $w, $h, $rx, $ry, $round_corner = '1111', $style = '', $border_style = [], $fill_color = [])
    {
        if ($this->state !== self::READY) {
            return;
        }
        if (($round_corner == '0000') or (($rx == $ry) and ($rx == 0))) {
            // Not rounded
            $this->Rect($x, $y, $w, $h, $style, $border_style, $fill_color);
            return;
        }
        // Rounded
        if (!(false === strpos($style, 'F')) and is_array($fill_color)) {
            $this->setFillColorArray($fill_color);
        }
        $op = StaticMethods::getPathPaintOperator($style);
        if ($op == 'f') {
            $border_style = [];
        }
        if ($border_style) {
            $this->setLineStyle($border_style);
        }
        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->point($x + $rx, $y);
        $xc = $x + $w - $rx;
        $yc = $y + $ry;
        $this->appendLine($xc, $y);
        if ($round_corner[0]) {
            $this->appendCurve($xc + ($rx * $MyArc), $yc - $ry, $xc + $rx, $yc - ($ry * $MyArc), $xc + $rx, $yc);
        }
        else {
            $this->appendLine($x + $w, $y);
        }
        $xc = $x + $w - $rx;
        $yc = $y + $h - $ry;
        $this->appendLine($x + $w, $yc);
        if ($round_corner[1]) {
            $this->appendCurve($xc + $rx, $yc + ($ry * $MyArc), $xc + ($rx * $MyArc), $yc + $ry, $xc, $yc + $ry);
        }
        else {
            $this->appendLine($x + $w, $y + $h);
        }
        $xc = $x + $rx;
        $yc = $y + $h - $ry;
        $this->appendLine($xc, $y + $h);
        if ($round_corner[2]) {
            $this->appendCurve($xc - ($rx * $MyArc), $yc + $ry, $xc - $rx, $yc + ($ry * $MyArc), $xc - $rx, $yc);
        }
        else {
            $this->appendLine($x, $y + $h);
        }
        $xc = $x + $rx;
        $yc = $y + $ry;
        $this->appendLine($x, $yc);
        if ($round_corner[3]) {
            $this->appendCurve($xc - $rx, $yc - ($ry * $MyArc), $xc - ($rx * $MyArc), $yc - $ry, $xc, $yc - $ry);
        }
        else {
            $this->appendLine($x, $y);
            $this->appendLine($x + $rx, $y);
        }
        $this->append($op);
    }
}