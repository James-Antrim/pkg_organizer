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

trait Geometry
{
    /** Cap value of the last line. */
    protected string $lineCapStyle = '0 J';
    /** Dash value of the last line. */
    protected string $lineDashStyle = '[] 0 d';
    /** Join value of the last line. */
    protected string $lineJoinStyle = '0 j';
    protected float $lineWidth = 0.20108333333334;
    /** Width value of the last line. */
    protected string $lineWidthStyle = '';

    /**
     * Append an elliptical arc to the current path, and returns the coordinates of the bounding box [x min, y min, x max, y max]. An ellipse is
     * formed from n Bézier curves.
     * @param float $centerX    abscissa of center point
     * @param float $centerY    ordinate of center point
     * @param float $xRadius
     * @param float $yRadius    (if ry = 0 then is a circle, see Circle())
     * @param float $xAngle     angle between the X-axis and the major axis of the ellipse
     * @param float $angleStart angle start of draw line
     * @param float $angleEnd   angle finish of draw line
     * @param bool  $pie        border points unmarked for pie sectors
     * @param int   $curves     number of curves used to draw a 90 degrees portion of ellipse
     * @param bool  $startPoint output a starting point
     * @param bool  $clockwise
     * @param bool  $svgMode    (angles already calculated)
     * @return array
     * @author Nicola Asuni
     */
    protected function appendArc(
        float $centerX,
        float $centerY,
        float $xRadius,
        float $yRadius,
        float $xAngle = 0,
        float $angleStart = 0,
        float $angleEnd = 360,
        bool  $pie = false,
        int   $curves = 2,
        bool  $startPoint = true,
        bool  $clockwise = false,
        bool  $svgMode = false
    ): array
    {
        if ($xRadius <= 0 or $yRadius < 0) {
            return [];
        }

        if ($curves < 2) {
            $curves = 2;
        }

        $xMax = 0;
        $xMin = 2147483647;
        $yMax = 0;
        $yMin = 2147483647;

        if ($pie) {
            // center of the arc
            $this->point($centerX, $centerY);
        }
        $xAngle     = deg2rad((float) $xAngle);
        $angleStart = deg2rad((float) $angleStart);
        $angleEnd   = deg2rad((float) $angleEnd);

        if ($svgMode) {
            $as = $angleStart;
            $af = $angleEnd;
        }
        else {
            $as = atan2((sin($angleStart) / $yRadius), (cos($angleStart) / $xRadius));
            $af = atan2((sin($angleEnd) / $yRadius), (cos($angleEnd) / $xRadius));
        }
        if ($as < 0) {
            $as += (2 * M_PI);
        }
        if ($af < 0) {
            $af += (2 * M_PI);
        }
        if ($clockwise and ($as > $af)) {
            // reverse rotation
            $as -= (2 * M_PI);
        }
        elseif (!$clockwise and ($as < $af)) {
            // reverse rotation
            $af -= (2 * M_PI);
        }
        $total_angle = ($af - $as);
        if ($curves < 2) {
            $curves = 2;
        }
        // total arcs to draw
        $curves *= (2 * abs($total_angle) / M_PI);
        $curves = round($curves) + 1;
        // angle of each arc
        $arcAngle = ($total_angle / $curves);
        // center point in PDF coordinates
        $x0 = $centerX;
        $y0 = ($this->height - $centerY);
        // starting angle
        $ang       = $as;
        $alpha     = sin($arcAngle) * ((sqrt(4 + (3 * pow(tan(($arcAngle) / 2), 2))) - 1) / 3);
        $cosXAngle = cos($xAngle);
        $sinXAngle = sin($xAngle);
        $cosAngle  = cos($ang);
        $sinAngle  = sin($ang);
        $px1       = $x0 + ($xRadius * $cosXAngle * $cosAngle) - ($yRadius * $sinXAngle * $sinAngle);
        $py1       = $y0 + ($xRadius * $sinXAngle * $cosAngle) + ($yRadius * $cosXAngle * $sinAngle);
        $qx1       = ($alpha * ((-$xRadius * $cosXAngle * $sinAngle) - ($yRadius * $sinXAngle * $cosAngle)));
        $qy1       = ($alpha * ((-$xRadius * $sinXAngle * $sinAngle) + ($yRadius * $cosXAngle * $cosAngle)));
        if ($pie) {
            // line from center to arc starting point
            $this->appendLine($px1, $this->height - $py1);
        }
        elseif ($startPoint) {
            // arc starting point
            $this->point($px1, $this->height - $py1);
        }
        // draw arcs
        for ($index = 1; $index <= $curves; ++$index) {
            // starting angle
            $ang = $as + ($index * $arcAngle);
            if ($index == $curves) {
                $ang = $af;
            }
            $cosAngle = cos($ang);
            $sinAngle = sin($ang);
            $px2      = $x0 + ($xRadius * $cosXAngle * $cosAngle) - ($yRadius * $sinXAngle * $sinAngle);
            $py2      = $y0 + ($xRadius * $sinXAngle * $cosAngle) + ($yRadius * $cosXAngle * $sinAngle);
            $qx2      = ($alpha * ((-$xRadius * $cosXAngle * $sinAngle) - ($yRadius * $sinXAngle * $cosAngle)));
            $qy2      = ($alpha * ((-$xRadius * $sinXAngle * $sinAngle) + ($yRadius * $cosXAngle * $cosAngle)));
            // draw arc
            $cx1 = ($px1 + $qx1);
            $cy1 = ($this->height - ($py1 + $qy1));
            $cx2 = ($px2 - $qx2);
            $cy2 = ($this->height - ($py2 - $qy2));
            $cx3 = $px2;
            $cy3 = ($this->height - $py2);
            $this->appendCurve($cx1, $cy1, $cx2, $cy2, $cx3, $cy3);

            // get bounding box coordinates
            $xMin = min($xMin, $cx1, $cx2, $cx3);
            $yMin = min($yMin, $cy1, $cy2, $cy3);
            $xMax = max($xMax, $cx1, $cx2, $cx3);
            $yMax = max($yMax, $cy1, $cy2, $cy3);
            // move to next point
            $px1 = $px2;
            $py1 = $py2;
            $qx1 = $qx2;
            $qy1 = $qy2;
        }

        if ($pie) {
            $this->appendLine($centerX, $centerY);
            // get bounding box coordinates
            $xMin = min($xMin, $centerX);
            $yMin = min($yMin, $centerY);
            $xMax = max($xMax, $centerX);
            $yMax = max($yMax, $centerY);
        }

        return [$xMin, $yMin, $xMax, $yMax];
    }

