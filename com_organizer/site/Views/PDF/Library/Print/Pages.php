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

namespace THM\Organizer\Views\PDF\Library\Print;

use THM\Organizer\Views\PDF\Library\StaticMethods;

trait Pages
{
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

    protected bool $autoPageBreak = true;
    protected float $bottomMargin = 0;
    protected float $breakTrigger = 0;
    protected string $currentOrientation = self::PORTRAIT;
    protected int $currentPageGroup = 0;
    protected float $formatHeightPT = 0;
    protected float $formatWidthPT = 0;
    protected float $height = 0;
    protected float $heightPT = 0;
    protected float $leftMargin = 0;
    protected float $originalLeftMargin = 0;
    protected float $originalRightMargin = 0;
    protected float $rightMargin = 0;
    protected int $page = 0;
    protected array $pageAnnotations = [];
    protected array $pageBoxes = [self::ART_BOX, self::BLEED_BOX, self::CROP_BOX, self::MEDIA_BOX, self::TRIM_BOX];
    protected int $pageCount = 0;
    protected array $pageDimensions = [];
    protected array $pageGroups = [];
    protected array $pageLengths = [];
    protected string $pageMode = self::USE_NONE;
    protected array $pageObjectIDs = [];
    protected array $pageObjects = [];
    protected array $pages = [];
    /** page number => open (true) / closed (false) */
    protected array $pageStatus = [];
    protected string $pageZoom = self::FULL_WIDTH;
    protected float $topMargin = 0;
    protected float $width = 0;
    protected float $widthPT = 0;

