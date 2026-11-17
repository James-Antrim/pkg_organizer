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

trait Transformations
{


/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED

    /**
     * Starts a 2D tranformation saving current graphic state.
     * This function must be called before scaling, mirroring, translation, rotation and skewing.
     * Use StartTransform() before, and StopTransform() after the transformations to restore the normal behavior.
     * @see   StartTransform(), StopTransform()
     */
    public function StartTransform()
    {
        if ($this->state !== self::READY) {
            return;
        }
        $this->_outSaveGraphicsState();
        if ($this->inXObject) {
            // we are inside an XObject template
            $this->xObjects[$this->xObjectID]['transfmrk'][] = strlen($this->xObjects[$this->xObjectID]['outdata']);
        }
        else {
            $this->transfmrk[$this->page][] = $this->pageLengths[$this->page];
        }
        ++$this->transfmatrix_key;
        $this->transfmatrix[$this->transfmatrix_key] = [];
    }

    /**
     * Stops a 2D tranformation restoring previous graphic state.
     * This function must be called after scaling, mirroring, translation, rotation and skewing.
     * Use StartTransform() before, and StopTransform() after the transformations to restore the normal behavior.
     * @see   StartTransform(), StopTransform()
     */
    public function StopTransform()
    {
        if ($this->state !== self::READY) {
            return;
        }
        $this->_outRestoreGraphicsState();
        if (isset($this->transfmatrix[$this->transfmatrix_key])) {
            array_pop($this->transfmatrix[$this->transfmatrix_key]);
            --$this->transfmatrix_key;
        }
        if ($this->inXObject) {
            // we are inside an XObject template
            array_pop($this->xObjects[$this->xObjectID]['transfmrk']);
        }
        else {
            array_pop($this->transfmrk[$this->page]);
        }
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
        $this->Transform($tm);
    }

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
        $this->Transform($tm);
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
        $this->Transform($tm);
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
        $this->Transform($tm);
    }

    /**
     * Apply graphic transformations.
     * @param array $tm transformation matrix
     * @see   StartTransform(), StopTransform()
     */
    protected function Transform($tm)
    {
        if ($this->state !== self::READY) {
            return;
        }
        $this->append(sprintf('%F %F %F %F %F %F cm', $tm[0], $tm[1], $tm[2], $tm[3], $tm[4], $tm[5]));
        // add tranformation matrix
        $this->transfmatrix[$this->transfmatrix_key][] = ['a' => $tm[0], 'b' => $tm[1], 'c' => $tm[2], 'd' => $tm[3], 'e' => $tm[4], 'f' => $tm[5]];
        // update transformation mark
        if ($this->inXObject) {
            // we are inside an XObject template
            if (end($this->xObjects[$this->xObjectID]['transfmrk']) !== false) {
                $key                                                 = key($this->xObjects[$this->xObjectID]['transfmrk']);
                $this->xObjects[$this->xObjectID]['transfmrk'][$key] = strlen($this->xObjects[$this->xObjectID]['outdata']);
            }
        }
        elseif (end($this->transfmrk[$this->page]) !== false) {
            $key                                = key($this->transfmrk[$this->page]);
            $this->transfmrk[$this->page][$key] = $this->pageLengths[$this->page];
        }
    }
}