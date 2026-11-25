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

use THM\Organizer\Adapters\Application;
use THM\Organizer\Views\PDF\Library\StaticMethods;

trait Pages
{
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

    protected bool $autoPageBreak = true;
    protected float $bottomMargin = 0;
    protected float $breakTrigger = 0;
    protected bool $checkRegions = true;
    protected string $currentOrientation = self::PORTRAIT;
    protected int $currentPageGroup = 0;
    protected array $emptyPageMarks = [];
    protected float $formatHeightPT = 0;
    protected float $formatWidthPT = 0;
    protected float $height = 0;
    protected float $heightPT = 0;
    protected bool $inTableHeader = false;
    protected string $layoutMode = self::SINGLE_PAGE;
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
    /** Array of page numbers where a new page group was started (the page numbers are the keys of the array). */
    protected array $pageGroupStarts = [];
    protected array $pageLengths = [];
    protected string $pageMode = self::USE_NONE;
    protected array $pageObjectIDs = [];
    protected array $pageObjects = [];
    /**
     * No-write regions:
     * [
     *     'page' => page number or empty for current page,
     *     'side' => page side (self::LEFT / self::RIGHT)
     *     'xb' => X bottom,
     *     'xt' => X top,
     *     'yb' => Y bottom,
     *     'yt' => Y top,
     * ]
     */
    protected array $pageRegions = [];
    protected array $pages = [];
    /** page number => open (true) / closed (false) */
    protected array $pageStatus = [];
    protected string $pageZoom = self::FULL_WIDTH;
    protected int $startingPageNumber = 1;
    protected string $tableHeader = '';
    protected array $tableHeaderMargins = [];
    protected float $topMargin = 0;
    protected float $width = 0;
    protected float $widthPT = 0;

    /**
     * Returns whether it is safe and configured to break a page automatically. Multicolumn is always false.
     * @return bool
     * @see   autoPageBreak()
     */
    protected function acceptPageBreak(): bool
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

