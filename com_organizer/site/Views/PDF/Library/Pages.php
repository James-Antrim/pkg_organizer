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

trait Pages
{
    use Headers;

    protected int $currentPageGroup = 0;
    protected float $leftMargin = 0;
    protected float $originalLeftMargin = 0;
    protected float $originalRightMargin = 0;
    protected float $rightMargin = 0;
    protected int $page = 0;
    protected array $pageAnnotations = [];
    protected array $pageDimensions = [];
    protected array $pageGroups = [];
    protected array $pageLengths = [];
    protected string $pageMode = self::USE_NONE;
    protected array $pageObjectIDs = [];
    protected array $pageObjects = [];
    protected array $pages = [];
    protected string $pageZoom = self::FULL_WIDTH;

    /**
     * Defines the way the document is to be displayed by the viewer.
     * @param string $zoom
     * @param string $layout
     * @param string $mode
     * @see self::PAGE_ZOOM_DESCRIPTIONS, self::PAGE_LAYOUT_DESCRIPTIONS, self::PAGE_MODE_DESCRIPTIONS
     */
    public function displayMode(string $zoom = self::FULL_WIDTH, string $layout = self::SINGLE_PAGE, string $mode = self::USE_NONE): void
    {
        $this->pageZoom = in_array($zoom, self::PAGE_ZOOMS) ? $zoom : self::FULL_WIDTH;
        $this->layoutMode($layout);
        $this->pageMode($mode);
    }

    /**
     * Set the canonical page layout mode.
     * @param string $layout
     * @see self::PAGE_LAYOUT_DESCRIPTIONS
     */
    public function layoutMode(string $layout = self::SINGLE_PAGE): void
    {
        $this->LayoutMode = match ($layout) {
            self::ONE_COLUMN => self::ONE_COLUMN,
            self::TWO_COLUMN_LEFT => self::TWO_COLUMN_LEFT,
            self::TWO_COLUMN_RIGHT => self::TWO_COLUMN_RIGHT,
            self::TWO_PAGE_LEFT => self::TWO_PAGE_LEFT,
            self::TWO_PAGE_RIGHT => self::TWO_PAGE_RIGHT,
            default => self::SINGLE_PAGE,
        };
    }

    /**
     * Getter / setter for the page buffer
     * @param int    $pageNumber
     * @param string $content
     * @param bool   $append true => append, false => replace
     * @return string
     */
    protected function pageBuffer(int $pageNumber, string $content = '', bool $append = false): string
    {
        if ($content) {
            $existing                       = $this->pages[$pageNumber] ?? '';
            $this->pages[$pageNumber]       = $append ? $existing . $content : $content;
            $this->pageLengths[$pageNumber] = strlen($this->pages[$pageNumber]);
        }

        return $this->pages[$pageNumber] ?? '';
    }

    /**
     * References page dimensions by format name.
     * @param string $format >
     * @return array
     * @see self::PAGE_FORMATS
     */
    public function pageDimensionsFromFormat(string $format = 'A4'): array
    {
        return self::PAGE_FORMATS[$format] ?: self::PAGE_FORMATS['A4'];
    }