    /**
     * Append a cubic Bézier curve to the current path
     * The curve shall extend from the current point to the point (x3, y3), using (x1, y1) and (x2, y2) as the Bézier control points
     * The new current point shall be (x3, y3)
     * @param float $x1 abscissa of control point 1
     * @param float $y1 ordinate of control point 1
     * @param float $x2 abscissa of control point 2
     * @param float $y2 ordinate of control point 2
     * @param float $x3 abscissa of end point
     * @param float $y3 ordinate of end point
     */
    protected function appendCurve(float $x1, float $y1, float $x2, float $y2, float $x3, float $y3): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $x1Points = $x1 * $this->factor;
        $x2Points = $x2 * $this->factor;
        $x3Points = $x3 * $this->factor;
        $y1Points = ($this->height - $y1) * $this->factor;
        $y2Points = ($this->height - $y2) * $this->factor;
        $y3Points = ($this->height - $y3) * $this->factor;

        $this->append(sprintf('%F %F %F %F %F %F c', $x1Points, $y1Points, $x2Points, $y2Points, $x3Points, $y3Points));

    }

    /**
     * Append a straight line segment from the current point to the point (x, y)
     * The new current point shall be (x, y)
     * @param float $x abscissa of end point
     * @param float $y ordinate of end point
     * @return void
     */
    protected function appendLine(float $x, float $y): void
    {
        if ($this->state === self::READY) {
            $this->append(sprintf('%F %F l', $x * $this->factor, ($this->height - $y) * $this->factor));
        }
    }

    /**
     * Append a rectangle to the current path as a complete subpath, with lower-left corner (x, y) and dimensions width and height in user space
     * @param float  $x       abscissa of upper-left corner
     * @param float  $y       ordinate of upper-left corner
     * @param float  $width
     * @param float  $height
     * @param string $options options
     * @return void
     */
    protected function appendRectangle(float $x, float $y, float $width, float $height, string $options): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $heightPoints = -$height * $this->factor;
        $widthPoints  = $width * $this->factor;
        $xPoints      = $x * $this->factor;
        $yPoints      = ($this->height - $y) * $this->factor;
        $this->append(sprintf('%F %F %F %F re %s', $xPoints, $yPoints, $widthPoints, $heightPoints, $options));
    }

    /**
     * Draws a circle formed from n Bézier curves
     * @param float  $x          abscissa of center point
     * @param float  $y          ordinate of center point
     * @param float  $radius     Radius
     * @param float  $startAngle Angle start of draw line. Default value: 0
     * @param float  $endAngle   Angle finish of draw line. Default value: 360
     * @param string $style      Style of rendering
     * @param array  $lineStyle  Line style of circle. Array like for SetLineStyle(). Default value: default line style (empty array)
     * @param array  $fillColor  fill color. Format: [red, green, blue]. Default value: default color (empty array)
     * @param int    $curves     number of curves used to draw a 90 degrees portion of circle
     * @see self::RENDERING_STYLES
     */
    public function circle(float $x, float $y, float $radius, float $startAngle = 0, float $endAngle = 360, string $style = '', array $lineStyle = [], array $fillColor = [], int $curves = 2): void
    {
        $this->ellipse($x, $y, $radius, $radius, 0, $startAngle, $endAngle, $style, $lineStyle, $fillColor, $curves);
    }

    /**
     * Draws an ellipse formed from n Bézier curves
     * @param float  $x          abscissa of center point
     * @param float  $y          ordinate of center point
     * @param float  $xRadius    Horizontal radius
     * @param float  $yRadius    Vertical radius (if ry = 0 then is a circle, see Circle()). Default value: 0
     * @param float  $angle      Angle oriented (anti-clockwise). Default value: 0
     * @param float  $startAngle Angle start of draw line. Default value: 0
     * @param float  $endAngle   Angle finish of draw line. Default value: 360
     * @param string $style
     * @param array  $lineStyle  Line style of ellipse. Array like for SetLineStyle(). Default value: default line style (empty array)
     * @param array  $fillColor  Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array)
     * @param int    $curves     Number of curves used to draw a 90 degrees portion of ellipse
     * @see self::RENDERING_STYLES
     */
    public function ellipse(float $x, float $y, float $xRadius, float $yRadius = 0, float $angle = 0, float $startAngle = 0, float $endAngle = 360, string $style = '', array $lineStyle = [], array $fillColor = [], int $curves = 2): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $yRadius = $yRadius ?: $xRadius;

        if (str_contains($style, self::FILL) and $fillColor) {
            $this->fillColorArray($fillColor);
        }

        $operator = $this->pathPaintingOperators($style);

        if ($operator === self::FILL) {
            $lineStyle = [];
        }

        if ($lineStyle) {
            $this->lineStyle($lineStyle);
        }

        $this->appendArc($x, $y, $xRadius, $yRadius, $angle, $startAngle, $endAngle, false, $curves);
        $this->append($operator);
    }

    /**
     * Draws a line between two points
     * @param float $x1    abscissa of first point
     * @param float $y1    ordinate of first point
     * @param float $x2    abscissa of second point
     * @param float $y2    ordinate of second point
     * @param array $style Line style. Array like for SetLineStyle(). Default value: default line style (empty array)
     * @see   lineWidth(), SetDrawColor(), lineStyle()
     */
    public function line(float $x1, float $y1, float $x2, float $y2, array $style = []): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        if (is_array($style)) {
            $this->lineStyle($style);
        }

        $this->point($x1, $y1);
        $this->appendLine($x2, $y2);
        $this->append(self::STROKE);
    }

    /**
     * Sets line style properties and generates a PDF style command, optionally appends the command to the document
     * @param array $configuration   Keys among the following:
     *                               - cap (string): type of cap to put on the line, possible values are:
     *                               => butt: normal rectangular end
     *                               => round: rounded end
     *                               => square: projects a flat end past the end of the line
     *                               - color (array): draw color, format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]
     *                               - dash (mixed): dash pattern, possible values are:
     *                               => 0: no dash
     *                               => series of length values of the on and off dashes
     *                               ==> ex "2" 2 on, 2 off, 2 on, 2 off, ...
     *                               ==> ex "2,1" is 2 on, 1 off, 2 on, 1 off, ...
     *                               - join (string): Type of join. Possible values are: miter, round, bevel
     *                               - phase (integer): dash pattern modifier, shifts the point at which the pattern starts
     *                               - width (float): Width of the line in user units
     * @param bool  $append          whether to append the style
     * @return string
     */
    public function lineStyle(array $configuration, bool $append = true): string
    {
        $style = '';

        if (isset($configuration['width'])) {
            $this->lineWidth      = $configuration['width'];
            $this->lineWidthStyle = sprintf('%F w', ($configuration['width'] * $this->factor));

            $style .= $this->lineWidthStyle . ' ';
        }

        if (isset($configuration['cap'])) {
            $ca = ['butt' => 0, 'round' => 1, 'square' => 2];

            if (isset($ca[$configuration['cap']])) {
                $this->lineCapStyle = $ca[$configuration['cap']] . ' J';

                $style .= $this->lineCapStyle . ' ';
            }
        }

        if (isset($configuration['join'])) {
            $ja = ['miter' => 0, 'round' => 1, 'bevel' => 2];
            if (isset($ja[$configuration['join']])) {
                $this->lineJoinStyle = $ja[$configuration['join']] . ' j';

                $style .= $this->lineJoinStyle . ' ';
            }
        }

        if (isset($configuration['dash'])) {
            $dashString = '';
            if ($configuration['dash']) {
                $tab = preg_match('/^.+,/', $configuration['dash']) > 0 ? explode(',', $configuration['dash']) : [$configuration['dash']];

                foreach ($tab as $key => $value) {
                    if ($key) {
                        $dashString .= ' ';
                    }

                    $dashString .= sprintf('%F', $value);
                }
            }

            if (!isset($configuration['phase']) or !$configuration['dash']) {
                $configuration['phase'] = 0;
            }

            $this->lineDashStyle = sprintf('[%s] %F d', $dashString, $configuration['phase']);

            $style .= $this->lineDashStyle . ' ';
        }

        if (isset($configuration['color'])) {
            $style .= $this->drawColorArray($configuration['color'], false) . ' ';
        }

        if ($append and $this->state === self::READY) {
            $this->append($style);
        }

        return $style;
    }

    /**
     * Defines the line width. By default, the value equals 0.2 mm. The method can be called before the first page is created and the value is retained from page to page
     * @param float|null $width The width
     * @return float
     * @see   line(), rectangle(), Cell(), MultiCell()
     */
    public function lineWidth(float|null $width): float
    {
        if (is_float($width)) {
            $this->lineWidth      = $width;
            $this->lineWidthStyle = sprintf('%F w', ($width * $this->factor));

            if ($this->state === self::READY) {
                $this->append($this->lineWidthStyle);
            }
        }

        return $this->lineWidth;
    }

    /**
     * Draw the sector of a circle
     * It can be used for instance to render pie charts
     * @param float  $xc abscissa of the center
     * @param float  $yc ordinate of the center
     * @param float  $r  the radius
     * @param float  $a  start angle (degrees)
     * @param float  $b  end angle (degrees)
     * @param string $style
     * @param bool   $cw clockwise
     * @param int    $o  origin of angles (0 for 3 o'clock, 90 for noon, 180 for 9 o'clock, 270 for 6 o'clock)
     * @see self::RENDERING_STYLES
     */
    public function pieSector(float $xc, float $yc, float $r, float $a, float $b, string $style = 'FD', bool $cw = true, int $o = 90): void
    {
        $this->pieSectorXY($xc, $yc, $r, $r, $a, $b, $style, $cw, $o);
    }

    /**
     * Draw the sector of an ellipse.
     * @param float  $centerX    abscissa of the center
     * @param float  $centerY    ordinate of the center
     * @param float  $xRadius
     * @param float  $yRadius
     * @param float  $startAngle (degrees)
     * @param float  $endAngle   (degrees)
     * @param string $style
     * @param bool   $cw         clockwise
     * @param int    $o          origin of angles (0 for 3 o'clock, 90 for noon, 180 for 9 o'clock, 270 for 6 o'clock)
     * @param int    $curves     number of curves used to draw a 90 degrees portion of arc
     * @see self::RENDERING_STYLES
     */
    public function pieSectorXY(
        float  $centerX,
        float  $centerY,
        float  $xRadius,
        float  $yRadius,
        float  $startAngle,
        float  $endAngle,
        string $style = self::FILL_DRAW,
        bool   $cw = false,
        int    $o = 0,
        int    $curves = 2
    ): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $op = $this->pathPaintingOperators($style);

        if ($cw) {
            $d          = $endAngle;
            $endAngle   = (360 - $startAngle + $o);
            $startAngle = (360 - $d + $o);
        }
        else {
            $endAngle   += $o;
            $startAngle += $o;
        }
        $this->appendArc($centerX, $centerY, $xRadius, $yRadius, 0, $startAngle, $endAngle, true, $curves);
        $this->append($op);
    }

    /**
     * Begin a new subpath by moving the current point to coordinates (x, y), omitting any connecting line segment
     * @param float $x abscissa of point
     * @param float $y ordinate of point
     */
    protected function point(float $x, float $y): void
    {
        if ($this->state === self::READY) {
            $this->append(sprintf('%F %F m', $x * $this->factor, ($this->height - $y) * $this->factor));
        }
    }

    /**
     * Draws a polygon
     * @param array  $values      Points 0 to ($np - 1). Array with values (x0, y0, x1, y1,..., x(np-1), y(np - 1))
     * @param string $style
     * @param array  $lineStyle   Line style of polygon. Array with keys among the following:
     *                            <ul>
     *                            <li>all: Line style of all lines. Array like for SetLineStyle().</li>
     *                            <li>0 to ($np - 1): Line style of each line. Array like for SetLineStyle().</li>
     *                            </ul>
     *                            If a key is not present or is null, not draws the line. Default value is default line style (empty array)
     * @param array  $fillColor   Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array)
     * @param bool   $closes      if true the polygon is closes, otherwise will remain open
     * @see self::RENDERING_STYLES
     */
    public function polygon(array $values, string $style = self::REGULAR, array $lineStyle = [], array $fillColor = [], bool $closes = true): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $valueCount  = count($values);
        $pointsCount = $valueCount / 2;

        if ($closes) {
            // Close polygon by adding the first 2 points at the end (one line)
            for ($i = 0; $i < 4; ++$i) {
                $values[$valueCount + $i] = $values[$i];
            }
            // Copy style for the last added line
            if (isset($lineStyle[0])) {
                $lineStyle[$pointsCount] = $lineStyle[0];
            }
            $valueCount += 4;
        }

        if (in_array($style, self::FILLING_STYLES) and is_array($fillColor)) {
            $this->fillColorArray($fillColor);
        }

        $op = $this->pathPaintingOperators($style);
        if ($op === self::FILL) {
            $lineStyle = [];
        }

        $draw = true;
        if ($lineStyle) {
            if (isset($lineStyle['all'])) {
                $this->lineStyle($lineStyle['all']);
            }
            else {
                $draw = false;
                if ($op === self::BOLD) {
                    // draw fill
                    $op = 'f';
                    $this->point($values[0], $values[1]);
                    for ($i = 2; $i < $valueCount; $i = $i + 2) {
                        $this->appendLine($values[$i], $values[$i + 1]);
                    }
                    $this->append($op);
                }
                // draw outline
                $this->point($values[0], $values[1]);
                for ($index = 2; $index < $valueCount; $index = $index + 2) {
                    $line_num = ($index / 2) - 1;
                    if (isset($lineStyle[$line_num])) {
                        if ($lineStyle[$line_num] != 0) {
                            if (is_array($lineStyle[$line_num])) {
                                $this->append(self::STROKE);
                                $this->lineStyle($lineStyle[$line_num]);
                                $this->point($values[$index - 2], $values[$index - 1]);
                                $this->appendLine($values[$index], $values[$index + 1]);
                                $this->append(self::STROKE);
                                $this->point($values[$index], $values[$index + 1]);
                            }
                            else {
                                $this->appendLine($values[$index], $values[$index + 1]);
                            }
                        }
                    }
                    else {
                        $this->appendLine($values[$index], $values[$index + 1]);
                    }
                }
                $this->append($op);
            }
        }
        if ($draw) {
            $this->point($values[0], $values[1]);
            for ($index = 2; $index < $valueCount; $index = $index + 2) {
                $this->appendLine($values[$index], $values[$index + 1]);
            }
            $this->append($op);
        }
    }

    /**
     * Draws a polygonal line
     * @param array  $values     Points 0 to ($np - 1). Array with values (x0, y0, x1, y1,..., x(np-1), y(np - 1))
     * @param string $style
     * @param array  $lineStyle  Line style of polygon. Array with keys among the following:
     *                           <ul>
     *                           <li>all: Line style of all lines. Array like for SetLineStyle().</li>
     *                           <li>0 to ($np - 1): Line style of each line. Array like for SetLineStyle().</li>
     *                           </ul>
     *                           If a key is not present or is null, not draws the line. Default value is default line style (empty array)
     * @param array  $fillColor  Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array)
     * @see self::RENDERING_STYLES
     */
    public function polyLine(array $values, string $style = self::REGULAR, array $lineStyle = [], array $fillColor = []): void
    {
        $this->polygon($values, $style, $lineStyle, $fillColor, false);
    }

    /**
     * Draws a rectangle
     * @param float  $x            abscissa of upper-left corner
     * @param float  $y            ordinate of upper-left corner
     * @param float  $width
     * @param float  $height
     * @param string $style
     * @param array  $borderStyle  Border style of rectangle. Array with keys among the following:
     *                             <ul>
     *                             <li>all: Line style of all borders. Array like for SetLineStyle().</li>
     *                             <li>L, T, R, B or combinations: Line style of left, top, right or bottom border. Array like for lineStyle().</li>
     *                             </ul>
     *                             If a key is not present or is null, the correspondent border is not drawn. Default value: default line style (empty array)
     * @param array  $fillColor    fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]
     * @see   self::RENDERING_STYLES
     */
    public function rectangle(float $x, float $y, float $width, float $height, string $style = self::REGULAR, array $borderStyle = [], array $fillColor = []): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $style = ($style and in_array($style, self::RENDERING_STYLES)) ? $style : self::STROKE;

        if (in_array($style, self::FILLING_STYLES) and !empty($fillColor)) {
            // set background color
            $this->fillColorArray($fillColor);
        }

        if (!empty($borderStyle)) {
            if (!empty($borderStyle['all'])) {
                $this->lineStyle($borderStyle['all']);
                $borderStyle = [];
            }
            // Remove stroke operator from style so that borders won't be drawn implicitly
            else {
                $strokelessStyles = [
                    'S' => '', 'D' => '', 's' => '', 'd' => '', 'B' => 'F', 'FD' => 'F', 'DF' => 'F', 'B*' => 'F*', 'F*D' => 'F*', 'DF*' => 'F*',
                    'b' => 'f', 'fd' => 'f', 'df' => 'f', 'b*' => 'f*', 'f*d' => 'f*', 'df*' => 'f*'
                ];
                if (isset($strokelessStyles[$style])) {
                    $style = $strokelessStyles[$style];
                }
            }
        }
        if (!empty($style)) {
            $op = $this->pathPaintingOperators($style);
            $this->appendRectangle($x, $y, $width, $height, $op);
        }
        if (!empty($borderStyle)) {
            $borderStyle2 = [];
            foreach ($borderStyle as $line => $value) {
                $length = strlen($line);
                for ($i = 0; $i < $length; ++$i) {
                    $borderStyle2[$line[$i]] = $value;
                }
            }
            $borderStyle = $borderStyle2;
            if (isset($borderStyle['L']) and $borderStyle['L']) {
                $this->line($x, $y, $x, $y + $height, $borderStyle['L']);
            }
            if (isset($borderStyle['T']) and $borderStyle['T']) {
                $this->line($x, $y, $x + $width, $y, $borderStyle['T']);
            }
            if (isset($borderStyle['R']) and $borderStyle['R']) {
                $this->line($x + $width, $y, $x + $width, $y + $height, $borderStyle['R']);
            }
            if (isset($borderStyle['B']) and $borderStyle['B']) {
                $this->line($x, $y + $height, $x + $width, $y + $height, $borderStyle['B']);
            }
        }
    }

    /**
     * Draws a rounded rectangle
     * @param float  $x           abscissa of upper-left corner
     * @param float  $y           ordinate of upper-left corner
     * @param float  $width
     * @param float  $height
     * @param float  $xRadius     the x-axis radius of the ellipse used to round off the corners of the rectangle
     * @param float  $yRadius     the y-axis radius of the ellipse used to round off the corners of the rectangle
     * @param string $corners     Draws rounded corner or not. String with a 0 (not rounded i-corner) or 1 (rounded i-corner) in i-position. Positions are, in order and begin to 0: top right, bottom right, bottom left and top left. Default value: all rounded corner ("1111")
     * @param string $style
     * @param array  $borderStyle Border style of rectangle. Array like for SetLineStyle(). Default value: default line style (empty array)
     * @param array  $fillColor   Fill color. Format: [GREY] or [R,G,B] or [C,M,Y,K] or [C,M,Y,K,SpotColorName]. Default value: default color (empty array)
     * @see self::RENDERING_STYLES
     */
    public function RoundedRectXY(
        float  $x,
        float  $y,
        float  $width,
        float  $height,
        float  $xRadius,
        float  $yRadius,
        string $corners = '1111',
        string $style = self::REGULAR,
        array  $borderStyle = [],
        array  $fillColor = []
    ): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        // Not rounded
        if ($corners === '0000' or ($xRadius === $yRadius and $xRadius == 0)) {
            $this->rectangle($x, $y, $width, $height, $style, $borderStyle, $fillColor);
            return;
        }

        if (in_array($style, self::FILLING_STYLES) and is_array($fillColor)) {
            $this->fillColorArray($fillColor);
        }

        $op = $this->pathPaintingOperators($style);
        if ($op === self::FILL) {
            $borderStyle = [];
        }

        if ($borderStyle) {
            $this->lineStyle($borderStyle);
        }

        $MyArc = 4 / 3 * (sqrt(2) - 1);
        $this->point($x + $xRadius, $y);
        $xc = $x + $width - $xRadius;
        $yc = $y + $yRadius;
        $this->appendLine($xc, $y);

        if ($corners[0]) {
            $this->appendCurve($xc + ($xRadius * $MyArc), $yc - $yRadius, $xc + $xRadius, $yc - ($yRadius * $MyArc), $xc + $xRadius, $yc);
        }
        else {
            $this->appendLine($x + $width, $y);
        }

        $xc = $x + $width - $xRadius;
        $yc = $y + $height - $yRadius;
        $this->appendLine($x + $width, $yc);

        if ($corners[1]) {
            $this->appendCurve($xc + $xRadius, $yc + ($yRadius * $MyArc), $xc + ($xRadius * $MyArc), $yc + $yRadius, $xc, $yc + $yRadius);
        }
        else {
            $this->appendLine($x + $width, $y + $height);
        }

        $xc = $x + $xRadius;
        $yc = $y + $height - $yRadius;
        $this->appendLine($xc, $y + $height);

        if ($corners[2]) {
            $this->appendCurve($xc - ($xRadius * $MyArc), $yc + $yRadius, $xc - $xRadius, $yc + ($yRadius * $MyArc), $xc - $xRadius, $yc);
        }
        else {
            $this->appendLine($x, $y + $height);
        }

        $xc = $x + $xRadius;
        $yc = $y + $yRadius;
        $this->appendLine($x, $yc);

        if ($corners[3]) {
            $this->appendCurve($xc - $xRadius, $yc - ($yRadius * $MyArc), $xc - ($xRadius * $MyArc), $yc - $yRadius, $xc, $yc - $yRadius);
        }
        else {
            $this->appendLine($x, $y);
            $this->appendLine($x + $xRadius, $y);
        }

        $this->append($op);
    }

    /**
     * Returns the angle in radians between two vectors.
     * @param int $x1 X coordinate of first vector point
     * @param int $y1 Y coordinate of first vector point
     * @param int $x2 X coordinate of second vector point
     * @param int $y2 Y coordinate of second vector point
     */
    protected function vectorAngle(int $x1, int $y1, int $x2, int $y2): float
    {
        $dprod = ($x1 * $x2) + ($y1 * $y2);
        $dist1 = sqrt(($x1 * $x1) + ($y1 * $y1));
        $dist2 = sqrt(($x2 * $x2) + ($y2 * $y2));
        $angle = acos($dprod / ($dist1 * $dist2));

        if (is_nan($angle)) {
            $angle = M_PI;
        }

        if ((($x1 * $y2) - ($x2 * $y1)) < 0) {
            $angle *= -1;
        }

        return $angle;
    }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED

}