            // Avoid page breaking from checkPageBreak()
            return false;
        }

        return $this->autoPageBreak;
    }

    /**
     * Adds a new page to the document.
     * @param string       $orientation
     * @param array|string $format
     * @param bool         $overwriteMargins whether to overwrite the default margins with the current margins
     * @param bool         $isToC            whether the added page is a toc page
     * @see   self::ORIENTATIONS, self::PAGE_FORMATS, self::PAGE_FORMAT_CONFIGURATION_DESCRIPTION
     */
    public function addPage(string $orientation = self::CURRENT, array|string $format = self::CURRENT, bool $overwriteMargins = false, bool $isToC = false): void
    {
        if ($this->inXObject) {
            return;
        }
        if (empty($this->originalLeftMargin) or $overwriteMargins) {
            $this->originalLeftMargin = $this->leftMargin;
        }
        if (empty($this->originalRightMargin) or $overwriteMargins) {
            $this->originalRightMargin = $this->rightMargin;
        }
        $this->closePage();
        $this->startPage($orientation, $format, $isToC);
    }

    /**
     * Get references to page annotations.
     * @param int $page
     * @return string
     */
    protected function annotationReferences(int $page): string
    {
        if (empty($this->pageAnnotations[$page])) {
            return '';
        }

        $out = ' /Annots [';

        foreach ($this->pageAnnotations[$page] as $annotation) {
            if (!in_array($annotation['n'], $this->radio_groups)) {
                $out .= ' ' . $annotation['n'] . ' 0 R';
            }
        }

        if (isset($this->radioButtonGroups[$page])) {
            foreach ($this->radioButtonGroups[$page] as $group) {
                if (isset($group['n'])) {
                    $out .= ' ' . $group['n'] . ' 0 R';
                }
            }
        }

        $out .= ' ]';
        return $out;
    }

    /**
     * Append pages to the document and replace page number aliases.
     */
    protected function appendPages(): void
    {
        $filter       = '/Filter /FlateDecode ';
        $gNumber      = 0;
        $lastPN8      = $this->formatPageNumber(($this->startingPageNumber + $this->pageCount - 1));
        $lastPN8Count = strlen($lastPN8);
        $lastPN16     = $this->utf8ToUTF16BE($lastPN8);
        $pgGN8        = 1;
        $pgGN16       = 1;
        $pgGN8Count   = 1;
        $pgNumber     = 0;

        // Aliases referenced by key later => is relevant
        $pnAliases = [self::TOTAL_PAGES_ALIAS, self::PAGE_NUMBER_ALIAS, self::PAGES_IN_GROUP_ALIAS, self::PAGE_GROUP_NUMBER_ALIAS, self::RIGHT_SHIFT_ALIAS];
        $variants  = [];

        foreach ($pnAliases as $pna) {
            $variants[] = $this->singlePNAVariants($pna);
        }

        for ($page = 1; $page <= $this->pageCount; ++$page) {
            $currentPN8      = $this->formatPageNumber(($this->startingPageNumber + $page - 1));
            $currentPN8Count = strlen($currentPN8);
            $currentPN16     = $this->utf8ToUTF16BE($currentPN8);
            $dimensions      = $this->pageDimensions[$page];
            $groupShift      = 0;
            $pageBuffer      = $this->pageBuffer($page);
            $pageShift       = 0;

            if ($this->pageGroups) {
                if (isset($this->pageGroupStarts[$page])) {
                    $pgNumber = 0;
                    ++$gNumber;
                    $pgGN8      = $this->formatPageNumber($this->pageGroups[$gNumber]);
                    $pgGN8Count = strlen($pgGN8);
                    $pgGN16     = $this->utf8ToUTF16BE($pgGN8);
                }
                ++$pgNumber;

                $currentGN8      = $this->formatPageNumber($pgNumber);
                $currentGN8Count = strlen($currentGN8);
                $currentGN16     = $this->utf8ToUTF16BE($currentGN8);

                // Replace group page numbers
                $replace   = [];
                $replace[] = [$pgGN16, $pgGN8Count, 9, $variants[2]['u']];
                $replace[] = [$pgGN8, $pgGN8Count, 7, $variants[2]['a']];
                $replace[] = [$currentGN16, $currentGN8Count, 9, $variants[3]['u']];
                $replace[] = [$currentGN8, $currentGN8Count, 7, $variants[3]['a']];
                [$pageBuffer, $groupShift] = StaticMethods::replacePageNumAliases($pageBuffer, $replace, $groupShift);
            }

            // Replace page numbers
            $replace   = [];
            $replace[] = [$lastPN16, $lastPN8Count, 9, $variants[0]['u']];
            $replace[] = [$lastPN8, $lastPN8Count, 7, $variants[0]['a']];
            $replace[] = [$currentPN16, $currentPN8Count, 9, $variants[1]['u']];
            $replace[] = [$currentPN8, $currentPN8Count, 7, $variants[1]['a']];
            [$pageBuffer, $pageShift] = StaticMethods::replacePageNumAliases($pageBuffer, $replace, $pageShift);

            $this->resolveRightShiftAlias($pageBuffer, $variants[4], max($pageShift, $groupShift));
            $pageBuffer = str_replace($this->epsMarker, '', $pageBuffer);

            //Page
            $this->pageObjectIDs[$page] = $this->appendObject();

            $out = '<< /Type /Page /Parent 1 0 R /LastModified ' . $this->metadataDateString($this->modificationTimestamp) . ' /Resources 2 0 R';

            foreach ($this->pageBoxes as $box) {
                $out .= ' /' . $box . " [{$dimensions[$box]['llx']} {$dimensions[$box]['lly']} {$dimensions[$box]['urx']} {$dimensions[$box]['ury']}]";
            }

            if (!empty($dimensions['boxSettings'])) {
                $out .= ' /BoxColorInfo <<';
                foreach ($this->pageBoxes as $box) {
                    if (isset($dimensions['boxSettings'][$box])) {
                        $out .= ' /' . $box . ' <<';
                        if (isset($dimensions['boxSettings'][$box]['C'])) {
                            $color = $dimensions['boxSettings'][$box]['C'];
                            $out   .= ' /C [';
                            $out   .= sprintf(' %F %F %F', $color[0] / 255, $color[1] / 255, $color[2] / 255);
                            $out   .= ' ]';
                        }
                        if (isset($dimensions['boxSettings'][$box]['W'])) {
                            $out .= ' /W ' . ($dimensions['boxSettings'][$box]['W'] * $this->factor);
                        }
                        if (isset($dimensions['boxSettings'][$box]['S'])) {
                            $out .= ' /S /' . $dimensions['boxSettings'][$box]['S'];
                        }
                        if (isset($dimensions['boxSettings'][$box]['D'])) {
                            $dashes = $dimensions['boxSettings'][$box]['D'];
                            $out    .= ' /D [';
                            foreach ($dashes as $dash) {
                                $out .= ' ' . ($dash * $this->factor);
                            }
                            $out .= ' ]';
                        }
                        $out .= ' >>';
                    }
                }
                $out .= ' >>';
            }

            $out .= ' /Contents ' . ($this->objectNumber + 1) . ' 0 R';
            $out .= ' /Rotate ' . $dimensions['rotate'];
            $out .= ' /Group << /Type /Group /S /Transparency /CS /DeviceRGB >>';
            $out .= $this->annotationReferences($page);
            $out .= ' /PZ ' . $dimensions['preferredZoom'];
            $out .= ' >>';
            $out .= "\n" . 'endobj';
            $this->append($out);

            //Page content
            $p = gzcompress($pageBuffer);
            $this->appendObject();
            $this->append('<<' . $filter . '/Length ' . strlen($p) . '>> stream' . "\n" . $p . "\n" . 'endstream' . "\n" . 'endobj');
        }

        //Pages root
        $out = $this->objectCommand(1) . "\n";
        $out .= '<< /Type /Pages /Kids [';
        foreach ($this->pageObjectIDs as $page_obj) {
            $out .= ' ' . $page_obj . ' 0 R';
        }
        $out .= ' ] /Count ' . $this->pageCount . ' >>';
        $out .= "\n" . 'endobj';
        $this->append($out);
    }

    /**
     * Getter / setter for  automatic page breaking. When enabling, the second parameter is the minimum bottom margin on break.
     * @param bool  $auto   bool indicating if mode should be on or off.
     * @param float $margin Distance from the bottom of the page.
     * @see   Cell(), MultiCell(), acceptPageBreak()
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
        $this->visibilityLayer();
        $this->state = self::NEUTRAL;

        $this->pageStatus[$this->page] = false;
        if ($toc) {
            $this->tocpage = false;
        }
    }

    /**
     * Removes the specified page.
     * @param int $page
     */
    public function deletePage(int $page): void
    {
        if ($page < 1 or $page > $this->pageCount) {
            Application::message("Invalid page ($page) used in deletePage.", Application::ERROR);
            return;
        }

        unset($this->borderMarks[$page], $this->contentMarks[$page], $this->footerLengths[$page], $this->footerPositions[$page]);
        unset($this->internalMarks[$page], $this->pages[$page], $this->pageAnnotations[$page], $this->pageDimensions[$page]);
        unset($this->pageLengths[$page], $this->pageStatus[$page], $this->transpositions[$page]);

        foreach ($this->pageObjects[$page] as $objectID) {
            if (isset($this->objectOffsets[$objectID])) {
                unset($this->objectOffsets[$objectID]);
            }
        }

        unset($this->pageObjects[$page]);

        if (!empty($this->pageGroupStarts)) {
            for ($i = $page; $i > 0; --$i) {
                if (isset($this->pageGroupStarts[$i]) and (($i + $this->pageGroups[$this->pageGroupStarts[$i]]) > $page)) {
                    --$this->pageGroups[$this->pageGroupStarts[$i]];
                    break;
                }
            }
        }

        $lastPage = $this->pageCount;
        if ($page < $lastPage) {
            // update remaining pages
            for ($thisPage = $page; $thisPage < $lastPage; ++$thisPage) {
                $nextPage = $thisPage + 1;
                $this->shiftPage($thisPage, $nextPage);
            }

            // remove last page
            unset($this->borderMarks[$lastPage], $this->contentMarks[$lastPage], $this->footerLengths[$lastPage], $this->footerPositions[$lastPage]);
            unset($this->internalMarks[$lastPage], $this->pages[$lastPage], $this->pageAnnotations[$lastPage], $this->pageDimensions[$lastPage]);
            unset($this->pageGroups[$lastPage], $this->pageGroupStarts[$lastPage], $this->pageLengths[$lastPage], $this->pageStatus[$lastPage]);
            unset($this->transpositions[$lastPage]);

            if ($this->currentPageGroup === $lastPage) {
                $this->currentPageGroup = $lastPage - 1;
            }

            foreach ($this->pageObjects[$lastPage] as $objectID) {
                if (isset($this->objectOffsets[$objectID])) {
                    unset($this->objectOffsets[$objectID]);
                }
            }
            unset($this->pageObjects[$lastPage]);
        }

        --$this->pageCount;
        $this->page = $this->pageCount;

        foreach ($this->outlines as $key => $outline) {
            if (!$outline['f']) {
                if ($outline['p'] > $page) {
                    $this->outlines[$key]['p'] = $outline['p'] - 1;
                }
                elseif ($outline['p'] === $page) {
                    unset($this->outlines[$key]);
                }
            }
        }

        foreach ($this->namedDestinationsMap as $key => $dest) {
            if (!$dest['f']) {
                if ($dest['p'] > $page) {
                    $this->namedDestinationsMap[$key]['p'] = $dest['p'] - 1;
                }
                elseif ($dest['p'] === $page) {
                    unset($this->namedDestinationsMap[$key]);
                }
            }
        }

        foreach ($this->internalLinks as $key => $link) {
            if (!$link['f']) {
                if ($link['p'] > $page) {
                    $this->internalLinks[$key]['p'] = $link['p'] - 1;
                }
                elseif ($link['p'] === $page) {
                    unset($this->internalLinks[$key]);
                }
            }
        }

        if (preg_match_all('/this\.addField\(\'([^\']*)\',\'([^\']*)\',([0-9]+)/', $this->javascript, $fields) > 0) {
            foreach ($fields[0] as $pk => $field) {
                $fieldPage = (int) $fields[3][$pk] + 1;
                $newPage   = $fieldPage >= $page ? $fieldPage - 1 : $fieldPage;
                --$newPage;
                $this->javascript = str_replace($field, "this.addField(\'{$fields[1][$pk]}\',\'{$fields[2][$pk]}\',$newPage", $this->javascript);
            }
            unset($fields);
        }

        if ($this->pageCount > 0) {
            $this->turnToLastPage(true);
        }
    }

    /**
     * Format the page numbers.
     * @param float $page
     * @return string
     */
    public function formatPageNumber(float $page): string
    {
        return number_format($page, 0, '', '.');
    }

    /**
     * Initialize a new page.
     * @param string       $orientation
     * @param array|string $format
     * @see self::ORIENTATIONS, self::PAGE_FORMATS, self::PAGE_FORMAT_CONFIGURATION_DESCRIPTION
     */
    protected function initializePage(string $orientation = self::CURRENT, array|string $format = self::CURRENT): void
    {
        ++$this->page;
        $this->pageObjects[$this->page] = [];
        $this->pageBuffer($this->page, '');
        $this->transpositions[$this->page] = [];

        $this->state = self::READY;

        if ($orientation === self::CURRENT) {
            if (isset($this->currentOrientation)) {
                $orientation = $this->currentOrientation;
            }
            elseif ($this->formatWidthPT > $this->formatHeightPT) {
                $orientation = self::LANDSCAPE;
            }
            else {
                $orientation = self::PORTRAIT;
            }
        }

        if ($format) {
            $this->pageFormat($format, $orientation);
        }
        else {
            $this->pageDimensions[$this->page] = $this->pageDimensions[($this->page - 1)];
            $this->pageOrientation($orientation);
        }

        $this->x = $this->leftMargin;
        $this->y = $this->topMargin;

        if (isset($this->pageGroupStarts[$this->page])) {
            // start a new group
            $this->currentPageGroup                    = $this->pageGroupStarts[$this->page];
            $this->pageGroups[$this->currentPageGroup] = 1;
        }
        elseif (isset($this->currentPageGroup) and ($this->currentPageGroup > 0)) {
            ++$this->pageGroups[$this->currentPageGroup];
        }
    }

    /**
     * Check if we are on the page body (excluding page header and footer).
     * @return bool
     */
    protected function inPageBody(): bool
    {
        return ($this->inHeader === false and $this->inFooter === false);
    }

    /**
     * Current document page number.
     * @return int
     */
    public function page(): int
    {
        return $this->page;
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
     * Determines whether a page break is necessary and add one if flag is set.
     * @param float      $height  Cell height. Default value: 0.
     * @param float|null $y       starting y position, leave empty for current position.
     * @param bool       $addPage if true add a page, otherwise only return the true/false state
     * @return bool true in case of page break, false otherwise.
     */
    protected function pageBreak(float $height = 0, float|null $y = null, bool $addPage = true): bool
    {
        $y = $y ?? $this->y;

        $currentPage = $this->page;
        if (($y + $height) > $this->breakTrigger and $this->inPageBody() and $this->acceptPageBreak()) {
            if ($addPage) {
                $this->addPage($this->currentOrientation);
                $this->y = $this->topMargin;
                $thisPD  = $this->pageDimensions[$this->page];
                $oldPD   = $this->pageDimensions[$this->page - 1];
                $this->x += $thisPD['originalLeftMargin'] - $oldPD['originalLeftMargin'];
            }

            return true;
        }

        // Account for columns mode is the original comment
        return $currentPage !== $this->page;
    }

    /**
     * Getter / setter for the page buffer
     * @param int         $pageNumber
     * @param string|null $content
     * @param bool        $append true => append, false => replace
     * @return string
     */
    protected function pageBuffer(int $pageNumber, string|null $content = null, bool $append = false): string
    {
        if (!is_null($content)) {
            $existing                       = $this->pages[$pageNumber] ?? '';
            $this->pages[$pageNumber]       = $append ? $existing . $content : $content;
            $this->pageLengths[$pageNumber] = strlen($this->pages[$pageNumber]);
        }

        return $this->pages[$pageNumber] ?? '';
    }

    /**
     * Returns the configured dimensions for the given page, defaults to current.
     * @param int $page
     * @return array
     * @see self::PAGE_DIMENSION_DESCRIPTION
     */
    public function pageDimensions(int $page = 0): array
    {
        return $this->pageDimensions[$page ?: $this->page];
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
    protected function pageFormat(array|string $format, string $orientation = self::CURRENT): void
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

            if (isset($format['boxSettings'])) {
                $page['boxSettings'] = $format['boxSettings'];
            }

            if (isset($format['rotate']) and ($format['rotate'] % 90 === 0)) {
                $page['rotate'] = (int) $format['rotate'];
            }

            if (isset($format['preferredZoom'])) {
                $page['preferredZoom'] = (float) $format['preferredZoom'];
            }
        }

        $this->pageOrientation($orientation);
    }

    /**
     * Set start-writing mark on current page stream used to put borders and fills.
     * Borders and fills are always created after content and inserted on the position marked by this method.
     * This function must be called after calling Image() function for a background image.
     * Background images must be always inserted before calling Multicell() or WriteHTMLCell() or WriteHTML() functions.
     */
    public function pageMark(): void
    {
        $position = $this->pageLengths[$this->page];

        $this->internalMarks[$this->page] = $position;
        $this->borderMarks[$this->page]   = $position;
        $this->contentMark();
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

        if (!isset($page['preferredZoom'])) {
            $page['preferredZoom'] = 1.0;
        }
        if (!isset($page['rotate'])) {
            $page['rotate'] = 0;
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
        $page['autoPagebreak']       = $autoPagebreak;
        $page['bottomMargin']        = $bottomMargin;
        $page['currentOrientation']  = $this->currentOrientation;
        $page['height']              = $this->height;
        $page['heightPT']            = $this->heightPT;
        $page['leftMargin']          = $this->leftMargin;
        $page['originalLeftMargin']  = $this->originalLeftMargin;
        $page['originalRightMargin'] = $this->originalRightMargin;
        $page['rightMargin']         = $this->rightMargin;
        $page['topMargin']           = $this->topMargin;
        $page['width']               = $this->width;
        $page['widthPT']             = $this->widthPT;
    }

    /** Renders the table header on new page. */
    protected function renderTableHeader(): void
    {
        // Multi column mode
        if ($this->columnCount > 1) {
            return;
        }

        $pageDimensions =& $this->pageDimensions[$this->page];
        if (isset($this->tableHeaderMargins['top'])) {
            // Restore the original top-margin
            $this->topMargin             = $this->tableHeaderMargins['top'];
            $pageDimensions['topMargin'] = $this->topMargin;
            $this->y                     = $this->topMargin;
        }

        if ($this->tableHeader and (!$this->inTableHeader)) {
            $thPageDimensions = $this->pageDimensions[$this->tableHeaderMargins['page']];
            // set margins
            $leftMarginBuffer   = $this->leftMargin;
            $rightMarginBuffer  = $this->rightMargin;
            $previousPaddings   = $this->cellPaddings;
            $this->leftMargin   = $this->tableHeaderMargins['leftMargin'] + $pageDimensions['originalLeftMargin'] - $thPageDimensions['originalLeftMargin'];
            $this->rightMargin  = $this->tableHeaderMargins['rightMargin'] + $pageDimensions['originalRightMargin'] - $thPageDimensions['originalRightMargin'];
            $this->cellPaddings = $this->tableHeaderMargins['cellPaddings'];
            $this->x            = $this->leftMargin;

            // Account for special "cell" mode
            if ($this->tableHeaderMargins['cell']) {
                $this->x += $this->cellPaddings['L'];
            }

            $settings = $this->graphicsSettings();
            if (!empty($this->tableHeaderMargins['settings'])) {
                // set the correct graphic style
                $this->graphicsSettings($this->tableHeaderMargins['settings']);
                $this->rightMargin = $settings['rightMargin'];
                $this->leftMargin  = $settings['leftMargin'];
            }

            // Append table header
            $this->writeHTML($this->tableHeader, false, false, false, false, '');
            $this->graphicsSettings($settings);

            // Set new top margin to skip the table headers
            if (!isset($this->tableHeaderMargins['top'])) {
                $this->tableHeaderMargins['top'] = $this->topMargin;
            }

            // Store end of header position
            if (!isset($this->columns[0]['th'])) {
                $this->columns[0]['th'] = [];
            }

            $this->columns[0]['th']['\'' . $this->page . '\''] = $this->y;

            $this->topMargin             = $this->y;
            $pageDimensions['topMargin'] = $this->topMargin;
            $this->lastPrintedCellHeight = 0;
            $this->leftMargin            = $leftMarginBuffer;
            $this->rightMargin           = $rightMarginBuffer;
            $this->cellPaddings          = $previousPaddings;
        }
    }

    /**
     * Check page for restricted regions and adapt current coordinates and page margins if necessary.
     * @param float $x      current X coordinate in user units
     * @param float $y      current Y coordinate in user units
     * @param float $height height of the item to print in user units
     * @return float[] [$x, $y]
     */
    protected function repositionForRegions(float $x, float $y, float $height = 0): array
    {
        if (!$this->checkRegions or empty($this->pageRegions)) {
            // no page regions defined
            return [$x, $y];
        }

        $height = $height ?: $this->cellHeight($this->fontSize);
        // check for page break
        if ($this->pageBreak($height, $y)) {
            // the content will be printed on a new page
            $x = $this->x;
            $y = $this->y;
        }
        if ($this->columnCount > 1) {
            $this->rightMargin = ($this->width - $this->columns[$this->currentColumn]['x'] - $this->columns[$this->currentColumn]['w']);
        }
        else {
            $this->rightMargin = max($this->cellRightMargin, $this->originalRightMargin);
        }
        // adjust coordinates and page margins
        foreach ($this->pageRegions as $regid => $regdata) {
            if ($regdata['page'] == $this->page) {
                // check region boundaries
                if (($y > ($regdata['yt'] - $height)) and ($y <= $regdata['yb'])) {
                    // Y is inside the region
                    $minv = ($regdata['xb'] - $regdata['xt']) / ($regdata['yb'] - $regdata['yt']); // inverse of angular coefficient
                    $yt   = max($y, $regdata['yt']);
                    $yb   = min(($yt + $height), $regdata['yb']);
                    $xt   = (($yt - $regdata['yt']) * $minv) + $regdata['xt'];
                    $xb   = (($yb - $regdata['yt']) * $minv) + $regdata['xt'];
                    if ($regdata['side'] == 'L') { // left side
                        $new_margin = max($xt, $xb);
                        if ($this->leftMargin < $new_margin) {
                            if ($x < $new_margin) {
                                // adjust x position
                                $x = $new_margin;
                                if ($new_margin > ($this->width - $this->rightMargin)) {
                                    // adjust y position
                                    $y = $regdata['yb'] - $height;
                                }
                            }
                        }
                    }
                    elseif ($regdata['side'] == 'R') { // right side
                        $new_margin = min($xt, $xb);
                        if (($this->width - $this->rightMargin) > $new_margin) {
                            $this->rightMargin = max(0, ($this->width - $new_margin));
                            if ($x > $new_margin) {
                                // adjust x position
                                $x = $new_margin;
                                if ($new_margin > $this->leftMargin) {
                                    // adjust y position
                                    $y = $regdata['yb'] - $height;
                                }
                            }
                        }
                    }
                }
            }
        }
        return [$x, $y];
    }

    /**
     * Replace right shift page number aliases with spaces to correct right alignment. This works perfectly only when using monospaced fonts.
     * @param string $content
     * @param array  $aliasVariants
     * @param int    $offset
     */
    protected function resolveRightShiftAlias(string &$content, array $aliasVariants, int $offset): void
    {
        foreach ($aliasVariants as $type => $variants) {
            foreach ($variants as $variant) {
                // find position of compensation factor
                $fPosition = strpos($variant, ':') + 1;
                $variant   = substr($variant, 0, $fPosition);
                if (($vPosition = strpos($content, $variant)) !== false) {
                    $vEnd   = strpos($content, '}', $vPosition);
                    $target = substr($content, $vPosition, ($vEnd - $vPosition + 1));
                    $ratio  = substr($content, $vPosition + $fPosition, $vEnd - $vPosition - $fPosition);
                    $ratio  = (float) preg_replace('/[^0-9.]/', '', $ratio);

                    if ($type === 'u') {
                        $chrOffset = floor(($offset + 12) * $ratio);
                        $shift     = str_repeat(' ', $chrOffset);
                        $shift     = $this->utf8ToUTF16BE($shift);
                    }
                    // Type === 'a'
                    else {
                        $chrOffset = floor(($offset + 11) * $ratio);
                        $shift     = str_repeat(' ', $chrOffset);
                    }
                    $content = str_replace($target, $shift, $content);
                }
            }
        }
    }

    /**
     * Copies the contents and references from the source page to the target page.
     * @param int $target
     * @param int $source
     */
    private function shiftPage(int $target, int $source): void
    {
        $this->pageBuffer($target, $this->pageBuffer($source));
        $this->pageDimensions[$target] = $this->pageDimensions[$source];
        $this->pageLengths[$target]    = $this->pageLengths[$source];
        $this->internalMarks[$target]  = $this->internalMarks[$source];
        $this->borderMarks[$target]    = $this->borderMarks[$source];
        $this->contentMarks[$target]   = $this->contentMarks[$source];
        $this->pageObjects[$target]    = $this->pageObjects[$source];
        if (isset($this->footerPositions[$source])) {
            $this->footerPositions[$target] = $this->footerPositions[$source];
        }
        elseif (isset($this->footerPositions[$target])) {
            unset($this->footerPositions[$target]);
        }
        if (isset($this->footerLengths[$source])) {
            $this->footerLengths[$target] = $this->footerLengths[$source];
        }
        elseif (isset($this->footerLengths[$target])) {
            unset($this->footerLengths[$target]);
        }
        if (isset($this->transpositions[$source])) {
            $this->transpositions[$target] = $this->transpositions[$source];
        }
        elseif (isset($this->transpositions[$target])) {
            unset($this->transpositions[$target]);
        }
        if (isset($this->pageAnnotations[$source])) {
            $this->pageAnnotations[$target] = $this->pageAnnotations[$source];
        }
        elseif (isset($this->pageAnnotations[$target])) {
            unset($this->pageAnnotations[$target]);
        }
        if (isset($this->pageGroupStarts[$source])) {
            $this->pageGroupStarts[$target] = $this->pageGroupStarts[$source];
            unset($this->pageGroupStarts[$source]);
        }
        if ($this->currentPageGroup == $source) {
            $this->currentPageGroup = $target;
        }
        if (isset($this->pageStatus[$source])) {
            $this->pageStatus[$target] = $this->pageStatus[$source];
        }
        elseif (isset($this->pageStatus[$target])) {
            unset($this->pageStatus[$target]);
        }
    }

    /**
     * Creates an array of Unicode + ASCII variants of a basic page number alias.
     * @param string $alias Base alias.
     * @return array of page number aliases
     */
    protected function singlePNAVariants(string $alias = ''): array
    {
        $bracketed = '{' . $alias . '}';
        $variants  = ['u' => [], 'a' => []];

        // The order is important
        $variants['u'][] = $this->escape($bracketed);
        $variants['u'][] = $this->escape($this->utf8ToLatin1($bracketed));
        $variants['u'][] = $this->escape($this->utf8ToUTF16BE($bracketed));
        $variants['a'][] = $this->escape($this->utf8ToLatin1($alias));
        $variants['a'][] = $this->escape($this->utf8ToUTF16BE($alias));
        $variants['a'][] = $this->escape($alias);
        return $variants;
    }

    /**
     * Starts a new page to the document. The page must be closed using the endPage() function.
     * @param string       $orientation
     * @param array|string $format The format used for pages. It can be either: one of the string values specified at getPageSizeFromFormat() or an array of parameters specified at setPageFormat().
     * @param bool         $toc    if true the page is designated to contain the Table-Of-Content.
     * @see   self::ORIENTATIONS, addPage(), closePage(), addTOCPage(), endTOCPage(), getPageSizeFromFormat(), pageFormat()
     */
    public function startPage(string $orientation = self::CURRENT, array|string $format = '', bool $toc = false): void
    {
        // Move page numbers of documents to be attached
        if ($toc) {
            $this->tocpage = true;
            // Move references to non-existent pages for page attachments
            // Adjust outlines
            foreach ($this->outlines as $key => &$outline) {
                if (!$outline['f'] and ($outline['p'] > $this->pageCount)) {
                    $outline['p'] += 1;
                }
            }
            // Adjust named destinations
            foreach ($this->namedDestinationsMap as $key => &$destination) {
                if (!$destination['f'] and ($destination['p'] > $this->pageCount)) {
                    $destination['p'] += 1;
                }
            }
            // Adjust links
            foreach ($this->internalLinks as &$link) {
                if (!$link['f'] and ($link['p'] > $this->pageCount)) {
                    $link['p'] += 1;
                }
            }
        }

        // Already existent
        if ($this->pageCount > $this->page) {
            $this->turnToPage($this->page + 1);
            $this->y($this->topMargin);
            return;
        }

        // Start a new page
        if ($this->state === self::INITIAL) {
            $this->state = self::NEUTRAL;
        }

        ++$this->pageCount;

        if ($this->booklet) {
            $this->swapMargins();
        }

        // save current graphic settings
        $settings = $this->graphicsSettings();
        // start new page
        $this->initializePage($orientation, $format);
        // mark page as open
        $this->pageStatus[$this->page] = true;
        // restore graphic settings
        $this->graphicsSettings($settings);
        // mark this point
        $this->pageMark();
        // print page header
        $this->header();
        // restore graphic settings
        $this->graphicsSettings($settings);
        // mark this point
        $this->pageMark();
        // print table header (if any)
        $this->renderTableHeader();
        // set mark for empty page check
        $this->emptyPageMarks[$this->page] = $this->pageLengths[$this->page];
    }

    /**
     * Swap the left and right margins.
     */
    protected function swapMargins(): void
    {
        $olmBuffer = $this->originalLeftMargin;
        $ormBuffer = $this->originalRightMargin;

        $this->originalLeftMargin  = $ormBuffer;
        $this->originalRightMargin = $olmBuffer;

        $marginDelta = $this->originalLeftMargin - $this->originalRightMargin;

        $this->leftMargin  += $marginDelta;
        $this->rightMargin -= $marginDelta;
    }

    /**
     * Move pointer at the last document page and update page dimensions.
     * @param bool $resetMargins whether to reset left, right, top margins and y position
     */
    public function turnToLastPage(bool $resetMargins = false): void
    {
        $this->turnToPage($this->pageCount, $resetMargins);
    }

    /**
     * Move pointer at the specified document page and update page dimensions.
     * @param int  $page
     * @param bool $resetMargins whether to reset left, right, top margins and y position
     */
    public function turnToPage(int $page, bool $resetMargins = false): void
    {
        if ($page === $this->page and $this->state === self::READY) {
            return;
        }

        if ($page < 0 or $page > $this->pageCount) {
            Application::message("Requested page $page has not been initiated.", Application::ERROR);
            return;
        }

        $this->state  = self::READY;
        $previousPage = $this->page;
        $this->page   = $page;

        // Load stored graphic settings
        $dimensions                = $this->pageDimensions[$this->page];
        $this->autoPageBreak       = $dimensions['autoPageBreak'];
        $this->bottomMargin        = $dimensions['bottomMargin'];
        $this->currentOrientation  = $dimensions['currentOrientation'];
        $this->height              = $dimensions['height'];
        $this->heightPT            = $dimensions['heightPT'];
        $this->topMargin           = $dimensions['topMargin'];
        $this->originalLeftMargin  = $dimensions['originalLeftMargin'];
        $this->originalRightMargin = $dimensions['originalRightMargin'];
        $this->width               = $dimensions['width'];
        $this->widthPT             = $dimensions['widthPT'];
        $this->autoPageBreak($this->autoPageBreak, $this->bottomMargin);

        // restore graphic settings
        if ($resetMargins) {
            $this->leftMargin  = $dimensions['originalLeftMargin'];
            $this->rightMargin = $dimensions['originalRightMargin'];
            $this->y($this->topMargin);
        }
        elseif ($dimensions['originalLeftMargin'] !== $this->pageDimensions[$previousPage]['originalLeftMargin']) {
            $delta = $dimensions['originalLeftMargin'] - $dimensions['originalRightMargin'];

            $this->leftMargin  += $delta;
            $this->rightMargin -= $delta;
        }
    }

#endregion
#region ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNUSED

    /**
     * Clone the specified page to a new page.
     * @param int $page number of page to copy (0 = current page)
     * @return bool true in case of success, false in case of error.
     */
    public function copyPage(int $page = 0): bool
    {
        $page = $page ?: $this->page;

        if ($page < 1 or $page > $this->pageCount) {
            Application::message("Invalid page ($page) used in copyPage.", Application::ERROR);
            return false;
        }

        $this->closePage();

        // Copy page-related states
        ++$this->pageCount;
        $this->page = $this->pageCount;
        $this->pageBuffer($this->page, $this->pageBuffer($page));
        $this->pageDimensions[$this->page] = $this->pageDimensions[$page];
        $this->pageLengths[$this->page]    = $this->pageLengths[$page];
        $this->internalMarks[$this->page]  = $this->internalMarks[$page];
        $this->borderMarks[$this->page]    = $this->borderMarks[$page];
        $this->contentMarks[$this->page]   = $this->contentMarks[$page];
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
        if (isset($this->pageGroupStarts[$page])) {
            // start a new group
            $this->pageGroupStarts[$this->page]        = sizeof($this->pageGroupStarts) + 1;
            $this->currentPageGroup                    = $this->pageGroupStarts[$this->page];
            $this->pageGroups[$this->currentPageGroup] = 1;
        }
        elseif (isset($this->currentPageGroup) and ($this->currentPageGroup > 0)) {
            ++$this->pageGroups[$this->currentPageGroup];
        }

        foreach ($this->outlines as $outline) {
            if ($outline['p'] === $page) {
                $this->outlines[] = ['t' => $outline['t'], 'l' => $outline['l'], 'x' => $outline['x'], 'y' => $outline['y'], 'p' => $this->page, 'f' => $outline['f'], 's' => $outline['s'], 'c' => $outline['c']];
            }
        }

        foreach ($this->internalLinks as $key => $link) {
            if ($link['p'] === $page) {
                $this->internalLinks[] = ['p' => $this->page, 'y' => $link['y'], 'f' => $link['f']];
            }
        }

        $this->turnToLastPage(true);
        return true;
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
        $this->layoutMode = match ($layout) {
            self::ONE_COLUMN => self::ONE_COLUMN,
            self::TWO_COLUMN_LEFT => self::TWO_COLUMN_LEFT,
            self::TWO_COLUMN_RIGHT => self::TWO_COLUMN_RIGHT,
            self::TWO_PAGE_LEFT => self::TWO_PAGE_LEFT,
            self::TWO_PAGE_RIGHT => self::TWO_PAGE_RIGHT,
            default => self::SINGLE_PAGE,
        };
    }

    /**
     * Get the total number of inserted pages.
     * @return int
     */
    public function pageCount(): int
    {
        return $this->pageCount;
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
     * Getter / setter for the starting page number.
     * @param int|null $page
     * @return int
     */
    public function startingPageNumber(int|null $page = null): int
    {
        if (is_int($page)) {
            $this->startingPageNumber = $page > 0 ? $page : 1;
        }

        return $this->startingPageNumber;
    }

    /**
     * Create a new page group.
     * NOTE: call this function before calling AddPage()
     * @param int|null $page starting group page (leave empty for next page).
     */
    public function startPageGroup(int|null $page = null): void
    {
        if (empty($page)) {
            $page = $this->page + 1;
        }
        $this->pageGroupStarts[$page] = sizeof($this->pageGroupStarts) + 1;
    }

#endregion
#region ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Returns the page height in units.
     * @param int|null $pagenum page number (empty = current page)
     * @return int|float page height.
     * @author Nicola Asuni
     * @see    pageDimensions()
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
     * @see    pageDimensions()
     */
    public function getPageWidth($pagenum = null)
    {
        if (empty($pagenum)) {
            return $this->width;
        }
        return $this->pageDimensions[$pagenum]['w'];
    }

    /**
     * Switch the contents and references of two pages.
     * @param int $pageA
     * @param int $pageB
     */
    public function switchPages(int $pageA, int $pageB): void
    {
        $identity = $pageA === $pageB;
        $invalidA = ($pageA < 1 or $pageA > $this->pageCount);
        $invalidB = ($pageB < 1 or $pageB > $this->pageCount);
        if ($identity or $invalidA or $invalidB) {
            return;
        }

        // Close current if referenced
        if ($pageA === $this->page or $pageB === $this->page) {
            $this->closePage();
        }

        // move all page-related states
        $bmBuffer = $this->borderMarks[$pageA];
        $cmBuffer = $this->contentMarks[$pageA];
        $dBuffer  = $this->pageDimensions[$pageA];
        if (isset($this->footerLengths[$pageA])) {
            $flBuffer = $this->footerLengths[$pageA];
        }
        if (isset($this->footerPositions[$pageA])) {
            $fpBuffer = $this->footerPositions[$pageA];
        }
        $imBuffer = $this->internalMarks[$pageA];
        $pBuffer  = $this->pageBuffer($pageA);
        if (isset($this->pageAnnotations[$pageA])) {
            $paBuffer = $this->pageAnnotations[$pageA];
        }
        $plBuffer = $this->pageLengths[$pageA];
        $poBuffer = $this->pageObjects[$pageA];
        if (isset($this->transpositions[$pageA])) {
            $tBuffer = $this->transpositions[$pageA];
        }

        if (isset($this->pageGroupStarts) and !empty($this->pageGroupStarts)) {
            for ($i = $pageA; $i > $pageB; --$i) {
                if (isset($this->pageGroupStarts[$i]) and (($i + $this->pageGroups[$this->pageGroupStarts[$i]]) > $pageA)) {
                    --$this->pageGroups[$this->pageGroupStarts[$i]];
                    break;
                }
            }
            for ($i = $pageB; $i > 0; --$i) {
                if (isset($this->pageGroupStarts[$i]) and (($i + $this->pageGroups[$this->pageGroupStarts[$i]]) > $pageB)) {
                    ++$this->pageGroups[$this->pageGroupStarts[$i]];
                    break;
                }
            }
        }
        for ($i = $pageA; $i > $pageB; --$i) {
            $j = $i - 1;
            // shift pages down
            $this->shiftPage($i, $j);
        }
        $this->pageBuffer($pageB, $pBuffer);
        $this->pageDimensions[$pageB] = $dBuffer;
        $this->pageLengths[$pageB]    = $plBuffer;
        $this->internalMarks[$pageB]  = $imBuffer;
        $this->borderMarks[$pageB]    = $bmBuffer;
        $this->contentMarks[$pageB]   = $cmBuffer;
        $this->pageObjects[$pageB]    = $poBuffer;
        if (isset($fpBuffer)) {
            $this->footerPositions[$pageB] = $fpBuffer;
        }
        elseif (isset($this->footerPositions[$pageB])) {
            unset($this->footerPositions[$pageB]);
        }
        if (isset($flBuffer)) {
            $this->footerLengths[$pageB] = $flBuffer;
        }
        elseif (isset($this->footerLengths[$pageB])) {
            unset($this->footerLengths[$pageB]);
        }
        if (isset($tBuffer)) {
            $this->transpositions[$pageB] = $tBuffer;
        }
        elseif (isset($this->transpositions[$pageB])) {
            unset($this->transpositions[$pageB]);
        }
        if (isset($paBuffer)) {
            $this->pageAnnotations[$pageB] = $paBuffer;
        }
        elseif (isset($this->pageAnnotations[$pageB])) {
            unset($this->pageAnnotations[$pageB]);
        }
        // adjust outlines
        $tmpoutlines = $this->outlines;
        foreach ($tmpoutlines as $key => $outline) {
            if (!$outline['f']) {
                if (($outline['p'] >= $pageB) and ($outline['p'] < $pageA)) {
                    $this->outlines[$key]['p'] = ($outline['p'] + 1);
                }
                elseif ($outline['p'] == $pageA) {
                    $this->outlines[$key]['p'] = $pageB;
                }
            }
        }
        // adjust dests
        $tmpdests = $this->namedDestinationsMap;
        foreach ($tmpdests as $key => $dest) {
            if (!$dest['f']) {
                if (($dest['p'] >= $pageB) and ($dest['p'] < $pageA)) {
                    $this->namedDestinationsMap[$key]['p'] = ($dest['p'] + 1);
                }
                elseif ($dest['p'] == $pageA) {
                    $this->namedDestinationsMap[$key]['p'] = $pageB;
                }
            }
        }
        // adjust links
        $tmplinks = $this->internalLinks;
        foreach ($tmplinks as $key => $link) {
            if (!$link['f']) {
                if (($link['p'] >= $pageB) and ($link['p'] < $pageA)) {
                    $this->internalLinks[$key]['p'] = ($link['p'] + 1);
                }
                elseif ($link['p'] == $pageA) {
                    $this->internalLinks[$key]['p'] = $pageB;
                }
            }
        }
        // adjust javascript
        $jfrompage = $pageA;
        $jtopage   = $pageB;
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
        $this->turnToLastPage(true);
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

#endregion
}