    /**
     * Sets the canonical page layout mode, specifying how the document should be displayed when opened.
     * @param string $mode
     * @see self::PAGE_MODE_DESCRIPTIONS
     */
    public function pageMode(string $mode = self::USE_NONE): void
    {
        $this->pageMode = match ($mode) {
            self::FULL_SCREEN => self::FULL_SCREEN,
            '', self::USE_ATTACHMENTS => self::USE_ATTACHMENTS,
            self::USE_OC => self::USE_OC,
            self::USE_OUTLINES => self::USE_OUTLINES,
            self::USE_THUMBS => self::USE_THUMBS,
            default => self::USE_NONE,
        };
    }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Change the format of the current page
     * @param mixed  $format      The format used for pages. It can be either: one of the string values specified at getPageSizeFromFormat() documentation or an array of two numbers (width, height) or an array containing the following measures and options:<ul>
     *                            <li>['format'] = page format name (one of the above);</li>
     *                            <li>['Rotate'] : The number of degrees by which the page shall be rotated clockwise when displayed or printed. The value shall be a multiple of 90.</li>
     *                            <li>['PZ'] : The page's preferred zoom (magnification) factor.</li>
     *                            <li>['MediaBox'] : the boundaries of the physical medium on which the page shall be displayed or printed:</li>
     *                            <li>['MediaBox']['llx'] : lower-left x coordinate</li>
     *                            <li>['MediaBox']['lly'] : lower-left y coordinate</li>
     *                            <li>['MediaBox']['urx'] : upper-right x coordinate</li>
     *                            <li>['MediaBox']['ury'] : upper-right y coordinate</li>
     *                            <li>['CropBox'] : the visible region of default user space:</li>
     *                            <li>['CropBox']['llx'] : lower-left x coordinate</li>
     *                            <li>['CropBox']['lly'] : lower-left y coordinate</li>
     *                            <li>['CropBox']['urx'] : upper-right x coordinate</li>
     *                            <li>['CropBox']['ury'] : upper-right y coordinate</li>
     *                            <li>['BleedBox'] : the region to which the contents of the page shall be clipped when output in a production environment:</li>
     *                            <li>['BleedBox']['llx'] : lower-left x coordinate</li>
     *                            <li>['BleedBox']['lly'] : lower-left y coordinate</li>
     *                            <li>['BleedBox']['urx'] : upper-right x coordinate</li>
     *                            <li>['BleedBox']['ury'] : upper-right y coordinate</li>
     *                            <li>['TrimBox'] : the intended dimensions of the finished page after trimming:</li>
     *                            <li>['TrimBox']['llx'] : lower-left x coordinate</li>
     *                            <li>['TrimBox']['lly'] : lower-left y coordinate</li>
     *                            <li>['TrimBox']['urx'] : upper-right x coordinate</li>
     *                            <li>['TrimBox']['ury'] : upper-right y coordinate</li>
     *                            <li>['ArtBox'] : the extent of the page's meaningful content:</li>
     *                            <li>['ArtBox']['llx'] : lower-left x coordinate</li>
     *                            <li>['ArtBox']['lly'] : lower-left y coordinate</li>
     *                            <li>['ArtBox']['urx'] : upper-right x coordinate</li>
     *                            <li>['ArtBox']['ury'] : upper-right y coordinate</li>
     *                            <li>['BoxColorInfo'] :specify the colours and other visual characteristics that should be used in displaying guidelines on the screen for each of the possible page boundaries other than the MediaBox:</li>
     *                            <li>['BoxColorInfo'][BOXTYPE]['C'] : an array of three numbers in the range 0-255, representing the components in the DeviceRGB colour space.</li>
     *                            <li>['BoxColorInfo'][BOXTYPE]['W'] : the guideline width in default user units</li>
     *                            <li>['BoxColorInfo'][BOXTYPE]['S'] : the guideline style: S = Solid; D = Dashed</li>
     *                            <li>['BoxColorInfo'][BOXTYPE]['D'] : dash array defining a pattern of dashes and gaps to be used in drawing dashed guidelines</li>
     *                            <li>['trans'] : the style and duration of the visual transition to use when moving from another page to the given page during a presentation</li>
     *                            <li>['trans']['Dur'] : The page's display duration (also called its advance timing): the maximum length of time, in seconds, that the page shall be displayed during presentations before the viewer application shall automatically advance to the next page.</li>
     *                            <li>['trans']['S'] : transition style : Split, Blinds, Box, Wipe, Dissolve, Glitter, R, Fly, Push, Cover, Uncover, Fade</li>
     *                            <li>['trans']['D'] : The duration of the transition effect, in seconds.</li>
     *                            <li>['trans']['Dm'] : (Split and Blinds transition styles only) The dimension in which the specified transition effect shall occur: H = Horizontal, V = Vertical. Default value: H.</li>
     *                            <li>['trans']['M'] : (Split, Box and Fly transition styles only) The direction of motion for the specified transition effect: I = Inward from the edges of the page, O = Outward from the center of the pageDefault value: I.</li>
     *                            <li>['trans']['Di'] : (Wipe, Glitter, Fly, Cover, Uncover and Push transition styles only) The direction in which the specified transition effect shall moves, expressed in degrees counterclockwise starting from a left-to-right direction. If the value is a number, it shall be one of: 0 = Left to right, 90 = Bottom to top (Wipe only), 180 = Right to left (Wipe only), 270 = Top to bottom, 315 = Top-left to bottom-right (Glitter only). If the value is a name, it shall be None, which is relevant only for the Fly transition when the value of SS is not 1.0. Default value: 0.</li>
     *                            <li>['trans']['SS'] : (Fly transition style only) The starting or ending scale at which the changes shall be drawn. If M specifies an inward transition, the scale of the changes drawn shall progress from SS to 1.0 over the course of the transition. If M specifies an outward transition, the scale of the changes drawn shall progress from 1.0 to SS over the course of the transition. Default: 1.0.</li>
     *                            <li>['trans']['B'] : (Fly transition style only) If true, the area that shall be flown in is rectangular and opaque. Default: false.</li>
     *                            </ul>
     * @param string $orientation page orientation. Possible values are (case insensitive):<ul>
     *                            <li>P or Portrait (default)</li>
     *                            <li>L or Landscape</li>
     *                            <li>'' (empty string) for automatic orientation</li>
     *                            </ul>
     * @see   getPageSizeFromFormat()
     */
    protected function setPageFormat($format, $orientation = 'P')
    {
        if (!empty($format) and isset($this->pageDimensions[$this->page])) {
            // remove inherited values
            unset($this->pageDimensions[$this->page]);
        }
        if (is_string($format)) {
            // get page measures from format name
            $pf         = $this->pageDimensionsFromFormat($format);
            $this->fwPt = $pf[0];
            $this->fhPt = $pf[1];
        }
        else {
            // the boundaries of the physical medium on which the page shall be displayed or printed
            if (isset($format['MediaBox'])) {
                $this->pageDimensions = StaticMethods::setPageBoxes($this->page, 'MediaBox', $format['MediaBox']['llx'], $format['MediaBox']['lly'], $format['MediaBox']['urx'], $format['MediaBox']['ury'], false, $this->k, $this->pageDimensions);
                $this->fwPt           = (($format['MediaBox']['urx'] - $format['MediaBox']['llx']) * $this->k);
                $this->fhPt           = (($format['MediaBox']['ury'] - $format['MediaBox']['lly']) * $this->k);
            }
            else {
                if (isset($format[0]) and is_numeric($format[0]) and isset($format[1]) and is_numeric($format[1])) {
                    $pf = array(($format[0] * $this->k), ($format[1] * $this->k));
                }
                else {
                    if (!isset($format['format'])) {
                        // default value
                        $format['format'] = 'A4';
                    }
                    $pf = $this->pageDimensionsFromFormat($format['format']);
                }
                $this->fwPt           = $pf[0];
                $this->fhPt           = $pf[1];
                $this->pageDimensions = StaticMethods::setPageBoxes($this->page, 'MediaBox', 0, 0, $this->fwPt, $this->fhPt, true, $this->k, $this->pageDimensions);
            }
            // the visible region of default user space
            if (isset($format['CropBox'])) {
                $this->pageDimensions = StaticMethods::setPageBoxes($this->page, 'CropBox', $format['CropBox']['llx'], $format['CropBox']['lly'], $format['CropBox']['urx'], $format['CropBox']['ury'], false, $this->k, $this->pageDimensions);
            }
            // the region to which the contents of the page shall be clipped when output in a production environment
            if (isset($format['BleedBox'])) {
                $this->pageDimensions = StaticMethods::setPageBoxes($this->page, 'BleedBox', $format['BleedBox']['llx'], $format['BleedBox']['lly'], $format['BleedBox']['urx'], $format['BleedBox']['ury'], false, $this->k, $this->pageDimensions);
            }
            // the intended dimensions of the finished page after trimming
            if (isset($format['TrimBox'])) {
                $this->pageDimensions = StaticMethods::setPageBoxes($this->page, 'TrimBox', $format['TrimBox']['llx'], $format['TrimBox']['lly'], $format['TrimBox']['urx'], $format['TrimBox']['ury'], false, $this->k, $this->pageDimensions);
            }
            // the page's meaningful content (including potential white space)
            if (isset($format['ArtBox'])) {
                $this->pageDimensions = StaticMethods::setPageBoxes($this->page, 'ArtBox', $format['ArtBox']['llx'], $format['ArtBox']['lly'], $format['ArtBox']['urx'], $format['ArtBox']['ury'], false, $this->k, $this->pageDimensions);
            }
            // specify the colours and other visual characteristics that should be used in displaying guidelines on the screen for the various page boundaries
            if (isset($format['BoxColorInfo'])) {
                $this->pageDimensions[$this->page]['BoxColorInfo'] = $format['BoxColorInfo'];
            }
            if (isset($format['Rotate']) and (($format['Rotate'] % 90) == 0)) {
                // The number of degrees by which the page shall be rotated clockwise when displayed or printed. The value shall be a multiple of 90.
                $this->pageDimensions[$this->page]['Rotate'] = intval($format['Rotate']);
            }
            if (isset($format['PZ'])) {
                // The page's preferred zoom (magnification) factor
                $this->pageDimensions[$this->page]['PZ'] = floatval($format['PZ']);
            }
            if (isset($format['trans'])) {
                // The style and duration of the visual transition to use when moving from another page to the given page during a presentation
                if (isset($format['trans']['Dur'])) {
                    // The page's display duration
                    $this->pageDimensions[$this->page]['trans']['Dur'] = floatval($format['trans']['Dur']);
                }
                $stansition_styles = ['Split', 'Blinds', 'Box', 'Wipe', 'Dissolve', 'Glitter', 'R', 'Fly', 'Push', 'Cover', 'Uncover', 'Fade'];
                if (isset($format['trans']['S']) and in_array($format['trans']['S'], $stansition_styles)) {
                    // The transition style that shall be used when moving to this page from another during a presentation
                    $this->pageDimensions[$this->page]['trans']['S'] = $format['trans']['S'];
                    $valid_effect                                    = ['Split', 'Blinds'];
                    $valid_vals                                      = ['H', 'V'];
                    if (isset($format['trans']['Dm']) and in_array($format['trans']['S'], $valid_effect) and in_array($format['trans']['Dm'], $valid_vals)) {
                        $this->pageDimensions[$this->page]['trans']['Dm'] = $format['trans']['Dm'];
                    }
                    $valid_effect = ['Split', 'Box', 'Fly'];
                    $valid_vals   = ['I', 'O'];
                    if (isset($format['trans']['M']) and in_array($format['trans']['S'], $valid_effect) and in_array($format['trans']['M'], $valid_vals)) {
                        $this->pageDimensions[$this->page]['trans']['M'] = $format['trans']['M'];
                    }
                    $valid_effect = ['Wipe', 'Glitter', 'Fly', 'Cover', 'Uncover', 'Push'];
                    if (isset($format['trans']['Di']) and in_array($format['trans']['S'], $valid_effect)) {
                        if (((($format['trans']['Di'] == 90) or ($format['trans']['Di'] == 180)) and ($format['trans']['S'] == 'Wipe'))
                            or (($format['trans']['Di'] == 315) and ($format['trans']['S'] == 'Glitter'))
                            or (($format['trans']['Di'] == 0) or ($format['trans']['Di'] == 270))) {
                            $this->pageDimensions[$this->page]['trans']['Di'] = intval($format['trans']['Di']);
                        }
                    }
                    if (isset($format['trans']['SS']) and ($format['trans']['S'] == 'Fly')) {
                        $this->pageDimensions[$this->page]['trans']['SS'] = floatval($format['trans']['SS']);
                    }
                    if (isset($format['trans']['B']) and ($format['trans']['B'] === true) and ($format['trans']['S'] == 'Fly')) {
                        $this->pageDimensions[$this->page]['trans']['B'] = 'true';
                    }
                }
                else {
                    $this->pageDimensions[$this->page]['trans']['S'] = 'R';
                }
                if (isset($format['trans']['D'])) {
                    // The duration of the transition effect, in seconds
                    $this->pageDimensions[$this->page]['trans']['D'] = floatval($format['trans']['D']);
                }
                else {
                    $this->pageDimensions[$this->page]['trans']['D'] = 1;
                }
            }
        }
        $this->setPageOrientation($orientation);
    }

    /**
     * Width of page format in points.
     */
    protected $fwPt;

    /**
     * Height of page format in points.
     */
    protected $fhPt;

    /**
     * Current width of page in points.
     */
    protected $wPt;

    /**
     * Current height of page in points.
     */
    protected $hPt;

    /**
     * Current width of page in user unit.
     */
    protected $w;

    /**
     * Current height of page in user unit.
     */
    protected $h;

    /**
     * Top margin.
     */
    protected $tMargin;

    /**
     * Page break margin.
     */
    protected $bMargin;


    /**
     * Automatic page breaking.
     */
    protected $AutoPageBreak;

    /**
     * Threshold used to trigger page breaks.
     */
    protected $PageBreakTrigger;


}