    /**
     * Getter / setter for  automatic page breaking. When enabling, the second parameter is the minimum bottom margin on break.
     * @param bool  $auto   bool indicating if mode should be on or off.
     * @param float $margin Distance from the bottom of the page.
     * @see   Cell(), MultiCell(), AcceptPageBreak()
     */
    public function autoPageBreak(bool|null $auto = null, float $margin = 0): bool
    {
        if (!is_null($auto)) {
            $this->autoPageBreak = $auto;
            $this->bottomMargin  = $margin;
            $this->breakTrigger  = $this->height - $margin;
        }

        return $this->autoPageBreak;
    }

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
     * Set page boundaries.
     * @param int    $page   page number
     * @param string $box
     * @param float  $llx    lower-left x coordinate in user units.
     * @param float  $lly    lower-left y coordinate in user units.
     * @param float  $urx    upper-right x coordinate in user units.
     * @param float  $ury    upper-right y coordinate in user units.
     * @param bool   $points If true uses user units as unit of measure, otherwise uses PDF points.
     * @see self::PAGE_BOXES
     */
    public function pageBoxes(int $page, string $box, float $llx, float $lly, float $urx, float $ury, bool $points): void
    {
        if (!isset($this->pageDimensions[$page])) {
            // initialize array
            $this->pageDimensions[$page] = [];
        }

        if (!in_array($box, self::PAGE_BOXES)) {
            return;
        }

        $factor = $points ? 1 : $this->factor;

        $this->pageDimensions[$page][$box]['llx'] = ($llx * $factor);
        $this->pageDimensions[$page][$box]['lly'] = ($lly * $factor);
        $this->pageDimensions[$page][$box]['urx'] = ($urx * $factor);
        $this->pageDimensions[$page][$box]['ury'] = ($ury * $factor);
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
     * Configure the page formatting.
     * @param array|string $format
     * @param string       $orientation
     * @see   self::PAGE_FORMAT_CONFIGURATION_DESCRIPTION, self::PAGE_FORMATS, self::ORIENTATIONS
     */
    protected function pageFormat(array|string $format, string $orientation = self::PORTRAIT): void
    {
        if (!empty($format) and isset($this->pageDimensions[$this->page])) {
            // remove inherited values
            unset($this->pageDimensions[$this->page]);
        }
        if (is_string($format)) {
            // get page measures from format name
            $dimensions           = $this->pageDimensionsFromFormat($format);
            $this->formatWidthPT  = $dimensions[0];
            $this->formatHeightPT = $dimensions[1];
        }
        else {
            $mediaBox = $format['MediaBox'] ?? false;
            if ($mediaBox) {
                $this->pageBoxes($this->page, 'MediaBox', $mediaBox['llx'], $mediaBox['lly'], $mediaBox['urx'], $mediaBox['ury'], false);
                $this->formatWidthPT  = ($mediaBox['urx'] - $mediaBox['llx']) * $this->factor;
                $this->formatHeightPT = ($mediaBox['ury'] - $mediaBox['lly']) * $this->factor;
            }
            else {
                if (isset($format[0]) and is_numeric($format[0]) and isset($format[1]) and is_numeric($format[1])) {
                    $dimensions = array(($format[0] * $this->factor), ($format[1] * $this->factor));
                }
                else {
                    if (!isset($format['format'])) {
                        // default value
                        $format['format'] = 'A4';
                    }
                    $dimensions = $this->pageDimensionsFromFormat($format['format']);
                }
                $this->formatWidthPT  = $dimensions[0];
                $this->formatHeightPT = $dimensions[1];
                $this->pageBoxes($this->page, 'MediaBox', 0, 0, $this->formatWidthPT, $this->formatHeightPT, true);
            }

            $artBox = $format['ArtBox'] ?? false;
            if (!empty($format['ArtBox'])) {
                $this->pageBoxes($this->page, 'ArtBox', $artBox['llx'], $artBox['lly'], $artBox['urx'], $artBox['ury'], false);
            }

            $bleedBox = $format['BleedBox'] ?? false;
            if ($bleedBox) {
                $this->pageBoxes($this->page, 'BleedBox', $bleedBox['llx'], $bleedBox['lly'], $bleedBox['urx'], $bleedBox['ury'], false);
            }

            $cropBox = $format['CropBox'] ?? false;
            if ($cropBox) {
                $this->pageBoxes($this->page, 'CropBox', $cropBox['llx'], $cropBox['lly'], $cropBox['urx'], $cropBox['ury'], false);
            }

            $trimBox = $format['TrimBox'] ?? false;
            if ($trimBox) {
                $this->pageBoxes($this->page, 'TrimBox', $trimBox['llx'], $trimBox['lly'], $trimBox['urx'], $trimBox['ury'], false);
            }

            $page =& $this->pageDimensions[$this->page];

            if (isset($format['BoxColorInfo'])) {
                $page['BoxColorInfo'] = $format['BoxColorInfo'];
            }

            if (isset($format['Rotate']) and ($format['Rotate'] % 90 === 0)) {
                $page['Rotate'] = (int) $format['Rotate'];
            }

            if (isset($format['PZ'])) {
                $page['PZ'] = (float) $format['PZ'];
            }
        }

        $this->pageOrientation($orientation);
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

    /**
     * Set page orientation.
     * @param string     $orientation
     * @param bool|null  $autoPagebreak
     * @param float|null $bottomMargin
     * @see self::ORIENTATIONS
     */
    public function pageOrientation(string $orientation, bool $autoPagebreak = null, float|null $bottomMargin = null): void
    {
        $page =& $this->pageDimensions[$this->page];
        if (!isset($page['MediaBox'])) {
            $this->pageBoxes($this->page, 'MediaBox', 0, 0, $this->formatWidthPT, $this->formatHeightPT, true);
        }

        $mediaBox = $page['MediaBox'];
        if (!isset($page['CropBox'])) {
            $this->pageBoxes($this->page, 'CropBox', $mediaBox['llx'], $mediaBox['lly'], $mediaBox['urx'], $mediaBox['ury'], true);
        }

        $cropBox = $page['CropBox'];
        if (!isset($page['BleedBox'])) {
            $this->pageBoxes($this->page, 'BleedBox', $cropBox['llx'], $cropBox['lly'], $cropBox['urx'], $cropBox['ury'], true);
        }
        if (!isset($page['TrimBox'])) {
            $this->pageBoxes($this->page, 'TrimBox', $cropBox['llx'], $cropBox['lly'], $cropBox['urx'], $cropBox['ury'], true);
        }
        if (!isset($page['ArtBox'])) {
            $this->pageBoxes($this->page, 'ArtBox', $cropBox['llx'], $cropBox['lly'], $cropBox['urx'], $cropBox['ury'], true);
        }

        if (!isset($page['PZ'])) {
            $page['PZ'] = 1;
        }
        if (!isset($page['Rotate'])) {
            $page['Rotate'] = 0;
        }

        $defaultOrientation = $this->formatHeightPT < $this->formatWidthPT ? self::LANDSCAPE : self::PORTRAIT;
        $orientation        = $orientation ?: $defaultOrientation;

        if (in_array($orientation, self::ORIENTATIONS) and ($orientation !== $defaultOrientation)) {
            $this->currentOrientation = $orientation;
            $this->heightPT           = $this->formatWidthPT;
            $this->widthPT            = $this->formatHeightPT;
        }
        else {
            $this->currentOrientation = $defaultOrientation;
            $this->heightPT           = $this->formatHeightPT;
            $this->widthPT            = $this->formatWidthPT;
        }

        if ((abs($mediaBox['urx'] - $this->heightPT) < self::EPSILON) and (abs($mediaBox['ury'] - $this->widthPT) < self::EPSILON)) {
            foreach (self::PAGE_BOXES as $box) {
                // swap X and Y coordinates
                if (isset($page[$box])) {
                    $lly = $page[$box]['llx'];
                    $ury = $page[$box]['urx'];

                    $page[$box]['llx'] = $page[$box]['lly'];
                    $page[$box]['lly'] = $lly;
                    $page[$box]['urx'] = $page[$box]['ury'];
                    $page[$box]['ury'] = $ury;
                }
            }
        }

        $this->width  = ($this->widthPT / $this->factor);
        $this->height = ($this->heightPT / $this->factor);

        if (is_null($autoPagebreak)) {
            $autoPagebreak = $this->autoPageBreak;
        }
        if (is_null($bottomMargin)) {
            $bottomMargin = $this->bottomMargin ?: 2 * self::POINTS_PER_CM / $this->factor;
        }
        $this->autoPageBreak($autoPagebreak, $bottomMargin);

        // store page dimensions
        $page['bm']  = $bottomMargin;
        $page['h']   = $this->heightPT;
        $page['hk']  = $this->height;
        $page['lm']  = $this->leftMargin;
        $page['olm'] = $this->originalLeftMargin;
        $page['or']  = $this->currentOrientation;
        $page['orm'] = $this->originalRightMargin;
        $page['pb']  = $autoPagebreak;
        $page['rm']  = $this->rightMargin;
        $page['tm']  = $this->topMargin;
        $page['w']   = $this->widthPT;
        $page['wk']  = $this->width;
    }

#endregion
#region /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


    /**
     * Adds a new page to the document. If a page is already present, the Footer() method is called first to output the footer (if enabled). Then the page is added, the current position set to the top-left corner according to the left and top margins, and Header() is called to display the header (if enabled).
     * The origin of the coordinate system is at the top-left corner and increasing ordinates go downwards.
     * @param string $orientation page orientation. Possible values are (case insensitive):<ul><li>P or PORTRAIT (default)</li><li>L or LANDSCAPE</li></ul>
     * @param mixed  $format      The format used for pages. It can be either: one of the string values specified at getPageSizeFromFormat() or an array of parameters specified at setPageFormat().
     * @param bool   $keepmargins if true overwrites the default page margins with the current margins
     * @param bool   $tocpage     if true set the tocpage state to true (the added page will be used to display Table Of Content).
     * @see   startPage(), closePage(), addTOCPage(), endTOCPage(), getPageSizeFromFormat(), pageFormat()
     */
    public function addPage($orientation = '', $format = '', $keepmargins = false, $tocpage = false)
    {
        if ($this->inXObject) {
            // we are inside an XObject template
            return;
        }
        if (empty($this->originalLeftMargin) or $keepmargins) {
            $this->originalLeftMargin = $this->leftMargin;
        }
        if (empty($this->originalRightMargin) or $keepmargins) {
            $this->originalRightMargin = $this->rightMargin;
        }
        // terminate previous page
        $this->closePage();
        // start new page
        $this->startPage($orientation, $format, $tocpage);
    }


    /**
     * Close the current page.
     * @param bool $toc if true set the tocpage state to false (end the page used to display Table Of Content).
     * @see   addPage(), startPage(), addTOCPage(), endTOCPage()
     */
    public function closePage(bool $toc = false): void
    {
        // Already closed
        if ($this->page === 0 or $this->pageCount > $this->page or !$this->pageStatus[$this->page]) {
            return;
        }

        $this->footer();
        $this->setVisibility('all');
        $this->state = self::NEUTRAL;
        // mark page as closed
        $this->pageStatus[$this->page] = false;
        if ($toc) {
            $this->tocpage = false;
        }
    }

#endregion
#region ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * bool value true when page region check is active.
     */
    protected $check_page_regions = true;

    /**
     * Array used to store page positions to track empty pages (keys are the page numbers).
     */
    protected $emptypagemrk = [];

    /**
     * Array of no-write regions.
     * ('page' => page number or empy for current page, 'xt' => X top, 'yt' => Y top, 'xb' => X bottom, 'yb' => Y bottom, 'side' => page side 'L' = left or 'R' = right)
     */
    protected $page_regions = [];

    /**
     * Starting page number.
     */
    protected $starting_page_number = 1;

    /**
     * Initialize a new page.
     * @param string $orientation page orientation. Possible values are (case insensitive):<ul><li>P or PORTRAIT (default)</li><li>L or LANDSCAPE</li></ul>
     * @param mixed  $format      The format used for pages. It can be either: one of the string values specified at getPageSizeFromFormat() or an array of parameters specified at setPageFormat().
     * @see getPageSizeFromFormat(), pageFormat()
     */
    protected function _beginpage($orientation = '', $format = '')
    {
        ++$this->page;
        $this->pageObjects[$this->page] = [];
        $this->pageBuffer($this->page, '');
        // initialize array for graphics tranformation positions inside a page buffer
        $this->transpositions[$this->page] = [];
        $this->state                       = self::READY;
        if (empty($orientation)) {
            if (isset($this->currentOrientation)) {
                $orientation = $this->currentOrientation;
            }
            elseif ($this->formatWidthPT > $this->formatHeightPT) {
                // landscape
                $orientation = 'L';
            }
            else {
                // portrait
                $orientation = 'P';
            }
        }
        if (empty($format)) {
            $this->pageDimensions[$this->page] = $this->pageDimensions[($this->page - 1)];
            $this->pageOrientation($orientation);
        }
        else {
            $this->pageFormat($format, $orientation);
        }
        $this->x = $this->leftMargin;
        $this->y = $this->topMargin;
        if (isset($this->newpagegroup[$this->page])) {
            // start a new group
            $this->currentPageGroup                    = $this->newpagegroup[$this->page];
            $this->pageGroups[$this->currentPageGroup] = 1;
        }
        elseif (isset($this->currentPageGroup) and ($this->currentPageGroup > 0)) {
            ++$this->pageGroups[$this->currentPageGroup];
        }
    }

    /**
     * Output pages (and replace page number aliases).
     */
    protected function _putpages()
    {
        $filter = '/Filter /FlateDecode ';
        // get internal aliases for page numbers
        $pnalias       = $this->getAllInternalPageNumberAliases();
        $num_pages     = $this->pageCount;
        $ptpa          = StaticMethods::formatPageNumber(($this->starting_page_number + $num_pages - 1));
        $ptpu          = $this->utf8ToUTF16BE($ptpa);
        $ptp_num_chars = $this->GetNumChars($ptpa);
        $pagegroupnum  = 0;
        $groupnum      = 0;
        $ptgu          = 1;
        $ptga          = 1;
        $ptg_num_chars = 1;
        for ($n = 1; $n <= $num_pages; ++$n) {
            // get current page
            $temppage = $this->pageBuffer($n);
            $pagelen  = strlen($temppage);
            // set replacements for total pages number
            $pnpa          = StaticMethods::formatPageNumber(($this->starting_page_number + $n - 1));
            $pnpu          = $this->utf8ToUTF16BE($pnpa);
            $pnp_num_chars = $this->GetNumChars($pnpa);
            $pdiff         = 0; // difference used for right shift alignment of page numbers
            $gdiff         = 0; // difference used for right shift alignment of page group numbers
            if (!empty($this->pageGroups)) {
                if (isset($this->newpagegroup[$n])) {
                    $pagegroupnum = 0;
                    ++$groupnum;
                    $ptga          = StaticMethods::formatPageNumber($this->pageGroups[$groupnum]);
                    $ptgu          = $this->utf8ToUTF16BE($ptga);
                    $ptg_num_chars = $this->GetNumChars($ptga);
                }
                ++$pagegroupnum;
                $pnga          = StaticMethods::formatPageNumber($pagegroupnum);
                $pngu          = $this->utf8ToUTF16BE($pnga);
                $png_num_chars = $this->GetNumChars($pnga);
                // replace page numbers
                $replace   = [];
                $replace[] = [$ptgu, $ptg_num_chars, 9, $pnalias[2]['u']];
                $replace[] = [$ptga, $ptg_num_chars, 7, $pnalias[2]['a']];
                $replace[] = [$pngu, $png_num_chars, 9, $pnalias[3]['u']];
                $replace[] = [$pnga, $png_num_chars, 7, $pnalias[3]['a']];
                list($temppage, $gdiff) = StaticMethods::replacePageNumAliases($temppage, $replace, $gdiff);
            }
            // replace page numbers
            $replace   = [];
            $replace[] = [$ptpu, $ptp_num_chars, 9, $pnalias[0]['u']];
            $replace[] = [$ptpa, $ptp_num_chars, 7, $pnalias[0]['a']];
            $replace[] = [$pnpu, $pnp_num_chars, 9, $pnalias[1]['u']];
            $replace[] = [$pnpa, $pnp_num_chars, 7, $pnalias[1]['a']];
            list($temppage, $pdiff) = StaticMethods::replacePageNumAliases($temppage, $replace, $pdiff);
            // replace right shift alias
            $temppage = $this->replaceRightShiftPageNumAliases($temppage, $pnalias[4], max($pdiff, $gdiff));
            // replace EPS marker
            $temppage = str_replace($this->epsmarker, '', $temppage);
            //Page
            $this->pageObjectIDs[$n] = $this->appendObject();
            $out                     = '<<';
            $out                     .= ' /Type /Page';
            $out                     .= ' /Parent 1 0 R';
            $out                     .= ' /LastModified ' . $this->_datestring($this->modificationTimestamp);
            $out                     .= ' /Resources 2 0 R';
            foreach ($this->pageBoxes as $box) {
                $out .= ' /' . $box;
                $out .= sprintf(' [%F %F %F %F]', $this->pageDimensions[$n][$box]['llx'], $this->pageDimensions[$n][$box]['lly'], $this->pageDimensions[$n][$box]['urx'], $this->pageDimensions[$n][$box]['ury']);
            }
            if (isset($this->pageDimensions[$n]['BoxColorInfo']) and !empty($this->pageDimensions[$n]['BoxColorInfo'])) {
                $out .= ' /BoxColorInfo <<';
                foreach ($this->pageBoxes as $box) {
                    if (isset($this->pageDimensions[$n]['BoxColorInfo'][$box])) {
                        $out .= ' /' . $box . ' <<';
                        if (isset($this->pageDimensions[$n]['BoxColorInfo'][$box]['C'])) {
                            $color = $this->pageDimensions[$n]['BoxColorInfo'][$box]['C'];
                            $out   .= ' /C [';
                            $out   .= sprintf(' %F %F %F', ($color[0] / 255), ($color[1] / 255), ($color[2] / 255));
                            $out   .= ' ]';
                        }
                        if (isset($this->pageDimensions[$n]['BoxColorInfo'][$box]['W'])) {
                            $out .= ' /W ' . ($this->pageDimensions[$n]['BoxColorInfo'][$box]['W'] * $this->factor);
                        }
                        if (isset($this->pageDimensions[$n]['BoxColorInfo'][$box]['S'])) {
                            $out .= ' /S /' . $this->pageDimensions[$n]['BoxColorInfo'][$box]['S'];
                        }
                        if (isset($this->pageDimensions[$n]['BoxColorInfo'][$box]['D'])) {
                            $dashes = $this->pageDimensions[$n]['BoxColorInfo'][$box]['D'];
                            $out    .= ' /D [';
                            foreach ($dashes as $dash) {
                                $out .= sprintf(' %F', ($dash * $this->factor));
                            }
                            $out .= ' ]';
                        }
                        $out .= ' >>';
                    }
                }
                $out .= ' >>';
            }
            $out .= ' /Contents ' . ($this->objectNumber + 1) . ' 0 R';
            $out .= ' /Rotate ' . $this->pageDimensions[$n]['Rotate'];
            $out .= ' /Group << /Type /Group /S /Transparency /CS /DeviceRGB >>';
            if (isset($this->pageDimensions[$n]['trans']) and !empty($this->pageDimensions[$n]['trans'])) {
                // page transitions
                if (isset($this->pageDimensions[$n]['trans']['Dur'])) {
                    $out .= ' /Dur ' . $this->pageDimensions[$n]['trans']['Dur'];
                }
                $out .= ' /Trans <<';
                $out .= ' /Type /Trans';
                if (isset($this->pageDimensions[$n]['trans']['S'])) {
                    $out .= ' /S /' . $this->pageDimensions[$n]['trans']['S'];
                }
                if (isset($this->pageDimensions[$n]['trans']['D'])) {
                    $out .= ' /D ' . $this->pageDimensions[$n]['trans']['D'];
                }
                if (isset($this->pageDimensions[$n]['trans']['Dm'])) {
                    $out .= ' /Dm /' . $this->pageDimensions[$n]['trans']['Dm'];
                }
                if (isset($this->pageDimensions[$n]['trans']['M'])) {
                    $out .= ' /M /' . $this->pageDimensions[$n]['trans']['M'];
                }
                if (isset($this->pageDimensions[$n]['trans']['Di'])) {
                    $out .= ' /Di ' . $this->pageDimensions[$n]['trans']['Di'];
                }
                if (isset($this->pageDimensions[$n]['trans']['SS'])) {
                    $out .= ' /SS ' . $this->pageDimensions[$n]['trans']['SS'];
                }
                if (isset($this->pageDimensions[$n]['trans']['B'])) {
                    $out .= ' /B ' . $this->pageDimensions[$n]['trans']['B'];
                }
                $out .= ' >>';
            }
            $out .= $this->_getannotsrefs($n);
            $out .= ' /PZ ' . $this->pageDimensions[$n]['PZ'];
            $out .= ' >>';
            $out .= "\n" . 'endobj';
            $this->append($out);
            //Page content
            $p = gzcompress($temppage);
            $this->appendObject();
            $this->append('<<' . $filter . '/Length ' . strlen($p) . '>> stream' . "\n" . $p . "\n" . 'endstream' . "\n" . 'endobj');
        }
        //Pages root
        $out = $this->objectCommand(1) . "\n";
        $out .= '<< /Type /Pages /Kids [';
        foreach ($this->pageObjectIDs as $page_obj) {
            $out .= ' ' . $page_obj . ' 0 R';
        }
        $out .= ' ] /Count ' . $num_pages . ' >>';
        $out .= "\n" . 'endobj';
        $this->append($out);
    }

    /**
     * Whenever a page break condition is met, the method is called, and the break is issued or not depending on the returned value.
     * The default implementation returns a value according to the mode selected by SetAutoPageBreak().<br />
     * This method is called automatically and should not be called directly by the application.
     * @return bool
     * @see   autoPageBreak()
     */
    public function AcceptPageBreak()
    {
        if ($this->columnCount > 1) {
            // multi column mode
            if ($this->currentColumn < ($this->columnCount - 1)) {
                // go to next column
                $this->selectColumn($this->currentColumn + 1);
            }
            elseif ($this->autoPageBreak) {
                // add a new page
                $this->addPage();
                // set first column
                $this->selectColumn(0);
            }
            // avoid page breaking from checkPageBreak()
            return false;
        }
        return $this->autoPageBreak;
    }

    /**
     * Clone the specified page to a new page.
     * @param int $page number of page to copy (0 = current page)
     * @return bool true in case of success, false in case of error.
     */
    public function copyPage($page = 0)
    {
        if ($page == 0) {
            // default value
            $page = $this->page;
        }
        if (($page < 1) or ($page > $this->pageCount)) {
            return false;
        }
        // close the last page
        $this->closePage();
        // copy all page-related states
        ++$this->pageCount;
        $this->page = $this->pageCount;
        $this->pageBuffer($this->page, $this->pageBuffer($page));
        $this->pageDimensions[$this->page] = $this->pageDimensions[$page];
        $this->pageLengths[$this->page]    = $this->pageLengths[$page];
        $this->intmrk[$this->page]         = $this->intmrk[$page];
        $this->bordermrk[$this->page]      = $this->bordermrk[$page];
        $this->cntmrk[$this->page]         = $this->cntmrk[$page];
        $this->pageObjects[$this->page]    = $this->pageObjects[$page];
        $this->pageStatus[$this->page]     = false;
        if (isset($this->footerPositions[$page])) {
            $this->footerPositions[$this->page] = $this->footerPositions[$page];
        }
        if (isset($this->footerLengths[$page])) {
            $this->footerLengths[$this->page] = $this->footerLengths[$page];
        }
        if (isset($this->transpositions[$page])) {
            $this->transpositions[$this->page] = $this->transpositions[$page];
        }
        if (isset($this->pageAnnotations[$page])) {
            $this->pageAnnotations[$this->page] = $this->pageAnnotations[$page];
        }
        if (isset($this->newpagegroup[$page])) {
            // start a new group
            $this->newpagegroup[$this->page]           = sizeof($this->newpagegroup) + 1;
            $this->currentPageGroup                    = $this->newpagegroup[$this->page];
            $this->pageGroups[$this->currentPageGroup] = 1;
        }
        elseif (isset($this->currentPageGroup) and ($this->currentPageGroup > 0)) {
            ++$this->pageGroups[$this->currentPageGroup];
        }
        // copy outlines
        $tmpoutlines = $this->outlines;
        foreach ($tmpoutlines as $key => $outline) {
            if ($outline['p'] == $page) {
                $this->outlines[] = ['t' => $outline['t'], 'l' => $outline['l'], 'x' => $outline['x'], 'y' => $outline['y'], 'p' => $this->page, 'f' => $outline['f'], 's' => $outline['s'], 'c' => $outline['c']];
            }
        }
        // copy links
        $tmplinks = $this->links;
        foreach ($tmplinks as $key => $link) {
            if ($link['p'] == $page) {
                $this->links[] = ['p' => $this->page, 'y' => $link['y'], 'f' => $link['f']];
            }
        }
        // return to last page
        $this->lastPage(true);
        return true;
    }

    /**
     * Remove the specified page.
     * @param int $page page to remove
     * @return bool true in case of success, false in case of error.
     */
    public function deletePage($page)
    {
        if (($page < 1) or ($page > $this->pageCount)) {
            return false;
        }
        // delete current page
        unset($this->pages[$page]);
        unset($this->pageDimensions[$page]);
        unset($this->pageLengths[$page]);
        unset($this->intmrk[$page]);
        unset($this->bordermrk[$page]);
        unset($this->cntmrk[$page]);
        foreach ($this->pageObjects[$page] as $oid) {
            if (isset($this->offsets[$oid])) {
                unset($this->offsets[$oid]);
            }
        }
        unset($this->pageObjects[$page]);
        if (isset($this->footerPositions[$page])) {
            unset($this->footerPositions[$page]);
        }
        if (isset($this->footerLengths[$page])) {
            unset($this->footerLengths[$page]);
        }
        if (isset($this->transpositions[$page])) {
            unset($this->transpositions[$page]);
        }
        if (isset($this->pageAnnotations[$page])) {
            unset($this->pageAnnotations[$page]);
        }
        if (isset($this->newpagegroup) and !empty($this->newpagegroup)) {
            for ($i = $page; $i > 0; --$i) {
                if (isset($this->newpagegroup[$i]) and (($i + $this->pageGroups[$this->newpagegroup[$i]]) > $page)) {
                    --$this->pageGroups[$this->newpagegroup[$i]];
                    break;
                }
            }
        }
        if (isset($this->pageStatus[$page])) {
            unset($this->pageStatus[$page]);
        }
        if ($page < $this->pageCount) {
            // update remaining pages
            for ($i = $page; $i < $this->pageCount; ++$i) {
                $j = $i + 1;
                // shift pages
                $this->pageBuffer($i, $this->pageBuffer($j));
                $this->pageDimensions[$i] = $this->pageDimensions[$j];
                $this->pageLengths[$i]    = $this->pageLengths[$j];
                $this->intmrk[$i]         = $this->intmrk[$j];
                $this->bordermrk[$i]      = $this->bordermrk[$j];
                $this->cntmrk[$i]         = $this->cntmrk[$j];
                $this->pageObjects[$i]    = $this->pageObjects[$j];
                if (isset($this->footerPositions[$j])) {
                    $this->footerPositions[$i] = $this->footerPositions[$j];
                }
                elseif (isset($this->footerPositions[$i])) {
                    unset($this->footerPositions[$i]);
                }
                if (isset($this->footerLengths[$j])) {
                    $this->footerLengths[$i] = $this->footerLengths[$j];
                }
                elseif (isset($this->footerLengths[$i])) {
                    unset($this->footerLengths[$i]);
                }
                if (isset($this->transpositions[$j])) {
                    $this->transpositions[$i] = $this->transpositions[$j];
                }
                elseif (isset($this->transpositions[$i])) {
                    unset($this->transpositions[$i]);
                }
                if (isset($this->pageAnnotations[$j])) {
                    $this->pageAnnotations[$i] = $this->pageAnnotations[$j];
                }
                elseif (isset($this->pageAnnotations[$i])) {
                    unset($this->pageAnnotations[$i]);
                }
                if (isset($this->newpagegroup[$j])) {
                    $this->newpagegroup[$i] = $this->newpagegroup[$j];
                    unset($this->newpagegroup[$j]);
                }
                if ($this->currentPageGroup == $j) {
                    $this->currentPageGroup = $i;
                }
                if (isset($this->pageStatus[$j])) {
                    $this->pageStatus[$i] = $this->pageStatus[$j];
                }
                elseif (isset($this->pageStatus[$i])) {
                    unset($this->pageStatus[$i]);
                }
            }
            // remove last page
            unset($this->pages[$this->pageCount]);
            unset($this->pageDimensions[$this->pageCount]);
            unset($this->pageLengths[$this->pageCount]);
            unset($this->intmrk[$this->pageCount]);
            unset($this->bordermrk[$this->pageCount]);
            unset($this->cntmrk[$this->pageCount]);
            foreach ($this->pageObjects[$this->pageCount] as $oid) {
                if (isset($this->offsets[$oid])) {
                    unset($this->offsets[$oid]);
                }
            }
            unset($this->pageObjects[$this->pageCount]);
            if (isset($this->footerPositions[$this->pageCount])) {
                unset($this->footerPositions[$this->pageCount]);
            }
            if (isset($this->footerLengths[$this->pageCount])) {
                unset($this->footerLengths[$this->pageCount]);
            }
            if (isset($this->transpositions[$this->pageCount])) {
                unset($this->transpositions[$this->pageCount]);
            }
            if (isset($this->pageAnnotations[$this->pageCount])) {
                unset($this->pageAnnotations[$this->pageCount]);
            }
            if (isset($this->newpagegroup[$this->pageCount])) {
                unset($this->newpagegroup[$this->pageCount]);
            }
            if ($this->currentPageGroup == $this->pageCount) {
                $this->currentPageGroup = ($this->pageCount - 1);
            }
            if (isset($this->pageGroups[$this->pageCount])) {
                unset($this->pageGroups[$this->pageCount]);
            }
            if (isset($this->pageStatus[$this->pageCount])) {
                unset($this->pageStatus[$this->pageCount]);
            }
        }
        --$this->pageCount;
        $this->page = $this->pageCount;
        // adjust outlines
        $tmpoutlines = $this->outlines;
        foreach ($tmpoutlines as $key => $outline) {
            if (!$outline['f']) {
                if ($outline['p'] > $page) {
                    $this->outlines[$key]['p'] = $outline['p'] - 1;
                }
                elseif ($outline['p'] == $page) {
                    unset($this->outlines[$key]);
                }
            }
        }
        // adjust dests
        $tmpdests = $this->dests;
        foreach ($tmpdests as $key => $dest) {
            if (!$dest['f']) {
                if ($dest['p'] > $page) {
                    $this->dests[$key]['p'] = $dest['p'] - 1;
                }
                elseif ($dest['p'] == $page) {
                    unset($this->dests[$key]);
                }
            }
        }
        // adjust links
        $tmplinks = $this->links;
        foreach ($tmplinks as $key => $link) {
            if (!$link['f']) {
                if ($link['p'] > $page) {
                    $this->links[$key]['p'] = $link['p'] - 1;
                }
                elseif ($link['p'] == $page) {
                    unset($this->links[$key]);
                }
            }
        }
        // adjust javascript
        $jpage = $page;
        if (preg_match_all('/this\.addField\(\'([^\']*)\',\'([^\']*)\',([0-9]+)/', $this->javascript, $pamatch) > 0) {
            foreach ($pamatch[0] as $pk => $pmatch) {
                $pagenum = intval($pamatch[3][$pk]) + 1;
                if ($pagenum >= $jpage) {
                    $newpage = ($pagenum - 1);
                }
                elseif ($pagenum == $jpage) {
                    $newpage = 1;
                }
                else {
                    $newpage = $pagenum;
                }
                --$newpage;
                $newjs            = "this.addField(\'" . $pamatch[1][$pk] . "\',\'" . $pamatch[2][$pk] . "\'," . $newpage;
                $this->javascript = str_replace($pmatch, $newjs, $this->javascript);
            }
            unset($pamatch);
        }
        // return to last page
        if ($this->pageCount > 0) {
            $this->lastPage(true);
        }
        return true;
    }

    /**
     * Get the total number of insered pages.
     * @return int number of pages
     * @see   setPage(), getPage(), lastpage()
     */
    public function getNumPages()
    {
        return $this->pageCount;
    }

    /**
     * Get current document page number.
     * @return int page number
     * @see   setPage(), lastpage(), getNumPages()
     */
    public function getPage()
    {
        return $this->page;
    }

    /**
     * Returns an array of page dimensions:
     * <ul><li>$this->pagedim[$this->page]['w'] = page width in points</li><li>$this->pagedim[$this->page]['h'] = height in points</li><li>$this->pagedim[$this->page]['wk'] = page width in user units</li><li>$this->pagedim[$this->page]['hk'] = page height in user units</li><li>$this->pagedim[$this->page]['tm'] = top margin</li><li>$this->pagedim[$this->page]['bm'] = bottom margin</li><li>$this->pagedim[$this->page]['lm'] = left margin</li><li>$this->pagedim[$this->page]['rm'] = right margin</li><li>$this->pagedim[$this->page]['pb'] = auto page break</li><li>$this->pagedim[$this->page]['or'] = page orientation</li><li>$this->pagedim[$this->page]['olm'] = original left margin</li><li>$this->pagedim[$this->page]['orm'] = original right margin</li><li>$this->pagedim[$this->page]['Rotate'] = The number of degrees by which the page shall be rotated clockwise when displayed or printed. The value shall be a multiple of 90.</li><li>$this->pagedim[$this->page]['PZ'] = The page's preferred zoom (magnification) factor.</li><li>$this->pagedim[$this->page]['trans'] : the style and duration of the visual transition to use when moving from another page to the given page during a presentation<ul><li>$this->pagedim[$this->page]['trans']['Dur'] = The page's display duration (also called its advance timing): the maximum length of time, in seconds, that the page shall be displayed during presentations before the viewer application shall automatically advance to the next page.</li><li>$this->pagedim[$this->page]['trans']['S'] = transition style : Split, Blinds, Box, Wipe, Dissolve, Glitter, R, Fly, Push, Cover, Uncover, Fade</li><li>$this->pagedim[$this->page]['trans']['D'] = The duration of the transition effect, in seconds.</li><li>$this->pagedim[$this->page]['trans']['Dm'] = (Split and Blinds transition styles only) The dimension in which the specified transition effect shall occur: H = Horizontal, V = Vertical. Default value: H.</li><li>$this->pagedim[$this->page]['trans']['M'] = (Split, Box and Fly transition styles only) The direction of motion for the specified transition effect: I = Inward from the edges of the page, O = Outward from the center of the pageDefault value: I.</li><li>$this->pagedim[$this->page]['trans']['Di'] = (Wipe, Glitter, Fly, Cover, Uncover and Push transition styles only) The direction in which the specified transition effect shall moves, expressed in degrees counterclockwise starting from a left-to-right direction. If the value is a number, it shall be one of: 0 = Left to right, 90 = Bottom to top (Wipe only), 180 = Right to left (Wipe only), 270 = Top to bottom, 315 = Top-left to bottom-right (Glitter only). If the value is a name, it shall be None, which is relevant only for the Fly transition when the value of SS is not 1.0. Default value: 0.</li><li>$this->pagedim[$this->page]['trans']['SS'] = (Fly transition style only) The starting or ending scale at which the changes shall be drawn. If M specifies an inward transition, the scale of the changes drawn shall progress from SS to 1.0 over the course of the transition. If M specifies an outward transition, the scale of the changes drawn shall progress from 1.0 to SS over the course of the transition. Default: 1.0. </li><li>$this->pagedim[$this->page]['trans']['B'] = (Fly transition style only) If true, the area that shall be flown in is rectangular and opaque. Default: false.</li></ul></li><li>$this->pagedim[$this->page]['MediaBox'] : the boundaries of the physical medium on which the page shall be displayed or printed<ul><li>$this->pagedim[$this->page]['MediaBox']['llx'] = lower-left x coordinate in points</li><li>$this->pagedim[$this->page]['MediaBox']['lly'] = lower-left y coordinate in points</li><li>$this->pagedim[$this->page]['MediaBox']['urx'] = upper-right x coordinate in points</li><li>$this->pagedim[$this->page]['MediaBox']['ury'] = upper-right y coordinate in points</li></ul></li><li>$this->pagedim[$this->page]['CropBox'] : the visible region of default user space<ul><li>$this->pagedim[$this->page]['CropBox']['llx'] = lower-left x coordinate in points</li><li>$this->pagedim[$this->page]['CropBox']['lly'] = lower-left y coordinate in points</li><li>$this->pagedim[$this->page]['CropBox']['urx'] = upper-right x coordinate in points</li><li>$this->pagedim[$this->page]['CropBox']['ury'] = upper-right y coordinate in points</li></ul></li><li>$this->pagedim[$this->page]['BleedBox'] : the region to which the contents of the page shall be clipped when output in a production environment<ul><li>$this->pagedim[$this->page]['BleedBox']['llx'] = lower-left x coordinate in points</li><li>$this->pagedim[$this->page]['BleedBox']['lly'] = lower-left y coordinate in points</li><li>$this->pagedim[$this->page]['BleedBox']['urx'] = upper-right x coordinate in points</li><li>$this->pagedim[$this->page]['BleedBox']['ury'] = upper-right y coordinate in points</li></ul></li><li>$this->pagedim[$this->page]['TrimBox'] : the intended dimensions of the finished page after trimming<ul><li>$this->pagedim[$this->page]['TrimBox']['llx'] = lower-left x coordinate in points</li><li>$this->pagedim[$this->page]['TrimBox']['lly'] = lower-left y coordinate in points</li><li>$this->pagedim[$this->page]['TrimBox']['urx'] = upper-right x coordinate in points</li><li>$this->pagedim[$this->page]['TrimBox']['ury'] = upper-right y coordinate in points</li></ul></li><li>$this->pagedim[$this->page]['ArtBox'] : the extent of the page's meaningful content<ul><li>$this->pagedim[$this->page]['ArtBox']['llx'] = lower-left x coordinate in points</li><li>$this->pagedim[$this->page]['ArtBox']['lly'] = lower-left y coordinate in points</li><li>$this->pagedim[$this->page]['ArtBox']['urx'] = upper-right x coordinate in points</li><li>$this->pagedim[$this->page]['ArtBox']['ury'] = upper-right y coordinate in points</li></ul></li></ul>
     * @param int|null $pagenum page number (empty = current page)
     * @return array of page dimensions.
     * @author Nicola Asuni
     */
    public function getPageDimensions($pagenum = null)
    {
        if (empty($pagenum)) {
            $pagenum = $this->page;
        }
        return $this->pageDimensions[$pagenum];
    }

    /**
     * Returns the page height in units.
     * @param int|null $pagenum page number (empty = current page)
     * @return int|float page height.
     * @author Nicola Asuni
     * @see    getPageDimensions()
     */
    public function getPageHeight($pagenum = null)
    {
        if (empty($pagenum)) {
            return $this->height;
        }
        return $this->pageDimensions[$pagenum]['h'];
    }

    /**
     * Returns the page width in units.
     * @param int|null $pagenum page number (empty = current page)
     * @return int|float page width.
     * @author Nicola Asuni
     * @see    getPageDimensions()
     */
    public function getPageWidth($pagenum = null)
    {
        if (empty($pagenum)) {
            return $this->width;
        }
        return $this->pageDimensions[$pagenum]['w'];
    }

    /**
     * Check if we are on the page body (excluding page header and footer).
     * @return bool true if we are not in page header nor in page footer, false otherwise.
     */
    protected function inPageBody()
    {
        return (($this->inHeader === false) and ($this->inFooter === false));
    }

    /**
     * Reset pointer to the last document page.
     * @param bool $resetmargins if true reset left, right, top margins and Y position.
     * @see   setPage(), getPage(), getNumPages()
     */
    public function lastPage($resetmargins = false)
    {
        $this->setPage($this->getNumPages(), $resetmargins);
    }

    /**
     * Move a page to a previous position.
     * @param int $frompage number of the source page
     * @param int $topage   number of the destination page (must be less than $frompage)
     * @return bool true in case of success, false in case of error.
     */
    public function movePage($frompage, $topage)
    {
        if (($frompage > $this->pageCount) or ($frompage <= $topage)) {
            return false;
        }
        if ($frompage == $this->page) {
            // close the page before moving it
            $this->closePage();
        }
        // move all page-related states
        $tmppage        = $this->pageBuffer($frompage);
        $tmppagedim     = $this->pageDimensions[$frompage];
        $tmppagelen     = $this->pageLengths[$frompage];
        $tmpintmrk      = $this->intmrk[$frompage];
        $tmpbordermrk   = $this->bordermrk[$frompage];
        $tmpcntmrk      = $this->cntmrk[$frompage];
        $tmppageobjects = $this->pageObjects[$frompage];
        if (isset($this->footerPositions[$frompage])) {
            $tmpfooterpos = $this->footerPositions[$frompage];
        }
        if (isset($this->footerLengths[$frompage])) {
            $tmpfooterlen = $this->footerLengths[$frompage];
        }
        if (isset($this->transpositions[$frompage])) {
            $transpositions = $this->transpositions[$frompage];
        }
        if (isset($this->pageAnnotations[$frompage])) {
            $tmpannots = $this->pageAnnotations[$frompage];
        }
        if (isset($this->newpagegroup) and !empty($this->newpagegroup)) {
            for ($i = $frompage; $i > $topage; --$i) {
                if (isset($this->newpagegroup[$i]) and (($i + $this->pageGroups[$this->newpagegroup[$i]]) > $frompage)) {
                    --$this->pageGroups[$this->newpagegroup[$i]];
                    break;
                }
            }
            for ($i = $topage; $i > 0; --$i) {
                if (isset($this->newpagegroup[$i]) and (($i + $this->pageGroups[$this->newpagegroup[$i]]) > $topage)) {
                    ++$this->pageGroups[$this->newpagegroup[$i]];
                    break;
                }
            }
        }
        for ($i = $frompage; $i > $topage; --$i) {
            $j = $i - 1;
            // shift pages down
            $this->pageBuffer($i, $this->pageBuffer($j));
            $this->pageDimensions[$i] = $this->pageDimensions[$j];
            $this->pageLengths[$i]    = $this->pageLengths[$j];
            $this->intmrk[$i]         = $this->intmrk[$j];
            $this->bordermrk[$i]      = $this->bordermrk[$j];
            $this->cntmrk[$i]         = $this->cntmrk[$j];
            $this->pageObjects[$i]    = $this->pageObjects[$j];
            if (isset($this->footerPositions[$j])) {
                $this->footerPositions[$i] = $this->footerPositions[$j];
            }
            elseif (isset($this->footerPositions[$i])) {
                unset($this->footerPositions[$i]);
            }
            if (isset($this->footerLengths[$j])) {
                $this->footerLengths[$i] = $this->footerLengths[$j];
            }
            elseif (isset($this->footerLengths[$i])) {
                unset($this->footerLengths[$i]);
            }
            if (isset($this->transpositions[$j])) {
                $this->transpositions[$i] = $this->transpositions[$j];
            }
            elseif (isset($this->transpositions[$i])) {
                unset($this->transpositions[$i]);
            }
            if (isset($this->pageAnnotations[$j])) {
                $this->pageAnnotations[$i] = $this->pageAnnotations[$j];
            }
            elseif (isset($this->pageAnnotations[$i])) {
                unset($this->pageAnnotations[$i]);
            }
            if (isset($this->newpagegroup[$j])) {
                $this->newpagegroup[$i] = $this->newpagegroup[$j];
                unset($this->newpagegroup[$j]);
            }
            if ($this->currentPageGroup == $j) {
                $this->currentPageGroup = $i;
            }
        }
        $this->pageBuffer($topage, $tmppage);
        $this->pageDimensions[$topage] = $tmppagedim;
        $this->pageLengths[$topage]    = $tmppagelen;
        $this->intmrk[$topage]         = $tmpintmrk;
        $this->bordermrk[$topage]      = $tmpbordermrk;
        $this->cntmrk[$topage]         = $tmpcntmrk;
        $this->pageObjects[$topage]    = $tmppageobjects;
        if (isset($tmpfooterpos)) {
            $this->footerPositions[$topage] = $tmpfooterpos;
        }
        elseif (isset($this->footerPositions[$topage])) {
            unset($this->footerPositions[$topage]);
        }
        if (isset($tmpfooterlen)) {
            $this->footerLengths[$topage] = $tmpfooterlen;
        }
        elseif (isset($this->footerLengths[$topage])) {
            unset($this->footerLengths[$topage]);
        }
        if (isset($transpositions)) {
            $this->transpositions[$topage] = $transpositions;
        }
        elseif (isset($this->transpositions[$topage])) {
            unset($this->transpositions[$topage]);
        }
        if (isset($tmpannots)) {
            $this->pageAnnotations[$topage] = $tmpannots;
        }
        elseif (isset($this->pageAnnotations[$topage])) {
            unset($this->pageAnnotations[$topage]);
        }
        // adjust outlines
        $tmpoutlines = $this->outlines;
        foreach ($tmpoutlines as $key => $outline) {
            if (!$outline['f']) {
                if (($outline['p'] >= $topage) and ($outline['p'] < $frompage)) {
                    $this->outlines[$key]['p'] = ($outline['p'] + 1);
                }
                elseif ($outline['p'] == $frompage) {
                    $this->outlines[$key]['p'] = $topage;
                }
            }
        }
        // adjust dests
        $tmpdests = $this->dests;
        foreach ($tmpdests as $key => $dest) {
            if (!$dest['f']) {
                if (($dest['p'] >= $topage) and ($dest['p'] < $frompage)) {
                    $this->dests[$key]['p'] = ($dest['p'] + 1);
                }
                elseif ($dest['p'] == $frompage) {
                    $this->dests[$key]['p'] = $topage;
                }
            }
        }
        // adjust links
        $tmplinks = $this->links;
        foreach ($tmplinks as $key => $link) {
            if (!$link['f']) {
                if (($link['p'] >= $topage) and ($link['p'] < $frompage)) {
                    $this->links[$key]['p'] = ($link['p'] + 1);
                }
                elseif ($link['p'] == $frompage) {
                    $this->links[$key]['p'] = $topage;
                }
            }
        }
        // adjust javascript
        $jfrompage = $frompage;
        $jtopage   = $topage;
        if (preg_match_all('/this\.addField\(\'([^\']*)\',\'([^\']*)\',([0-9]+)/', $this->javascript, $pamatch) > 0) {
            foreach ($pamatch[0] as $pk => $pmatch) {
                $pagenum = intval($pamatch[3][$pk]) + 1;
                if (($pagenum >= $jtopage) and ($pagenum < $jfrompage)) {
                    $newpage = ($pagenum + 1);
                }
                elseif ($pagenum == $jfrompage) {
                    $newpage = $jtopage;
                }
                else {
                    $newpage = $pagenum;
                }
                --$newpage;
                $newjs            = "this.addField(\'" . $pamatch[1][$pk] . "\',\'" . $pamatch[2][$pk] . "\'," . $newpage;
                $this->javascript = str_replace($pmatch, $newjs, $this->javascript);
            }
            unset($pamatch);
        }
        // return to last page
        $this->lastPage(true);
        return true;
    }

    /**
     * Returns the current page number.
     * @return int page number
     * @see   getAliasNbPages()
     */
    public function PageNo()
    {
        return $this->page;
    }

    /**
     * Move pointer at the specified document page and update page dimensions.
     * @param int  $pnum         page number (1 ... numpages)
     * @param bool $resetmargins if true reset left, right, top margins and Y position.
     * @see   getPage(), lastpage(), getNumPages()
     */
    public function setPage($pnum, $resetmargins = false)
    {
        if (($pnum == $this->page) and ($this->state === self::READY)) {
            return;
        }
        if (($pnum > 0) and ($pnum <= $this->pageCount)) {
            $this->state = self::READY;
            // save current graphic settings
            $oldpage                   = $this->page;
            $this->page                = $pnum;
            $this->widthPT             = $this->pageDimensions[$this->page]['w'];
            $this->heightPT            = $this->pageDimensions[$this->page]['h'];
            $this->width               = $this->pageDimensions[$this->page]['wk'];
            $this->height              = $this->pageDimensions[$this->page]['hk'];
            $this->topMargin           = $this->pageDimensions[$this->page]['tm'];
            $this->bottomMargin        = $this->pageDimensions[$this->page]['bm'];
            $this->originalLeftMargin  = $this->pageDimensions[$this->page]['olm'];
            $this->originalRightMargin = $this->pageDimensions[$this->page]['orm'];
            $this->autoPageBreak       = $this->pageDimensions[$this->page]['pb'];
            $this->currentOrientation  = $this->pageDimensions[$this->page]['or'];
            $this->autoPageBreak($this->autoPageBreak, $this->bottomMargin);
            // restore graphic settings
            if ($resetmargins) {
                $this->leftMargin  = $this->pageDimensions[$this->page]['olm'];
                $this->rightMargin = $this->pageDimensions[$this->page]['orm'];
                $this->y($this->topMargin);
            }
            else {
                // account for booklet mode
                if ($this->pageDimensions[$this->page]['olm'] != $this->pageDimensions[$oldpage]['olm']) {
                    $deltam            = $this->pageDimensions[$this->page]['olm'] - $this->pageDimensions[$this->page]['orm'];
                    $this->leftMargin  += $deltam;
                    $this->rightMargin -= $deltam;
                }
            }
        }
        else {
            $this->Error('Wrong page number on setPage() function: ' . $pnum);
        }
    }

    /**
     * Set page boxes to be included on page descriptions.
     * @param array $boxes Array of page boxes to set on document: ('MediaBox', 'CropBox', 'BleedBox', 'TrimBox', 'ArtBox').
     * @protected
     */
    protected function setPageBoxTypes($boxes)
    {
        $this->pageBoxes = [];
        foreach ($boxes as $box) {
            if (in_array($box, self::PAGE_BOXES)) {
                $this->pageBoxes[] = $box;
            }
        }
    }

    /**
     * Set start-writing mark on current page stream used to put borders and fills.
     * Borders and fills are always created after content and inserted on the position marked by this method.
     * This function must be called after calling Image() function for a background image.
     * Background images must be always inserted before calling Multicell() or WriteHTMLCell() or WriteHTML() functions.
     */
    public function setPageMark()
    {
        $this->intmrk[$this->page]    = $this->pageLengths[$this->page];
        $this->bordermrk[$this->page] = $this->intmrk[$this->page];
        $this->setContentMark();
    }

    /**
     * Set the starting page number.
     * @param int $num Starting page number.
     * @since 5.9.093 (2011-06-16)
     * @public
     */
    public function setStartingPageNumber($num = 1)
    {
        $this->starting_page_number = max(0, intval($num));
    }

    /**
     * Starts a new page to the document. The page must be closed using the endPage() function.
     * The origin of the coordinate system is at the top-left corner and increasing ordinates go downwards.
     * @param string $orientation page orientation. Possible values are (case insensitive):<ul><li>P or PORTRAIT (default)</li><li>L or LANDSCAPE</li></ul>
     * @param mixed  $format      The format used for pages. It can be either: one of the string values specified at getPageSizeFromFormat() or an array of parameters specified at setPageFormat().
     * @param bool   $tocpage     if true the page is designated to contain the Table-Of-Content.
     * @see   addPage(), closePage(), addTOCPage(), endTOCPage(), getPageSizeFromFormat(), pageFormat()
     */
    public function startPage($orientation = '', $format = '', $tocpage = false)
    {
        if ($tocpage) {
            $this->tocpage = true;
        }
        // move page numbers of documents to be attached
        if ($this->tocpage) {
            // move reference to unexistent pages (used for page attachments)
            // adjust outlines
            $tmpoutlines = $this->outlines;
            foreach ($tmpoutlines as $key => $outline) {
                if (!$outline['f'] and ($outline['p'] > $this->pageCount)) {
                    $this->outlines[$key]['p'] = ($outline['p'] + 1);
                }
            }
            // adjust dests
            $tmpdests = $this->dests;
            foreach ($tmpdests as $key => $dest) {
                if (!$dest['f'] and ($dest['p'] > $this->pageCount)) {
                    $this->dests[$key]['p'] = ($dest['p'] + 1);
                }
            }
            // adjust links
            $tmplinks = $this->links;
            foreach ($tmplinks as $key => $link) {
                if (!$link['f'] and ($link['p'] > $this->pageCount)) {
                    $this->links[$key]['p'] = ($link['p'] + 1);
                }
            }
        }
        if ($this->pageCount > $this->page) {
            // this page has been already added
            $this->setPage($this->page + 1);
            $this->y($this->topMargin);
            return;
        }
        // start a new page
        if ($this->state === self::INITIAL) {
            $this->Open();
        }
        ++$this->pageCount;
        $this->swapMargins($this->booklet);
        // save current graphic settings
        $gvars = $this->graphicsSettings();
        // start new page
        $this->_beginpage($orientation, $format);
        // mark page as open
        $this->pageStatus[$this->page] = true;
        // restore graphic settings
        $this->graphicsSettings($gvars);
        // mark this point
        $this->setPageMark();
        // print page header
        $this->header();
        // restore graphic settings
        $this->graphicsSettings($gvars);
        // mark this point
        $this->setPageMark();
        // print table header (if any)
        $this->setTableHeader();
        // set mark for empty page check
        $this->emptypagemrk[$this->page] = $this->pageLengths[$this->page];
    }


    /**
     * Create a new page group.
     * NOTE: call this function before calling AddPage()
     * @param int|null $page starting group page (leave empty for next page).
     * @public
     * @since 3.0.000 (2008-03-27)
     */
    public function startPageGroup($page = null)
    {
        if (empty($page)) {
            $page = $this->page + 1;
        }
        $this->newpagegroup[$page] = sizeof($this->newpagegroup) + 1;
    }

#endregion
}