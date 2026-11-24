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

use THM\Organizer\Adapters\Application;

use Joomla\CMS\Uri\Uri;
use THM\Organizer\Views\PDF\Library\Barcodes\{Barcode1D, Barcode2D};
use THM\Organizer\Views\PDF\Library\Print\PrintTools;
use THM\Organizer\Views\PDF\Library\Tools\Tools;

/**  Class for generating PDF documents without requiring external extensions. */
class PDF
{
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

    use Bloatware;
    use Images;
    use PrintTools;
    use Tools;

    protected array $annotationIDs = [];
    protected string $author = '';
    protected string $buffer = '';
    protected int $bufferLength = 0;
    protected object|null $clone = null;
    protected int $creationTimestamp = 0;
    protected string $creator = 'THM Organizer';
    protected array $graphicsSettings = [];
    protected array $encodingDeltas = [];
    protected float $factor = 0;
    /** File ID for document trailer */
    protected string $fileID;
    protected array $internalLinks = [];
    protected string $keywords = '';
    protected float $lastPrintedCellHeight = 0;
    protected bool $layerOpened = false;
    protected array $layers = [];
    protected string $libraryURL = '';
    protected bool $lineThrough = false;
    protected bool $inXObject = false;
    /** Transformation matrix */
    protected array $matrix = [];
    protected int $matrixKey = 0;
    protected int $modificationTimestamp = 0;
    protected string $namedDestinationID = '';
    /** A dictionary of names and corresponding destinations (Dests key on document Catalog). */
    protected array $namedDestinationsMap = [];
    protected bool $newLine = true;
    protected int $objectNumber = 2;
    protected bool $overline = false;
    /** Array of $spacesRE parts. */
    protected array $spaceRE = ['p' => '(?!\xa0)[\s\p{Z}]', 'm' => 'u'];
    /** Regular expression used to find blank characters for word-wrapping. */
    protected string $spacesRE = '/(?!\xa0)[\s\p{Z}]/u';
    protected int $state = self::INITIAL;
    protected string $subject = '';
    protected string $title = '';
    protected bool $underline = false;
    protected string $unit = self::MILLIMETER;
    /** Preserves the ids of files who have had all properties unset. */
    protected static array $unsetFileIDs = [];
    protected float $x = 0;
    protected string $xObjectID = '';
    protected array $xObjects = [];
    protected float $y = 0;


    /**
     * Constructor
     *
     * @param string $orientation
     * @param string $unit
     * @param mixed  $format
     * @see self::ORIENTATIONS, self::UNITS, self::PAGE_FORMATS, self::PAGE_FORMAT_CONFIGURATION_DESCRIPTION
     */
    public function __construct(string $orientation = self::PORTRAIT, string $unit = self::MILLIMETER, string $format = 'A4')
    {
        $this->unit   = in_array($unit, self::UNITS) ? $unit : self::MILLIMETER;
        $this->factor = match ($this->unit) {
            self::CENTIMETER => self::DPI / 2.54,
            self::INCH => self::DPI,
            self::POINT => 1,
            default => self::DPI / 25.4,
        };

        $margin      = self::POINTS_PER_CM / $this->factor;
        $cellPadding = $margin / 10;

        $this->cellPaddings($cellPadding, 0, $cellPadding, 0);
        $this->cellMargins(0, 0, 0, 0);
        $this->displayMode();
        $this->pageFormat($format, $orientation);
        $this->populateFonts();
        $this->setMargins($margin, $margin);
        $this->setTextShadow();

        $this->font($this->fontFamily, $this->fontStyle, $this->fontSizePT);

        $this->cellLeftMargin        = $this->leftMargin;
        $this->cellRightMargin       = $this->rightMargin;
        $this->creationTimestamp     = time();
        $this->fileID                = md5($this->randomSeed('Organizer' . $orientation . $unit . $format . 'UTF-8'));
        $this->graphicsSettings      = $this->graphicsSettings();
        $this->libraryURL            = Uri::base() . '/components/com_organizer/Views/PDF/Library/';
        $this->lineWidthStyle        = sprintf('%F w', $this->lineWidth * $this->factor);
        $this->modificationTimestamp = $this->creationTimestamp;
        $this->transpositions[0]     = [];

    }

    /** Destructor */
    public function __destruct()
    {
        $this->unsetProperties(true);
    }

    /**
     * Set alpha for stroking (CA) and non-stroking (ca) operations.
     * @param float      $stroking    alpha value for stroking operations: 0 (transparent) <=>> 1 (opaque)
     * @param string     $blendMode
     * @param float|null $nonStroking alpha value for non-stroking operations: 0 (transparent) <=>> 1 (opaque)
     * @param bool       $ais
     * @return array
     * @see self::BLEND_MODES
     */
    public function alpha(float $stroking = 1, string $blendMode = self::NORMAL, float|null $nonStroking = null, bool $ais = false): array
    {
        $nonStroking = is_null($nonStroking) ? $stroking : $nonStroking;

        if (str_starts_with($blendMode, '/')) {
            $blendMode = substr($blendMode, 1);
        }

        $blendMode = in_array($blendMode, self::BLEND_MODES) ? $blendMode : self::NORMAL;

        $this->alpha = ['CA' => $stroking, 'ca' => $nonStroking, 'BM' => '/' . $blendMode, 'AIS' => $ais];
        $gs          = $this->addExtGState($this->alpha);
        $this->setExtGState($gs);

        return $this->alpha;
    }

    /**
     * Appends a string to the document.
     * @param string $string
     */
    protected function append(string $string): void
    {
        if ($this->state === self::READY) {
            if ($this->inXObject) {
                $this->xObjects[$this->xObjectID]['content'] .= $string . "\n";
                return;
            }

            if (!$this->inFooter and isset($this->footerLengths[$this->page]) and ($pageLength = $this->footerLengths[$this->page]) > 0) {
                $pageBuffer = $this->pageBuffer($this->page);

                $page   = substr($pageBuffer, 0, -$pageLength);
                $footer = substr($pageBuffer, -$pageLength);

                $this->pageBuffer($this->page, $page . $string . "\n" . $footer);
                // update footer position
                $this->footerPositions[$this->page] += strlen($string . "\n");
                return;
            }

            $this->pageBuffer($this->page, $string . "\n", true);
            return;
        }

        if ($this->state > self::INITIAL) {
            $this->buffer($string . "\n");
        }
    }

    /**
     * Adds some metadata information (Document Information Dictionary)
     * (see Chapter 14.3.3 Document Information Dictionary of PDF32000_2008.pdf Reference)
     * @return string
     */
    protected function appendMetadata(): string
    {
        $objectID = $this->appendObject();
        $out      = '<<';

        if ($this->title) {
            $out .= ' /Title ' . $this->metadataTextString($this->title);
        }
        if ($this->author) {
            $out .= ' /Author ' . $this->metadataTextString($this->author);
        }
        if ($this->subject) {
            $out .= ' /Subject ' . $this->metadataTextString($this->subject);
        }
        if ($this->keywords) {
            $out .= ' /Keywords ' . $this->metadataTextString($this->keywords);
        }

        $out .= ' /Creator ' . $this->metadataTextString($this->creator);
        $out .= ' /Producer ' . $this->metadataTextString($this->producer());
        $out .= ' /CreationDate ' . $this->metadataDateString($this->creationTimestamp);
        $out .= ' /ModDate ' . $this->metadataDateString($this->modificationTimestamp);
        $out .= ' /Trapped /False';
        $out .= ' >>';
        $out .= "\n" . 'endobj';
        $this->append($out);
        return $objectID;
    }

    /**
     * Append named Destinations.
     * @author Johannes G\FCntert, Nicola Asuni
     */
    protected function appendNamedDestinations(): void
    {
        if (empty($this->namedDestinationsMap)) {
            return;
        }

        $this->namedDestinationID = $this->appendObject();

        $out = ' <<';

        foreach ($this->namedDestinationsMap as $name => $destination) {
            $page    = $destination['p'];
            $pageOID = $this->pageObjectIDs[$page];
            $x       = $destination['x'] * $this->factor;
            $y       = $this->pageDimensions[$page]['h'] - ($destination['y'] * $this->factor);
            $out     .= ' /' . $name . ' ' . sprintf('[%u 0 R /XYZ %F %F null]', $pageOID, $x, $y);
        }

        $out .= ' >>';
        $out .= "\n" . 'endobj';
        $this->append($out);
    }

    /**
     * Create a new object id, append it to the document and return it.
     * @return int
     */
    protected function appendObject(): int
    {
        $this->append($this->objectCommand());
        return $this->objectNumber;
    }

    /** Outputs the "restore graphics state" operator 'Q' */
    protected function appendRestoreGraphicsState(): void
    {
        $this->append('Q');
    }

    /** Outputs the "save graphics state" operator 'q' */
    protected function appendSaveGraphicsState(): void
    {
        $this->append('q');
    }

    /**
     * Defines the author of the document.
     * @param string $author
     */
    public function author(string $author): void
    {
        $this->author = $author;
    }

    /**
     * Calculates the widths of the given characters and returns the value as a sum or an array.
     * @param array  $characters
     * @param bool   $asArray
     * @param string $font
     * @param string $fontStyle
     * @param float  $fontSize Font size in points. The default value is the current size.
     * @return float[]|float
     */
    public function characterWidths(array $characters, bool $asArray = false, string $font = '', string $fontStyle = '', float $fontSize = 0): array|float
    {
        // Cache current values if calculations are to be made on a different font.
        if ($font) {
            $currentFont   = $this->fontFamily;
            $currentStyle  = $this->fontStyle;
            $currentSizePT = $this->fontSizePT;
            $this->font($font, $fontStyle, $fontSize, '', 'default', false);
        }

        $characters = $this->utf8ArrayToLatin1Array($characters);

        $sum    = 0;
        $widths = [];
        foreach ($characters as $ck => $char) {
            $width    = $this->GetCharWidth($char, isset($characters[($ck + 1)]));
            $widths[] = $width;
            $sum      += $width;
        }

        // Restore previous font
        if ($font) {
            $this->font($currentFont, $currentStyle, $currentSizePT, '', 'default', false);
        }

        return $asArray ? $widths : $sum;
    }

    /**
     * Getter / setter for the buffer
     * @param string $content
     * @return string
     */
    protected function buffer(string $content = ''): string
    {
        if ($content) {
            $this->buffer       .= $content;
            $this->bufferLength += strlen($content);
        }

        return $this->buffer;
    }

    /**
     * Encode a name for appending to the document
     * @param string $text
     * @return string
     */
    public function encodeText(string $text): string
    {
        $return = '';

        for ($index = 0; $index < strlen($text); ++$index) {
            $character = $text[$index];
            $return    .= preg_match('/[0-9a-zA-Z#_=-]/', $character) === 1 ? $character : sprintf('#%02X', ord($character));
        }

        return $return;
    }

    /** End the current PDF layer. */
    public function endLayer(): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        if ($this->layerOpened) {
            // close existing open marked-content layer
            $this->append('EMC');
            $this->layerOpened = false;
        }
    }

    /**
     * Ends a 2D transformation, restoring previous graphic state. This function must be called after mirroring, rotation, scaling, skewing and
     * translation transformations. Use startTransformation() before the transformations to save normal behavior before the transformation.
     * @see   startTransformation()
     */
    public function endTransformation(): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $this->appendRestoreGraphicsState();

        if (isset($this->matrix[$this->matrixKey])) {
            array_pop($this->matrix[$this->matrixKey]);
            --$this->matrixKey;
        }

        if ($this->inXObject) {
            array_pop($this->xObjects[$this->xObjectID]['transpositions']);
        }
        else {
            array_pop($this->transpositions[$this->page]);
        }
    }

    /**
     * Associates keywords with the document, generally in the form 'keyword1 keyword2 ...'.
     * @param array|string $keywords The list of keywords.
     */
    public function keywords(array|string $keywords): void
    {
        $this->keywords = is_array($keywords) ? implode(' ', $keywords) : $keywords;
    }

    /**
     * Creates a new internal link and returns its identifier.
     * @param int|string|null $pageNumber a string prefixed with '*' fill fix the link when adding/deleting/moving pages
     * @param float           $y          ordinate of target position, -1 indicates the current position
     * @return int
     * @see   cell(), write(), image(), annotateLink()
     */
    public function link(int|string|null $pageNumber = null, float $y = 0): int
    {
        $fixed  = false;
        $linkID = count($this->internalLinks) + 1;

        if ($pageNumber) {
            if (is_string($pageNumber)) {
                $offset = 0;
                if (str_starts_with($pageNumber, '*')) {
                    $fixed  = true;
                    $offset = 1;
                }
                $pageNumber = (int) substr($pageNumber, $offset);
            }

            $pageNumber = $pageNumber < 0 ? $this->page : $pageNumber;

            if ($y === -1.0) {
                $y = $this->y;
            }
        }
        else {
            $pageNumber = 0;
        }

        $this->internalLinks[$linkID] = ['p' => $pageNumber, 'y' => $y, 'f' => $fixed];
        return $linkID;
    }

    /**
     * Add a unique named destination and return its id. Destination names are unique, only the last entry will be saved.
     * @param string $name Destination name.
     * @param int    $page
     * @param float  $x
     * @param float  $y
     * @param bool   $fixed
     * @return string
     */
    public function namedDestination(string $name, int $page = 0, float $x = 0, float $y = 0, bool $fixed = false): string
    {
        if (!$name = $this->encodeText($name) or !$page = $page ?: $this->page) {
            return '';
        }

        if ($y < 0) {
            $y = 0;
        }
        elseif ($y > $this->height) {
            $y = $this->height;
        }

        if ($x < 0) {
            $x = 0;
        }
        elseif ($x > $this->width) {
            $x = $this->width;
        }

        $this->namedDestinationsMap[$name] = ['x' => $x, 'y' => $y, 'p' => $page, 'f' => $fixed];
        return $name;
    }

    /**
     * Retrieves the named destination array.
     * @return array
     */
    public function namedDestinations(): array
    {
        return $this->namedDestinationsMap;
    }

    /**
     * Creates an object command for the given id. An empty value iterates object id values before returning the command.
     * @param int|null $objectID Object ID (leave empty to get a new ID).
     * @return string the starting object string
     */
    protected function objectCommand(int $objectID = null): string
    {
        if (empty($objectID)) {
            ++$this->objectNumber;
            $objectID = $this->objectNumber;
        }

        $this->offsets[$objectID]         = $this->bufferLength;
        $this->pageObjects[$this->page][] = $objectID;
        return $objectID . ' 0 obj';
    }

    /**
     * Output data and compress it if possible.
     * @param string $output
     * @param int    $length (bytes)
     */
    public function outputData(string $output, int $length): void
    {
        if (empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            // the content length may vary if the server is using compression
            header('Content-Length: ' . $length);
        }
        echo $output;
    }

    /**
     * Gets the path painting operators.
     * @param string $style
     * @return string
     * @see PDF::RENDERING_STYLES
     */
    public function pathPaintingOperators(string $style): string
    {
        $style = in_array($style, PDF::RENDERING_STYLES) ? $style : PDF::STROKE;
        return match ($style) {
            PDF::STROKE_CLOSE, PDF::DRAW_CLOSE => PDF::STROKE_CLOSE,
            PDF::FILL => PDF::FILL,
            PDF::FILL_EO => PDF::FILL_EO,
            PDF::BOLD, PDF::DRAW_FILL, PDF::FILL_DRAW => PDF::BOLD,
            PDF::BOLD_EO, PDF::DRAW_FILL_EO, PDF::FILL_DRAW_EO => PDF::BOLD_EO,
            PDF::BOLD_CLOSE, PDF::DRAW_FILL_CLOSE, PDF::FILL_DRAW_CLOSE => PDF::BOLD_CLOSE,
            PDF::BOLD_CLOSE_EO, PDF::DRAW_FILL_CLOSE_EO, PDF::FILL_DRAW_CLOSE_EO => PDF::BOLD_CLOSE_EO,
            PDF::CLIP_NZ => 'W n',
            PDF::CLIP_EO => 'W* n',
            PDF::END_PATH => PDF::END_PATH,
            default => PDF::STROKE,
        };
    }

    /**
     * Returns "THM Organizer 5.0.0" in Unicode.
     * @return string
     */
    public function producer(): string
    {
        return "\x54\x48\x4D\x20\x4F\x72\x67\x61\x6E\x69\x7A\x65\x72\x20\x76\x35\x2E\x30\x2E\x30";
    }

    /**
     * Returns a string containing random data to be used as a seed for encryption methods.
     * @param string $seed starting seed value
     * @return string containing random data
     * @author Nicola Asuni
     */
    public function randomSeed(string $seed = ''): string
    {
        $rnd = uniqid(rand() . microtime(true), true);
        $rnd .= posix_getpid();
        $rnd .= openssl_random_pseudo_bytes(512);
        return $rnd . $seed . __FILE__ . serialize($_SERVER) . microtime(true);
    }

    /**
     * Defines the subject of the document.
     * @param string $subject The subject.
     */
    public function subject(string $subject): void
    {
        $this->subject = $subject;
    }

    /**
     * Getter / setter for the title of the document.
     * @param string|null $title
     * @return string
     */
    public function title(string|null $title = null): string
    {
        if ($title) {
            $this->title = $title;
        }

        return $this->title;
    }

    /**
     * Unset properties.
     * @param bool $all          if true destroys all class variables, otherwise preserves critical variables.
     * @param bool $preserveCopy if true preserves the clone property
     */
    public function unsetProperties(bool $all = false, bool $preserveCopy = false): void
    {
        if (isset(self::$unsetFileIDs[$this->fileID])) {
            $all = false;
        }

        if ($all and !$preserveCopy and isset($this->fileID)) {
            self::$unsetFileIDs[$this->fileID] = true;
            // remove all temporary files
            if ($handle = @opendir(self::CACHE_PATH)) {
                while (false !== ($file_name = readdir($handle))) {
                    if (str_starts_with($file_name, '__organizer_' . $this->fileID . '_')) {
                        unlink(self::CACHE_PATH . $file_name);
                    }
                }
                closedir($handle);
            }
            foreach ($this->imageKeys as $file) {
                if (str_starts_with($file, self::CACHE_PATH) && @file_exists($file)) {
                    @unlink($file);
                }
            }
        }

        $preserve = ['buffer', 'bufferLength', 'fileID', 'imageKeys', 'state'];

        foreach (array_keys(get_object_vars($this)) as $property) {
            if ($all or !in_array($property, $preserve)) {
                if ((!$preserveCopy or ($property != 'clone')) and ($property != 'fileID') and isset($this->$property)) {
                    unset($this->$property);
                }
            }
        }
    }

    /**
     * Defines the abscissa of the current position. If the passed value is negative, it is set relative to the right of the page.
     * @param float|null $x abscissa value in user units
     * @see   y(), reposition()
     */
    public function x(float|null $x = null): float
    {
        if (is_float($x)) {
            $this->x = $x >= 0 ? $x : $this->width + $x;
            $this->x = max($this->x, 0);
            $this->x = min($this->x, $this->width);
        }

        return $this->x;
    }

    /**
     * Defines the abscissa and ordinate of the current position.
     * If the passed values are negative, they are relative respectively to the right and bottom of the page.
     * @param float $x abscissa value in user units
     * @param float $y ordinate value in user units
     * @see   x(), y()
     */
    public function reposition(float $x, float $y): array
    {
        $this->y($y, false);
        $this->x($x);

        return [$this->x, $this->y];
    }

    /**
     * Start a new layer.
     * @param string    $name  layer name (alphanumeric), empty value creates a name from a template
     * @param bool|null $print print (true), don't print (false), unset (null)
     * @param bool      $view  whether to view the layer
     * @param bool      $lock  whether to lock the layer
     */
    public function startLayer(string $name = '', bool|null $print = true, bool $view = true, bool $lock = true): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $layer = sprintf('LYR%03d', (count($this->layers) + 1));
        $name  = $name ? preg_replace('/[^a-zA-Z0-9_\-]/', '', $name) : $layer;

        $this->layers[]    = ['layer' => $layer, 'name' => $name, 'print' => $print, 'view' => $view, 'lock' => $lock];
        $this->layerOpened = true;
        $this->append('/OC /' . $layer . ' BDC');
    }

    /**
     * Returns the length of a string in user unit. A font must be selected.<br>
     * @param string $string   The string whose length is to be computed
     * @param string $font
     * @param string $fontStyle
     * @param float  $fontSize Font size in points. The default value is the current size.
     * @return float total string length or array of characted widths
     */
    public function stringWidth(string $string, string $font = '', string $fontStyle = '', float $fontSize = 0): float
    {
        return $this->characterWidths($this->utf8StringToArray($string), false, $font, $fontStyle, $fontSize);
    }

    /**
     * Adds a layer for a media context. This can be used to put a background image or color that will show on screen but won't print.
     * @param string $visibility
     * @see self::VISIBILITIES, startLayer()
     */
    public function visibilityLayer(string $visibility = self::VISIBILITY_ALL): void
    {
        if ($this->state !== self::READY) {
            return;
        }

        $this->endLayer();
        switch ($visibility) {
            case self::VISIBILITY_PRINT:
                $this->startLayer('Print', true, false);
                break;
            case self::VISIBILITY_SCREEN:
            case self::VISIBILITY_VIEW:
                $this->startLayer('View', false, true);
                break;
            default:
                $this->append('');
                break;
        }
    }

    /**
     * Moves the current abscissa back to the left margin and sets the ordinate. If the passed value is negative, it is relative to the bottom of the page.
     * @param float|null $y      ordinate value in user units
     * @param bool       $resetX whether to reset the abscissa
     * @return float
     * @see   x(), reposition()
     */
    public function y(float|null $y = null, bool $resetX = true): float
    {
        if (is_float($y)) {
            $this->x = $resetX ? $this->leftMargin : $this->x;
            $this->y = $y >= 0 ? $y : $this->height + $y;
            $this->y = max($this->y, 0);
            $this->y = min($this->y, $this->height);
        }

        return $this->y;
    }

#endregion
#region /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


    /**
     * Add page if needed.
     * @param float      $h       Cell height. Default value: 0.
     * @param float|null $y       starting y position, leave empty for current position.
     * @param bool       $addpage if true add a page, otherwise only return the true/false state
     * @return bool true in case of page break, false otherwise.
     */
    protected function checkPageBreak($h = 0, $y = null, $addpage = true)
    {
        if (StaticMethods::empty_string($y)) {
            $y = $this->y;
        }
        $current_page = $this->page;
        if ((($y + $h) > $this->breakTrigger) and ($this->inPageBody()) and ($this->acceptPageBreak())) {
            if ($addpage) {
                //Automatic page break
                $x = $this->x;
                $this->addPage($this->currentOrientation);
                $this->y = $this->topMargin;
                $oldpage = $this->page - 1;
                if ($this->pageDimensions[$this->page]['olm'] != $this->pageDimensions[$oldpage]['olm']) {
                    $this->x = $x + ($this->pageDimensions[$this->page]['olm'] - $this->pageDimensions[$oldpage]['olm']);
                }
                else {
                    $this->x = $x;
                }
            }
            return true;
        }
        if ($current_page != $this->page) {
            // account for columns mode
            return true;
        }
        return false;
    }

    /**
     * Check page for no-write regions and adapt current coordinates and page margins if necessary.
     * A no-write region is a portion of the page with a rectangular or trapezium shape that will not be covered when writing text or html code.
     * A region is always aligned on the left or right side of the page ad is defined using a vertical segment.
     * @param float $height height of the text/image/object to print in user units
     * @param float $x      current X coordinate in user units
     * @param float $y      current Y coordinate in user units
     * @return float[] [$x, $y]
     * @author Nicola Asuni
     */
    protected function checkPageRegions(float $height, float $x, float $y)
    {
        if (!$this->checkRegions or empty($this->pageRegions)) {
            // no page regions defined
            return [$x, $y];
        }

        $height = $height ?: $this->cellHeight($this->fontSize);
        // check for page break
        if ($this->checkPageBreak($height, $y)) {
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
     * Print a Linear Barcode.
     * @param string     $code
     * @param string     $type
     * @param float|null $x        x position in user units (null = current x position)
     * @param float|null $y        y position in user units (null = current y position)
     * @param float|null $width    width in user units (null = remaining page width)
     * @param float|null $height   height in user units (null = remaining page height)
     * @param float|null $minWidth width of the smallest bar in user units (null = default value = 0.4mm)
     * @param array      $style
     * @param string     $align    Indicates the alignment of the pointer next to barcode insertion relative to barcode height. The value can be:<ul><li>T: top-right</li><li>M: middle-right</li><li>B: bottom-right</li><li>N: next line</li></ul>
     * @see Barcode1D::TYPES, self::BARCODE_STYLE_DESCRIPTIONS
     */
    public function write1DBarcode(
        string     $code,
        string     $type,
        float|null $x = null,
        float|null $y = null,
        float|null $width = null,
        float|null $height = null,
        float|null $minWidth = null,
        array      $style = [],
        string     $align = ''
    ): void
    {
        if (!$code = trim($code)) {
            return;
        }

        // Cache current graphic settings
        $settings = $this->graphicsSettings();

        // create new barcode object
        $bcObject = new Barcode1D($code, $type);
        if (!$bcArray = $bcObject->barcode() or $bcArray['maxWidth'] <= 0) {
            Application::message('Barcode width invalid', Application::ERROR);
            return;
        }

        if ($bcArray['maxHeight'] <= 0) {
            $bcArray['maxHeight'] = 1;
        }

        // set default values
        if (empty($style['position'])) {
            $style['position'] = '';
        }
        elseif ($style['position'] == self::STRETCH) {
            // keep this for backward compatibility
            $style['position'] = '';
            $style['stretch']  = true;
        }
        if (!isset($style['reduceWidth'])) {
            if (!isset($style['stretch'])) {
                $style['reduceWidth'] = true;
            }
            else {
                $style['reduceWidth'] = false;
            }
        }
        if ($style['reduceWidth']) {
            // disable stretch
            $style['stretch'] = false;
        }
        if (!isset($style['stretch'])) {
            if (is_null($width) or ($width <= 0)) {
                $style['stretch'] = false;
            }
            else {
                $style['stretch'] = true;
            }
        }
        $style['fgColor'] = $style['fgColor'] ?? self::BLACK;
        $style['bgColor'] = $style['bgColor'] ?? false; // default transparent
        $style['border']  = $style['border'] ?? false;
        $fontSize         = 0;
        $style['text']    = $style['text'] ?? false;
        if ($style['text'] and isset($style['font'])) {
            $fontSize = $style['fontSize'] ?? $fontSize;
            $this->font($style['font'], '', $fontSize);
        }
        $style['stretchText'] = $style['stretchText'] ?? self::C_SPACING_FORCED;
        $x                    = $x ?? $this->x;
        $y                    = $y ?? $this->y;

        // check page for no-write regions and adapt page margins if necessary
        [$x, $y] = $this->checkPageRegions($height, $x, $y);
        if (StaticMethods::empty_string($width) or ($width <= 0)) {
            $width = $this->width - $this->rightMargin - $x;
        }
        // padding
        if (!isset($style['padding'])) {
            $padding = 0;
        }
        elseif ($style['padding'] === 'auto') {
            $padding = 10 * ($width / ($bcArray['maxWidth'] + 20));
        }
        else {
            $padding = floatval($style['padding']);
        }
        // horizontal padding
        if (!isset($style['hPadding'])) {
            $hPadding = $padding;
        }
        elseif ($style['hPadding'] === 'auto') {
            $hPadding = 10 * ($width / ($bcArray['maxWidth'] + 20));
        }
        else {
            $hPadding = floatval($style['hPadding']);
        }
        // vertical padding
        if (!isset($style['vPadding'])) {
            $vPadding = $padding;
        }
        elseif ($style['vPadding'] === 'auto') {
            $vPadding = ($hPadding / 2);
        }
        else {
            $vPadding = floatval($style['vPadding']);
        }
        // calculate xres (single bar width)
        $max_xres = ($width - (2 * $hPadding)) / $bcArray['maxWidth'];
        if ($style['stretch']) {
            $minWidth = $max_xres;
        }
        else {
            if (StaticMethods::empty_string($minWidth)) {
                $minWidth = (0.141 * $this->factor); // default bar width = 0.4 mm
            }
            if ($minWidth > $max_xres) {
                // correct xres to fit on $w
                $minWidth = $max_xres;
            }
            if ((isset($style['padding']) and ($style['padding'] === 'auto'))
                or (isset($style['hPadding']) and ($style['hPadding'] === 'auto'))) {
                $hPadding = 10 * $minWidth;
                if (isset($style['vPadding']) and ($style['vPadding'] === 'auto')) {
                    $vPadding = ($hPadding / 2);
                }
            }
        }
        if ($style['reduceWidth']) {
            $wold  = $width;
            $width = (($bcArray['maxWidth'] * $minWidth) + (2 * $hPadding));
            if (isset($style['hAlign'])) {
                switch ($style['hAlign']) {
                    case 'R':
                    {
                        $x += ($wold - $width);
                        break;
                    }
                    case 'C':
                    {
                        $x += (($wold - $width) / 2);
                        break;
                    }
                    default :
                    {
                        break;
                    }
                }
            }
        }
        $text_height = $this->cellHeight($fontSize / $this->factor);
        // height
        if (StaticMethods::empty_string($height) or ($height <= 0)) {
            // set default height
            $height = (($bcArray['maxWidth'] * $minWidth) / 3) + (2 * $vPadding) + $text_height;
        }
        $barh = $height - $text_height - (2 * $vPadding);
        if ($barh <= 0) {
            // try to reduce font or padding to fit barcode on available height
            if ($text_height > $height) {
                $fontSize    = (($height * $this->factor) / (4 * $this->cellHeightRatio));
                $text_height = $this->cellHeight($fontSize / $this->factor);
                $this->font($style['font'], '', $fontSize);
            }
            if ($vPadding > 0) {
                $vPadding = (($height - $text_height) / 4);
            }
            $barh = $height - $text_height - (2 * $vPadding);
        }
        // fit the barcode on available space
        list($width, $height, $x, $y) = $this->fitBlock($width, $height, $x, $y, false);
        // set alignment
        $this->imageRBY = $y + $height;
        // set alignment
        if ($style['position'] == 'L') {
            $xpos = $this->leftMargin;
        }
        elseif ($style['position'] == 'C') {
            $xpos = ($this->width + $this->leftMargin - $this->rightMargin - $width) / 2;
        }
        elseif ($style['position'] == 'R') {
            $xpos = $this->width - $this->rightMargin - $width;
        }
        else {
            $xpos = $x;
        }
        $this->imageRBX = $xpos + $width;
        $xpos_rect      = $xpos;
        if (!isset($style['align'])) {
            $style['align'] = 'C';
        }
        switch ($style['align']) {
            case 'L':
            {
                $xpos = $xpos_rect + $hPadding;
                break;
            }
            case 'R':
            {
                $xpos = $xpos_rect + ($width - ($bcArray['maxWidth'] * $minWidth)) - $hPadding;
                break;
            }
            case 'C':
            default :
            {
                $xpos = $xpos_rect + (($width - ($bcArray['maxWidth'] * $minWidth)) / 2);
                break;
            }
        }
        $xpos_text = $xpos;
        // print background color
        if ($style['bgColor']) {
            $this->rectangle($xpos_rect, $y, $width, $height, $style['border'] ? 'DF' : 'F', '', $style['bgColor']);
        }
        elseif ($style['border']) {
            $this->rectangle($xpos_rect, $y, $width, $height, 'D');
        }
        // set foreground color
        $this->drawColorArray($style['fgColor']);
        $this->textColorArray($style['fgColor']);
        // print bars
        foreach ($bcArray['bcode'] as $k => $v) {
            $bw = ($v['w'] * $minWidth);
            if ($v['t']) {
                // draw a vertical bar
                $ypos = $y + $vPadding + ($v['p'] * $barh / $bcArray['maxHeight']);
                $this->rectangle($xpos, $ypos, $bw, ($v['h'] * $barh / $bcArray['maxHeight']), 'F', [], $style['fgColor']);
            }
            $xpos += $bw;
        }
        // print text
        if ($style['text']) {
            if (isset($style['label']) and !StaticMethods::empty_string($style['label'])) {
                $label = $style['label'];
            }
            else {
                $label = $code;
            }
            $txtwidth = ($bcArray['maxWidth'] * $minWidth);
            if ($this->stringWidth($label) > $txtwidth) {
                $style['stretchText'] = 2;
            }
            // print text
            $this->x     = $xpos_text;
            $this->y     = $y + $vPadding + $barh;
            $cellpadding = $this->cellPaddings;
            $this->cellPadding(0);
            $this->cell($txtwidth, 0, $label, 0, 0, 'C', false, '', $style['stretchText'], false, 'T', 'T');
            $this->cellPaddings = $cellpadding;
        }
        // restore previous settings
        $this->graphicsSettings($settings);
        // set pointer to align the next text/objects
        switch ($align) {
            case 'T':
            {
                $this->y = $y;
                $this->x = $this->imageRBX;
                break;
            }
            case 'M':
            {
                $this->y = $y + round($height / 2);
                $this->x = $this->imageRBX;
                break;
            }
            case 'B':
            {
                $this->y = $this->imageRBY;
                $this->x = $this->imageRBX;
                break;
            }
            case 'N':
            {
                $this->y($this->imageRBY);
                break;
            }
            default:
            {
                break;
            }
        }
        $this->endlinex = $this->imageRBX;
    }

    /**
     * Terminates the PDF document.
     * It is not necessary to call this method explicitly because Output() does it automatically.
     * If the document contains no page, AddPage() is called to prevent from getting an invalid document.
     * @see   Open(), Output()
     */
    public function close()
    {
        if ($this->state === self::FINAL) {
            return;
        }

        if ($this->page === 0) {
            $this->addPage();
        }

        $this->endLayer();

        // close page
        $this->closePage();
        // close document
        $this->_enddoc();
        // unset all class variables (except critical ones)
        $this->unsetProperties(false);
    }

    /**
     * Batch getter / setter for graphics configuration properties. Setting is only enabled when the document is in a READY state.
     * @param array $parameters array of graphic variables to set
     * @param bool  $extended   whether to set extended graphic variables
     */
    protected function graphicsSettings(array $parameters = [], bool $extended = false): array
    {
        if ($this->state === self::READY and $parameters) {
            $this->alpha           = $parameters['alpha'];
            $this->bgColor         = $parameters['bgColor'];
            $this->cellHeightRatio = $parameters['cellHeightRatio'];
            $this->cellMargins     = $parameters['cellMargins'];
            $this->cellPaddings    = $parameters['cellPaddings'];
            $this->differentColors = $parameters['differentColors'];
            $this->drawColor       = $parameters['drawColor'];
            $this->fgColor         = $parameters['fgColor'];
            $this->fillColor       = $parameters['fillColor'];
            $this->fontFamily      = $parameters['fontFamily'];
            $this->fontSizePT      = $parameters['fontSizePT'];
            $this->fontSpacing     = $parameters['fontSpacing'];
            $this->fontStretching  = $parameters['fontStretching'];
            $this->fontStyle       = $parameters['fontStyle'];
            $this->htmlvspace      = $parameters['htmlvspace'];
            $this->leftMargin      = $parameters['lMargin'];
            $this->lineCapStyle    = $parameters['lineCapStyle'];
            $this->lineDashStyle   = $parameters['lineDashStyle'];
            $this->lineJoinStyle   = $parameters['lineJoinStyle'];
            $this->lineWidth       = $parameters['lineWidth'];
            $this->lineWidthStyle  = $parameters['lineWidthStyle'];
            $this->lispacer        = $parameters['lispacer'];
            $this->listcount       = $parameters['listcount'];
            $this->listindent      = $parameters['listindent'];
            $this->listindentlevel = $parameters['listindentlevel'];
            $this->listnum         = $parameters['listnum'];
            $this->listordered     = $parameters['listordered'];
            $this->rightMargin     = $parameters['rMargin'];
            $this->textColor       = $parameters['TextColor'];
            $this->textrendermode  = $parameters['textrendermode'];
            $this->textstrokewidth = $parameters['textstrokewidth'];

            // Restore extended values
            if ($extended) {
                $this->autoPageBreak         = $parameters['autoPageBreak'];
                $this->bottomMargin          = $parameters['bMargin'];
                $this->columnCount           = $parameters['columnCount'];
                $this->currentColumn         = $parameters['currentColumn'];
                $this->formatHeightPT        = $parameters['fhPt'];
                $this->formatWidthPT         = $parameters['fwPt'];
                $this->height                = $parameters['h'];
                $this->heightPT              = $parameters['hPt'];
                $this->lastPrintedCellHeight = $parameters['lasth'];
                $this->page                  = $parameters['page'];
                $this->breakTrigger          = $parameters['PageBreakTrigger'];
                $this->topMargin             = $parameters['tMargin'];
                $this->width                 = $parameters['w'];
                $this->widthPT               = $parameters['wPt'];
                $this->x                     = $parameters['x'];
                $this->y                     = $parameters['y'];
            }
            $this->append('' . $this->lineWidthStyle . ' ' . $this->lineCapStyle . ' ' . $this->lineJoinStyle . ' ' . $this->lineDashStyle . ' ' . $this->drawColor . ' ' . $this->fillColor . '');
            if ($this->fontFamily) {
                $this->font($this->fontFamily, $this->fontStyle, $this->fontSizePT);
            }
        }

        return [
            'alpha'            => $this->alpha,
            'bgColor'          => $this->bgColor,
            'cellHeightRatio'  => $this->cellHeightRatio,
            'cellMargins'      => $this->cellMargins,
            'cellPaddings'     => $this->cellPaddings,
            'differentColors'  => $this->differentColors,
            'drawColor'        => $this->drawColor,
            'fgColor'          => $this->fgColor,
            'fillColor'        => $this->fillColor,
            'fontFamily'       => $this->fontFamily,
            'fontSizePT'       => $this->fontSizePT,
            'fontSpacing'      => $this->fontSpacing,
            'fontStretching'   => $this->fontStretching,
            'fontStyle'        => $this->fontStyle,
            'htmlvspace'       => $this->htmlvspace,
            'lMargin'          => $this->leftMargin,
            'lineCapStyle'     => $this->lineCapStyle,
            'lineDashStyle'    => $this->lineDashStyle,
            'lineJoinStyle'    => $this->lineJoinStyle,
            'lineWidth'        => $this->lineWidth,
            'lineWidthStyle'   => $this->lineWidthStyle,
            'lispacer'         => $this->lispacer,
            'listcount'        => $this->listcount,
            'listindent'       => $this->listindent,
            'listindentlevel'  => $this->listindentlevel,
            'listnum'          => $this->listnum,
            'listordered'      => $this->listordered,
            'rMargin'          => $this->rightMargin,
            'TextColor'        => $this->textColor,
            'textrendermode'   => $this->textrendermode,
            'textstrokewidth'  => $this->textstrokewidth,

            // Extended
            'autoPageBreak'    => $this->autoPageBreak,
            'bMargin'          => $this->bottomMargin,
            'columnCount'      => $this->columnCount,
            'currentColumn'    => $this->currentColumn,
            'fhPt'             => $this->formatHeightPT,
            'fwPt'             => $this->formatWidthPT,
            'h'                => $this->height,
            'hPt'              => $this->heightPT,
            'lasth'            => $this->lastPrintedCellHeight,
            'page'             => $this->page,
            'PageBreakTrigger' => $this->breakTrigger,
            'tMargin'          => $this->topMargin,
            'w'                => $this->width,
            'wPt'              => $this->widthPT,
            'x'                => $this->x,
            'y'                => $this->y,
        ];
    }


    /**
     * Returns a temporary filename for caching object on filesystem.
     * @param string $type   Type of file (name of the subdir on the tcpdf cache folder).
     * @param string $fileID internal file id
     * @return string filename.
     */
    public function getObjFilename($type = 'tmp', $fileID = '')
    {
        return tempnam(PDF::CACHE_PATH, '__organizer_' . $fileID . '_' . $type . '_' . md5($this->randomSeed()) . '_');
    }

#endregion
#region ///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * True when we are printing the thead section on a new page.
     */
    protected bool $inthead = false;

    /**
     * This method is used to render the table header on new page (if any).
     */
    protected function setTableHeader()
    {
        if ($this->columnCount > 1) {
            // multi column mode
            return;
        }
        if (isset($this->headerTemplateMargins['top'])) {
            // restore the original top-margin
            $this->topMargin                         = $this->headerTemplateMargins['top'];
            $this->pageDimensions[$this->page]['tm'] = $this->topMargin;
            $this->y                                 = $this->topMargin;
        }
        if ($this->headerTemplate and (!$this->inthead)) {
            // set margins
            $prev_lMargin       = $this->leftMargin;
            $prev_rMargin       = $this->rightMargin;
            $previousPaddings   = $this->cellPaddings;
            $this->leftMargin   = $this->headerTemplateMargins['lmargin'] + ($this->pageDimensions[$this->page]['olm'] - $this->pageDimensions[$this->headerTemplateMargins['page']]['olm']);
            $this->rightMargin  = $this->headerTemplateMargins['rmargin'] + ($this->pageDimensions[$this->page]['orm'] - $this->pageDimensions[$this->headerTemplateMargins['page']]['orm']);
            $this->cellPaddings = $this->headerTemplateMargins['cellPaddings'];
            $this->x            = $this->leftMargin;
            // account for special "cell" mode
            if ($this->headerTemplateMargins['cell']) {
                $this->x += $this->cellPaddings['L'];
            }
            $gvars = $this->graphicsSettings();
            if (!empty($this->headerTemplateMargins['gvars'])) {
                // set the correct graphic style
                $this->graphicsSettings($this->headerTemplateMargins['gvars']);
                $this->rightMargin = $gvars['rMargin'];
                $this->leftMargin  = $gvars['lMargin'];
            }
            // print table header
            $this->writeHTML($this->headerTemplate, false, false, false, false, '');
            $this->graphicsSettings($gvars);
            // set new top margin to skip the table headers
            if (!isset($this->headerTemplateMargins['top'])) {
                $this->headerTemplateMargins['top'] = $this->topMargin;
            }
            // store end of header position
            if (!isset($this->columns[0]['th'])) {
                $this->columns[0]['th'] = [];
            }
            $this->columns[0]['th']['\'' . $this->page . '\''] = $this->y;
            $this->topMargin                                   = $this->y;
            $this->pageDimensions[$this->page]['tm']           = $this->topMargin;
            $this->lastPrintedCellHeight                       = 0;
            $this->leftMargin                                  = $prev_lMargin;
            $this->rightMargin                                 = $prev_rMargin;
            $this->cellPaddings                                = $previousPaddings;
        }
    }

    /**
     * Array of object offsets.
     */
    protected $offsets = [];

    /**
     * Array of used images.
     */
    protected $images = [];

    /**
     * If true set the document information dictionary in Unicode.
     */
    protected $docinfounicode = true;

    /**
     * Text shadow data array.
     */
    protected $txtshadow = ['enabled' => false, 'depth_w' => 0, 'depth_h' => 0, 'color' => false, 'opacity' => 1, 'blend_mode' => 'Normal'];

    /**
     * Default number of columns for html table.
     */
    protected $default_table_columns = 4;

    /**
     * Spacer string for LI tags.
     */
    protected $lispacer = '';

    // --- bookmark ---

    /**
     * Outlines for bookmark.
     */
    protected $outlines = [];

    /**
     * Outline root for bookmark.
     */
    protected $OutlineRoot;

    // --- javascript and form ---

    /**
     * Array of page numbers were a new page group was started (the page numbers are the keys of the array).
     */
    protected $newpagegroup = [];

    /**
     * Array of transparency objects and parameters.
     */
    protected array $extgstates = [];

    /**
     * PDF viewer preferences.
     */
    protected $viewer_preferences;

    /**
     * Array used to store positions inside the pages buffer (keys are the page numbers).
     */
    protected $intmrk = [];

    /**
     * Array used to store positions inside the pages buffer (keys are the page numbers).
     */
    protected $bordermrk = [];

    /**
     * Array used to store content positions inside the pages buffer (keys are the page numbers).
     */
    protected $cntmrk = [];

    /**
     * End position of the latest inserted line.
     */
    protected $endlinex = 0;


    /**
     * Count the latest inserted vertical spaces on HTML.
     */
    protected $htmlvspace = 0;

    /**
     * Symbol used for HTML unordered list items.
     */
    protected $lisymbol = '';

    /**
     * String used to mark the beginning and end of EPS image blocks.
     */
    protected $epsmarker = 'x#!#EPS#!#x';

    /**
     * Booklet mode for double-sided pages.
     */
    protected $booklet = false;

    /**
     * Array used for custom vertical spaces for HTML tags.
     */
    protected $tagvspaces = [];

    /**
     * bool flag to indicate if we are inside a PRE tag.
     */
    protected $premode = false;

    /**
     * Array used to store the lengths of cache files.
     */
    protected $cache_file_length = [];

    /**
     * Digital signature object ID.
     */
    protected $sig_obj_id = 0;

    /**
     * Current method to submit forms.
     */
    protected $form_mode = 'post';

    /**
     * Store page number when startTransaction() is called.
     */
    protected $start_transaction_page = 0;

    /**
     * Store Y position when startTransaction() is called.
     */
    protected $start_transaction_y = 0;

    /**
     * Embedded Files Names
     */
    protected $efnames = [];

    /**
     * Custom XMP data.
     */
    protected string $custom_xmp = '';

    /**
     * Custom XMP RDF data.
     */
    protected string $custom_xmp_rdf = '';

    /**
     * Overprint mode array.
     * (Check the "Entries in a Graphics State Parameter Dictionary" on PDF 32000-1:2008).
     * @var array<string,bool|int>
     */
    protected $overprint = ['OP' => false, 'op' => false, 'OPM' => 0];

    /**
     * Alpha mode array.
     * (Check the "Entries in a Graphics State Parameter Dictionary" on PDF 32000-1:2008).
     */
    protected $alpha = ['CA' => 1, 'ca' => 1, 'BM' => '/Normal', 'AIS' => false];

    /**
     * Define the page boundaries boxes to be set on document.
     */
    protected $page_boxes = ['MediaBox', 'CropBox', 'BleedBox', 'TrimBox', 'ArtBox'];

    /**
     * Cache array for computed GD gamma values.
     */
    protected $gdgammacache = [];

    /**
     * Cache array for file content
     * @var array
     */
    protected $fileContentCache = [];

    //------------------------------------------------------------
    // METHODS
    //------------------------------------------------------------

    /**
     * Throw an exception or print an error message and die if the K_TCPDF_PARSER_THROW_EXCEPTION_ERROR constant is set to true.
     * @param string $msg The error message
     */
    public function Error($msg)
    {
        // unset all class variables
        $this->unsetProperties(true);
        die('<strong>PDF Library Error: </strong>' . $msg);
        // TODO comment in for production
        /*if (JDEBUG) {
            Application::error(500, $msg);
        }*/
    }

    /**
     * Returns the page break margin.
     * @param int|null $pagenum page number (empty = current page)
     * @return int|float page break margin.
     * @author Nicola Asuni
     * @see    getPageDimensions()
     */
    public function getBreakMargin($pagenum = null)
    {
        if (empty($pagenum)) {
            return $this->bottomMargin;
        }
        return $this->pageDimensions[$pagenum]['bm'];
    }

    /**
     * Get the last cell height.
     * @return float last cell height
     */
    public function getLastH()
    {
        return $this->lastPrintedCellHeight;
    }

    /**
     * This method begins the generation of the PDF document.
     * It is not necessary to call it explicitly because AddPage() does it automatically.
     * Note: no page is created by this method
     * @see   addPage(), close()
     */
    public function Open()
    {
        $this->state = self::NEUTRAL;
    }

    /**
     * Reset the last cell height.
     */
    public function resetLastH()
    {
        $this->lastPrintedCellHeight = $this->cellHeight($this->fontSize);
    }

    /**
     * Set the last cell height.
     * @param float $h cell height.
     * @author Nicola Asuni
     */
    public function setLastH($h)
    {
        $this->lastPrintedCellHeight = $h;
    }

    /**
     * Defines the left margin. The method can be called before creating the first page. If the current abscissa gets out of page, it is brought back to the margin.
     * @param int|float $margin The margin.
     * @see   SetTopMargin(), SetRightMargin(), autoPageBreak(), SetMargins()
     */
    public function setLeftMargin($margin)
    {
        //Set left margin
        $this->leftMargin = $margin;
        if (($this->page > 0) and ($this->x < $margin)) {
            $this->x = $margin;
        }
    }

    /**
     * Defines the left, top and right margins.
     * @param int|float      $left        Left margin.
     * @param int|float      $top         Top margin.
     * @param int|float|null $right       Right margin. Default value is the left one.
     * @param bool           $keepmargins if true overwrites the default page margins
     * @see   SetLeftMargin(), SetTopMargin(), SetRightMargin(), autoPageBreak()
     */
    public function setMargins($left, $top, $right = null, $keepmargins = false)
    {
        //Set left, top and right margins
        $this->leftMargin = $left;
        $this->topMargin  = $top;
        if ($right == -1 or $right === null) {
            $right = $left;
        }
        $this->rightMargin = $right;
        if ($keepmargins) {
            // overwrite original values
            $this->originalLeftMargin  = $this->leftMargin;
            $this->originalRightMargin = $this->rightMargin;
        }
    }

    /**
     * Defines the right margin. The method can be called before creating the first page.
     * @param int|float $margin The margin.
     * @see   SetLeftMargin(), SetTopMargin(), autoPageBreak(), SetMargins()
     */
    public function setRightMargin($margin)
    {
        $this->rightMargin = $margin;
        if (($this->page > 0) and ($this->x > ($this->width - $margin))) {
            $this->x = $this->width - $margin;
        }
    }

    /**
     * Defines the top margin. The method can be called before creating the first page.
     * @param int|float $margin The margin.
     * @see   SetLeftMargin(), SetRightMargin(), autoPageBreak(), SetMargins()
     */
    public function setTopMargin($margin)
    {
        //Set top margin
        $this->topMargin = $margin;
        if (($this->page > 0) and ($this->y < $margin)) {
            $this->y = $margin;
        }
    }

    /**
     * Set start-writing mark on selected page.
     * Borders and fills are always created after content and inserted on the position marked by this method.
     * @param int $page page number (default is the current page)
     */
    protected function setContentMark($page = 0)
    {
        if ($page <= 0) {
            $page = $this->page;
        }
        if (isset($this->footerLengths[$page])) {
            $this->cntmrk[$page] = $this->pageLengths[$page] - $this->footerLengths[$page];
        }
        else {
            $this->cntmrk[$page] = $this->pageLengths[$page];
        }
    }

    /**
     * Returns the length of the char in user unit for the current font considering current stretching and spacing (tracking).
     * @param int  $char    The char code whose length is to be returned
     * @param bool $notlast If false ignore the font-spacing.
     * @return float char width
     * @author Nicola Asuni
     */
    public function GetCharWidth($char, $notlast = true)
    {
        // get raw width
        $chw = $this->getRawCharWidth($char);
        if (($this->fontSpacing < 0) or (($this->fontSpacing > 0) and $notlast)) {
            // increase/decrease font spacing
            $chw += $this->fontSpacing;
        }
        if ($this->fontStretching != 100) {
            // fixed stretching mode
            $chw *= ($this->fontStretching / 100);
        }
        return $chw;
    }

    /**
     * Returns the length of the char in user unit for the current font.
     * @param int $char The char code whose length is to be returned
     * @return float char width
     * @author Nicola Asuni
     */
    public function getRawCharWidth($char)
    {
        if ($char == 173) {
            // SHY character will not be printed
            return (0);
        }
        if (isset($this->currentFont['cw'][intval($char)])) {
            $w = $this->currentFont['cw'][intval($char)];
        }
        elseif (isset($this->currentFont['dw'])) {
            // default width
            $w = $this->currentFont['dw'];
        }
        elseif (isset($this->currentFont['cw'][32])) {
            // default width
            $w = $this->currentFont['cw'][32];
        }
        else {
            $w = 600;
        }
        return $this->getAbsFontMeasure($w);
    }

    /**
     * Returns the numbero of characters in a string.
     * @param string $s The input string.
     * @return int number of characters
     */
    public function GetNumChars($s)
    {
        return strlen($s);
    }

    /**
     * Puts a link on a rectangular area of the page.
     * Text or image links are generally put via Cell(), Write() or Image(), but this method can be useful for instance to define a clickable area inside an image.
     * @param float $x      Abscissa of the upper-left corner of the rectangle
     * @param float $y      Ordinate of the upper-left corner of the rectangle
     * @param float $w      Width of the rectangle
     * @param float $h      Height of the rectangle
     * @param mixed $link   URL or identifier returned by AddLink()
     * @param int   $spaces number of spaces on the text to link
     * @see   link(), Annotation(), cell(), write(), image()
     */
    public function annotateLink($x, $y, $w, $h, $link, $spaces = 0)
    {
        $this->Annotation($x, $y, $w, $h, $link, ['Subtype' => 'Link'], $spaces);
    }

    /**
     * Puts a markup annotation on a rectangular area of the page.
     * !!!!THE ANNOTATION SUPPORT IS NOT YET FULLY IMPLEMENTED !!!!
     * @param float  $x      Abscissa of the upper-left corner of the rectangle
     * @param float  $y      Ordinate of the upper-left corner of the rectangle
     * @param float  $w      Width of the rectangle
     * @param float  $h      Height of the rectangle
     * @param string $text   annotation text or alternate content
     * @param array  $opt    array of options (see section 8.4 of PDF reference 1.7).
     * @param int    $spaces number of spaces on the text to link
     */
    public function Annotation($x, $y, $w, $h, $text, $opt = ['Subtype' => 'Text'], $spaces = 0)
    {
        if ($this->inXObject) {
            // store parameters for later use on template
            $this->xObjects[$this->xObjectID]['annotations'][] = ['x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'text' => $text, 'opt' => $opt, 'spaces' => $spaces];
            return;
        }
        if ($x === '') {
            $x = $this->x;
        }
        if ($y === '') {
            $y = $this->y;
        }
        // check page for no-write regions and adapt page margins if necessary
        list($x, $y) = $this->checkPageRegions($h, $x, $y);
        // recalculate coordinates to account for graphic transformations
        if (isset($this->matrix) and !empty($this->matrix)) {
            for ($i = $this->matrixKey; $i > 0; --$i) {
                $maxid = count($this->matrix[$i]) - 1;
                for ($j = $maxid; $j >= 0; --$j) {
                    $ctm = $this->matrix[$i][$j];
                    if (isset($ctm['a'])) {
                        $x = $x * $this->factor;
                        $y = ($this->height - $y) * $this->factor;
                        $w = $w * $this->factor;
                        $h = $h * $this->factor;
                        // top left
                        $xt = $x;
                        $yt = $y;
                        $x1 = ($ctm['a'] * $xt) + ($ctm['c'] * $yt) + $ctm['e'];
                        $y1 = ($ctm['b'] * $xt) + ($ctm['d'] * $yt) + $ctm['f'];
                        // top right
                        $xt = $x + $w;
                        $yt = $y;
                        $x2 = ($ctm['a'] * $xt) + ($ctm['c'] * $yt) + $ctm['e'];
                        $y2 = ($ctm['b'] * $xt) + ($ctm['d'] * $yt) + $ctm['f'];
                        // bottom left
                        $xt = $x;
                        $yt = $y - $h;
                        $x3 = ($ctm['a'] * $xt) + ($ctm['c'] * $yt) + $ctm['e'];
                        $y3 = ($ctm['b'] * $xt) + ($ctm['d'] * $yt) + $ctm['f'];
                        // bottom right
                        $xt = $x + $w;
                        $yt = $y - $h;
                        $x4 = ($ctm['a'] * $xt) + ($ctm['c'] * $yt) + $ctm['e'];
                        $y4 = ($ctm['b'] * $xt) + ($ctm['d'] * $yt) + $ctm['f'];
                        // new coordinates (rectangle area)
                        $x = min($x1, $x2, $x3, $x4);
                        $y = max($y1, $y2, $y3, $y4);
                        $w = (max($x1, $x2, $x3, $x4) - $x) / $this->factor;
                        $h = ($y - min($y1, $y2, $y3, $y4)) / $this->factor;
                        $x = $x / $this->factor;
                        $y = $this->height - ($y / $this->factor);
                    }
                }
            }
        }
        if ($this->page <= 0) {
            $page = 1;
        }
        else {
            $page = $this->page;
        }
        if (!isset($this->pageAnnotations[$page])) {
            $this->pageAnnotations[$page] = [];
        }
        $this->pageAnnotations[$page][] = ['n' => ++$this->objectNumber, 'x' => $x, 'y' => $y, 'w' => $w, 'h' => $h, 'txt' => $text, 'opt' => $opt, 'numspaces' => $spaces];
        if ((($opt['Subtype'] == 'FileAttachment') or ($opt['Subtype'] == 'Sound')) and (!StaticMethods::empty_string($opt['FS']))
            and (@file_exists($opt['FS']) or $this->validURL($opt['FS']))
            and (!isset($this->embeddedfiles[basename($opt['FS'])]))) {
            $this->embeddedfiles[basename($opt['FS'])] = ['f' => ++$this->objectNumber, 'n' => ++$this->objectNumber, 'file' => $opt['FS']];
        }
        // Add widgets annotation's icons
        if (isset($opt['mk']['i']) and @file_exists($opt['mk']['i'])) {
            $this->image($opt['mk']['i'], null, null, 10, 10, '', '', '', false, 300, '', false, false, 0, false, true);
        }
        if (isset($opt['mk']['ri']) and @file_exists($opt['mk']['ri'])) {
            $this->image($opt['mk']['ri'], null, null, 0, 0, '', '', '', false, 300, '', false, false, 0, false, true);
        }
        if (isset($opt['mk']['ix']) and @file_exists($opt['mk']['ix'])) {
            $this->image($opt['mk']['ix'], null, null, 0, 0, '', '', '', false, 300, '', false, false, 0, false, true);
        }
    }

    /**
     * Embedd the attached files.
     * @see   Annotation()
     */
    protected function _putEmbeddedFiles()
    {
        reset($this->embeddedfiles);
        foreach ($this->embeddedfiles as $filename => $filedata) {
            $data = $this->getCachedFileContents($filedata['file']);
            if ($data !== false) {
                $rawsize = strlen($data);
                if ($rawsize > 0) {
                    // update name tree
                    $this->efnames[$filename] = $filedata['f'] . ' 0 R';
                    // embedded file specification object
                    $out = $this->objectCommand($filedata['f']) . "\n";
                    $out .= '<</Type /Filespec /F ' . $this->metadataDataString($filename);
                    $out .= ' /UF ' . $this->metadataDataString($filename);
                    $out .= ' /AFRelationship /Source';
                    $out .= ' /EF <</F ' . $filedata['n'] . ' 0 R>> >>';
                    $out .= "\n" . 'endobj';
                    $this->append($out);
                    // embedded file object
                    $filter = '';
                    $data   = gzcompress($data);
                    $filter = ' /Filter /FlateDecode';
                    $stream = $data;
                    $out    = $this->objectCommand($filedata['n']) . "\n";
                    $out    .= '<< /Type /EmbeddedFile' . $filter . ' /Length ' . strlen($stream) . ' /Params <</Size ' . $rawsize . '>> >>';
                    $out    .= ' stream' . "\n" . $stream . "\n" . 'endstream';
                    $out    .= "\n" . 'endobj';
                    $this->append($out);
                }
            }
        }
    }

    /**
     * Prints a text cell at the specified position.
     * This method allows to place a string precisely on the page.
     * @param float  $x                 Abscissa of the cell origin
     * @param float  $y                 Ordinate of the cell origin
     * @param string $txt               String to print
     * @param int    $fstroke           outline size in user units (0 = disable)
     * @param bool   $fclip             if true activate clipping mode (you must call StartTransform() before this function and StopTransform() to stop the clipping tranformation).
     * @param bool   $ffill             if true fills the text
     * @param mixed  $border            Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK])
     * @param int    $ln                Indicates where the current position should go after the call. Possible values are:<ul><li>0: to the right</li><li>1: to the beginning of the next line</li><li>2: below</li></ul>Putting 1 is equivalent to putting 0 and calling Ln() just after. Default value: 0.
     * @param string $align             Allows to center or align the text. Possible values are:<ul><li>L or empty string: left align (default value)</li><li>C: center</li><li>R: right align</li><li>J: justify</li></ul>
     * @param bool   $fill              Indicates if the cell background must be painted (true) or transparent (false).
     * @param mixed  $link              URL or identifier returned by AddLink().
     * @param int    $stretch
     * @param bool   $ignore_min_height if true ignore automatic minimum height value.
     * @param string $calign            cell vertical alignment relative to the specified Y value. Possible values are:<ul><li>T : cell top</li><li>A : font top</li><li>L : font baseline</li><li>D : font bottom</li><li>B : cell bottom</li></ul>
     * @param string $valign            text vertical alignment inside the cell. Possible values are:<ul><li>T : top</li><li>C : center</li><li>B : bottom</li></ul>
     * @see   cell(), write(), multiCell(), WriteHTML(), WriteHTMLCell(), self::STRETCH_MODES
     */
    public function Text($x, $y, $txt, $fstroke = 0, $fclip = false, $ffill = true, $border = 0, $ln = 0, $align = '', $fill = false, $link = '', int $stretch = self::STRETCH_DISABLED, $ignore_min_height = false, $calign = 'T', $valign = 'M')
    {
        $textrendermode  = $this->textrendermode;
        $textstrokewidth = $this->textstrokewidth;
        $this->setTextRenderingMode($fstroke, $ffill, $fclip);
        $this->reposition($x, $y);
        $this->cell(0, 0, $txt, $border, $ln, $align, $fill, $link, $stretch, $ignore_min_height, $calign, $valign);
        // restore previous rendering mode
        $this->textrendermode  = $textrendermode;
        $this->textstrokewidth = $textstrokewidth;
    }

    /**
     * This method return the estimated number of lines for print a simple text string using Multicell() method.
     * @param string     $txt         String for calculating his height
     * @param float      $w           Width of cells. If 0, they extend up to the right margin of the page.
     * @param bool       $reseth      if true reset the last cell height (default false).
     * @param bool       $autopadding if true, uses internal padding and automatically adjust it to account for line width (default true).
     * @param array|null $cellpadding Internal cell padding, if empty uses default cell padding.
     * @param mixed      $border      Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK])
     * @return float Return the minimal height needed for multicell method for printing the $txt param.
     * @author Alexander Escalona Fern\E1ndez, Nicola Asuni
     */
    public function getNumLines($txt, $w = 0, $reseth = false, $autopadding = true, $cellpadding = null, $border = 0)
    {
        if ($txt === NULL) {
            return 0;
        }
        if ($txt === '') {
            // empty string
            return 1;
        }
        // adjust internal padding
        $previousPaddings = $this->cellPaddings;
        $prev_lasth       = $this->lastPrintedCellHeight;
        if (is_array($cellpadding)) {
            $this->cellPaddings = $cellpadding;
        }
        $this->adjustCellPadding($border);
        if (StaticMethods::empty_string($w) or ($w <= 0)) {
            $w = $this->width - $this->rightMargin - $this->x;
        }
        $wmax = $w - $this->cellPaddings['L'] - $this->cellPaddings['R'];
        if ($reseth) {
            // reset row height
            $this->resetLastH();
        }
        $lines         = 1;
        $sum           = 0;
        $chars         = $this->utf8StringToArray($txt);
        $charsWidth    = $this->characterWidths($chars, true);
        $length        = count($chars);
        $lastSeparator = -1;
        for ($i = 0; $i < $length; ++$i) {
            $c         = $chars[$i];
            $charWidth = $charsWidth[$i];
            if (($c != 160)
                and (($c == 173)
                    or preg_match($this->spacesRE, $this->unicodeCharacter($c))
                    or (($c == 45)
                        and ($i > 0) and ($i < ($length - 1))
                        and @preg_match('/[\p{L}]/' . $this->spaceRE['m'], $this->unicodeCharacter($chars[($i - 1)]))
                        and @preg_match('/[\p{L}]/' . $this->spaceRE['m'], $this->unicodeCharacter($chars[($i + 1)]))
                    )
                )
            ) {
                $lastSeparator = $i;
            }
            if ((($sum + $charWidth) > $wmax) or ($c == 10)) {
                ++$lines;
                if ($c == 10) {
                    $lastSeparator = -1;
                    $sum           = 0;
                }
                elseif ($lastSeparator != -1) {
                    $i             = $lastSeparator;
                    $lastSeparator = -1;
                    $sum           = 0;
                }
                else {
                    $sum = $charWidth;
                }
            }
            else {
                $sum += $charWidth;
            }
        }
        if ($chars[($length - 1)] == 10) {
            --$lines;
        }
        $this->cellPaddings          = $previousPaddings;
        $this->lastPrintedCellHeight = $prev_lasth;
        return $lines;
    }

    /**
     * This method return the estimated height needed for printing a simple text string using the Multicell() method.
     * Generally, if you want to know the exact height for a block of content you can use the following alternative technique:
     * @pre
     *  // store current object
     *  $pdf->startTransaction();
     *  // store starting values
     *  $start_y = $pdf->y();
     *  $start_page = $pdf->getPage();
     *  // call your printing functions with your parameters
     *  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
     *  $pdf->MultiCell($w=0, $h=0, $txt, $border=1, $align='L', $fill=false, $ln=1, $x=null, $y=null, $reseth=true, $stretch=0, $ishtml=false, $autopadding=true, $maxh=0);
     *  // - - - - - - - - - - - - - - - - - - - - - - - - - - - - -
     *  // get the new Y
     *  $end_y = $pdf->y();
     *  $end_page = $pdf->getPage();
     *  // calculate height
     *  $height = 0;
     *  if ($end_page == $start_page) {
     *    $height = $end_y - $start_y;
     *  } else {
     *    for ($page=$start_page; $page <= $end_page; ++$page) {
     *        $this->setPage($page);
     *        if ($page == $start_page) {
     *            // first page
     *            $height += $this->h - $start_y - $this->bMargin;
     *        } elseif ($page == $end_page) {
     *            // last page
     *            $height += $end_y - $this->tMargin;
     *        } else {
     *            $height += $this->h - $this->tMargin - $this->bMargin;
     *        }
     *    }
     *  }
     *  // restore previous object
     *  $pdf = $pdf->rollbackTransaction();
     *
     * @param float      $w           Width of cells. If 0, they extend up to the right margin of the page.
     * @param string     $txt         String for calculating his height
     * @param bool       $reseth      if true reset the last cell height (default false).
     * @param bool       $autopadding if true, uses internal padding and automatically adjust it to account for line width (default true).
     * @param array|null $cellpadding Internal cell padding, if empty uses default cell padding.
     * @param mixed      $border      Indicates if borders must be drawn around the cell. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul> or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group - for example: array('LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK])
     * @return float Return the minimal height needed for multicell method for printing the $txt param.
     * @author Nicola Asuni, Alexander Escalona Fern\E1ndez
     */
    public function getStringHeight($w, $txt, $reseth = false, $autopadding = true, $cellpadding = null, $border = 0)
    {
        // adjust internal padding
        $previousPaddings = $this->cellPaddings;
        $prev_lasth       = $this->lastPrintedCellHeight;
        if (is_array($cellpadding)) {
            $this->cellPaddings = $cellpadding;
        }
        $this->adjustCellPadding($border);
        $lines                       = $this->getNumLines($txt, $w, $reseth, $autopadding, $cellpadding, $border);
        $height                      = $this->cellHeight(($lines * $this->fontSize), $autopadding);
        $this->cellPaddings          = $previousPaddings;
        $this->lastPrintedCellHeight = $prev_lasth;
        return $height;
    }

    /**
     * This method prints text from the current position.<br />
     * @param float      $h          Line height
     * @param string     $txt        String to print
     * @param mixed      $link       URL or identifier returned by AddLink()
     * @param bool       $fill       Indicates if the cell background must be painted (true) or transparent (false).
     * @param string     $align      Allows to center or align the text. Possible values are:<ul><li>L or empty string: left align (default value)</li><li>C: center</li><li>R: right align</li><li>J: justify</li></ul>
     * @param bool       $ln         if true set cursor at the bottom of the line, otherwise set cursor at the top of the line.
     * @param int        $stretch
     * @param bool       $firstline  if true prints only the first line and return the remaining string.
     * @param bool       $firstblock if true the string is the starting of a line.
     * @param float      $maxh       maximum height. It should be >= $h and less then remaining space to the bottom of the page, or 0 for disable this feature.
     * @param float      $wadj       first line width will be reduced by this amount (used in HTML mode).
     * @param array|null $margin     margin array of the parent container
     * @return mixed Return the number of cells or the remaining string if $firstline = true.
     * @see self::STRETCH_MODES
     */
    public function write($h, $txt, $link = '', $fill = false, $align = '', $ln = false, int $stretch = self::STRETCH_DISABLED, $firstline = false, $firstblock = false, $maxh = 0, $wadj = 0, $margin = null)
    {
        // check page for no-write regions and adapt page margins if necessary
        list($this->x, $this->y) = $this->checkPageRegions($h, $this->x, $this->y);
        if (strlen($txt) == 0) {
            // fix empty text
            $txt = ' ';
        }
        if (!is_array($margin)) {
            // set default margins
            $margin = $this->cellMargins;
        }
        // remove carriage returns
        $s = str_replace("\r", '', $txt);
        // get a char width
        $chrwidth = $this->GetCharWidth(46); // dot character
        // get array of unicode values
        $chars = $this->utf8StringToArray($s);
        // calculate maximum width for a single character on string
        $chrw = $this->characterWidths($chars, true);
        array_walk($chrw, [$this, 'getRawCharWidth']);
        $maxchwidth = ((is_array($chrw) || $chrw instanceof Countable) && count($chrw) > 0) ? max($chrw) : 0;
        // get array of chars
        $uchars = $this->utf8ArrayToUnicodeArray($chars);
        // get the number of characters
        $nb = count($chars);
        // replacement for SHY character (minus symbol)
        $shy_replacement      = 45;
        $shy_replacement_char = $this->unicodeCharacter($shy_replacement);
        // widht for SHY replacement
        $shy_replacement_width = $this->GetCharWidth($shy_replacement);
        // page width
        $pw = $w = $this->width - $this->leftMargin - $this->rightMargin;
        // calculate remaining line width ($w)
        $w = $this->width - $this->rightMargin - $this->x;
        // max column width
        $wmax = ($w - $wadj);
        if (!$firstline) {
            $wmax -= ($this->cellPaddings['L'] + $this->cellPaddings['R']);
        }
        if ((!$firstline) and (($chrwidth > $wmax) or ($maxchwidth > $wmax))) {
            // the maximum width character do not fit on column
            return '';
        }
        // minimum row height
        $row_height = max($h, $this->cellHeight($this->fontSize));
        // max Y
        $maxy       = $this->y + $maxh - max($row_height, $h);
        $start_page = $this->page;
        $i          = 0; // character position
        $j          = 0; // current starting position
        $sep        = -1; // position of the last blank space
        $prevsep    = $sep; // previous separator
        $shy        = false; // true if the last blank is a soft hypen (SHY)
        $prevshy    = $shy; // previous shy mode
        $l          = 0; // current string length
        $nl         = 0; //number of lines
        $linebreak  = false;
        $pc         = 0; // previous character
        // for each character
        while ($i < $nb) {
            if (($maxh > 0) and ($this->y > $maxy)) {
                break;
            }
            //Get the current character
            $c = $chars[$i];
            if ($c == 10) { // 10 = "\n" = new line
                //Explicit line break
                if ($align == self::JUSTIFY) {
                    $talign = self::LEFT;
                }
                else {
                    $talign = $align;
                }
                $tmpstr = $this->unicodeSliceToString($uchars, $j, $i);
                if ($firstline) {
                    $startx = $this->x;
                    $tmparr = array_slice($chars, $j, ($i - $j));
                    $linew  = $this->characterWidths($tmparr);
                    unset($tmparr);
                    $this->endlinex = $startx + $linew;
                    $w              = $linew;
                    $tmpcellpadding = $this->cellPaddings;
                    if ($maxh == 0) {
                        $this->cellPadding(0);
                    }
                }
                // Skip newlines at the beginning of a page or column
                if (!empty($tmpstr) or ($this->y < ($this->breakTrigger - $row_height))) {
                    $this->cell($w, $h, $tmpstr, 0, 1, $talign, $fill, $link, $stretch);
                }
                unset($tmpstr);
                if ($firstline) {
                    $this->cellPaddings = $tmpcellpadding;
                    return ($this->unicodeSliceToString($uchars, $i));
                }
                ++$nl;
                $j       = $i + 1;
                $l       = 0;
                $sep     = -1;
                $prevsep = $sep;
                $shy     = false;
                // account for margin changes
                if ((($this->y + $this->lastPrintedCellHeight) > $this->breakTrigger) and ($this->inPageBody())) {
                    if ($this->acceptPageBreak()) {
                        $this->x           += $margin['L'];
                        $this->leftMargin  += $margin['L'];
                        $this->rightMargin += $margin['R'];
                    }
                }
                $w    = $this->getRemainingWidth();
                $wmax = ($w - $this->cellPaddings['L'] - $this->cellPaddings['R']);
            }
            else {
                // 160 is the non-breaking space.
                // 173 is SHY (Soft Hypen).
                // \p{Z} or \p{Separator}: any kind of Unicode whitespace or invisible separator.
                // \p{Lo} or \p{Other_Letter}: a Unicode letter or ideograph that does not have lowercase and uppercase variants.
                // \p{Lo} is needed because Chinese characters are packed next to each other without spaces in between.
                if (($c != 160)
                    and (($c == 173)
                        or preg_match($this->spacesRE, $this->unicodeCharacter($c))
                        or (($c == 45)
                            and ($i < ($nb - 1))
                            and @preg_match('/[\p{L}]/' . $this->spaceRE['m'], $this->unicodeCharacter($pc))
                            and @preg_match('/[\p{L}]/' . $this->spaceRE['m'], $this->unicodeCharacter($chars[($i + 1)]))
                        )
                    )
                ) {
                    // update last blank space position
                    $prevsep = $sep;
                    $sep     = $i;
                    // check if is a SHY
                    if (($c == 173) or ($c == 45)) {
                        $prevshy = $shy;
                        $shy     = true;
                        if ($pc == 45) {
                            $tmp_shy_replacement_width = 0;
                            $tmp_shy_replacement_char  = '';
                        }
                        else {
                            $tmp_shy_replacement_width = $shy_replacement_width;
                            $tmp_shy_replacement_char  = $shy_replacement_char;
                        }
                    }
                    else {
                        $shy = false;
                    }
                }
                // update string length
                $l += $this->GetCharWidth($c, ($i + 1 < $nb));
                if (($l > $wmax) or (($c == 173) and (($l + $tmp_shy_replacement_width) >= $wmax))) {
                    if (($c == 173) and (($l + $tmp_shy_replacement_width) > $wmax)) {
                        $sep = $prevsep;
                        $shy = $prevshy;
                    }
                    // we have reached the end of column
                    if ($sep == -1) {
                        // check if the line was already started
                        if ($this->x >= ($this->leftMargin + $this->cellPaddings['L'] + $margin['L'] + $chrwidth)) {
                            // print a void cell and go to next line
                            $this->cell($w, $h, '', 0, 1);
                            $linebreak = true;
                            if ($firstline) {
                                return ($this->unicodeSliceToString($uchars, $j));
                            }
                        }
                        else {
                            // truncate the word because do not fit on column
                            $tmpstr = $this->unicodeSliceToString($uchars, $j, $i);
                            if ($firstline) {
                                $startx = $this->x;
                                $tmparr = array_slice($chars, $j, ($i - $j));
                                $linew  = $this->characterWidths($tmparr);
                                unset($tmparr);
                                $this->endlinex = $startx + $linew;
                                $w              = $linew;
                                $tmpcellpadding = $this->cellPaddings;
                                if ($maxh == 0) {
                                    $this->cellPadding(0);
                                }
                            }
                            $this->cell($w, $h, $tmpstr, 0, 1, $align, $fill, $link, $stretch);
                            unset($tmpstr);
                            if ($firstline) {
                                $this->cellPaddings = $tmpcellpadding;
                                return ($this->unicodeSliceToString($uchars, $i));
                            }
                            $j = $i;
                            --$i;
                        }
                    }
                    else {
                        // word wrapping
                        $endspace = 0;
                        // check the length of the next string
                        $strrest = $this->unicodeSliceToString($uchars, ($sep + $endspace));
                        $nextstr = preg_split('/' . $this->spaceRE['p'] . '/' . $this->spaceRE['m'], $this->trim($strrest));
                        if (isset($nextstr[0]) and ($this->stringWidth($nextstr[0]) > $pw)) {
                            // truncate the word because do not fit on a full page width
                            $tmpstr = $this->unicodeSliceToString($uchars, $j, $i);
                            if ($firstline) {
                                $startx = $this->x;
                                $tmparr = array_slice($chars, $j, ($i - $j));
                                $linew  = $this->characterWidths($tmparr);
                                unset($tmparr);
                                $this->endlinex = ($startx + $linew);
                                $w              = $linew;
                                $tmpcellpadding = $this->cellPaddings;
                                if ($maxh == 0) {
                                    $this->cellPadding(0);
                                }
                            }
                            $this->cell($w, $h, $tmpstr, 0, 1, $align, $fill, $link, $stretch);
                            unset($tmpstr);
                            if ($firstline) {
                                $this->cellPaddings = $tmpcellpadding;
                                return ($this->unicodeSliceToString($uchars, $i));
                            }
                            $j = $i;
                            --$i;
                        }
                        else {
                            // word wrapping
                            if ($shy) {
                                // add hypen (minus symbol) at the end of the line
                                $shy_width      = $tmp_shy_replacement_width;
                                $shy_char_left  = '';
                                $shy_char_right = $tmp_shy_replacement_char;
                            }
                            else {
                                $shy_width      = 0;
                                $shy_char_left  = '';
                                $shy_char_right = '';
                            }
                            $tmpstr = $this->unicodeSliceToString($uchars, $j, ($sep + $endspace));
                            if ($firstline) {
                                $startx = $this->x;
                                $tmparr = array_slice($chars, $j, (($sep + $endspace) - $j));
                                $linew  = $this->characterWidths($tmparr);
                                unset($tmparr);
                                $this->endlinex = $startx + $linew + $shy_width;
                                $w              = $linew;
                                $tmpcellpadding = $this->cellPaddings;
                                if ($maxh == 0) {
                                    $this->cellPadding(0);
                                }
                            }
                            // print the line
                            $this->cell($w, $h, $shy_char_left . $tmpstr . $shy_char_right, 0, 1, $align, $fill, $link, $stretch);
                            unset($tmpstr);
                            if ($firstline) {
                                if ($chars[$sep] == 45) {
                                    $endspace += 1;
                                }
                                // return the remaining text
                                $this->cellPaddings = $tmpcellpadding;
                                return ($this->unicodeSliceToString($uchars, ($sep + $endspace)));
                            }
                            $i   = $sep;
                            $sep = -1;
                            $shy = false;
                            $j   = ($i + 1);
                        }
                    }
                    // account for margin changes
                    if ((($this->y + $this->lastPrintedCellHeight) > $this->breakTrigger) and ($this->inPageBody())) {
                        if ($this->acceptPageBreak()) {
                            $this->x           += $margin['L'];
                            $this->leftMargin  += $margin['L'];
                            $this->rightMargin += $margin['R'];
                        }
                    }
                    $w    = $this->getRemainingWidth();
                    $wmax = $w - $this->cellPaddings['L'] - $this->cellPaddings['R'];
                    if ($linebreak) {
                        $linebreak = false;
                    }
                    else {
                        ++$nl;
                        $l = 0;
                    }
                }
            }
            // save last character
            $pc = $c;
            ++$i;
        } // end while i < nb
        // print last substring (if any)
        if ($l > 0) {
            switch ($align) {
                case self::JUSTIFY:
                case self::CENTER:
                case self::RIGHT:
                {
                    break;
                }
                default:
                {
                    $w = $l;
                    break;
                }
            }
            $tmpstr = $this->unicodeSliceToString($uchars, $j, $nb);
            if ($firstline) {
                $startx = $this->x;
                $tmparr = array_slice($chars, $j, ($nb - $j));
                $linew  = $this->characterWidths($tmparr);
                unset($tmparr);
                $this->endlinex = $startx + $linew;
                $w              = $linew;
                $tmpcellpadding = $this->cellPaddings;
                if ($maxh == 0) {
                    $this->cellPadding(0);
                }
            }
            $this->cell($w, $h, $tmpstr, 0, $ln, $align, $fill, $link, $stretch);
            unset($tmpstr);
            if ($firstline) {
                $this->cellPaddings = $tmpcellpadding;
                return ($this->unicodeSliceToString($uchars, $nb));
            }
            ++$nl;
        }
        if ($firstline) {
            return '';
        }
        return $nl;
    }

    /**
     * Returns the remaining width between the current position and margins.
     * @return float Return the remaining width
     */
    protected function getRemainingWidth()
    {
        list($this->x, $this->y) = $this->checkPageRegions(0, $this->x, $this->y);
        return ($this->width - $this->rightMargin - $this->x);
    }

    /**
     * Set the block dimensions accounting for page breaks and page/column fitting
     * @param float $w         width
     * @param float $h         height
     * @param float $x         X coordinate
     * @param float $y         Y coodiante
     * @param bool  $fitonpage if true the block is resized to not exceed page dimensions.
     * @return array [$w, $h, $x, $y]
     */
    protected function fitBlock($w, $h, $x, $y, $fitonpage = false)
    {
        if ($w <= 0) {
            // set maximum width
            $w = ($this->width - $this->leftMargin - $this->rightMargin);
            if ($w <= 0) {
                $w = 1;
            }
        }
        if ($h <= 0) {
            // set maximum height
            $h = ($this->breakTrigger - $this->topMargin);
            if ($h <= 0) {
                $h = 1;
            }
        }
        // resize the block to be vertically contained on a single page or single column
        if ($fitonpage or $this->autoPageBreak) {
            $ratio_wh = ($w / $h);
            if ($h > ($this->breakTrigger - $this->topMargin)) {
                $h = $this->breakTrigger - $this->topMargin;
                $w = ($h * $ratio_wh);
            }
            // resize the block to be horizontally contained on a single page or single column
            if ($fitonpage) {
                $maxw = ($this->width - $this->leftMargin - $this->rightMargin);
                if ($w > $maxw) {
                    $w = $maxw;
                    $h = ($w / $ratio_wh);
                }
            }
        }
        // Check whether we need a new page or new column first as this does not fit
        $prev_x = $this->x;
        $prev_y = $this->y;
        if ($this->checkPageBreak($h, $y) or ($this->y < $prev_y)) {
            $y             = $this->y;
            $x             += ($this->x - $prev_x);
            $this->newLine = true;
        }
        // resize the block to be contained on the remaining available page or column space
        if ($fitonpage) {
            // fallback to avoid division by zero
            $h        = $h == 0 ? 1 : $h;
            $ratio_wh = ($w / $h);
            if (($y + $h) > $this->breakTrigger) {
                $h = $this->breakTrigger - $y;
                $w = ($h * $ratio_wh);
            }
            if (($x + $w) > ($this->width - $this->rightMargin)) {
                $w = $this->width - $this->rightMargin - $x;
                $h = ($w / $ratio_wh);
            }
        }
        return [$w, $h, $x, $y];
    }

    /**
     * Get the GD-corrected PNG gamma value from alpha color
     * @param resource $img GD image Resource ID.
     * @param int      $c   alpha color
     */
    protected function getGDgamma($img, $c)
    {
        if (!isset($this->gdgammacache['#' . $c])) {
            $colors = imagecolorsforindex($img, $c);
            // GD alpha is only 7 bit (0 -> 127)
            $this->gdgammacache['#' . $c] = (int) (((127 - $colors['alpha']) / 127) * 255);
            // correct gamma
            $this->gdgammacache['#' . $c] = (int) (pow(($this->gdgammacache['#' . $c] / 255), 2.2) * 255);
            // store the latest values on cache to improve performances
            if (count($this->gdgammacache) > 8) {
                // remove one element from the cache array
                array_shift($this->gdgammacache);
            }
        }
        return $this->gdgammacache['#' . $c];
    }

    /**
     * Send the document to a given destination: string, local file or browser.
     * In the last case, the plug-in may be used (if present) or a download ("Save as" dialog box) may be forced.<br />
     * The method first calls Close() if necessary to terminate the document.
     * @param string $name The name of the file when saved
     * @param string $dest Destination where to send the document. It can take one of the following values:<ul><li>I: send the file inline to the browser (default). The plug-in is used if available. The name given by name is used when one selects the "Save as" option on the link generating the PDF.</li><li>D: send to the browser and force a file download with the name given by name.</li><li>F: save to a local server file with the name given by name.</li><li>S: return the document as a string (name is ignored).</li><li>FI: equivalent to F + I option</li><li>FD: equivalent to F + D option</li><li>E: return the document as base64 mime multi-part email attachment (RFC 2045)</li></ul>
     * @return string
     * @see   close()
     */
    public function Output($name = 'doc.pdf', $dest = 'I')
    {
        //Output PDF to some destination
        //Finish document if necessary
        if ($this->state < self::FINAL) {
            $this->close();
        }
        //Normalize parameters
        if (is_bool($dest)) {
            $dest = $dest ? 'D' : 'F';
        }
        $dest = strtoupper($dest);
        switch ($dest) {
            case 'I':
            {
                // Send PDF to the standard output
                if (ob_get_contents()) {
                    $this->Error('Some data has already been output, can\'t send PDF file');
                }
                if (php_sapi_name() != 'cli') {
                    // send output to a browser
                    header('Content-Type: application/pdf');
                    if (headers_sent()) {
                        $this->Error('Some data has already been output to browser, can\'t send PDF file');
                    }
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                    //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                    header('Pragma: public');
                    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                    header('Content-Disposition: inline; filename="' . rawurlencode(basename($name)) . '"; ' .
                        'filename*=UTF-8\'\'' . rawurlencode(basename($name)));
                    $this->outputData($this->buffer(), $this->bufferLength);
                }
                else {
                    echo $this->buffer();
                }
                break;
            }
            case 'D':
            {
                // download PDF as file
                if (ob_get_contents()) {
                    $this->Error('Some data has already been output, can\'t send PDF file');
                }
                header('Content-Description: File Transfer');
                if (headers_sent()) {
                    $this->Error('Some data has already been output to browser, can\'t send PDF file');
                }
                header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                header('Pragma: public');
                header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                // force download dialog
                if (strpos(php_sapi_name(), 'cgi') === false) {
                    header('Content-Type: application/force-download');
                    header('Content-Type: application/octet-stream', false);
                    header('Content-Type: application/download', false);
                    header('Content-Type: application/pdf', false);
                }
                else {
                    header('Content-Type: application/pdf');
                }
                // use the Content-Disposition header to supply a recommended filename
                header('Content-Disposition: attachment; filename="' . rawurlencode(basename($name)) . '"; ' .
                    'filename*=UTF-8\'\'' . rawurlencode(basename($name)));
                header('Content-Transfer-Encoding: binary');
                $this->outputData($this->buffer(), $this->bufferLength);
                break;
            }
            case 'F':
            case 'FI':
            case 'FD':
            {
                // save PDF to a local file
                $f = $this->openLocal($name, 'wb');
                if (!$f) {
                    $this->Error('Unable to create output file: ' . $name);
                }
                fwrite($f, $this->buffer(), $this->bufferLength);
                fclose($f);
                if ($dest == 'FI') {
                    // send headers to browser
                    header('Content-Type: application/pdf');
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                    //header('Cache-Control: public, must-revalidate, max-age=0'); // HTTP/1.1
                    header('Pragma: public');
                    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                    header('Content-Disposition: inline; filename="' . basename($name) . '"');
                    $this->outputData(file_get_contents($name), filesize($name));
                }
                elseif ($dest == 'FD') {
                    // send headers to browser
                    if (ob_get_contents()) {
                        $this->Error('Some data has already been output, can\'t send PDF file');
                    }
                    header('Content-Description: File Transfer');
                    if (headers_sent()) {
                        $this->Error('Some data has already been output to browser, can\'t send PDF file');
                    }
                    header('Cache-Control: private, must-revalidate, post-check=0, pre-check=0, max-age=1');
                    header('Pragma: public');
                    header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
                    header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
                    // force download dialog
                    if (strpos(php_sapi_name(), 'cgi') === false) {
                        header('Content-Type: application/force-download');
                        header('Content-Type: application/octet-stream', false);
                        header('Content-Type: application/download', false);
                        header('Content-Type: application/pdf', false);
                    }
                    else {
                        header('Content-Type: application/pdf');
                    }
                    // use the Content-Disposition header to supply a recommended filename
                    header('Content-Disposition: attachment; filename="' . basename($name) . '"');
                    header('Content-Transfer-Encoding: binary');
                    $this->outputData(file_get_contents($name), filesize($name));
                }
                break;
            }
            case 'E':
            {
                // return PDF as base64 mime multi-part email attachment (RFC 2045)
                $retval = 'Content-Type: application/pdf;' . "\r\n";
                $retval .= ' name="' . $name . '"' . "\r\n";
                $retval .= 'Content-Transfer-Encoding: base64' . "\r\n";
                $retval .= 'Content-Disposition: attachment;' . "\r\n";
                $retval .= ' filename="' . $name . '"' . "\r\n\r\n";
                $retval .= chunk_split(base64_encode($this->buffer()), 76, "\r\n");
                return $retval;
            }
            case 'S':
            {
                // returns PDF as a string
                return $this->buffer();
            }
            default:
            {
                $this->Error('Incorrect output destination: ' . $dest);
            }
        }
        return '';
    }

    /**
     * Return an array containing variations for the basic page number alias.
     * @param string $a Base alias.
     * @return array of page number aliases
     */
    protected function getInternalPageNumberAliases($a = '')
    {
        $alias = [];
        // build array of Unicode + ASCII variants (the order is important)
        $alias        = ['u' => [], 'a' => []];
        $u            = '{' . $a . '}';
        $alias['u'][] = $this->escape($u);
        $alias['u'][] = $this->escape($this->utf8ToLatin1($u));
        $alias['u'][] = $this->escape($this->utf8ToUTF16BE($u));
        $alias['a'][] = $this->escape($this->utf8ToLatin1($a));
        $alias['a'][] = $this->escape($this->utf8ToUTF16BE($a));
        $alias['a'][] = $this->escape($a);
        return $alias;
    }

    /**
     * Return an array containing all internal page aliases.
     * @return array of page number aliases
     */
    protected function getAllInternalPageNumberAliases()
    {
        $basic_alias = [self::TOTAL_PAGES_ALIAS, self::PAGE_NUMBER_ALIAS, self::PAGES_IN_GROUP_ALIAS, self::PAGE_GROUP_NUMBER_ALIAS, self::RIGHT_SHIFT_ALIAS];
        $pnalias     = [];
        foreach ($basic_alias as $k => $a) {
            $pnalias[$k] = $this->getInternalPageNumberAliases($a);
        }
        return $pnalias;
    }

    /**
     * Replace right shift page number aliases with spaces to correct right alignment.
     * This works perfectly only when using monospaced fonts.
     * @param string $page    Page content.
     * @param array  $aliases Array of page aliases.
     * @param int    $diff    initial difference to add.
     * @return string replaced page content.
     */
    protected function replaceRightShiftPageNumAliases($page, $aliases, $diff)
    {
        foreach ($aliases as $type => $alias) {
            foreach ($alias as $a) {
                // find position of compensation factor
                $startnum = (strpos($a, ':') + 1);
                $a        = substr($a, 0, $startnum);
                if (($pos = strpos($page, $a)) !== false) {
                    // end of alias
                    $endnum = strpos($page, '}', $pos);
                    // string to be replaced
                    $aa = substr($page, $pos, ($endnum - $pos + 1));
                    // get compensation factor
                    $ratio = substr($page, ($pos + $startnum), ($endnum - $pos - $startnum));
                    $ratio = preg_replace('/[^0-9\.]/', '', $ratio);
                    $ratio = floatval($ratio);
                    if ($type == 'u') {
                        $chrdiff = floor(($diff + 12) * $ratio);
                        $shift   = str_repeat(' ', $chrdiff);
                        $shift   = $this->utf8ToUTF16BE($shift);
                    }
                    else {
                        $chrdiff = floor(($diff + 11) * $ratio);
                        $shift   = str_repeat(' ', $chrdiff);
                    }
                    $page = str_replace($aa, $shift, $page);
                }
            }
        }
        return $page;
    }

    /**
     * Get references to page annotations.
     * @param int $n page number
     * @return string
     * @author Nicola Asuni
     */
    protected function _getannotsrefs($n)
    {
        if (!isset($this->pageAnnotations[$n])) {
            return '';
        }
        $out = ' /Annots [';
        if (isset($this->pageAnnotations[$n])) {
            foreach ($this->pageAnnotations[$n] as $key => $val) {
                if (!in_array($val['n'], $this->radio_groups)) {
                    $out .= ' ' . $val['n'] . ' 0 R';
                }
            }
            // add radiobutton groups
            if (isset($this->radiobutton_groups[$n])) {
                foreach ($this->radiobutton_groups[$n] as $key => $data) {
                    if (isset($data['n'])) {
                        $out .= ' ' . $data['n'] . ' 0 R';
                    }
                }
            }
        }
        $out .= ' ]';
        return $out;
    }

    /**
     * Output annotations objects for all pages.
     * !!! THIS METHOD IS NOT YET COMPLETED !!!
     * See section 12.5 of PDF 32000_2008 reference.
     * @author Nicola Asuni
     */
    protected function _putannotsobjs()
    {
        // reset object counter
        for ($n = 1; $n <= $this->pageCount; ++$n) {
            if (isset($this->pageAnnotations[$n])) {
                // set page annotations
                foreach ($this->pageAnnotations[$n] as $key => $pl) {
                    $annot_obj_id = $this->pageAnnotations[$n][$key]['n'];
                    // create annotation object for grouping radiobuttons
                    if (isset($this->radiobutton_groups[$n][$pl['txt']]) and is_array($this->radiobutton_groups[$n][$pl['txt']])) {
                        $radio_button_obj_id = $this->radiobutton_groups[$n][$pl['txt']]['n'];
                        $annots              = '<<';
                        $annots              .= ' /Type /Annot';
                        $annots              .= ' /Subtype /Widget';
                        $annots              .= ' /Rect [0 0 0 0]';
                        if ($this->radiobutton_groups[$n][$pl['txt']]['#readonly#']) {
                            // read only
                            $annots .= ' /F 68';
                            $annots .= ' /Ff 49153';
                        }
                        else {
                            $annots .= ' /F 4'; // default print for PDF/A
                            $annots .= ' /Ff 49152';
                        }
                        $annots .= ' /T ' . $this->metadataDataString($pl['txt']);
                        if (isset($pl['opt']['tu']) and is_string($pl['opt']['tu'])) {
                            $annots .= ' /TU ' . $this->metadataDataString($pl['opt']['tu']);
                        }
                        $annots .= ' /FT /Btn';
                        $annots .= ' /Kids [';
                        $defval = '';
                        foreach ($this->radiobutton_groups[$n][$pl['txt']] as $key => $data) {
                            if (isset($data['kid'])) {
                                $annots .= ' ' . $data['kid'] . ' 0 R';
                                if ($data['def'] !== 'Off') {
                                    $defval = $data['def'];
                                }
                            }
                        }
                        $annots .= ' ]';
                        if (!empty($defval)) {
                            $annots .= ' /V /' . $defval;
                        }
                        $annots .= ' >>';
                        $this->append($this->objectCommand($radio_button_obj_id) . "\n" . $annots . "\n" . 'endobj');
                        $this->annotationIDs[] = $radio_button_obj_id;
                        // store object id to be used on Parent entry of Kids
                        $this->radiobutton_groups[$n][$pl['txt']] = $radio_button_obj_id;
                    }
                    $formfield = false;
                    $pl['opt'] = array_change_key_case($pl['opt'], CASE_LOWER);
                    $a         = $pl['x'] * $this->factor;
                    $b         = $this->pageDimensions[$n]['h'] - (($pl['y'] + $pl['h']) * $this->factor);
                    $c         = $pl['w'] * $this->factor;
                    $d         = $pl['h'] * $this->factor;
                    $rect      = sprintf('%F %F %F %F', $a, $b, $a + $c, $b + $d);
                    // create new annotation object
                    $annots = '<</Type /Annot';
                    $annots .= ' /Subtype /' . $pl['opt']['subtype'];
                    $annots .= ' /Rect [' . $rect . ']';
                    $ft     = ['Btn', 'Tx', 'Ch', 'Sig'];
                    if (isset($pl['opt']['ft']) and in_array($pl['opt']['ft'], $ft)) {
                        $annots    .= ' /FT /' . $pl['opt']['ft'];
                        $formfield = true;
                    }
                    if ($pl['opt']['subtype'] !== 'Link') {
                        $annots .= ' /Contents ' . $this->metadataTextString($pl['txt']);
                    }
                    $annots .= ' /P ' . $this->pageObjectIDs[$n] . ' 0 R';
                    $annots .= ' /NM ' . $this->metadataDataString(sprintf('%04u-%04u', $n, $key));
                    $annots .= ' /M ' . $this->metadataDateString($this->modificationTimestamp);
                    if (isset($pl['opt']['f'])) {
                        $fval = 0;
                        if (is_array($pl['opt']['f'])) {
                            foreach ($pl['opt']['f'] as $f) {
                                switch (strtolower($f)) {
                                    case 'invisible':
                                    {
                                        $fval += 1 << 0;
                                        break;
                                    }
                                    case 'hidden':
                                    {
                                        $fval += 1 << 1;
                                        break;
                                    }
                                    case 'print':
                                    {
                                        $fval += 1 << 2;
                                        break;
                                    }
                                    case 'nozoom':
                                    {
                                        $fval += 1 << 3;
                                        break;
                                    }
                                    case 'norotate':
                                    {
                                        $fval += 1 << 4;
                                        break;
                                    }
                                    case 'noview':
                                    {
                                        $fval += 1 << 5;
                                        break;
                                    }
                                    case 'readonly':
                                    {
                                        $fval += 1 << 6;
                                        break;
                                    }
                                    case 'locked':
                                    {
                                        $fval += 1 << 8;
                                        break;
                                    }
                                    case 'togglenoview':
                                    {
                                        $fval += 1 << 9;
                                        break;
                                    }
                                    case 'lockedcontents':
                                    {
                                        $fval += 1 << 10;
                                        break;
                                    }
                                    default:
                                    {
                                        break;
                                    }
                                }
                            }
                        }
                        else {
                            $fval = intval($pl['opt']['f']);
                        }
                    }
                    else {
                        $fval = 4;
                    }
                    $annots .= ' /F ' . intval($fval);
                    if (isset($pl['opt']['as']) and is_string($pl['opt']['as'])) {
                        $annots .= ' /AS /' . $pl['opt']['as'];
                    }
                    if (isset($pl['opt']['ap'])) {
                        // appearance stream
                        $annots .= ' /AP <<';
                        if (is_array($pl['opt']['ap'])) {
                            foreach ($pl['opt']['ap'] as $apmode => $apdef) {
                                // $apmode can be: n = normal; r = rollover; d = down;
                                $annots .= ' /' . strtoupper($apmode);
                                if (is_array($apdef)) {
                                    $annots .= ' <<';
                                    foreach ($apdef as $apstate => $stream) {
                                        // reference to XObject that define the appearance for this mode-state
                                        $apsobjid = $this->_putAPXObject($c, $d, $stream);
                                        $annots   .= ' /' . $apstate . ' ' . $apsobjid . ' 0 R';
                                    }
                                    $annots .= ' >>';
                                }
                                else {
                                    // reference to XObject that define the appearance for this mode
                                    $apsobjid = $this->_putAPXObject($c, $d, $apdef);
                                    $annots   .= ' ' . $apsobjid . ' 0 R';
                                }
                            }
                        }
                        else {
                            $annots .= $pl['opt']['ap'];
                        }
                        $annots .= ' >>';
                    }
                    if (isset($pl['opt']['bs']) and (is_array($pl['opt']['bs']))) {
                        $annots .= ' /BS <<';
                        $annots .= ' /Type /Border';
                        if (isset($pl['opt']['bs']['w'])) {
                            $annots .= ' /W ' . intval($pl['opt']['bs']['w']);
                        }
                        $bstyles = ['S', 'D', 'B', 'I', 'U'];
                        if (isset($pl['opt']['bs']['s']) and in_array($pl['opt']['bs']['s'], $bstyles)) {
                            $annots .= ' /S /' . $pl['opt']['bs']['s'];
                        }
                        if (isset($pl['opt']['bs']['d']) and (is_array($pl['opt']['bs']['d']))) {
                            $annots .= ' /D [';
                            foreach ($pl['opt']['bs']['d'] as $cord) {
                                $annots .= ' ' . intval($cord);
                            }
                            $annots .= ']';
                        }
                        $annots .= ' >>';
                    }
                    else {
                        $annots .= ' /Border [';
                        if (isset($pl['opt']['border']) and (count($pl['opt']['border']) >= 3)) {
                            $annots .= intval($pl['opt']['border'][0]) . ' ';
                            $annots .= intval($pl['opt']['border'][1]) . ' ';
                            $annots .= intval($pl['opt']['border'][2]);
                            if (isset($pl['opt']['border'][3]) and is_array($pl['opt']['border'][3])) {
                                $annots .= ' [';
                                foreach ($pl['opt']['border'][3] as $dash) {
                                    $annots .= intval($dash) . ' ';
                                }
                                $annots .= ']';
                            }
                        }
                        else {
                            $annots .= '0 0 0';
                        }
                        $annots .= ']';
                    }
                    if (isset($pl['opt']['be']) and (is_array($pl['opt']['be']))) {
                        $annots  .= ' /BE <<';
                        $bstyles = ['S', 'C'];
                        if (isset($pl['opt']['be']['s']) and in_array($pl['opt']['be']['s'], $bstyles)) {
                            $annots .= ' /S /' . $pl['opt']['bs']['s'];
                        }
                        else {
                            $annots .= ' /S /S';
                        }
                        if (isset($pl['opt']['be']['i']) and ($pl['opt']['be']['i'] >= 0) and ($pl['opt']['be']['i'] <= 2)) {
                            $annots .= ' /I ' . sprintf(' %F', $pl['opt']['be']['i']);
                        }
                        $annots .= '>>';
                    }
                    if (isset($pl['opt']['c']) and (is_array($pl['opt']['c'])) and !empty($pl['opt']['c'])) {
                        $annots .= ' /C ' . $this->colorArrayToString($pl['opt']['c']);
                    }
                    //$annots .= ' /StructParent ';
                    //$annots .= ' /OC ';
                    $markups = ['text', 'freetext', 'line', 'square', 'circle', 'polygon', 'polyline', 'highlight', 'underline', 'squiggly', 'strikeout', 'stamp', 'caret', 'ink', 'fileattachment', 'sound'];
                    if (in_array(strtolower($pl['opt']['subtype']), $markups)) {
                        // this is a markup type
                        if (isset($pl['opt']['t']) and is_string($pl['opt']['t'])) {
                            $annots .= ' /T ' . $this->metadataTextString($pl['opt']['t']);
                        }
                        //$annots .= ' /Popup ';
                        if (isset($pl['opt']['ca'])) {
                            $annots .= ' /CA ' . sprintf('%F', floatval($pl['opt']['ca']));
                        }
                        if (isset($pl['opt']['rc'])) {
                            $annots .= ' /RC ' . $this->metadataTextString($pl['opt']['rc']);
                        }
                        $annots .= ' /CreationDate ' . $this->metadataDateString($this->creationTimestamp);
                        //$annots .= ' /IRT ';
                        if (isset($pl['opt']['subj'])) {
                            $annots .= ' /Subj ' . $this->metadataTextString($pl['opt']['subj']);
                        }
                        //$annots .= ' /RT ';
                        //$annots .= ' /IT ';
                        //$annots .= ' /ExData ';
                    }
                    $lineendings = ['Square', 'Circle', 'Diamond', 'OpenArrow', 'ClosedArrow', 'None', 'Butt', 'ROpenArrow', 'RClosedArrow', 'Slash'];
                    // Annotation types
                    switch (strtolower($pl['opt']['subtype'])) {
                        case 'text':
                        {
                            if (isset($pl['opt']['open'])) {
                                $annots .= ' /Open ' . (strtolower($pl['opt']['open']) == 'true' ? 'true' : 'false');
                            }
                            $iconsapp = ['Comment', 'Help', 'Insert', 'Key', 'NewParagraph', 'Note', 'Paragraph'];
                            if (isset($pl['opt']['name']) and in_array($pl['opt']['name'], $iconsapp)) {
                                $annots .= ' /Name /' . $pl['opt']['name'];
                            }
                            else {
                                $annots .= ' /Name /Note';
                            }
                            $hasStateModel = isset($pl['opt']['statemodel']);
                            $hasState      = isset($pl['opt']['state']);
                            $statemodels   = ['Marked', 'Review'];
                            if (!$hasStateModel && !$hasState) {
                                break;
                            }
                            if ($hasStateModel and in_array($pl['opt']['statemodel'], $statemodels)) {
                                $annots .= ' /StateModel /' . $pl['opt']['statemodel'];
                            }
                            else {
                                $pl['opt']['statemodel'] = 'Marked';
                                $annots                  .= ' /StateModel /' . $pl['opt']['statemodel'];
                            }
                            if ($pl['opt']['statemodel'] == 'Marked') {
                                $states = ['Accepted', 'Unmarked'];
                            }
                            else {
                                $states = ['Accepted', 'Rejected', 'Cancelled', 'Completed', 'None'];
                            }
                            if ($hasState and in_array($pl['opt']['state'], $states)) {
                                $annots .= ' /State /' . $pl['opt']['state'];
                            }
                            else {
                                if ($pl['opt']['statemodel'] == 'Marked') {
                                    $annots .= ' /State /Unmarked';
                                }
                                else {
                                    $annots .= ' /State /None';
                                }
                            }
                            break;
                        }
                        case 'link':
                        {
                            if (is_string($pl['txt']) && !empty($pl['txt'])) {
                                if ($pl['txt'][0] === '#') {
                                    // internal destination
                                    $annots .= ' /A <</S /GoTo /D /' . $this->encodeText(substr($pl['txt'], 1)) . '>>';
                                }
                                elseif ($pl['txt'][0] === '%') {
                                    // embedded PDF file
                                    $filename = basename(substr($pl['txt'], 1));
                                    $annots   .= ' /A << /S /GoToE /D [0 /Fit] /NewWindow true /T << /R /C /P ' . ($n - 1) . ' /A ' . $this->embeddedfiles[$filename]['a'] . ' >> >>';
                                }
                                elseif ($pl['txt'][0] == '*') {
                                    // embedded generic file
                                    $filename = basename(substr($pl['txt'], 1));
                                    $jsa      = 'var D=event.target.doc;var MyData=D.dataObjects;for (var i in MyData) if (MyData[i].path=="' . $filename . '") D.exportDataObject( { cName : MyData[i].name, nLaunch : 2});';
                                    $annots   .= ' /A << /S /JavaScript /JS ' . $this->metadataTextString($jsa) . '>>';
                                }
                                else {
                                    $parsedUrl = parse_url($pl['txt']);
                                    if (empty($parsedUrl['scheme']) and (!empty($parsedUrl['path']) && strtolower(substr($parsedUrl['path'], -4)) == '.pdf')) {
                                        // relative link to a PDF file
                                        $dest = '[0 /Fit]'; // default page 0
                                        if (!empty($parsedUrl['fragment'])) {
                                            // check for named destination
                                            $tmp  = explode('=', $parsedUrl['fragment']);
                                            $dest = '(' . ((count($tmp) == 2) ? $tmp[1] : $tmp[0]) . ')';
                                        }
                                        $annots .= ' /A <</S /GoToR /D ' . $dest . ' /F ' . $this->metadataDataString($this->unhtmlentities($parsedUrl['path'])) . ' /NewWindow true>>';
                                    }
                                    else {
                                        // external URI link
                                        $annots .= ' /A <</S /URI /URI ' . $this->metadataDataString($this->unhtmlentities($pl['txt'])) . '>>';
                                    }
                                }
                            }
                            elseif (isset($this->internalLinks[$pl['txt']])) {
                                // internal link ID
                                $l = $this->internalLinks[$pl['txt']];
                                if (isset($this->pageObjectIDs[($l['p'])])) {
                                    $annots .= sprintf(' /Dest [%u 0 R /XYZ 0 %F null]', $this->pageObjectIDs[($l['p'])], ($this->pageDimensions[$l['p']]['h'] - ($l['y'] * $this->factor)));
                                }
                            }
                            $hmodes = ['N', 'I', 'O', 'P'];
                            if (isset($pl['opt']['h']) and in_array($pl['opt']['h'], $hmodes)) {
                                $annots .= ' /H /' . $pl['opt']['h'];
                            }
                            else {
                                $annots .= ' /H /I';
                            }
                            //$annots .= ' /PA ';
                            //$annots .= ' /Quadpoints ';
                            break;
                        }
                        case 'freetext':
                        {
                            if (isset($pl['opt']['da']) and !empty($pl['opt']['da'])) {
                                $annots .= ' /DA (' . $pl['opt']['da'] . ')';
                            }
                            if (isset($pl['opt']['q']) and ($pl['opt']['q'] >= 0) and ($pl['opt']['q'] <= 2)) {
                                $annots .= ' /Q ' . intval($pl['opt']['q']);
                            }
                            if (isset($pl['opt']['rc'])) {
                                $annots .= ' /RC ' . $this->metadataTextString($pl['opt']['rc']);
                            }
                            if (isset($pl['opt']['ds'])) {
                                $annots .= ' /DS ' . $this->metadataTextString($pl['opt']['ds']);
                            }
                            if (isset($pl['opt']['cl']) and is_array($pl['opt']['cl'])) {
                                $annots .= ' /CL [';
                                foreach ($pl['opt']['cl'] as $cl) {
                                    $annots .= sprintf('%F ', $cl * $this->factor);
                                }
                                $annots .= ']';
                            }
                            $tfit = ['FreeText', 'FreeTextCallout', 'FreeTextTypeWriter'];
                            if (isset($pl['opt']['it']) and in_array($pl['opt']['it'], $tfit)) {
                                $annots .= ' /IT /' . $pl['opt']['it'];
                            }
                            if (isset($pl['opt']['rd']) and is_array($pl['opt']['rd'])) {
                                $l      = $pl['opt']['rd'][0] * $this->factor;
                                $r      = $pl['opt']['rd'][1] * $this->factor;
                                $t      = $pl['opt']['rd'][2] * $this->factor;
                                $b      = $pl['opt']['rd'][3] * $this->factor;
                                $annots .= ' /RD [' . sprintf('%F %F %F %F', $l, $r, $t, $b) . ']';
                            }
                            if (isset($pl['opt']['le']) and in_array($pl['opt']['le'], $lineendings)) {
                                $annots .= ' /LE /' . $pl['opt']['le'];
                            }
                            break;
                        }
                        case 'line':
                        {
                            break;
                        }
                        case 'square':
                        {
                            break;
                        }
                        case 'circle':
                        {
                            break;
                        }
                        case 'polygon':
                        {
                            break;
                        }
                        case 'polyline':
                        {
                            break;
                        }
                        case 'highlight':
                        {
                            break;
                        }
                        case 'underline':
                        {
                            break;
                        }
                        case 'squiggly':
                        {
                            break;
                        }
                        case 'strikeout':
                        {
                            break;
                        }
                        case 'stamp':
                        {
                            break;
                        }
                        case 'caret':
                        {
                            break;
                        }
                        case 'ink':
                        {
                            break;
                        }
                        case 'popup':
                        {
                            break;
                        }
                        case 'fileattachment':
                        {
                            if (!isset($pl['opt']['fs'])) {
                                break;
                            }
                            $filename = basename($pl['opt']['fs']);
                            if (isset($this->embeddedfiles[$filename]['f'])) {
                                $annots   .= ' /FS ' . $this->embeddedfiles[$filename]['f'] . ' 0 R';
                                $iconsapp = ['Graph', 'Paperclip', 'PushPin', 'Tag'];
                                if (isset($pl['opt']['name']) and in_array($pl['opt']['name'], $iconsapp)) {
                                    $annots .= ' /Name /' . $pl['opt']['name'];
                                }
                                else {
                                    $annots .= ' /Name /PushPin';
                                }
                                // index (zero-based) of the annotation in the Annots array of this page
                                $this->embeddedfiles[$filename]['a'] = $key;
                            }
                            break;
                        }
                        case 'sound':
                        {
                            if (!isset($pl['opt']['fs'])) {
                                break;
                            }
                            $filename = basename($pl['opt']['fs']);
                            if (isset($this->embeddedfiles[$filename]['f'])) {
                                // ... TO BE COMPLETED ...
                                // /R /C /B /E /CO /CP
                                $annots   .= ' /Sound ' . $this->embeddedfiles[$filename]['f'] . ' 0 R';
                                $iconsapp = ['Speaker', 'Mic'];
                                if (isset($pl['opt']['name']) and in_array($pl['opt']['name'], $iconsapp)) {
                                    $annots .= ' /Name /' . $pl['opt']['name'];
                                }
                                else {
                                    $annots .= ' /Name /Speaker';
                                }
                            }
                            break;
                        }
                        case 'movie':
                        {
                            break;
                        }
                        case 'widget':
                        {
                            $hmode = ['N', 'I', 'O', 'P', 'T'];
                            if (isset($pl['opt']['h']) and in_array($pl['opt']['h'], $hmode)) {
                                $annots .= ' /H /' . $pl['opt']['h'];
                            }
                            if (isset($pl['opt']['mk']) and (is_array($pl['opt']['mk'])) and !empty($pl['opt']['mk'])) {
                                $annots .= ' /MK <<';
                                if (isset($pl['opt']['mk']['r'])) {
                                    $annots .= ' /R ' . $pl['opt']['mk']['r'];
                                }
                                if (isset($pl['opt']['mk']['bc']) and (is_array($pl['opt']['mk']['bc']))) {
                                    $annots .= ' /BC ' . $this->colorArrayToString($pl['opt']['mk']['bc']);
                                }
                                if (isset($pl['opt']['mk']['bg']) and (is_array($pl['opt']['mk']['bg']))) {
                                    $annots .= ' /BG ' . $this->colorArrayToString($pl['opt']['mk']['bg']);
                                }
                                if (isset($pl['opt']['mk']['ca'])) {
                                    $annots .= ' /CA ' . $pl['opt']['mk']['ca'];
                                }
                                if (isset($pl['opt']['mk']['rc'])) {
                                    $annots .= ' /RC ' . $pl['opt']['mk']['rc'];
                                }
                                if (isset($pl['opt']['mk']['ac'])) {
                                    $annots .= ' /AC ' . $pl['opt']['mk']['ac'];
                                }
                                if (isset($pl['opt']['mk']['i'])) {
                                    if ($info = $this->imageBuffer($pl['opt']['mk']['i'])) {
                                        $annots .= ' /I ' . $info['n'] . ' 0 R';
                                    }
                                }
                                if (isset($pl['opt']['mk']['ri'])) {
                                    if ($info = $this->imageBuffer($pl['opt']['mk']['ri'])) {
                                        $annots .= ' /RI ' . $info['n'] . ' 0 R';
                                    }
                                }
                                if (isset($pl['opt']['mk']['ix'])) {
                                    if ($info = $this->imageBuffer($pl['opt']['mk']['ix'])) {
                                        $annots .= ' /IX ' . $info['n'] . ' 0 R';
                                    }
                                }
                                if (isset($pl['opt']['mk']['if']) and (is_array($pl['opt']['mk']['if'])) and !empty($pl['opt']['mk']['if'])) {
                                    $annots .= ' /IF <<';
                                    $if_sw  = ['A', 'B', 'S', 'N'];
                                    if (isset($pl['opt']['mk']['if']['sw']) and in_array($pl['opt']['mk']['if']['sw'], $if_sw)) {
                                        $annots .= ' /SW /' . $pl['opt']['mk']['if']['sw'];
                                    }
                                    $if_s = ['A', 'P'];
                                    if (isset($pl['opt']['mk']['if']['s']) and in_array($pl['opt']['mk']['if']['s'], $if_s)) {
                                        $annots .= ' /S /' . $pl['opt']['mk']['if']['s'];
                                    }
                                    if (isset($pl['opt']['mk']['if']['a']) and (is_array($pl['opt']['mk']['if']['a'])) and !empty($pl['opt']['mk']['if']['a'])) {
                                        $annots .= sprintf(' /A [%F %F]', $pl['opt']['mk']['if']['a'][0], $pl['opt']['mk']['if']['a'][1]);
                                    }
                                    if (isset($pl['opt']['mk']['if']['fb']) and ($pl['opt']['mk']['if']['fb'])) {
                                        $annots .= ' /FB true';
                                    }
                                    $annots .= '>>';
                                }
                                if (isset($pl['opt']['mk']['tp']) and ($pl['opt']['mk']['tp'] >= 0) and ($pl['opt']['mk']['tp'] <= 6)) {
                                    $annots .= ' /TP ' . intval($pl['opt']['mk']['tp']);
                                }
                                $annots .= '>>';
                            } // end MK
                            // --- Entries for field dictionaries ---
                            if (isset($this->radiobutton_groups[$n][$pl['txt']])) {
                                // set parent
                                $annots .= ' /Parent ' . $this->radiobutton_groups[$n][$pl['txt']] . ' 0 R';
                            }
                            if (isset($pl['opt']['t']) and is_string($pl['opt']['t'])) {
                                $annots .= ' /T ' . $this->metadataDataString($pl['opt']['t']);
                            }
                            if (isset($pl['opt']['tu']) and is_string($pl['opt']['tu'])) {
                                $annots .= ' /TU ' . $this->metadataDataString($pl['opt']['tu']);
                            }
                            if (isset($pl['opt']['tm']) and is_string($pl['opt']['tm'])) {
                                $annots .= ' /TM ' . $this->metadataDataString($pl['opt']['tm']);
                            }
                            if (isset($pl['opt']['ff'])) {
                                if (is_array($pl['opt']['ff'])) {
                                    // array of bit settings
                                    $flag = 0;
                                    foreach ($pl['opt']['ff'] as $val) {
                                        $flag += 1 << ($val - 1);
                                    }
                                }
                                else {
                                    $flag = intval($pl['opt']['ff']);
                                }
                                $annots .= ' /Ff ' . $flag;
                            }
                            if (isset($pl['opt']['maxlen'])) {
                                $annots .= ' /MaxLen ' . intval($pl['opt']['maxlen']);
                            }
                            if (isset($pl['opt']['v'])) {
                                $annots .= ' /V';
                                if (is_array($pl['opt']['v'])) {
                                    foreach ($pl['opt']['v'] as $optval) {
                                        if (is_float($optval)) {
                                            $optval = sprintf('%F', $optval);
                                        }
                                        $annots .= ' ' . $optval;
                                    }
                                }
                                else {
                                    $annots .= ' ' . $this->metadataTextString($pl['opt']['v']);
                                }
                            }
                            if (isset($pl['opt']['dv'])) {
                                $annots .= ' /DV';
                                if (is_array($pl['opt']['dv'])) {
                                    foreach ($pl['opt']['dv'] as $optval) {
                                        if (is_float($optval)) {
                                            $optval = sprintf('%F', $optval);
                                        }
                                        $annots .= ' ' . $optval;
                                    }
                                }
                                else {
                                    $annots .= ' ' . $this->metadataTextString($pl['opt']['dv']);
                                }
                            }
                            if (isset($pl['opt']['rv'])) {
                                $annots .= ' /RV';
                                if (is_array($pl['opt']['rv'])) {
                                    foreach ($pl['opt']['rv'] as $optval) {
                                        if (is_float($optval)) {
                                            $optval = sprintf('%F', $optval);
                                        }
                                        $annots .= ' ' . $optval;
                                    }
                                }
                                else {
                                    $annots .= ' ' . $this->metadataTextString($pl['opt']['rv']);
                                }
                            }
                            if (isset($pl['opt']['a']) and !empty($pl['opt']['a'])) {
                                $annots .= ' /A << ' . $pl['opt']['a'] . ' >>';
                            }
                            if (isset($pl['opt']['aa']) and !empty($pl['opt']['aa'])) {
                                $annots .= ' /AA << ' . $pl['opt']['aa'] . ' >>';
                            }
                            if (isset($pl['opt']['da']) and !empty($pl['opt']['da'])) {
                                $annots .= ' /DA (' . $pl['opt']['da'] . ')';
                            }
                            if (isset($pl['opt']['q']) and ($pl['opt']['q'] >= 0) and ($pl['opt']['q'] <= 2)) {
                                $annots .= ' /Q ' . intval($pl['opt']['q']);
                            }
                            if (isset($pl['opt']['opt']) and (is_array($pl['opt']['opt'])) and !empty($pl['opt']['opt'])) {
                                $annots .= ' /Opt [';
                                foreach ($pl['opt']['opt'] as $copt) {
                                    if (is_array($copt)) {
                                        $annots .= ' [' . $this->metadataTextString($copt[0]) . ' ' . $this->metadataTextString($copt[1]) . ']';
                                    }
                                    else {
                                        $annots .= ' ' . $this->metadataTextString($copt);
                                    }
                                }
                                $annots .= ']';
                            }
                            if (isset($pl['opt']['ti'])) {
                                $annots .= ' /TI ' . intval($pl['opt']['ti']);
                            }
                            if (isset($pl['opt']['i']) and (is_array($pl['opt']['i'])) and !empty($pl['opt']['i'])) {
                                $annots .= ' /I [';
                                foreach ($pl['opt']['i'] as $copt) {
                                    $annots .= intval($copt) . ' ';
                                }
                                $annots .= ']';
                            }
                            break;
                        }
                        case 'screen':
                        {
                            break;
                        }
                        case 'printermark':
                        {
                            break;
                        }
                        case 'trapnet':
                        {
                            break;
                        }
                        case 'watermark':
                        {
                            break;
                        }
                        case '3d':
                        {
                            break;
                        }
                        default:
                        {
                            break;
                        }
                    }
                    $annots .= '>>';
                    // create new annotation object
                    $this->append($this->objectCommand($annot_obj_id) . "\n" . $annots . "\n" . 'endobj');
                    if ($formfield and !isset($this->radiobutton_groups[$n][$pl['txt']])) {
                        // store reference of form object
                        $this->annotationIDs[] = $annot_obj_id;
                    }
                }
            }
        } // end for each page
    }

    /**
     * Put appearance streams XObject used to define annotation's appearance states.
     * @param int    $w      annotation width
     * @param int    $h      annotation height
     * @param string $stream appearance stream
     * @return int object ID
     */
    protected function _putAPXObject($w = 0, $h = 0, $stream = '')
    {
        $stream                                     = trim($stream);
        $out                                        = $this->objectCommand() . "\n";
        $this->xObjects['AX' . $this->objectNumber] = ['n' => $this->objectNumber];
        $out                                        .= '<<';
        $out                                        .= ' /Type /XObject';
        $out                                        .= ' /Subtype /Form';
        $out                                        .= ' /FormType 1';
        $stream                                     = gzcompress($stream);
        $out                                        .= ' /Filter /FlateDecode';
        $rect                                       = sprintf('%F %F', $w, $h);
        $out                                        .= ' /BBox [0 0 ' . $rect . ']';
        $out                                        .= ' /Matrix [1 0 0 1 0 0]';
        $out                                        .= ' /Resources 2 0 R';
        $out                                        .= ' /Length ' . strlen($stream);
        $out                                        .= ' >>';
        $out                                        .= ' stream' . "\n" . $stream . "\n" . 'endstream';
        $out                                        .= "\n" . 'endobj';
        $this->append($out);
        return $this->objectNumber;
    }

    /**
     * Output Form XObjects Templates.
     * @author Nicola Asuni
     * @see    startTemplate(), endTemplate(), printTemplate()
     */
    protected function _putxobjects()
    {
        foreach ($this->xObjects as $key => $data) {
            if (isset($data['content'])) {
                $stream = str_replace($this->epsmarker, '', trim($data['content']));
                $out    = $this->objectCommand($data['n']) . "\n";
                $out    .= '<<';
                $out    .= ' /Type /XObject';
                $out    .= ' /Subtype /Form';
                $out    .= ' /FormType 1';
                $stream = gzcompress($stream);
                $out    .= ' /Filter /FlateDecode';
                $out    .= sprintf(' /BBox [%F %F %F %F]', ($data['x'] * $this->factor), (-$data['y'] * $this->factor), (($data['w'] + $data['x']) * $this->factor), (($data['h'] - $data['y']) * $this->factor));
                $out    .= ' /Matrix [1 0 0 1 0 0]';
                $out    .= ' /Resources <<';
                $out    .= ' /ProcSet [/PDF /Text /ImageB /ImageC /ImageI]';
                if (isset($data['extgstates']) and !empty($data['extgstates'])) {
                    $out .= ' /ExtGState <<';
                    foreach ($data['extgstates'] as $k => $extgstate) {
                        if (isset($this->extgstates[$k]['name'])) {
                            $out .= ' /' . $this->extgstates[$k]['name'];
                        }
                        else {
                            $out .= ' /GS' . $k;
                        }
                        $out .= ' ' . $this->extgstates[$k]['n'] . ' 0 R';
                    }
                    $out .= ' >>';
                }
                if (isset($data['gradients']) and !empty($data['gradients'])) {
                    $gp = '';
                    $gs = '';
                    foreach ($data['gradients'] as $id => $grad) {
                        // gradient patterns
                        $gp .= ' /p' . $id . ' ' . $this->gradients[$id]['pattern'] . ' 0 R';
                        // gradient shadings
                        $gs .= ' /Sh' . $id . ' ' . $this->gradients[$id]['id'] . ' 0 R';
                    }
                    $out .= ' /Pattern <<' . $gp . ' >>';
                    $out .= ' /Shading <<' . $gs . ' >>';
                }
                // spot colors
                if (isset($data['spot_colors']) and !empty($data['spot_colors'])) {
                    $out .= ' /ColorSpace <<';
                    foreach ($data['spot_colors'] as $name => $color) {
                        $out .= ' /CS' . $color['i'] . ' ' . $this->spotColors[$name]['n'] . ' 0 R';
                    }
                    $out .= ' >>';
                }
                // fonts
                if (!empty($data['fonts'])) {
                    $out .= ' /Font <<';
                    foreach ($data['fonts'] as $fontKey => $fontID) {
                        $out .= ' /F' . $fontID . ' ' . $this->fontObjectIDs[$fontKey] . ' 0 R';
                    }
                    $out .= ' >>';
                }
                // images or nested xobjects
                if (!empty($data['images']) or !empty($data['xobjects'])) {
                    $out .= ' /XObject <<';
                    foreach ($data['images'] as $imgid) {
                        $out .= ' /I' . $imgid . ' ' . $this->xObjects['I' . $imgid]['n'] . ' 0 R';
                    }
                    foreach ($data['xobjects'] as $sub_id => $sub_objid) {
                        $out .= ' /' . $sub_id . ' ' . $sub_objid['n'] . ' 0 R';
                    }
                    $out .= ' >>';
                }
                $out .= ' >>'; //end resources
                if (isset($data['group']) and ($data['group'] !== false)) {
                    // set transparency group
                    $out .= ' /Group << /Type /Group /S /Transparency';
                    if (is_array($data['group'])) {
                        if (isset($data['group']['CS']) and !empty($data['group']['CS'])) {
                            $out .= ' /CS /' . $data['group']['CS'];
                        }
                        if (isset($data['group']['I'])) {
                            $out .= ' /I /' . ($data['group']['I'] === true ? 'true' : 'false');
                        }
                        if (isset($data['group']['K'])) {
                            $out .= ' /K /' . ($data['group']['K'] === true ? 'true' : 'false');
                        }
                    }
                    $out .= ' >>';
                }
                $out .= ' /Length ' . strlen($stream);
                $out .= ' >>';
                $out .= ' stream' . "\n" . $stream . "\n" . 'endstream';
                $out .= "\n" . 'endobj';
                $this->append($out);
            }
        }
    }

    /**
     * Return XObjects Dictionary.
     * @return string XObjects dictionary
     */
    protected function _getxobjectdict()
    {
        $out = '';
        foreach ($this->xObjects as $id => $objid) {
            $out .= ' /' . $id . ' ' . $objid['n'] . ' 0 R';
        }
        return $out;
    }

    /**
     * Output Resources Dictionary.
     */
    protected function _putresourcedict()
    {
        $out = $this->objectCommand(2) . "\n";
        $out .= '<< /ProcSet [/PDF /Text /ImageB /ImageC /ImageI]';
        $out .= ' /Font <<';
        foreach ($this->fontKeys as $fontKey) {
            $font = $this->fontBuffer($fontKey);
            $out  .= ' /F' . $font['i'] . ' ' . $font['n'] . ' 0 R';
        }
        $out .= ' >>';
        $out .= ' /XObject <<';
        $out .= $this->_getxobjectdict();
        $out .= ' >>';
        // layers
        if (!empty($this->layers)) {
            $out .= ' /Properties <<';
            foreach ($this->layers as $layer) {
                $out .= ' /' . $layer['layer'] . ' ' . $layer['objid'] . ' 0 R';
            }
            $out .= ' >>';
        }
        // transparency
        if (isset($this->extgstates) and !empty($this->extgstates)) {
            $out .= ' /ExtGState <<';
            foreach ($this->extgstates as $k => $extgstate) {
                if (isset($extgstate['name'])) {
                    $out .= ' /' . $extgstate['name'];
                }
                else {
                    $out .= ' /GS' . $k;
                }
                $out .= ' ' . $extgstate['n'] . ' 0 R';
            }
            $out .= ' >>';
        }
        if (isset($this->gradients) and !empty($this->gradients)) {
            $gp = '';
            $gs = '';
            foreach ($this->gradients as $id => $grad) {
                // gradient patterns
                $gp .= ' /p' . $id . ' ' . $grad['pattern'] . ' 0 R';
                // gradient shadings
                $gs .= ' /Sh' . $id . ' ' . $grad['id'] . ' 0 R';
            }
            $out .= ' /Pattern <<' . $gp . ' >>';
            $out .= ' /Shading <<' . $gs . ' >>';
        }
        // spot colors
        if (isset($this->spotColors) and !empty($this->spotColors)) {
            $out .= ' /ColorSpace <<';
            foreach ($this->spotColors as $color) {
                $out .= ' /CS' . $color['i'] . ' ' . $color['n'] . ' 0 R';
            }
            $out .= ' >>';
        }
        $out .= ' >>';
        $out .= "\n" . 'endobj';
        $this->append($out);
    }

    /**
     * Output Resources.
     */
    protected function _putresources()
    {
        $this->_putextgstates();
        $this->_putocg();
        $this->_putfonts();
        $this->appendImages();
        $this->appendSpotColors();
        $this->_putshaders();
        $this->_putxobjects();
        $this->_putresourcedict();
        $this->appendNamedDestinations();
        $this->_putEmbeddedFiles();
        $this->_putannotsobjs();
        $this->appendJavascript();
        $this->_putbookmarks();
    }

    /**
     * Put XMP data object and return ID.
     * @return int The object ID.
     */
    protected function _putXMP()
    {
        $oid = $this->appendObject();
        // set XMP data
        $xmp = '<?xpacket begin="' . $this->unicodeCharacter(0xfeff) . '" id="W5M0MpCehiHzreSzNTczkc9d"?>' . "\n";
        $xmp .= '<x:xmpmeta xmlns:x="adobe:ns:meta/" x:xmptk="Adobe XMP Core 4.2.1-c043 52.372728, 2009/01/18-15:08:04">' . "\n";
        $xmp .= "\t" . '<rdf:RDF xmlns:rdf="http://www.w3.org/1999/02/22-rdf-syntax-ns#">' . "\n";
        $xmp .= "\t\t" . '<rdf:Description rdf:about="" xmlns:dc="http://purl.org/dc/elements/1.1/">' . "\n";
        $xmp .= "\t\t\t" . '<dc:format>application/pdf</dc:format>' . "\n";
        $xmp .= "\t\t\t" . '<dc:title>' . "\n";
        $xmp .= "\t\t\t\t" . '<rdf:Alt>' . "\n";
        $xmp .= "\t\t\t\t\t" . '<rdf:li xml:lang="x-default">' . $this->escapeXML($this->title) . '</rdf:li>' . "\n";
        $xmp .= "\t\t\t\t" . '</rdf:Alt>' . "\n";
        $xmp .= "\t\t\t" . '</dc:title>' . "\n";
        $xmp .= "\t\t\t" . '<dc:creator>' . "\n";
        $xmp .= "\t\t\t\t" . '<rdf:Seq>' . "\n";
        $xmp .= "\t\t\t\t\t" . '<rdf:li>' . $this->escapeXML($this->author) . '</rdf:li>' . "\n";
        $xmp .= "\t\t\t\t" . '</rdf:Seq>' . "\n";
        $xmp .= "\t\t\t" . '</dc:creator>' . "\n";
        $xmp .= "\t\t\t" . '<dc:description>' . "\n";
        $xmp .= "\t\t\t\t" . '<rdf:Alt>' . "\n";
        $xmp .= "\t\t\t\t\t" . '<rdf:li xml:lang="x-default">' . $this->escapeXML($this->subject) . '</rdf:li>' . "\n";
        $xmp .= "\t\t\t\t" . '</rdf:Alt>' . "\n";
        $xmp .= "\t\t\t" . '</dc:description>' . "\n";
        $xmp .= "\t\t\t" . '<dc:subject>' . "\n";
        $xmp .= "\t\t\t\t" . '<rdf:Bag>' . "\n";
        $xmp .= "\t\t\t\t\t" . '<rdf:li>' . $this->escapeXML($this->keywords) . '</rdf:li>' . "\n";
        $xmp .= "\t\t\t\t" . '</rdf:Bag>' . "\n";
        $xmp .= "\t\t\t" . '</dc:subject>' . "\n";
        $xmp .= "\t\t" . '</rdf:Description>' . "\n";
        // convert doc creation date format
        $dcdate          = $this->formattedDate($this->creationTimestamp);
        $doccreationdate = substr($dcdate, 0, 4) . '-' . substr($dcdate, 4, 2) . '-' . substr($dcdate, 6, 2);
        $doccreationdate .= 'T' . substr($dcdate, 8, 2) . ':' . substr($dcdate, 10, 2) . ':' . substr($dcdate, 12, 2);
        $doccreationdate .= substr($dcdate, 14, 3) . ':' . substr($dcdate, 18, 2);
        $doccreationdate = $this->escapeXML($doccreationdate);
        // convert doc modification date format
        $dmdate     = $this->formattedDate($this->modificationTimestamp);
        $docmoddate = substr($dmdate, 0, 4) . '-' . substr($dmdate, 4, 2) . '-' . substr($dmdate, 6, 2);
        $docmoddate .= 'T' . substr($dmdate, 8, 2) . ':' . substr($dmdate, 10, 2) . ':' . substr($dmdate, 12, 2);
        $docmoddate .= substr($dmdate, 14, 3) . ':' . substr($dmdate, 18, 2);
        $docmoddate = $this->escapeXML($docmoddate);
        $xmp        .= "\t\t" . '<rdf:Description rdf:about="" xmlns:xmp="http://ns.adobe.com/xap/1.0/">' . "\n";
        $xmp        .= "\t\t\t" . '<xmp:CreateDate>' . $doccreationdate . '</xmp:CreateDate>' . "\n";
        $xmp        .= "\t\t\t" . '<xmp:CreatorTool>' . $this->creator . '</xmp:CreatorTool>' . "\n";
        $xmp        .= "\t\t\t" . '<xmp:ModifyDate>' . $docmoddate . '</xmp:ModifyDate>' . "\n";
        $xmp        .= "\t\t\t" . '<xmp:MetadataDate>' . $doccreationdate . '</xmp:MetadataDate>' . "\n";
        $xmp        .= "\t\t" . '</rdf:Description>' . "\n";
        $xmp        .= "\t\t" . '<rdf:Description rdf:about="" xmlns:pdf="http://ns.adobe.com/pdf/1.3/">' . "\n";
        $xmp        .= "\t\t\t" . '<pdf:Keywords>' . $this->escapeXML($this->keywords) . '</pdf:Keywords>' . "\n";
        $xmp        .= "\t\t\t" . '<pdf:Producer>' . $this->escapeXML($this->producer()) . '</pdf:Producer>' . "\n";
        $xmp        .= "\t\t" . '</rdf:Description>' . "\n";
        $xmp        .= "\t\t" . '<rdf:Description rdf:about="" xmlns:xmpMM="http://ns.adobe.com/xap/1.0/mm/">' . "\n";
        $uuid       = 'uuid:' . substr($this->fileID, 0, 8) . '-' . substr($this->fileID, 8, 4) . '-' . substr($this->fileID, 12, 4) . '-' . substr($this->fileID, 16, 4) . '-' . substr($this->fileID, 20, 12);
        $xmp        .= "\t\t\t" . '<xmpMM:DocumentID>' . $uuid . '</xmpMM:DocumentID>' . "\n";
        $xmp        .= "\t\t\t" . '<xmpMM:InstanceID>' . $uuid . '</xmpMM:InstanceID>' . "\n";
        $xmp        .= "\t\t" . '</rdf:Description>' . "\n";
        // XMP extension schemas
        $xmp .= $this->custom_xmp_rdf;
        $xmp .= "\t" . '</rdf:RDF>' . "\n";
        $xmp .= $this->custom_xmp;
        $xmp .= '</x:xmpmeta>' . "\n";
        $xmp .= '<?xpacket end="w"?>';
        $out = '<< /Type /Metadata /Subtype /XML /Length ' . strlen($xmp) . ' >> stream' . "\n" . $xmp . "\n" . 'endstream' . "\n" . 'endobj';
        $this->append($out);
        return $oid;
    }

    /**
     * Output Catalog.
     * @return int object id
     */
    protected function _putcatalog()
    {
        // put XMP
        $xmpobj = $this->_putXMP();
        // start catalog
        $oid = $this->appendObject();
        $out = '<< /Type /Catalog';
        $out .= ' /Version /1.7';
        //$out .= ' /Extensions <<>>';
        $out .= ' /Pages 1 0 R';
        //$out .= ' /PageLabels ' //...;
        $out .= ' /Names <<';
        if (!empty($this->javascriptCounter)) {
            $out .= ' /JavaScript ' . $this->javascriptCounter;
        }
        if (!empty($this->efnames)) {
            $out .= ' /EmbeddedFiles <</Names [';
            foreach ($this->efnames as $fn => $fref) {
                $out .= ' ' . $this->metadataDataString($fn) . ' ' . $fref;
            }
            $out .= ' ]>>';
        }
        $out .= ' >>';
        if (!empty($this->namedDestinationsMap)) {
            $out .= ' /Dests ' . ($this->namedDestinationID) . ' 0 R';
        }
        $out .= $this->_putviewerpreferences();
        if (isset($this->layoutMode) and (!StaticMethods::empty_string($this->layoutMode))) {
            $out .= ' /PageLayout /' . $this->layoutMode;
        }
        if (isset($this->pageMode) and (!StaticMethods::empty_string($this->pageMode))) {
            $out .= ' /PageMode /' . $this->pageMode;
        }
        if (count($this->outlines) > 0) {
            $out .= ' /Outlines ' . $this->OutlineRoot . ' 0 R';
            $out .= ' /PageMode /UseOutlines';
        }
        //$out .= ' /Threads []';
        if ($this->pageZoom === self::FULL_PAGE) {
            $out .= ' /OpenAction [' . $this->pageObjectIDs[1] . ' 0 R /Fit]';
        }
        elseif ($this->pageZoom === self::FULL_WIDTH) {
            $out .= ' /OpenAction [' . $this->pageObjectIDs[1] . ' 0 R /FitH null]';
        }
        elseif ($this->pageZoom === self::REAL_SIZE) {
            $out .= ' /OpenAction [' . $this->pageObjectIDs[1] . ' 0 R /XYZ null null 1]';
        }
        $out .= ' /Metadata ' . $xmpobj . ' 0 R';
        if (!empty($this->layers)) {
            $lyrobjs      = '';
            $lyrobjs_off  = '';
            $lyrobjs_lock = '';
            foreach ($this->layers as $layer) {
                $layer_obj_ref = ' ' . $layer['objid'] . ' 0 R';
                $lyrobjs       .= $layer_obj_ref;
                if ($layer['view'] === false) {
                    $lyrobjs_off .= $layer_obj_ref;
                }
                if ($layer['lock']) {
                    $lyrobjs_lock .= $layer_obj_ref;
                }
            }
            $out .= ' /OCProperties << /OCGs [' . $lyrobjs . ']';
            $out .= ' /D <<';
            $out .= ' /Name ' . $this->metadataTextString('Layers');
            $out .= ' /Creator ' . $this->metadataTextString('TCPDF');
            $out .= ' /BaseState /ON';
            $out .= ' /OFF [' . $lyrobjs_off . ']';
            $out .= ' /Locked [' . $lyrobjs_lock . ']';
            $out .= ' /Intent /View';
            $out .= ' /AS [';
            $out .= ' << /Event /Print /OCGs [' . $lyrobjs . '] /Category [/Print] >>';
            $out .= ' << /Event /View /OCGs [' . $lyrobjs . '] /Category [/View] >>';
            $out .= ' ]';
            $out .= ' /Order [' . $lyrobjs . ']';
            $out .= ' /ListMode /AllPages';
            //$out .= ' /RBGroups ['..']';
            //$out .= ' /Locked ['..']';
            $out .= ' >>';
            $out .= ' >>';
        }
        // AcroForm
        if (!empty($this->annotationIDs)) {
            $out     .= ' /AcroForm <<';
            $objrefs = '';
            if (!empty($this->annotationIDs)) {
                foreach ($this->annotationIDs as $objid) {
                    $objrefs .= ' ' . $objid . ' 0 R';
                }
            }
            $out .= ' /Fields [' . $objrefs . ']';
            // It's better to turn off this value and set the appearance stream for each annotation (/AP) to avoid conflicts with signature fields.
            $out .= ' /NeedAppearances false';
            if (isset($this->annotationFonts) and !empty($this->annotationFonts)) {
                $out .= ' /DR <<';
                $out .= ' /Font <<';
                foreach ($this->annotationFonts as $fontKey => $fontID) {
                    $out .= ' /F' . $fontID . ' ' . $this->fontObjectIDs[$fontKey] . ' 0 R';
                }
                $out .= ' >> >>';
            }
            $font = $this->fontBuffer('helvetica');
            $out  .= ' /DA (/F' . $font['i'] . ' 0 Tf 0 g) /Q 0';
            $out  .= ' >>';
        }
        $out .= ' >>';
        $out .= "\n" . 'endobj';
        $this->append($out);
        return $oid;
    }

    /**
     * Output viewer preferences.
     * @return string for viewer preferences
     * @author Nicola asuni
     */
    protected function _putviewerpreferences()
    {
        $vp  = $this->viewer_preferences;
        $out = ' /ViewerPreferences <<';
        $out .= ' /Direction /L2R';
        if (isset($vp['HideToolbar']) and ($vp['HideToolbar'])) {
            $out .= ' /HideToolbar true';
        }
        if (isset($vp['HideMenubar']) and ($vp['HideMenubar'])) {
            $out .= ' /HideMenubar true';
        }
        if (isset($vp['HideWindowUI']) and ($vp['HideWindowUI'])) {
            $out .= ' /HideWindowUI true';
        }
        if (isset($vp['FitWindow']) and ($vp['FitWindow'])) {
            $out .= ' /FitWindow true';
        }
        if (isset($vp['CenterWindow']) and ($vp['CenterWindow'])) {
            $out .= ' /CenterWindow true';
        }
        if (isset($vp['DisplayDocTitle']) and ($vp['DisplayDocTitle'])) {
            $out .= ' /DisplayDocTitle true';
        }
        if (isset($vp['NonFullScreenPageMode'])) {
            $out .= ' /NonFullScreenPageMode /' . $vp['NonFullScreenPageMode'];
        }
        if (isset($vp['ViewArea'])) {
            $out .= ' /ViewArea /' . $vp['ViewArea'];
        }
        if (isset($vp['ViewClip'])) {
            $out .= ' /ViewClip /' . $vp['ViewClip'];
        }
        if (isset($vp['PrintArea'])) {
            $out .= ' /PrintArea /' . $vp['PrintArea'];
        }
        if (isset($vp['PrintClip'])) {
            $out .= ' /PrintClip /' . $vp['PrintClip'];
        }
        if (isset($vp['PrintScaling'])) {
            $out .= ' /PrintScaling /' . $vp['PrintScaling'];
        }
        if (isset($vp['Duplex']) and (!StaticMethods::empty_string($vp['Duplex']))) {
            $out .= ' /Duplex /' . $vp['Duplex'];
        }
        if (isset($vp['PickTrayByPDFSize'])) {
            if ($vp['PickTrayByPDFSize']) {
                $out .= ' /PickTrayByPDFSize true';
            }
            else {
                $out .= ' /PickTrayByPDFSize false';
            }
        }
        if (isset($vp['PrintPageRange'])) {
            $PrintPageRangeNum = '';
            foreach ($vp['PrintPageRange'] as $k => $v) {
                $PrintPageRangeNum .= ' ' . ($v - 1) . '';
            }
            $out .= ' /PrintPageRange [' . substr($PrintPageRangeNum, 1) . ']';
        }
        if (isset($vp['NumCopies'])) {
            $out .= ' /NumCopies ' . intval($vp['NumCopies']);
        }
        $out .= ' >>';
        return $out;
    }

    /**
     * Output end of document (EOF).
     */
    protected function _enddoc()
    {
        if (isset($this->currentFont['fontKey']) and isset($this->currentFont['subsetCharacters'])) {
            // save subset chars of the previous font
            $this->fontSubBuffer($this->currentFont['fontKey'], 'subsetCharacters', $this->currentFont['subsetCharacters']);
        }
        $this->state = self::NEUTRAL;
        $this->append('%PDF-1.7');
        $this->append('%' . chr(0xe2) . chr(0xe3) . chr(0xcf) . chr(0xd3));
        $this->_putpages();
        $this->_putresources();
        // Info
        $objid_info = $this->appendMetadata();
        // Catalog
        $objid_catalog = $this->_putcatalog();
        // Cross-ref
        $o = $this->bufferLength;
        // XREF section
        $this->append('xref');
        $this->append('0 ' . ($this->objectNumber + 1));
        $this->append('0000000000 65535 f ');
        $freegen = ($this->objectNumber + 2);
        for ($i = 1; $i <= $this->objectNumber; ++$i) {
            if (!isset($this->offsets[$i]) and ($i > 1)) {
                $this->append(sprintf('0000000000 %05d f ', $freegen));
                ++$freegen;
            }
            else {
                $this->append(sprintf('%010d 00000 n ', $this->offsets[$i]));
            }
        }
        // TRAILER
        $out = 'trailer' . "\n";
        $out .= '<<';
        $out .= ' /Size ' . ($this->objectNumber + 1);
        $out .= ' /Root ' . $objid_catalog . ' 0 R';
        $out .= ' /Info ' . $objid_info . ' 0 R';
        $out .= ' /ID [ <' . $this->fileID . '> <' . $this->fileID . '> ]';
        $out .= ' >>';
        $this->append($out);
        $this->append('startxref');
        $this->append($o);
        $this->append('%%EOF');
        $this->state = self::FINAL;
    }

    /**
     * Underline text.
     * @param int    $x   X coordinate
     * @param int    $y   Y coordinate
     * @param string $txt text to underline
     */
    protected function _dounderline($x, $y, $txt)
    {
        $w = $this->stringWidth($txt);
        return $this->_dounderlinew($x, $y, $w);
    }

    /**
     * Underline for rectangular text area.
     * @param int $x X coordinate
     * @param int $y Y coordinate
     * @param int $w width to underline
     */
    protected function _dounderlinew($x, $y, $w)
    {
        $linew = -$this->currentFont['ut'] / 1000 * $this->fontSizePT;
        return sprintf('%F %F %F %F re f', $x * $this->factor, ((($this->height - $y) * $this->factor) + $linew), $w * $this->factor, $linew);
    }

    /**
     * Line through text.
     * @param int    $x   X coordinate
     * @param int    $y   Y coordinate
     * @param string $txt text to line-through
     */
    protected function _dolinethrough($x, $y, $txt)
    {
        $w = $this->stringWidth($txt);
        return $this->_dolinethroughw($x, $y, $w);
    }

    /**
     * Line through for rectangular text area.
     * @param int $x X coordinate
     * @param int $y Y coordinate
     * @param int $w line length (width)
     */
    protected function _dolinethroughw($x, $y, $w)
    {
        $linew = -$this->currentFont['ut'] / 1000 * $this->fontSizePT;
        return sprintf('%F %F %F %F re f', $x * $this->factor, ((($this->height - $y) * $this->factor) + $linew + ($this->fontSizePT / 3)), $w * $this->factor, $linew);
    }

    /**
     * Overline text.
     * @param int    $x   X coordinate
     * @param int    $y   Y coordinate
     * @param string $txt text to overline
     */
    protected function _dooverline($x, $y, $txt)
    {
        $w = $this->stringWidth($txt);
        return $this->_dooverlinew($x, $y, $w);
    }

    /**
     * Overline for rectangular text area.
     * @param int $x X coordinate
     * @param int $y Y coordinate
     * @param int $w width to overline
     */
    protected function _dooverlinew($x, $y, $w)
    {
        $linew = -$this->currentFont['ut'] / 1000 * $this->fontSizePT;
        return sprintf('%F %F %F %F re f', $x * $this->factor, (($this->height - $y + $this->fontAscent) * $this->factor) - $linew, $w * $this->factor, $linew);

    }

    /**
     * Returns the PDF data.
     * @public
     */
    public function getPDFData()
    {
        if ($this->state < 3) {
            $this->close();
        }
        return $this->buffer;
    }

    /**
     * Converts pixels to User's Units.
     * @param int $px pixels
     * @return float value in user's unit
     */
    public function pixelsToUnits($px)
    {
        return ($px / ($this->imageFactor * $this->factor));
    }

    /**
     * Reverse function for htmlentities.
     * Convert entities in UTF-8.
     * @param string $text_to_convert Text to convert.
     * @return string converted text string
     */
    public function unhtmlentities($text_to_convert)
    {
        return @html_entity_decode($text_to_convert, ENT_QUOTES, 'UTF-8');
    }

    /**
     * Returns the string alias used right align page numbers.
     * If the current font is unicode type, the returned string wil contain an additional open curly brace.
     * @return string
     */
    public function getAliasRightShift()
    {
        // calculate aproximatively the ratio between widths of aliases and replacements.
        $ref  = '{' . self::RIGHT_SHIFT_ALIAS . '}{' . self::TOTAL_PAGES_ALIAS . '}{' . self::PAGE_NUMBER_ALIAS . '}';
        $rep  = str_repeat(' ', $this->GetNumChars($ref));
        $wrep = $this->stringWidth($rep);
        if ($wrep > 0) {
            $wdiff = max(1, ($this->stringWidth($ref) / $wrep));
        }
        else {
            $wdiff = 1;
        }
        $sdiff = sprintf('%F', $wdiff);
        $alias = self::RIGHT_SHIFT_ALIAS . $sdiff . '}';
        return $alias;
    }

    /**
     * Return the current page in the group.
     * @return int current page in the group
     * @public
     * @since 3.0.000 (2008-03-27)
     */
    public function getGroupPageNo()
    {
        return $this->pagegroups[$this->currpagegroup];
    }

    /**
     * Returns the current group page number formatted as a string.
     * @public
     * @since 4.3.003 (2008-11-18)
     * @see   PaneNo(), formatPageNumber()
     */
    public function getGroupPageNoFormatted()
    {
        return TCPDF_STATIC::formatPageNumber($this->getGroupPageNo());
    }

    /**
     * Returns the current page number formatted as a string.
     * @public
     * @since 4.2.005 (2008-11-06)
     * @see   PaneNo(), formatPageNumber()
     */
    public function PageNoFormatted()
    {
        return TCPDF_STATIC::formatPageNumber($this->page);
    }

    /**
     * Put pdf layers.
     */
    protected function _putocg()
    {
        if (empty($this->layers)) {
            return;
        }
        foreach ($this->layers as $key => $layer) {
            $this->layers[$key]['objid'] = $this->appendObject();
            $out                         = '<< /Type /OCG';
            $out                         .= ' /Name ' . $this->metadataTextString($layer['name']);
            $out                         .= ' /Usage <<';
            if (isset($layer['print']) and ($layer['print'] !== NULL)) {
                $out .= ' /Print <</PrintState /' . ($layer['print'] ? 'ON' : 'OFF') . '>>';
            }
            $out .= ' /View <</ViewState /' . ($layer['view'] ? 'ON' : 'OFF') . '>>';
            $out .= ' >> >>';
            $out .= "\n" . 'endobj';
            $this->append($out);
        }
    }

    /**
     * Add transparency parameters to the current extgstate
     * @param array $parameters parameters
     * @return int|void the number of extgstates
     */
    protected function addExtGState($parameters)
    {
        // check if this ExtGState already exist
        foreach ($this->extgstates as $i => $ext) {
            if ($ext['parameters'] == $parameters) {
                if ($this->inXObject) {
                    // we are inside an XObject template
                    $this->xObjects[$this->xObjectID]['extgstates'][$i] = $ext;
                }
                // return reference to existing ExtGState
                return $i;
            }
        }
        $n                    = (count($this->extgstates) + 1);
        $this->extgstates[$n] = ['parameters' => $parameters];
        if ($this->inXObject) {
            // we are inside an XObject template
            $this->xObjects[$this->xObjectID]['extgstates'][$n] = $this->extgstates[$n];
        }
        return $n;
    }

    /**
     * Add an extgstate
     * @param int $gs extgstate
     */
    protected function setExtGState($gs)
    {
        if ($this->state !== self::READY) {
            // transparency is not allowed in PDF/A-1 mode
            return;
        }
        $this->append(sprintf('/GS%d gs', $gs));
    }

    /**
     * Put extgstates for object transparency
     */
    protected function _putextgstates()
    {
        foreach ($this->extgstates as $i => $ext) {
            $this->extgstates[$i]['n'] = $this->appendObject();
            $out                       = '<< /Type /ExtGState';
            foreach ($ext['parameters'] as $k => $v) {
                if (is_float($v)) {
                    $v = sprintf('%F', $v);
                }
                elseif ($v === true) {
                    $v = 'true';
                }
                elseif ($v === false) {
                    $v = 'false';
                }
                $out .= ' /' . $k . ' ' . $v;
            }
            $out .= ' >>';
            $out .= "\n" . 'endobj';
            $this->append($out);
        }
    }

    /**
     * Set overprint mode for stroking (OP) and non-stroking (op) painting operations.
     * (Check the "Entries in a Graphics State Parameter Dictionary" on PDF 32000-1:2008).
     * @param bool      $stroking    If true apply overprint for stroking operations.
     * @param bool|null $nonstroking If true apply overprint for painting operations other than stroking.
     * @param int       $mode        Overprint mode: (0 = each source colour component value replaces the value previously painted for the corresponding device colorant; 1 = a tint value of 0.0 for a source colour component shall leave the corresponding component of the previously painted colour unchanged).
     */
    public function setOverprint($stroking = true, $nonstroking = null, $mode = 0)
    {
        if ($this->state !== self::READY) {
            return;
        }
        $stroking = $stroking ? true : false;
        if (StaticMethods::empty_string($nonstroking)) {
            // default value if not set
            $nonstroking = $stroking;
        }
        else {
            $nonstroking = $nonstroking ? true : false;
        }
        if (($mode != 0) and ($mode != 1)) {
            $mode = 0;
        }
        $this->overprint = ['OP' => $stroking, 'op' => $nonstroking, 'OPM' => $mode];
        $gs              = $this->addExtGState($this->overprint);
        $this->setExtGState($gs);
    }

    /**
     * Get the overprint mode array (OP, op, OPM).
     * (Check the "Entries in a Graphics State Parameter Dictionary" on PDF 32000-1:2008).
     * @return array<string,bool|int>
     */
    public function getOverprint()
    {
        return $this->overprint;
    }

    /**
     * Set the default JPEG compression quality (1-100)
     * @param int $quality JPEG quality, integer between 1 and 100
     */
    public function setJPEGQuality($quality)
    {
        if (($quality < 1) or ($quality > 100)) {
            $quality = 75;
        }
        $this->jpeg_quality = intval($quality);
    }

    /**
     * Set the default number of columns in a row for HTML tables.
     * @param int $cols number of columns
     */
    public function setDefaultTableColumns($cols = 4)
    {
        $this->default_table_columns = intval($cols);
    }

    /**
     * Set the height of the cell (line height) respect the font height.
     * @param float $h cell proportion respect font height (typical value = 1.25).
     */
    public function setCellHeightRatio($h)
    {
        $this->cellHeightRatio = $h;
    }

    /**
     * return the height of cell repect font height.
     * @return float
     */
    public function getCellHeightRatio()
    {
        return $this->cellHeightRatio;
    }

    /**
     * Set the viewer preferences dictionary controlling the way the document is to be presented on the screen or in print.
     * (see Section 8.1 of PDF reference, "Viewer Preferences").
     * <ul><li>HideToolbar bool (Optional) A flag specifying whether to hide the viewer application's tool bars when the document is active. Default value: false.</li><li>HideMenubar bool (Optional) A flag specifying whether to hide the viewer application's menu bar when the document is active. Default value: false.</li><li>HideWindowUI bool (Optional) A flag specifying whether to hide user interface elements in the document's window (such as scroll bars and navigation controls), leaving only the document's contents displayed. Default value: false.</li><li>FitWindow bool (Optional) A flag specifying whether to resize the document's window to fit the size of the first displayed page. Default value: false.</li><li>CenterWindow bool (Optional) A flag specifying whether to position the document's window in the center of the screen. Default value: false.</li><li>DisplayDocTitle bool (Optional; PDF 1.4) A flag specifying whether the window's title bar should display the document title taken from the Title entry of the document information dictionary (see Section 10.2.1, "Document Information Dictionary"). If false, the title bar should instead display the name of the PDF file containing the document. Default value: false.</li><li>NonFullScreenPageMode name (Optional) The document's page mode, specifying how to display the document on exiting full-screen mode:<ul><li>UseNone Neither document outline nor thumbnail images visible</li><li>UseOutlines Document outline visible</li><li>UseThumbs Thumbnail images visible</li><li>UseOC Optional content group panel visible</li></ul>This entry is meaningful only if the value of the PageMode entry in the catalog dictionary (see Section 3.6.1, "Document Catalog") is FullScreen; it is ignored otherwise. Default value: UseNone.</li><li>ViewArea name (Optional; PDF 1.4) The name of the page boundary representing the area of a page to be displayed when viewing the document on the screen. Valid values are (see Section 10.10.1, "Page Boundaries").:<ul><li>MediaBox</li><li>CropBox (default)</li><li>BleedBox</li><li>TrimBox</li><li>ArtBox</li></ul></li><li>ViewClip name (Optional; PDF 1.4) The name of the page boundary to which the contents of a page are to be clipped when viewing the document on the screen. Valid values are (see Section 10.10.1, "Page Boundaries").:<ul><li>MediaBox</li><li>CropBox (default)</li><li>BleedBox</li><li>TrimBox</li><li>ArtBox</li></ul></li><li>PrintArea name (Optional; PDF 1.4) The name of the page boundary representing the area of a page to be rendered when printing the document. Valid values are (see Section 10.10.1, "Page Boundaries").:<ul><li>MediaBox</li><li>CropBox (default)</li><li>BleedBox</li><li>TrimBox</li><li>ArtBox</li></ul></li><li>PrintClip name (Optional; PDF 1.4) The name of the page boundary to which the contents of a page are to be clipped when printing the document. Valid values are (see Section 10.10.1, "Page Boundaries").:<ul><li>MediaBox</li><li>CropBox (default)</li><li>BleedBox</li><li>TrimBox</li><li>ArtBox</li></ul></li><li>PrintScaling name (Optional; PDF 1.6) The page scaling option to be selected when a print dialog is displayed for this document. Valid values are: <ul><li>None, which indicates that the print dialog should reflect no page scaling</li><li>AppDefault (default), which indicates that applications should use the current print scaling</li></ul></li><li>Duplex name (Optional; PDF 1.7) The paper handling option to use when printing the file from the print dialog. The following values are valid:<ul><li>Simplex - Print single-sided</li><li>DuplexFlipShortEdge - Duplex and flip on the short edge of the sheet</li><li>DuplexFlipLongEdge - Duplex and flip on the long edge of the sheet</li></ul>Default value: none</li><li>PickTrayByPDFSize bool (Optional; PDF 1.7) A flag specifying whether the PDF page size is used to select the input paper tray. This setting influences only the preset values used to populate the print dialog presented by a PDF viewer application. If PickTrayByPDFSize is true, the check box in the print dialog associated with input paper tray is checked. Note: This setting has no effect on Mac OS systems, which do not provide the ability to pick the input tray by size.</li><li>PrintPageRange array (Optional; PDF 1.7) The page numbers used to initialize the print dialog box when the file is printed. The first page of the PDF file is denoted by 1. Each pair consists of the first and last pages in the sub-range. An odd number of integers causes this entry to be ignored. Negative numbers cause the entire array to be ignored. Default value: as defined by PDF viewer application</li><li>NumCopies integer (Optional; PDF 1.7) The number of copies to be printed when the print dialog is opened for this file. Supported values are the integers 2 through 5. Values outside this range are ignored. Default value: as defined by PDF viewer application, but typically 1</li></ul>
     * @param array $preferences array of options.
     * @author Nicola Asuni
     */
    public function setViewerPreferences($preferences)
    {
        $this->viewer_preferences = $preferences;
    }

    /**
     * Paints color transition registration bars
     * @param float   $x          abscissa of the top left corner of the rectangle.
     * @param float   $y          ordinate of the top left corner of the rectangle.
     * @param float   $w          width of the rectangle.
     * @param float   $h          height of the rectangle.
     * @param boolean $transition if true prints tcolor transitions to white.
     * @param boolean $vertical   if true prints bar vertically.
     * @param string  $colors     colors to print separated by comma. Valid values are: A,W,R,G,B,C,M,Y,K,RGB,CMYK,ALL,ALLSPOT,<SPOT_COLOR_NAME>. Where: A = grayscale black, W = grayscale white, R = RGB red, G RGB green, B RGB blue, C = CMYK cyan, M = CMYK magenta, Y = CMYK yellow, K = CMYK key/black, RGB = RGB registration color, CMYK = CMYK registration color, ALL = Spot registration color, ALLSPOT = print all defined spot colors, <SPOT_COLOR_NAME> = name of the spot color to print.
     * @author Nicola Asuni
     * @since  4.9.000 (2010-03-26)
     * @public
     */
    public function colorRegistrationBar($x, $y, $w, $h, $transition = true, $vertical = false, $colors = 'A,R,G,B,C,M,Y,K')
    {
        if (strpos($colors, 'ALLSPOT') !== false) {
            // expand spot colors
            $spot_colors = '';
            foreach ($this->spot_colors as $spot_color_name => $v) {
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
            $coords = array(0, 0, 0, 1);
            $wb     = $w / $numbars; // bar width
            $hb     = $h; // bar height
            $xd     = $wb; // delta x
            $yd     = 0; // delta y
        }
        else {
            $coords = array(1, 0, 0, 0);
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
                    $col_a = array(255);
                    $col_b = array(0);
                    break;
                }
                case 'W':
                { // WHITE (GRAYSCALE)
                    $col_a = array(0);
                    $col_b = array(255);
                    break;
                }
                case 'R':
                { // RED (RGB)
                    $col_a = array(255, 255, 255);
                    $col_b = array(255, 0, 0);
                    break;
                }
                case 'G':
                { // GREEN (RGB)
                    $col_a = array(255, 255, 255);
                    $col_b = array(0, 255, 0);
                    break;
                }
                case 'B':
                { // BLUE (RGB)
                    $col_a = array(255, 255, 255);
                    $col_b = array(0, 0, 255);
                    break;
                }
                case 'C':
                { // CYAN (CMYK)
                    $col_a = array(0, 0, 0, 0);
                    $col_b = array(100, 0, 0, 0);
                    break;
                }
                case 'M':
                { // MAGENTA (CMYK)
                    $col_a = array(0, 0, 0, 0);
                    $col_b = array(0, 100, 0, 0);
                    break;
                }
                case 'Y':
                { // YELLOW (CMYK)
                    $col_a = array(0, 0, 0, 0);
                    $col_b = array(0, 0, 100, 0);
                    break;
                }
                case 'K':
                { // KEY - BLACK (CMYK)
                    $col_a = array(0, 0, 0, 0);
                    $col_b = array(0, 0, 0, 100);
                    break;
                }
                case 'RGB':
                { // BLACK REGISTRATION (RGB)
                    $col_a = array(255, 255, 255);
                    $col_b = array(0, 0, 0);
                    break;
                }
                case 'CMYK':
                { // BLACK REGISTRATION (CMYK)
                    $col_a = array(0, 0, 0, 0);
                    $col_b = array(100, 100, 100, 100);
                    break;
                }
                case 'ALL':
                { // SPOT COLOR REGISTRATION
                    $col_a = array(0, 0, 0, 0, 'None');
                    $col_b = array(100, 100, 100, 100, 'All');
                    break;
                }
                case 'NONE':
                { // SKIP THIS COLOR
                    $col_a = array(0, 0, 0, 0, 'None');
                    $col_b = array(0, 0, 0, 0, 'None');
                    break;
                }
                default:
                { // SPECIFIC SPOT COLOR NAME
                    $col_a = array(0, 0, 0, 0, 'None');
                    $col_b = TCPDF_COLORS::getSpotColor($col, $this->spot_colors);
                    if ($col_b === false) {
                        // in case of error defaults to the registration color
                        $col_b = array(100, 100, 100, 100, 'All');
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
                    $this->setFillColorArray($col_b);
                    // colored rectangle
                    $this->Rect($xb, $yb, $wb, $hb, 'F', []);
                }
                $xb += $xd;
                $yb += $yd;
            }
        }
    }

    /**
     * Paints crop marks.
     * @param float  $x     abscissa of the crop mark center.
     * @param float  $y     ordinate of the crop mark center.
     * @param float  $w     width of the crop mark.
     * @param float  $h     height of the crop mark.
     * @param string $type  type of crop mark, one symbol per type separated by comma: T = TOP, F = BOTTOM, L = LEFT, R = RIGHT, TL = A = TOP-LEFT, TR = B = TOP-RIGHT, BL = C = BOTTOM-LEFT, BR = D = BOTTOM-RIGHT.
     * @param array  $color crop mark color (default spot registration color).
     * @author Nicola Asuni
     */
    public function cropMark($x, $y, $w, $h, $type = 'T,R,B,L', $color = [100, 100, 100, 100, 'All'])
    {
        $this->lineStyle(array('width' => (0.5 / $this->factor), 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $color));
        $type = strtoupper($type);
        $type = preg_replace('/[^A-Z\-\,]*/', '', $type);
        // split type in single components
        $type  = str_replace('-', ',', $type);
        $type  = str_replace('TL', 'T,L', $type);
        $type  = str_replace('TR', 'T,R', $type);
        $type  = str_replace('BL', 'F,L', $type);
        $type  = str_replace('BR', 'F,R', $type);
        $type  = str_replace('A', 'T,L', $type);
        $type  = str_replace('B', 'T,R', $type);
        $type  = str_replace('T,RO', 'BO', $type);
        $type  = str_replace('C', 'F,L', $type);
        $type  = str_replace('D', 'F,R', $type);
        $crops = explode(',', strtoupper($type));
        // remove duplicates
        $crops = array_unique($crops);
        $dw    = ($w / 4); // horizontal space to leave before the intersection point
        $dh    = ($h / 4); // vertical space to leave before the intersection point
        foreach ($crops as $crop) {
            switch ($crop) {
                case 'T':
                case 'TOP':
                {
                    $x1 = $x;
                    $y1 = ($y - $h);
                    $x2 = $x;
                    $y2 = ($y - $dh);
                    break;
                }
                case 'F':
                case 'BOTTOM':
                {
                    $x1 = $x;
                    $y1 = ($y + $dh);
                    $x2 = $x;
                    $y2 = ($y + $h);
                    break;
                }
                case 'L':
                case 'LEFT':
                {
                    $x1 = ($x - $w);
                    $y1 = $y;
                    $x2 = ($x - $dw);
                    $y2 = $y;
                    break;
                }
                case 'R':
                case 'RIGHT':
                {
                    $x1 = ($x + $dw);
                    $y1 = $y;
                    $x2 = ($x + $w);
                    $y2 = $y;
                    break;
                }
            }
            $this->line($x1, $y1, $x2, $y2);
        }
    }

    /**
     * Paints a registration mark
     * @param float $x      abscissa of the registration mark center.
     * @param float $y      ordinate of the registration mark center.
     * @param float $r      radius of the crop mark.
     * @param bool  $double if true print two concentric crop marks.
     * @param array $cola   crop mark color (default spot registration color 'All').
     * @param array $colb   second crop mark color (default spot registration color 'None').
     * @author Nicola Asuni
     */
    public function registrationMark($x, $y, $r, $double = false, $cola = [100, 100, 100, 100, 'All'], $colb = [0, 0, 0, 0, 'None'])
    {
        $line_style = array('width' => max((0.5 / $this->factor), ($r / 30)), 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => $cola);
        $this->fillColorArray($cola);
        $this->pieSector($x, $y, $r, 90, 180, 'F');
        $this->pieSector($x, $y, $r, 270, 360, 'F');
        $this->circle($x, $y, $r, 0, 360, 'C', $line_style, [], 8);
        if ($double) {
            $ri = $r * 0.5;
            $this->fillColorArray($colb);
            $this->pieSector($x, $y, $ri, 90, 180, 'F');
            $this->pieSector($x, $y, $ri, 270, 360, 'F');
            $this->fillColorArray($cola);
            $this->pieSector($x, $y, $ri, 0, 90, 'F');
            $this->pieSector($x, $y, $ri, 180, 270, 'F');
            $this->circle($x, $y, $ri, 0, 360, 'C', $line_style, [], 8);
        }
    }

    /**
     * Paints a CMYK registration mark
     * @param float $x abscissa of the registration mark center.
     * @param float $y ordinate of the registration mark center.
     * @param float $r radius of the crop mark.
     * @author Nicola Asuni
     */
    public function registrationMarkCMYK($x, $y, $r)
    {
        // line width
        $lw = max((0.5 / $this->factor), ($r / 8));
        // internal radius
        $ri = ($r * 0.6);
        // external radius
        $re = ($r * 1.3);
        // Cyan
        $this->fillColorArray([100, 0, 0, 0]);
        $this->pieSector($x, $y, $ri, 270, 360, 'F');
        // Magenta
        $this->fillColorArray([0, 100, 0, 0]);
        $this->pieSector($x, $y, $ri, 0, 90, 'F');
        // Yellow
        $this->fillColorArray([0, 0, 100, 0]);
        $this->pieSector($x, $y, $ri, 90, 180, 'F');
        // Key - black
        $this->fillColorArray([0, 0, 0, 100]);
        $this->pieSector($x, $y, $ri, 180, 270, 'F');
        // registration color
        $line_style = ['width' => $lw, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => [100, 100, 100, 100, 'All']];
        $this->fillColorArray([100, 100, 100, 100, 'All']);
        // external circle
        $this->circle($x, $y, $r, 0, 360, 'C', $line_style, [], 8);
        // cross lines
        $this->line($x, ($y - $re), $x, ($y - $ri));
        $this->line($x, ($y + $ri), $x, ($y + $re));
        $this->line(($x - $re), $y, ($x - $ri), $y);
        $this->line(($x + $ri), $y, ($x + $re), $y);
    }

    /**
     * Paints a linear colour gradient.
     * @param float $x      abscissa of the top left corner of the rectangle.
     * @param float $y      ordinate of the top left corner of the rectangle.
     * @param float $w      width of the rectangle.
     * @param float $h      height of the rectangle.
     * @param array $col1   first color (Grayscale, RGB or CMYK components).
     * @param array $col2   second color (Grayscale, RGB or CMYK components).
     * @param array $coords array of the form (x1, y1, x2, y2) which defines the gradient vector (see linear_gradient_coords.jpg). The default value is from left to right (x1=0, y1=0, x2=1, y2=0).
     * @author Andreas W\FCrmser, Nicola Asuni
     */
    public function LinearGradient($x, $y, $w, $h, $col1 = [], $col2 = [], $coords = [0, 0, 1, 0])
    {
        $this->Clip($x, $y, $w, $h);
        $this->Gradient(2, $coords, [['color' => $col1, 'offset' => 0, 'exponent' => 1], ['color' => $col2, 'offset' => 1, 'exponent' => 1]], [], false);
    }

    /**
     * Paints a radial colour gradient.
     * @param float $x      abscissa of the top left corner of the rectangle.
     * @param float $y      ordinate of the top left corner of the rectangle.
     * @param float $w      width of the rectangle.
     * @param float $h      height of the rectangle.
     * @param array $col1   first color (Grayscale, RGB or CMYK components).
     * @param array $col2   second color (Grayscale, RGB or CMYK components).
     * @param array $coords array of the form (fx, fy, cx, cy, r) where (fx, fy) is the starting point of the gradient with color1, (cx, cy) is the center of the circle with color2, and r is the radius of the circle (see radial_gradient_coords.jpg). (fx, fy) should be inside the circle, otherwise some areas will not be defined.
     * @author Andreas W\FCrmser, Nicola Asuni
     */
    public function RadialGradient($x, $y, $w, $h, $col1 = [], $col2 = [], $coords = [0.5, 0.5, 0.5, 0.5, 1])
    {
        $this->Clip($x, $y, $w, $h);
        $this->Gradient(3, $coords, [['color' => $col1, 'offset' => 0, 'exponent' => 1], ['color' => $col2, 'offset' => 1, 'exponent' => 1]], [], false);
    }

    /**
     * Paints a coons patch mesh.
     * @param float $x          abscissa of the top left corner of the rectangle.
     * @param float $y          ordinate of the top left corner of the rectangle.
     * @param float $w          width of the rectangle.
     * @param float $h          height of the rectangle.
     * @param array $col1       first color (lower left corner) (RGB components).
     * @param array $col2       second color (lower right corner) (RGB components).
     * @param array $col3       third color (upper right corner) (RGB components).
     * @param array $col4       fourth color (upper left corner) (RGB components).
     * @param array $coords     <ul><li>for one patch mesh: [float x1, float y1, .... float x12, float y12]: 12 pairs of coordinates (normally from 0 to 1) which specify the Bezier control points that define the patch. First pair is the lower left edge point, next is its right control point (control point 2). Then the other points are defined in the order: control point 1, edge point, control point 2 going counter-clockwise around the patch. Last (x12, y12) is the first edge point's left control point (control point 1).</li><li>for two or more patch meshes: array[number of patches]: arrays with the following keys for each patch: f: where to put that patch (0 = first patch, 1, 2, 3 = right, top and left of precedent patch - I didn't figure this out completely - just try and error ;-) points: 12 pairs of coordinates of the Bezier control points as above for the first patch, 8 pairs of coordinates for the following patches, ignoring the coordinates already defined by the precedent patch (I also didn't figure out the order of these - also: try and see what's happening) colors: must be 4 colors for the first patch, 2 colors for the following patches</li></ul>
     * @param array $coords_min minimum value used by the coordinates. If a coordinate's value is smaller than this it will be cut to coords_min. default: 0
     * @param array $coords_max maximum value used by the coordinates. If a coordinate's value is greater than this it will be cut to coords_max. default: 1
     * @param bool  $antialias  A flag indicating whether to filter the shading function to prevent aliasing artifacts.
     * @author Andreas W\FCrmser, Nicola Asuni
     */
    public function CoonsPatchMesh($x, $y, $w, $h, $col1 = [], $col2 = [], $col3 = [], $col4 = [], $coords = [0.00, 0.0, 0.33, 0.00, 0.67, 0.00, 1.00, 0.00, 1.00, 0.33, 1.00, 0.67, 1.00, 1.00, 0.67, 1.00, 0.33, 1.00, 0.00, 1.00, 0.00, 0.67, 0.00, 0.33], $coords_min = 0, $coords_max = 1, $antialias = false)
    {
        if ($this->state !== self::READY) {
            return;
        }
        $this->Clip($x, $y, $w, $h);
        $n                                   = count($this->gradients) + 1;
        $this->gradients[$n]                 = [];
        $this->gradients[$n]['type']         = 6; //coons patch mesh
        $this->gradients[$n]['coords']       = [];
        $this->gradients[$n]['antialias']    = $antialias;
        $this->gradients[$n]['colors']       = [];
        $this->gradients[$n]['transparency'] = false;
        //check the coords array if it is the simple array or the multi patch array
        if (!isset($coords[0]['f'])) {
            //simple array -> convert to multi patch array
            if (!isset($col1[1])) {
                $col1[1] = $col1[2] = $col1[0];
            }
            if (!isset($col2[1])) {
                $col2[1] = $col2[2] = $col2[0];
            }
            if (!isset($col3[1])) {
                $col3[1] = $col3[2] = $col3[0];
            }
            if (!isset($col4[1])) {
                $col4[1] = $col4[2] = $col4[0];
            }
            $patch_array[0]['f']              = 0;
            $patch_array[0]['points']         = $coords;
            $patch_array[0]['colors'][0]['r'] = $col1[0];
            $patch_array[0]['colors'][0]['g'] = $col1[1];
            $patch_array[0]['colors'][0]['b'] = $col1[2];
            $patch_array[0]['colors'][1]['r'] = $col2[0];
            $patch_array[0]['colors'][1]['g'] = $col2[1];
            $patch_array[0]['colors'][1]['b'] = $col2[2];
            $patch_array[0]['colors'][2]['r'] = $col3[0];
            $patch_array[0]['colors'][2]['g'] = $col3[1];
            $patch_array[0]['colors'][2]['b'] = $col3[2];
            $patch_array[0]['colors'][3]['r'] = $col4[0];
            $patch_array[0]['colors'][3]['g'] = $col4[1];
            $patch_array[0]['colors'][3]['b'] = $col4[2];
        }
        else {
            //multi patch array
            $patch_array = $coords;
        }
        $bpcd = 65535; //16 bits per coordinate
        //build the data stream
        $this->gradients[$n]['stream'] = '';
        $count_patch                   = count($patch_array);
        for ($i = 0; $i < $count_patch; ++$i) {
            $this->gradients[$n]['stream'] .= chr($patch_array[$i]['f']); //start with the edge flag as 8 bit
            $count_points                  = count($patch_array[$i]['points']);
            for ($j = 0; $j < $count_points; ++$j) {
                //each point as 16 bit
                $patch_array[$i]['points'][$j] = (($patch_array[$i]['points'][$j] - $coords_min) / ($coords_max - $coords_min)) * $bpcd;
                if ($patch_array[$i]['points'][$j] < 0) {
                    $patch_array[$i]['points'][$j] = 0;
                }
                if ($patch_array[$i]['points'][$j] > $bpcd) {
                    $patch_array[$i]['points'][$j] = $bpcd;
                }
                $this->gradients[$n]['stream'] .= chr((int) floor($patch_array[$i]['points'][$j] / 256));
                $this->gradients[$n]['stream'] .= chr((int) floor(intval($patch_array[$i]['points'][$j]) % 256));
            }
            $count_cols = count($patch_array[$i]['colors']);
            for ($j = 0; $j < $count_cols; ++$j) {
                //each color component as 8 bit
                $this->gradients[$n]['stream'] .= chr($patch_array[$i]['colors'][$j]['r']);
                $this->gradients[$n]['stream'] .= chr($patch_array[$i]['colors'][$j]['g']);
                $this->gradients[$n]['stream'] .= chr($patch_array[$i]['colors'][$j]['b']);
            }
        }
        //paint the gradient
        $this->append('/Sh' . $n . ' sh');
        //restore previous Graphic State
        $this->appendRestoreGraphicsState();
        if ($this->inXObject) {
            // we are inside an XObject template
            $this->xObjects[$this->xObjectID]['gradients'][$n] = $this->gradients[$n];
        }
    }

    /**
     * Set a rectangular clipping area.
     * @param float $x abscissa of the top left corner of the rectangle.
     * @param float $y ordinate of the top left corner of the rectangle.
     * @param float $w width of the rectangle.
     * @param float $h height of the rectangle.
     * @author Andreas W\FCrmser, Nicola Asuni
     */
    protected function Clip($x, $y, $w, $h)
    {
        if ($this->state !== self::READY) {
            return;
        }
        //save current Graphic State
        $s = 'q';
        //set clipping area
        $s .= sprintf(' %F %F %F %F re W n', $x * $this->factor, ($this->height - $y) * $this->factor, $w * $this->factor, -$h * $this->factor);
        //set up transformation matrix for gradient
        $s .= sprintf(' %F 0 0 %F %F %F cm', $w * $this->factor, $h * $this->factor, $x * $this->factor, ($this->height - ($y + $h)) * $this->factor);
        $this->append($s);
    }

    /**
     * Output gradient.
     * @param int   $type       type of gradient (1 Function-based shading; 2 Axial shading; 3 Radial shading; 4 Free-form Gouraud-shaded triangle mesh; 5 Lattice-form Gouraud-shaded triangle mesh; 6 Coons patch mesh; 7 Tensor-product patch mesh). (Not all types are currently supported)
     * @param array $coords     array of coordinates.
     * @param array $stops      array gradient color components: color = array of GRAY, RGB or CMYK color components; offset = (0 to 1) represents a location along the gradient vector; exponent = exponent of the exponential interpolation function (default = 1).
     * @param array $background An array of colour components appropriate to the colour space, specifying a single background colour value.
     * @param bool  $antialias  A flag indicating whether to filter the shading function to prevent aliasing artifacts.
     * @author Nicola Asuni
     */
    public function Gradient($type, $coords, $stops, $background = [], $antialias = false)
    {
        if ($this->state !== self::READY) {
            return;
        }
        $n                                   = count($this->gradients) + 1;
        $this->gradients[$n]                 = [];
        $this->gradients[$n]['type']         = $type;
        $this->gradients[$n]['coords']       = $coords;
        $this->gradients[$n]['antialias']    = $antialias;
        $this->gradients[$n]['colors']       = [];
        $this->gradients[$n]['transparency'] = false;
        // color space
        $numcolspace = count($stops[0]['color']);
        $bcolor      = array_values($background);
        switch ($numcolspace) {
            case 5:   // SPOT
            case 4:
            { // CMYK
                $this->gradients[$n]['colspace'] = 'DeviceCMYK';
                if (!empty($background)) {
                    $this->gradients[$n]['background'] = sprintf('%F %F %F %F', $bcolor[0] / 100, $bcolor[1] / 100, $bcolor[2] / 100, $bcolor[3] / 100);
                }
                break;
            }
            case 3:
            { // RGB
                $this->gradients[$n]['colspace'] = 'DeviceRGB';
                if (!empty($background)) {
                    $this->gradients[$n]['background'] = sprintf('%F %F %F', $bcolor[0] / 255, $bcolor[1] / 255, $bcolor[2] / 255);
                }
                break;
            }
            case 1:
            { // GRAY SCALE
                $this->gradients[$n]['colspace'] = 'DeviceGray';
                if (!empty($background)) {
                    $this->gradients[$n]['background'] = sprintf('%F', $bcolor[0] / 255);
                }
                break;
            }
        }
        $num_stops    = count($stops);
        $last_stop_id = $num_stops - 1;
        foreach ($stops as $key => $stop) {
            $this->gradients[$n]['colors'][$key] = [];
            // offset represents a location along the gradient vector
            if (isset($stop['offset'])) {
                $this->gradients[$n]['colors'][$key]['offset'] = $stop['offset'];
            }
            else {
                if ($key == 0) {
                    $this->gradients[$n]['colors'][$key]['offset'] = 0;
                }
                elseif ($key == $last_stop_id) {
                    $this->gradients[$n]['colors'][$key]['offset'] = 1;
                }
                else {
                    $offsetstep                                    = (1 - $this->gradients[$n]['colors'][($key - 1)]['offset']) / ($num_stops - $key);
                    $this->gradients[$n]['colors'][$key]['offset'] = $this->gradients[$n]['colors'][($key - 1)]['offset'] + $offsetstep;
                }
            }
            if (isset($stop['opacity'])) {
                $this->gradients[$n]['colors'][$key]['opacity'] = $stop['opacity'];
                if ($stop['opacity'] < 1) {
                    $this->gradients[$n]['transparency'] = true;
                }
            }
            else {
                $this->gradients[$n]['colors'][$key]['opacity'] = 1;
            }
            // exponent for the exponential interpolation function
            if (isset($stop['exponent'])) {
                $this->gradients[$n]['colors'][$key]['exponent'] = $stop['exponent'];
            }
            else {
                $this->gradients[$n]['colors'][$key]['exponent'] = 1;
            }
            // set colors
            $color = array_values($stop['color']);
            switch ($numcolspace) {
                case 5:   // SPOT
                case 4:
                { // CMYK
                    $this->gradients[$n]['colors'][$key]['color'] = sprintf('%F %F %F %F', $color[0] / 100, $color[1] / 100, $color[2] / 100, $color[3] / 100);
                    break;
                }
                case 3:
                { // RGB
                    $this->gradients[$n]['colors'][$key]['color'] = sprintf('%F %F %F', $color[0] / 255, $color[1] / 255, $color[2] / 255);
                    break;
                }
                case 1:
                { // GRAY SCALE
                    $this->gradients[$n]['colors'][$key]['color'] = sprintf('%F', $color[0] / 255);
                    break;
                }
            }
        }
        if ($this->gradients[$n]['transparency']) {
            // paint luminosity gradient
            $this->append('/TGS' . $n . ' gs');
        }
        //paint the gradient
        $this->append('/Sh' . $n . ' sh');
        //restore previous Graphic State
        $this->appendRestoreGraphicsState();
        if ($this->inXObject) {
            // we are inside an XObject template
            $this->xObjects[$this->xObjectID]['gradients'][$n] = $this->gradients[$n];
        }
    }

    /**
     * Output gradient shaders.
     * @author Nicola Asuni
     */
    function _putshaders()
    {
        $idt = count($this->gradients); //index for transparency gradients
        foreach ($this->gradients as $id => $grad) {
            if (($grad['type'] == 2) or ($grad['type'] == 3)) {
                $fc        = $this->appendObject();
                $out       = '<<';
                $out       .= ' /FunctionType 3';
                $out       .= ' /Domain [0 1]';
                $functions = '';
                $bounds    = '';
                $encode    = '';
                $i         = 1;
                $num_cols  = count($grad['colors']);
                $lastcols  = $num_cols - 1;
                for ($i = 1; $i < $num_cols; ++$i) {
                    $functions .= ($fc + $i) . ' 0 R ';
                    if ($i < $lastcols) {
                        $bounds .= sprintf('%F ', $grad['colors'][$i]['offset']);
                    }
                    $encode .= '0 1 ';
                }
                $out .= ' /Functions [' . trim($functions) . ']';
                $out .= ' /Bounds [' . trim($bounds) . ']';
                $out .= ' /Encode [' . trim($encode) . ']';
                $out .= ' >>';
                $out .= "\n" . 'endobj';
                $this->append($out);
                for ($i = 1; $i < $num_cols; ++$i) {
                    $this->appendObject();
                    $out = '<<';
                    $out .= ' /FunctionType 2';
                    $out .= ' /Domain [0 1]';
                    $out .= ' /C0 [' . $grad['colors'][($i - 1)]['color'] . ']';
                    $out .= ' /C1 [' . $grad['colors'][$i]['color'] . ']';
                    $out .= ' /N ' . $grad['colors'][$i]['exponent'];
                    $out .= ' >>';
                    $out .= "\n" . 'endobj';
                    $this->append($out);
                }
                // set transparency functions
                if ($grad['transparency']) {
                    $ft        = $this->appendObject();
                    $out       = '<<';
                    $out       .= ' /FunctionType 3';
                    $out       .= ' /Domain [0 1]';
                    $functions = '';
                    $i         = 1;
                    $num_cols  = count($grad['colors']);
                    for ($i = 1; $i < $num_cols; ++$i) {
                        $functions .= ($ft + $i) . ' 0 R ';
                    }
                    $out .= ' /Functions [' . trim($functions) . ']';
                    $out .= ' /Bounds [' . trim($bounds) . ']';
                    $out .= ' /Encode [' . trim($encode) . ']';
                    $out .= ' >>';
                    $out .= "\n" . 'endobj';
                    $this->append($out);
                    for ($i = 1; $i < $num_cols; ++$i) {
                        $this->appendObject();
                        $out = '<<';
                        $out .= ' /FunctionType 2';
                        $out .= ' /Domain [0 1]';
                        $out .= ' /C0 [' . $grad['colors'][($i - 1)]['opacity'] . ']';
                        $out .= ' /C1 [' . $grad['colors'][$i]['opacity'] . ']';
                        $out .= ' /N ' . $grad['colors'][$i]['exponent'];
                        $out .= ' >>';
                        $out .= "\n" . 'endobj';
                        $this->append($out);
                    }
                }
            }
            // set shading object
            $this->appendObject();
            $out = '<< /ShadingType ' . $grad['type'];
            if (isset($grad['colspace'])) {
                $out .= ' /ColorSpace /' . $grad['colspace'];
            }
            else {
                $out .= ' /ColorSpace /DeviceRGB';
            }
            if (isset($grad['background']) and !empty($grad['background'])) {
                $out .= ' /Background [' . $grad['background'] . ']';
            }
            if (isset($grad['antialias']) and ($grad['antialias'] === true)) {
                $out .= ' /AntiAlias true';
            }
            if ($grad['type'] == 2) {
                $out .= ' ' . sprintf('/Coords [%F %F %F %F]', $grad['coords'][0], $grad['coords'][1], $grad['coords'][2], $grad['coords'][3]);
                $out .= ' /Domain [0 1]';
                $out .= ' /Function ' . $fc . ' 0 R';
                $out .= ' /Extend [true true]';
                $out .= ' >>';
            }
            elseif ($grad['type'] == 3) {
                //x0, y0, r0, x1, y1, r1
                //at this this time radius of inner circle is 0
                $out .= ' ' . sprintf('/Coords [%F %F 0 %F %F %F]', $grad['coords'][0], $grad['coords'][1], $grad['coords'][2], $grad['coords'][3], $grad['coords'][4]);
                $out .= ' /Domain [0 1]';
                $out .= ' /Function ' . $fc . ' 0 R';
                $out .= ' /Extend [true true]';
                $out .= ' >>';
            }
            elseif ($grad['type'] == 6) {
                $out    .= ' /BitsPerCoordinate 16';
                $out    .= ' /BitsPerComponent 8';
                $out    .= ' /Decode[0 1 0 1 0 1 0 1 0 1]';
                $out    .= ' /BitsPerFlag 8';
                $stream = $grad['stream'];
                $out    .= ' /Length ' . strlen($stream);
                $out    .= ' >>';
                $out    .= ' stream' . "\n" . $stream . "\n" . 'endstream';
            }
            $out .= "\n" . 'endobj';
            $this->append($out);
            if ($grad['transparency']) {
                $shading_transparency = preg_replace('/\/ColorSpace \/[^\s]+/si', '/ColorSpace /DeviceGray', $out);
                $shading_transparency = preg_replace('/\/Function [0-9]+ /si', '/Function ' . $ft . ' ', $shading_transparency);
            }
            $this->gradients[$id]['id'] = $this->objectNumber;
            // set pattern object
            $this->appendObject();
            $out = '<< /Type /Pattern /PatternType 2';
            $out .= ' /Shading ' . $this->gradients[$id]['id'] . ' 0 R';
            $out .= ' >>';
            $out .= "\n" . 'endobj';
            $this->append($out);
            $this->gradients[$id]['pattern'] = $this->objectNumber;
            // set shading and pattern for transparency mask
            if ($grad['transparency']) {
                // luminosity pattern
                $idgs = $id + $idt;
                $this->appendObject();
                $this->append($shading_transparency);
                $this->gradients[$idgs]['id'] = $this->objectNumber;
                $this->appendObject();
                $out = '<< /Type /Pattern /PatternType 2';
                $out .= ' /Shading ' . $this->gradients[$idgs]['id'] . ' 0 R';
                $out .= ' >>';
                $out .= "\n" . 'endobj';
                $this->append($out);
                $this->gradients[$idgs]['pattern'] = $this->objectNumber;
                // luminosity XObject
                $oid                         = $this->appendObject();
                $this->xObjects['LX' . $oid] = ['n' => $oid];
                $filter                      = '';
                $stream                      = 'q /a0 gs /Pattern cs /p' . $idgs . ' scn 0 0 ' . $this->widthPT . ' ' . $this->heightPT . ' re f Q';
                $filter                      = ' /Filter /FlateDecode';
                $stream                      = gzcompress($stream);
                $out                         = '<< /Type /XObject /Subtype /Form /FormType 1' . $filter;
                $out                         .= ' /Length ' . strlen($stream);
                $rect                        = sprintf('%F %F', $this->widthPT, $this->heightPT);
                $out                         .= ' /BBox [0 0 ' . $rect . ']';
                $out                         .= ' /Group << /Type /Group /S /Transparency /CS /DeviceGray >>';
                $out                         .= ' /Resources <<';
                $out                         .= ' /ExtGState << /a0 << /ca 1 /CA 1 >> >>';
                $out                         .= ' /Pattern << /p' . $idgs . ' ' . $this->gradients[$idgs]['pattern'] . ' 0 R >>';
                $out                         .= ' >>';
                $out                         .= ' >> ';
                $out                         .= ' stream' . "\n" . $stream . "\n" . 'endstream';
                $out                         .= "\n" . 'endobj';
                $this->append($out);
                // SMask
                $this->appendObject();
                $out = '<< /Type /Mask /S /Luminosity /G ' . ($this->objectNumber - 1) . ' 0 R >>' . "\n" . 'endobj';
                $this->append($out);
                // ExtGState
                $this->appendObject();
                $out = '<< /Type /ExtGState /SMask ' . ($this->objectNumber - 1) . ' 0 R /AIS false >>' . "\n" . 'endobj';
                $this->append($out);
                $this->extgstates[] = ['n' => $this->objectNumber, 'name' => 'TGS' . $id];
            }
        }
    }

    /**
     * Print 2D Barcode.
     * @param string     $code    code to print
     * @param string     $type    type of barcode (see tcpdf_barcodes_2d.php for supported formats).
     * @param float|null $x       x position in user units
     * @param float|null $y       y position in user units
     * @param float|null $w       width in user units
     * @param float|null $h       height in user units
     * @param array      $style
     * @param string     $align   Indicates the alignment of the pointer next to barcode insertion relative to barcode height. The value can be:<ul><li>T: top-right</li><li>M: middle-right</li><li>B: bottom-right</li><li>N: next line</li></ul>
     * @param bool       $distort if true distort the barcode to fit width and height, otherwise preserve aspect ratio
     * @see self::BARCODE_STYLE_DESCRIPTIONS
     */
    public function write2DBarcode($code, $type, $x = null, $y = null, $w = null, $h = null, $style = [], $align = '', $distort = false)
    {
        if (StaticMethods::empty_string(trim($code))) {
            return;
        }
        // save current graphic settings
        $gvars = $this->graphicsSettings();
        // create new barcode object
        $barcodeobj = new Barcode2D($code, $type);
        $arrcode    = $barcodeobj->barcode();
        if (empty($arrcode) or !isset($arrcode['num_rows']) or ($arrcode['num_rows'] == 0) or !isset($arrcode['num_cols']) or ($arrcode['num_cols'] == 0)) {
            $this->Error('Error in 2D barcode string');
        }
        // set default values
        if (!isset($style['position'])) {
            $style['position'] = '';
        }
        if (!isset($style['fgColor'])) {
            $style['fgColor'] = self::BLACK;
        }
        if (!isset($style['bgColor'])) {
            $style['bgColor'] = false; // default transparent
        }
        if (!isset($style['border'])) {
            $style['border'] = false;
        }
        // padding
        if (!isset($style['padding'])) {
            $style['padding'] = 0;
        }
        elseif ($style['padding'] === 'auto') {
            $style['padding'] = 4;
        }
        if (!isset($style['hPadding'])) {
            $style['hPadding'] = $style['padding'];
        }
        elseif ($style['hPadding'] === 'auto') {
            $style['hPadding'] = 4;
        }
        if (!isset($style['vPadding'])) {
            $style['vPadding'] = $style['padding'];
        }
        elseif ($style['vPadding'] === 'auto') {
            $style['vPadding'] = 4;
        }
        $hpad = (2 * $style['hPadding']);
        $vpad = (2 * $style['vPadding']);
        // cell (module) dimension
        if (!isset($style['moduleWidth'])) {
            $style['moduleWidth'] = 1; // width of a single module in points
        }
        if (!isset($style['moduleHeight'])) {
            $style['moduleHeight'] = 1; // height of a single module in points
        }
        if (StaticMethods::empty_string($x)) {
            $x = $this->x;
        }
        if (StaticMethods::empty_string($y)) {
            $y = $this->y;
        }
        // check page for no-write regions and adapt page margins if necessary
        list($x, $y) = $this->checkPageRegions($h, $x, $y);
        // number of barcode columns and rows
        $rows = $arrcode['num_rows'];
        $cols = $arrcode['num_cols'];
        if (($rows <= 0) || ($cols <= 0)) {
            $this->Error('Error in 2D barcode string');
        }
        // module width and height
        $mw = $style['moduleWidth'];
        $mh = $style['moduleHeight'];
        if (($mw <= 0) or ($mh <= 0)) {
            $this->Error('Error in 2D barcode string');
        }
        // get max dimensions
        $maxw    = $this->width - $this->rightMargin - $x;
        $maxh    = ($this->height - $this->topMargin - $this->bottomMargin);
        $ratioHW = ((($rows * $mh) + $hpad) / (($cols * $mw) + $vpad));
        $ratioWH = ((($cols * $mw) + $vpad) / (($rows * $mh) + $hpad));
        if (!$distort) {
            if (($maxw * $ratioHW) > $maxh) {
                $maxw = $maxh * $ratioWH;
            }
            if (($maxh * $ratioWH) > $maxw) {
                $maxh = $maxw * $ratioHW;
            }
        }
        // set maximum dimensions
        if ($w > $maxw) {
            $w = $maxw;
        }
        if ($h > $maxh) {
            $h = $maxh;
        }
        // set dimensions
        if ((StaticMethods::empty_string($w) or ($w <= 0)) and (StaticMethods::empty_string($h) or ($h <= 0))) {
            $w = ($cols + $hpad) * ($mw / $this->factor);
            $h = ($rows + $vpad) * ($mh / $this->factor);
        }
        elseif (($w === '') or ($w <= 0)) {
            $w = $h * $ratioWH;
        }
        elseif (($h === '') or ($h <= 0)) {
            $h = $w * $ratioHW;
        }
        // barcode size (excluding padding)
        $bw = ($w * $cols) / ($cols + $hpad);
        $bh = ($h * $rows) / ($rows + $vpad);
        // dimension of single barcode cell unit
        $cw = $bw / $cols;
        $ch = $bh / $rows;
        if (!$distort) {
            if (($cw / $ch) > ($mw / $mh)) {
                // correct horizontal distortion
                $cw                = $ch * $mw / $mh;
                $bw                = $cw * $cols;
                $style['hPadding'] = ($w - $bw) / (2 * $cw);
            }
            else {
                // correct vertical distortion
                $ch                = $cw * $mh / $mw;
                $bh                = $ch * $rows;
                $style['vPadding'] = ($h - $bh) / (2 * $ch);
            }
        }
        // fit the barcode on available space
        list($w, $h, $x, $y) = $this->fitBlock($w, $h, $x, $y, false);
        // set alignment
        $this->imageRBY = $y + $h;
        // set alignment
        if ($style['position'] == 'L') {
            $xpos = $this->leftMargin;
        }
        elseif ($style['position'] == 'C') {
            $xpos = ($this->width + $this->leftMargin - $this->rightMargin - $w) / 2;
        }
        elseif ($style['position'] == 'R') {
            $xpos = $this->width - $this->rightMargin - $w;
        }
        else {
            $xpos = $x;
        }
        $this->imageRBX = $xpos + $w;
        $xstart         = $xpos + ($style['hPadding'] * $cw);
        $ystart         = $y + ($style['vPadding'] * $ch);
        // print background color
        if ($style['bgColor']) {
            $this->rectangle($xpos, $y, $w, $h, $style['border'] ? 'DF' : 'F', '', $style['bgColor']);
        }
        elseif ($style['border']) {
            $this->rectangle($xpos, $y, $w, $h, 'D');
        }
        // set foreground color
        $this->drawColorArray($style['fgColor']);
        // print barcode cells
        // for each row
        for ($r = 0; $r < $rows; ++$r) {
            $xr = $xstart;
            // for each column
            for ($c = 0; $c < $cols; ++$c) {
                if ($arrcode['bcode'][$r][$c] == 1) {
                    // draw a single barcode cell
                    $this->rectangle($xr, $ystart, $cw, $ch, 'F', [], $style['fgColor']);
                }
                $xr += $cw;
            }
            $ystart += $ch;
        }
        // restore previous settings
        $this->graphicsSettings($gvars);
        // set pointer to align the next text/objects
        switch ($align) {
            case 'T':
            {
                $this->y = $y;
                $this->x = $this->imageRBX;
                break;
            }
            case 'M':
            {
                $this->y = $y + round($h / 2);
                $this->x = $this->imageRBX;
                break;
            }
            case 'B':
            {
                $this->y = $this->imageRBY;
                $this->x = $this->imageRBX;
                break;
            }
            case 'N':
            {
                $this->y($this->imageRBY);
                break;
            }
            default:
            {
                break;
            }
        }
        $this->endlinex = $this->imageRBX;
    }

    /**
     * Returns an array containing original margins:
     * <ul>
     * <li>$ret['left'] = left margin</li>
     * <li>$ret['right'] = right margin</li>
     * </ul>
     * @return array containing all margins measures
     */
    public function getOriginalMargins()
    {
        $ret = [
            'left'  => $this->originalLeftMargin,
            'right' => $this->originalRightMargin
        ];
        return $ret;
    }

    /**
     * Returns the string used to find spaces
     * @return string
     * @author Nicola Asuni
     */
    protected function getSpaceString()
    {
        return chr(32);
    }

    /**
     * Return an hash code used to ensure that the serialized data has been generated by this TCPDF instance.
     * @param string $data serialized data
     * @return string
     */
    protected function getHashForTCPDFtagParams($data)
    {
        return md5(strlen($data) . $this->fileID . $data);
    }

    /**
     * Serialize an array of parameters to be used with TCPDF tag in HTML code.
     * @param array $data parameters array
     * @return string containing serialized data
     */
    public function serializeTCPDFtagParameters($data)
    {
        $encoded = urlencode(json_encode($data));
        return $this->getHashForTCPDFtagParams($encoded) . $encoded;
    }

    /**
     * Unserialize parameters to be used with TCPDF tag in HTML code.
     * @param string $data serialized data
     * @return array containing unserialized data
     */
    protected function unserializeTCPDFtagParameters($data)
    {
        $hash    = substr($data, 0, 32);
        $encoded = substr($data, 32);
        if ($hash != $this->getHashForTCPDFtagParams($encoded)) {
            $this->Error('Invalid parameters');
        }
        return json_decode(urldecode($encoded), true);
    }

    /**
     * Return the starting coordinates to draw an html border
     * @return array containing top-left border coordinates
     */
    protected function getBorderStartPosition()
    {
        $xmax = $this->width - $this->rightMargin;
        return ['page' => $this->page, 'column' => $this->currentColumn, 'x' => $this->x, 'y' => $this->y, 'xmax' => $xmax];
    }

    /**
     * Set the default bullet to be used as LI bullet symbol
     * @param string $symbol character or string to be used (legal values are: '' = automatic, '!' = auto bullet, '#' = auto numbering, 'disc', 'disc', 'circle', 'square', '1', 'decimal', 'decimal-leading-zero', 'i', 'lower-roman', 'I', 'upper-roman', 'a', 'lower-alpha', 'lower-latin', 'A', 'upper-alpha', 'upper-latin', 'lower-greek', 'img|type|width|height|image.ext')
     */
    public function setLIsymbol($symbol = '!')
    {
        // check for custom image symbol
        if (substr($symbol, 0, 4) == 'img|') {
            $this->lisymbol = $symbol;
            return;
        }
        $symbol        = strtolower($symbol);
        $valid_symbols = ['!', '#', 'disc', 'circle', 'square', '1', 'decimal', 'decimal-leading-zero', 'i', 'lower-roman', 'I', 'upper-roman', 'a', 'lower-alpha', 'lower-latin', 'A', 'upper-alpha', 'upper-latin', 'lower-greek'];
        if (in_array($symbol, $valid_symbols)) {
            $this->lisymbol = $symbol;
        }
        else {
            $this->lisymbol = '';
        }
    }

    /**
     * Set the booklet mode for double-sided pages.
     * @param bool  $booklet true set the booklet mode on, false otherwise.
     * @param float $inner   Inner page margin.
     * @param float $outer   Outer page margin.
     */
    public function setBooklet($booklet = true, $inner = -1, $outer = -1)
    {
        $this->booklet = $booklet;
        if ($inner >= 0) {
            $this->leftMargin = $inner;
        }
        if ($outer >= 0) {
            $this->rightMargin = $outer;
        }
    }

    /**
     * Swap the left and right margins.
     * @param bool $reverse if true swap left and right margins.
     */
    protected function swapMargins($reverse = true)
    {
        if ($reverse) {
            // swap left and right margins
            $mtemp                     = $this->originalLeftMargin;
            $this->originalLeftMargin  = $this->originalRightMargin;
            $this->originalRightMargin = $mtemp;
            $deltam                    = $this->originalLeftMargin - $this->originalRightMargin;
            $this->leftMargin          += $deltam;
            $this->rightMargin         -= $deltam;
        }
    }

    /**
     * Set the vertical spaces for HTML tags.
     * The array must have the following structure (example):
     * $tagvs = array('h1' => [0 => ['h' => '', 'n' => 2], 1 => ['h' => 1.3, 'n' => 1]]);
     * The first array level contains the tag names,
     * the second level contains 0 for opening tags or 1 for closing tags,
     * the third level contains the vertical space unit (h) and the number spaces to add (n).
     * If the h parameter is not specified, default values are used.
     * @param array $tagvs array of tags and relative vertical spaces.
     */
    public function setHtmlVSpace($tagvs)
    {
        $this->tagvspaces = $tagvs;
    }

    /**
     * Set custom width for list indentation.
     * @param float $width width of the indentation. Use negative value to disable it.
     */
    public function setListIndentWidth($width)
    {
        return $this->customlistindent = floatval($width);
    }

    /**
     * Set the top/bottom cell sides to be open or closed when the cell cross the page.
     * @param bool $isopen if true keeps the top/bottom border open for the cell sides that cross the page.
     */
    public function setOpenCell($isopen)
    {
        $this->opencell = $isopen;
    }

    /**
     * Replace the buffer content
     * @param string $data data
     */
    protected function replaceBuffer($data)
    {
        $this->bufferLength = strlen($data);
        $this->buffer       = $data;
    }

    /**
     * Stores a copy of the current TCPDF object used for undo operation.
     */
    public function startTransaction()
    {
        if (isset($this->clone)) {
            // remove previous copy
            $this->commitTransaction();
        }
        // record current page number and Y position
        $this->start_transaction_page = $this->page;
        $this->start_transaction_y    = $this->y;
        // clone current object
        $this->clone = @clone($this);
    }

    /**
     * Delete the copy of the current TCPDF object used for undo operation.
     */
    public function commitTransaction()
    {
        if (isset($this->clone)) {
            $this->clone->_destroy(true, true);
            $this->clone->fileID = NULL;
            unset($this->clone);
        }
    }

    /**
     * This method allows to undo the latest transaction by returning the latest saved TCPDF object with startTransaction().
     * @param bool $self if true restores current class object to previous state without the need of reassignment via the returned value.
     * @return PDF object.
     */
    public function rollbackTransaction($self = false)
    {
        if (isset($this->clone)) {
            $clone = $this->clone;
            $this->unsetProperties(true, true);
            if ($self) {
                $objvars = get_object_vars($clone);
                foreach ($objvars as $key => $value) {
                    $this->$key = $value;
                }
                $clone->_destroy(true, true);
                $clone->fileID = NULL;
                unset($clone);
                return $this;
            }
            $this->fileID = NULL;
            return $clone;
        }
        return $this;
    }

    // --- MULTI COLUMNS METHODS -----------------------

    /**
     * Set multiple columns of the same size
     * @param int      $numcols number of columns (set to zero to disable columns mode)
     * @param int      $width   column width
     * @param int|null $y       column starting Y position (leave empty for current Y position)
     */
    public function setEqualColumns($numcols = 0, $width = 0, $y = null)
    {
        $this->columns = [];
        if ($numcols < 2) {
            $numcols       = 0;
            $this->columns = [];
        }
        else {
            // maximum column width
            $maxwidth = ($this->width - $this->originalLeftMargin - $this->originalRightMargin) / $numcols;
            if (($width == 0) or ($width > $maxwidth)) {
                $width = $maxwidth;
            }
            if (StaticMethods::empty_string($y)) {
                $y = $this->y;
            }
            // space between columns
            $space = (($this->width - $this->originalLeftMargin - $this->originalRightMargin - ($numcols * $width)) / ($numcols - 1));
            // fill the columns array (with, space, starting Y position)
            for ($i = 0; $i < $numcols; ++$i) {
                $this->columns[$i] = ['w' => $width, 's' => $space, 'y' => $y];
            }
        }
        $this->columnCount       = $numcols;
        $this->currentColumn     = 0;
        $this->column_start_page = $this->page;
        $this->selectColumn(0);
    }

    /**
     * Set position at a given column
     * @param int|null $col column number (from 0 to getNumberOfColumns()-1); empty string = current column.
     */
    public function selectColumn($col = null)
    {
        if (StaticMethods::empty_string($col)) {
            $col = $this->currentColumn;
        }
        elseif ($col >= $this->columnCount) {
            $col = 0;
        }
        $xshift       = ['x' => 0, 's' => ['H' => 0, 'V' => 0], 'p' => ['L' => 0, 'T' => 0, 'R' => 0, 'B' => 0]];
        $enable_thead = false;
        if ($this->columnCount > 1) {
            if ($col != $this->currentColumn) {
                // move Y pointer at the top of the column
                if ($this->column_start_page == $this->page) {
                    $this->y = $this->columns[$col]['y'];
                }
                else {
                    $this->y = $this->topMargin;
                }
                // Avoid to write table headers more than once
                if (($this->page > $this->maxselcol['page']) or (($this->page == $this->maxselcol['page']) and ($col > $this->maxselcol['column']))) {
                    $enable_thead              = true;
                    $this->maxselcol['page']   = $this->page;
                    $this->maxselcol['column'] = $col;
                }
            }
            $xshift = $this->colxshift;
            // set X position of the current column by case
            $listindent = ($this->listindentlevel * $this->listindent);
            // calculate column X position
            $colpos = 0;
            for ($i = 0; $i < $col; ++$i) {
                $colpos += ($this->columns[$i]['w'] + $this->columns[$i]['s']);
            }
            $x                        = $this->originalLeftMargin + $colpos;
            $this->leftMargin         = ($x + $listindent);
            $this->rightMargin        = ($this->width - $x - $this->columns[$col]['w']);
            $this->x                  = $x + $listindent;
            $this->columns[$col]['x'] = $x;
        }
        $this->currentColumn = $col;
        // fix for HTML mode
        $this->newLine = true;
        // print HTML table header (if any)
        if ((!StaticMethods::empty_string($this->headerTemplate)) and (!$this->inthead)) {
            if ($enable_thead) {
                // print table header
                $this->writeHTML($this->headerTemplate, false, false, false, false, '');
                $this->y += $xshift['s']['V'];
                // store end of header position
                if (!isset($this->columns[$col]['th'])) {
                    $this->columns[$col]['th'] = [];
                }
                $this->columns[$col]['th']['\'' . $this->page . '\''] = $this->y;
                $this->lastPrintedCellHeight                          = 0;
            }
            elseif (isset($this->columns[$col]['th']['\'' . $this->page . '\''])) {
                $this->y = $this->columns[$col]['th']['\'' . $this->page . '\''];
            }
        }
        // account for an html table cell over multiple columns
        $this->leftMargin += $xshift['x'];
        $this->x          += $xshift['x'] + $xshift['p']['L'];
    }

    /**
     * Set Text rendering mode.
     * @param int  $stroke outline size in user units (0 = disable).
     * @param bool $fill   if true fills the text (default).
     * @param bool $clip   if true activate clipping mode
     */
    public function setTextRenderingMode($stroke = 0, $fill = true, $clip = false)
    {
        // Ref.: PDF 32000-1:2008 - 9.3.6 Text Rendering Mode
        // convert text rendering parameters
        if ($stroke < 0 || !is_numeric($stroke)) {
            $stroke = 0;
        }
        if ($fill === true) {
            if ($stroke > 0) {
                if ($clip === true) {
                    // Fill, then stroke text and add to path for clipping
                    $textrendermode = 6;
                }
                else {
                    // Fill, then stroke text
                    $textrendermode = 2;
                }
                $textstrokewidth = $stroke;
            }
            else {
                if ($clip === true) {
                    // Fill text and add to path for clipping
                    $textrendermode = 4;
                }
                else {
                    // Fill text
                    $textrendermode = 0;
                }
            }
        }
        else {
            if ($stroke > 0) {
                if ($clip === true) {
                    // Stroke text and add to path for clipping
                    $textrendermode = 5;
                }
                else {
                    // Stroke text
                    $textrendermode = 1;
                }
                $textstrokewidth = $stroke;
            }
            else {
                if ($clip === true) {
                    // Add text to path for clipping
                    $textrendermode = 7;
                }
                else {
                    // Neither fill nor stroke text (invisible)
                    $textrendermode = 3;
                }
            }
        }
        $this->textrendermode  = $textrendermode;
        $this->textstrokewidth = $stroke;
    }

    /**
     * Set parameters for drop shadow effect for text.
     * @param array $params Array of parameters: enabled (bool) set to true to enable shadow; depth_w (float) shadow width in user units; depth_h (float) shadow height in user units; color (array) shadow color or false to use the stroke color; opacity (float) Alpha value: real value from 0 (transparent) to 1 (opaque); blend_mode (string) blend mode, one of the following: Normal, Multiply, Screen, Overlay, Darken, Lighten, ColorDodge, ColorBurn, HardLight, SoftLight, Difference, Exclusion, Hue, Saturation, Color, Luminosity.
     */
    public function setTextShadow($params = ['enabled' => false, 'depth_w' => 0, 'depth_h' => 0, 'color' => false, 'opacity' => 1, 'blend_mode' => 'Normal'])
    {
        if (isset($params['enabled'])) {
            $this->txtshadow['enabled'] = $params['enabled'] ? true : false;
        }
        else {
            $this->txtshadow['enabled'] = false;
        }
        if (isset($params['depth_w'])) {
            $this->txtshadow['depth_w'] = floatval($params['depth_w']);
        }
        else {
            $this->txtshadow['depth_w'] = 0;
        }
        if (isset($params['depth_h'])) {
            $this->txtshadow['depth_h'] = floatval($params['depth_h']);
        }
        else {
            $this->txtshadow['depth_h'] = 0;
        }
        if (isset($params['color']) and ($params['color'] !== false) and is_array($params['color'])) {
            $this->txtshadow['color'] = $params['color'];
        }
        else {
            $this->txtshadow['color'] = $this->strokeColor;
        }
        if (isset($params['opacity'])) {
            $this->txtshadow['opacity'] = min(1, max(0, floatval($params['opacity'])));
        }
        else {
            $this->txtshadow['opacity'] = 1;
        }
        if (isset($params['blend_mode']) and in_array($params['blend_mode'], ['Normal', 'Multiply', 'Screen', 'Overlay', 'Darken', 'Lighten', 'ColorDodge', 'ColorBurn', 'HardLight', 'SoftLight', 'Difference', 'Exclusion', 'Hue', 'Saturation', 'Color', 'Luminosity'])) {
            $this->txtshadow['blend_mode'] = $params['blend_mode'];
        }
        else {
            $this->txtshadow['blend_mode'] = 'Normal';
        }
        if ((($this->txtshadow['depth_w'] == 0) and ($this->txtshadow['depth_h'] == 0)) or ($this->txtshadow['opacity'] == 0)) {
            $this->txtshadow['enabled'] = false;
        }
    }

    /**
     * Return the text shadow parameters array.
     * @return array array of parameters.
     */
    public function getTextShadow()
    {
        return $this->txtshadow;
    }

    /**
     * Returns an array of chars containing soft hyphens.
     * @param array $word       array of chars
     * @param array $patterns   Array of hypenation patterns.
     * @param array $dictionary Array of words to be returned without applying the hyphenation algorithm.
     * @param int   $leftmin    Minimum number of character to leave on the left of the word without applying the hyphens.
     * @param int   $rightmin   Minimum number of character to leave on the right of the word without applying the hyphens.
     * @param int   $charmin    Minimum word length to apply the hyphenation algorithm.
     * @param int   $charmax    Maximum length of broken piece of word.
     * @return array text with soft hyphens
     * @author Nicola Asuni
     */
    protected function hyphenateWord($word, $patterns, $dictionary = [], $leftmin = 1, $rightmin = 2, $charmin = 1, $charmax = 8)
    {
        $hyphenword = []; // hyphens positions
        $numchars   = count($word);
        if ($numchars <= $charmin) {
            return $word;
        }
        $word_string = $this->utf8SliceToString($word, '', '');
        // some words will be returned as-is
        $pattern = '/^([a-zA-Z0-9_\.\-]+)@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/';
        if (preg_match($pattern, $word_string) > 0) {
            // email
            return $word;
        }
        $pattern = '/(([a-zA-Z0-9\-]+\.)?)((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.)|(([a-zA-Z0-9\-]+\.)+))([a-zA-Z]{2,4}|[0-9]{1,3})(\]?)$/';
        if (preg_match($pattern, $word_string) > 0) {
            // URL
            return $word;
        }
        if (isset($dictionary[$word_string])) {
            return $this->utf8StringToArray($dictionary[$word_string]);
        }
        // surround word with '_' characters
        $tmpword     = array_merge([46], $word, [46]);
        $tmpnumchars = $numchars + 2;
        $maxpos      = $tmpnumchars - 1;
        for ($pos = 0; $pos < $maxpos; ++$pos) {
            $imax = min(($tmpnumchars - $pos), $charmax);
            for ($i = 1; $i <= $imax; ++$i) {
                $subword = strtolower($this->utf8SliceToString($tmpword, $pos, ($pos + $i)));
                if (isset($patterns[$subword])) {
                    $pattern        = $this->utf8StringToArray($patterns[$subword]);
                    $pattern_length = count($pattern);
                    $digits         = 1;
                    for ($j = 0; $j < $pattern_length; ++$j) {
                        // check if $pattern[$j] is a number = hyphenation level (only numbers from 1 to 5 are valid)
                        if (($pattern[$j] >= 48) and ($pattern[$j] <= 57)) {
                            if ($j == 0) {
                                $zero = $pos - 1;
                            }
                            else {
                                $zero = $pos + $j - $digits;
                            }
                            // get hyphenation level
                            $level = ($pattern[$j] - 48);
                            // if two levels from two different patterns match at the same point, the higher one is selected.
                            if (!isset($hyphenword[$zero]) or ($hyphenword[$zero] < $level)) {
                                $hyphenword[$zero] = $level;
                            }
                            ++$digits;
                        }
                    }
                }
            }
        }
        $inserted = 0;
        $maxpos   = $numchars - $rightmin;
        for ($i = $leftmin; $i <= $maxpos; ++$i) {
            // only odd levels indicate allowed hyphenation points
            if (isset($hyphenword[$i]) and (($hyphenword[$i] % 2) != 0)) {
                // 173 = soft hyphen character
                array_splice($word, $i + $inserted, 0, 173);
                ++$inserted;
            }
        }
        return $word;
    }

    /**
     * Returns text with soft hyphens.
     * @param string $text       text to process
     * @param mixed  $patterns   Array of hypenation patterns or a TEX file containing hypenation patterns. TEX patterns can be downloaded from http://www.ctan.org/tex-archive/language/hyph-utf8/tex/generic/hyph-utf8/patterns/
     * @param array  $dictionary Array of words to be returned without applying the hyphenation algorithm.
     * @param int    $leftmin    Minimum number of character to leave on the left of the word without applying the hyphens.
     * @param int    $rightmin   Minimum number of character to leave on the right of the word without applying the hyphens.
     * @param int    $charmin    Minimum word length to apply the hyphenation algorithm.
     * @param int    $charmax    Maximum length of broken piece of word.
     * @return string text with soft hyphens
     * @author Nicola Asuni
     */
    public function hyphenateText($text, $patterns, $dictionary = [], $leftmin = 1, $rightmin = 2, $charmin = 1, $charmax = 8)
    {
        $text   = $this->unhtmlentities($text);
        $word   = []; // last word
        $txtarr = []; // text to be returned
        $intag  = false; // true if we are inside an HTML tag
        $skip   = false; // true to skip hyphenation
        // get array of characters
        $unichars = $this->utf8StringToArray($text);
        // for each char
        foreach ($unichars as $char) {
            if ((!$intag) and (!$skip) and self::UNICODE_TYPES[$char] == 'L') {
                // letter character
                $word[] = $char;
            }
            else {
                // other type of character
                if (!StaticMethods::empty_string($word)) {
                    // hypenate the word
                    $txtarr = array_merge($txtarr, $this->hyphenateWord($word, $patterns, $dictionary, $leftmin, $rightmin, $charmin, $charmax));
                    $word   = [];
                }
                $txtarr[] = $char;
                if (chr($char) == '<') {
                    // we are inside an HTML tag
                    $intag = true;
                }
                elseif ($intag and (chr($char) == '>')) {
                    // end of HTML tag
                    $intag = false;
                    // check for style tag
                    $expected = [115, 116, 121, 108, 101]; // = 'style'
                    $current  = array_slice($txtarr, -6, 5); // last 5 chars
                    $compare  = array_diff($expected, $current);
                    if (empty($compare)) {
                        // check if it is a closing tag
                        $expected = [47]; // = '/'
                        $current  = array_slice($txtarr, -7, 1);
                        $compare  = array_diff($expected, $current);
                        if (empty($compare)) {
                            // closing style tag
                            $skip = false;
                        }
                        else {
                            // opening style tag
                            $skip = true;
                        }
                    }
                }
            }
        }
        if (!StaticMethods::empty_string($word)) {
            // hypenate the word
            $txtarr = array_merge($txtarr, $this->hyphenateWord($word, $patterns, $dictionary, $leftmin, $rightmin, $charmin, $charmax));
        }
        // convert char array to string and return
        return $this->utf8SliceToString($txtarr, '', '');
    }

    /**
     * Start a new XObject Template.
     * An XObject Template is a PDF block that is a self-contained description of any sequence of graphics objects (including path objects, text objects, and sampled images).
     * An XObject Template may be painted multiple times, either on several pages or at several locations on the same page and produces the same results each time, subject only to the graphics state at the time it is invoked.
     * Note: X,Y coordinates will be reset to 0,0.
     * @param int   $w     Template width in user units (empty string or zero = page width less margins).
     * @param int   $h     Template height in user units (empty string or zero = page height less margins).
     * @param mixed $group Set transparency group. Can be a bool value or an array specifying optional parameters: 'CS' (solour space name), 'I' (bool flag to indicate isolated group) and 'K' (bool flag to indicate knockout group).
     * @return string|false the XObject Template ID in case of success or false in case of error.
     * @author Nicola Asuni
     * @see    endTemplate(), printTemplate()
     */
    public function startTemplate($w = 0, $h = 0, $group = false)
    {
        if ($this->inXObject) {
            // we are already inside an XObject template
            return false;
        }
        $this->inXObject = true;
        ++$this->objectNumber;
        // XObject ID
        $this->xObjectID = 'XT' . $this->objectNumber;
        // object ID
        $this->xObjects[$this->xObjectID] = ['n' => $this->objectNumber];
        // store current graphic state
        $this->xObjects[$this->xObjectID]['gvars'] = $this->graphicsSettings();
        // initialize data
        $this->xObjects[$this->xObjectID]['intmrk']         = 0;
        $this->xObjects[$this->xObjectID]['transpositions'] = [];
        $this->xObjects[$this->xObjectID]['content']        = '';
        $this->xObjects[$this->xObjectID]['xobjects']       = [];
        $this->xObjects[$this->xObjectID]['images']         = [];
        $this->xObjects[$this->xObjectID]['fonts']          = [];
        $this->xObjects[$this->xObjectID]['annotations']    = [];
        $this->xObjects[$this->xObjectID]['extgstates']     = [];
        $this->xObjects[$this->xObjectID]['gradients']      = [];
        $this->xObjects[$this->xObjectID]['spot_colors']    = [];
        // set new environment
        $this->columnCount   = 1;
        $this->currentColumn = 0;
        $this->autoPageBreak(false);
        if (($w === '') or ($w <= 0)) {
            $w = $this->width - $this->leftMargin - $this->rightMargin;
        }
        if (($h === '') or ($h <= 0)) {
            $h = $this->height - $this->topMargin - $this->bottomMargin;
        }
        $this->xObjects[$this->xObjectID]['x'] = 0;
        $this->xObjects[$this->xObjectID]['y'] = 0;
        $this->xObjects[$this->xObjectID]['w'] = $w;
        $this->xObjects[$this->xObjectID]['h'] = $h;
        $this->width                           = $w;
        $this->height                          = $h;
        $this->widthPT                         = $this->width * $this->factor;
        $this->heightPT                        = $this->height * $this->factor;
        $this->formatWidthPT                   = $this->widthPT;
        $this->formatHeightPT                  = $this->heightPT;
        $this->x                               = 0;
        $this->y                               = 0;
        $this->leftMargin                      = 0;
        $this->rightMargin                     = 0;
        $this->topMargin                       = 0;
        $this->bottomMargin                    = 0;
        // set group mode
        $this->xObjects[$this->xObjectID]['group'] = $group;
        return $this->xObjectID;
    }

    /**
     * End the current XObject Template started with startTemplate() and restore the previous graphic state.
     * An XObject Template is a PDF block that is a self-contained description of any sequence of graphics objects (including path objects, text objects, and sampled images).
     * An XObject Template may be painted multiple times, either on several pages or at several locations on the same page and produces the same results each time, subject only to the graphics state at the time it is invoked.
     * @return string|false the XObject Template ID in case of success or false in case of error.
     * @author Nicola Asuni
     * @see    startTemplate(), printTemplate()
     */
    public function endTemplate()
    {
        if (!$this->inXObject) {
            // we are not inside a template
            return false;
        }
        $this->inXObject = false;
        // restore previous graphic state
        $this->graphicsSettings($this->xObjects[$this->xObjectID]['gvars'], true);
        return $this->xObjectID;
    }

    /**
     * Print an XObject Template.
     * You can print an XObject Template inside the currently opened Template.
     * An XObject Template is a PDF block that is a self-contained description of any sequence of graphics objects (including path objects, text objects, and sampled images).
     * An XObject Template may be painted multiple times, either on several pages or at several locations on the same page and produces the same results each time, subject only to the graphics state at the time it is invoked.
     * @param string     $id        The ID of XObject Template to print.
     * @param float|null $x         X position in user units (empty string = current x position)
     * @param float|null $y         Y position in user units (empty string = current y position)
     * @param float      $w         Width in user units (zero = remaining page width)
     * @param float      $h         Height in user units (zero = remaining page height)
     * @param string     $align     Indicates the alignment of the pointer next to template insertion relative to template height. The value can be:<ul><li>T: top-right</li><li>M: middle-right</li><li>B: bottom-right</li><li>N: next line</li></ul>
     * @param string     $palign    Allows to center or align the template on the current line. Possible values are:<ul><li>L : left align</li><li>C : center</li><li>R : right align</li><li>'' : empty string : left</li></ul>
     * @param bool       $fitonpage If true the template is resized to not exceed page dimensions.
     * @author Nicola Asuni
     * @see    startTemplate(), endTemplate()
     */
    public function printTemplate($id, $x = null, $y = null, $w = 0, $h = 0, $align = '', $palign = '', $fitonpage = false)
    {
        if ($this->state !== self::READY) {
            return;
        }
        if (!isset($this->xObjects[$id])) {
            $this->Error('The XObject Template \'' . $id . '\' doesn\'t exist!');
        }
        if ($this->inXObject) {
            if ($id == $this->xObjectID) {
                // close current template
                $this->endTemplate();
            }
            else {
                // use the template as resource for the template currently opened
                $this->xObjects[$this->xObjectID]['xobjects'][$id] = $this->xObjects[$id];
            }
        }
        // set default values
        if (StaticMethods::empty_string($x)) {
            $x = $this->x;
        }
        if (StaticMethods::empty_string($y)) {
            $y = $this->y;
        }
        // check page for no-write regions and adapt page margins if necessary
        list($x, $y) = $this->checkPageRegions($h, $x, $y);
        $ow = $this->xObjects[$id]['w'];
        if ($ow <= 0) {
            $ow = 1;
        }
        $oh = $this->xObjects[$id]['h'];
        if ($oh <= 0) {
            $oh = 1;
        }
        // calculate template width and height on document
        if (($w <= 0) and ($h <= 0)) {
            $w = $ow;
            $h = $oh;
        }
        elseif ($w <= 0) {
            $w = $h * $ow / $oh;
        }
        elseif ($h <= 0) {
            $h = $w * $oh / $ow;
        }
        // fit the template on available space
        list($w, $h, $x, $y) = $this->fitBlock($w, $h, $x, $y, $fitonpage);
        // set page alignment
        $rb_y = $y + $h;
        // set alignment
        if ($palign == 'L') {
            $xt = $this->leftMargin;
        }
        elseif ($palign == 'C') {
            $xt = ($this->width + $this->leftMargin - $this->rightMargin - $w) / 2;
        }
        elseif ($palign == 'R') {
            $xt = $this->width - $this->rightMargin - $w;
        }
        else {
            $xt = $x;
        }
        $rb_x = $xt + $w;
        // print XObject Template + Transformation matrix
        $this->startTransformation();
        // translate and scale
        $sx    = ($w / $ow);
        $sy    = ($h / $oh);
        $tm    = [];
        $tm[0] = $sx;
        $tm[1] = 0;
        $tm[2] = 0;
        $tm[3] = $sy;
        $tm[4] = $xt * $this->factor;
        $tm[5] = ($this->height - $h - $y) * $this->factor;
        $this->transform($tm);
        // set object
        $this->append('/' . $id . ' Do');
        $this->endTransformation();
        // add annotations
        if (!empty($this->xObjects[$id]['annotations'])) {
            foreach ($this->xObjects[$id]['annotations'] as $annot) {
                // transform original coordinates
                $coordlt = $this->getTransformationMatrixProduct($tm, array(1, 0, 0, 1, ($annot['x'] * $this->factor), (-$annot['y'] * $this->factor)));
                $ax      = ($coordlt[4] / $this->factor);
                $ay      = ($this->height - $h - ($coordlt[5] / $this->factor));
                $coordrb = $this->getTransformationMatrixProduct($tm, array(1, 0, 0, 1, (($annot['x'] + $annot['w']) * $this->factor), ((-$annot['y'] - $annot['h']) * $this->factor)));
                $aw      = ($coordrb[4] / $this->factor) - $ax;
                $ah      = ($this->height - $h - ($coordrb[5] / $this->factor)) - $ay;
                $this->Annotation($ax, $ay, $aw, $ah, $annot['text'], $annot['opt'], $annot['spaces']);
            }
        }
        // set pointer to align the next text/objects
        switch ($align) {
            case 'T':
            {
                $this->y = $y;
                $this->x = $rb_x;
                break;
            }
            case 'M':
            {
                $this->y = $y + round($h / 2);
                $this->x = $rb_x;
                break;
            }
            case 'B':
            {
                $this->y = $rb_y;
                $this->x = $rb_x;
                break;
            }
            case 'N':
            {
                $this->y($rb_y);
                break;
            }
            default:
            {
                break;
            }
        }
    }

    /**
     * Set the percentage of character stretching.
     * @param int $perc percentage of stretching (100 = no stretching)
     * @author Nicola Asuni
     */
    public function setFontStretching($perc = 100)
    {
        $this->fontStretching = $perc;
    }

    /**
     * Get the percentage of character stretching.
     * @return float stretching value
     * @author Nicola Asuni
     */
    public function getFontStretching()
    {
        return $this->fontStretching;
    }

    /**
     * Set the amount to increase or decrease the space between characters in a text.
     * @param float $spacing amount to increase or decrease the space between characters in a text (0 = default spacing)
     * @author Nicola Asuni
     */
    public function setFontSpacing($spacing = 0)
    {
        $this->fontSpacing = $spacing;
    }

    /**
     * Get the amount to increase or decrease the space between characters in a text.
     * @return int font spacing (tracking) value
     * @author Nicola Asuni
     * @public
     * @since  5.9.000 (2010-09-29)
     */
    public function getFontSpacing()
    {
        return $this->font_spacing;
    }

    /**
     * Return an array of no-write page regions
     * @return array of no-write page regions
     * @author Nicola Asuni
     * @see    setPageRegions(), addPageRegion()
     */
    public function getPageRegions()
    {
        return $this->pageRegions;
    }

    /**
     * Set no-write regions on page.
     * A no-write region is a portion of the page with a rectangular or trapezium shape that will not be covered when writing text or html code.
     * A region is always aligned on the left or right side of the page ad is defined using a vertical segment.
     * You can set multiple regions for the same page.
     * @param array $regions array of no-write regions. For each region you can define an array as follow: ('page' => page number or empy for current page, 'xt' => X top, 'yt' => Y top, 'xb' => X bottom, 'yb' => Y bottom, 'side' => page side 'L' = left or 'R' = right). Omit this parameter to remove all regions.
     * @author Nicola Asuni
     * @see    addPageRegion(), getPageRegions()
     */
    public function setPageRegions($regions = [])
    {
        // empty current regions array
        $this->pageRegions = [];
        // add regions
        foreach ($regions as $data) {
            $this->addPageRegion($data);
        }
    }

    /**
     * Add a single no-write region on selected page.
     * A no-write region is a portion of the page with a rectangular or trapezium shape that will not be covered when writing text or html code.
     * A region is always aligned on the left or right side of the page ad is defined using a vertical segment.
     * You can set multiple regions for the same page.
     * @param array $region array of a single no-write region array: ('page' => page number or empy for current page, 'xt' => X top, 'yt' => Y top, 'xb' => X bottom, 'yb' => Y bottom, 'side' => page side 'L' = left or 'R' = right).
     * @author Nicola Asuni
     * @see    setPageRegions(), getPageRegions()
     */
    public function addPageRegion($region)
    {
        if (!isset($region['page']) or empty($region['page'])) {
            $region['page'] = $this->page;
        }
        if (isset($region['xt']) and isset($region['xb']) and ($region['xt'] > 0) and ($region['xb'] > 0)
            and isset($region['yt']) and isset($region['yb']) and ($region['yt'] >= 0) and ($region['yt'] < $region['yb'])
            and isset($region['side']) and (($region['side'] == 'L') or ($region['side'] == 'R'))) {
            $this->pageRegions[] = $region;
        }
    }

    /**
     * Remove a single no-write region.
     * @param int $key region key
     * @author Nicola Asuni
     * @public
     * @since  5.9.003 (2010-10-13)
     * @see    setPageRegions(), getPageRegions()
     */
    public function removePageRegion($key)
    {
        if (isset($this->pageRegions[$key])) {
            unset($this->pageRegions[$key]);
        }
    }

    /**
     * Keeps files in memory, so it doesn't need to downloaded everytime in a loop
     * @param string $file
     * @return string
     */
    protected function getCachedFileContents($file)
    {
        if (!isset($this->fileContentCache[$file])) {
            $this->fileContentCache[$file] = $this->readContents($file);
        }
        return $this->fileContentCache[$file];
    }

    /**
     * Avoid multiple calls to an external server to see if a file exists
     * @param string $file
     * @return bool
     */
    protected function fileExists($file)
    {
        if (isset($this->fileContentCache[$file]) or $this->imageBuffer($file)) {
            return true;
        }

        return @file_exists($file);
    }

    /**
     * Replace a char if is defined on the current font.
     * @param int $oldchar Integer code (unicode) of the character to replace.
     * @param int $newchar Integer code (unicode) of the new character.
     * @return int the replaced char or the old char in case the new char i not defined
     * @protected
     * @since 5.9.167 (2012-06-22)
     */
    protected function replaceChar($oldchar, $newchar)
    {
        if ($this->isCharDefined($newchar)) {
            // add the new char on the subset list
            $this->CurrentFont['subsetchars'][$newchar] = true;
            // return the new character
            return $newchar;
        }
        // return the old char
        return $oldchar;
    }

    /**
     * Defines the default monospaced font.
     * @param string $font Font name.
     * @public
     * @since 4.5.025
     */
    public function setDefaultMonospacedFont($font)
    {
        $this->default_monospaced_font = $font;
    }

    /**
     * Return true in the character is present in the specified font.
     * @param mixed  $char  Character to check (integer value or string)
     * @param string $font  Font name (family name).
     * @param string $style Font style.
     * @return bool true if the char is defined, false otherwise.
     * @public
     * @since 5.9.153 (2012-03-28)
     */
    public function isCharDefined($char, $font = '', $style = '')
    {
        if (is_string($char)) {
            // get character code
            $char = $this->utf8StringToArray($char);
            $char = $char[0];
        }
        if (StaticMethods::empty_string($font)) {
            if (StaticMethods::empty_string($style)) {
                return (isset($this->CurrentFont['cw'][intval($char)]));
            }
            $font = $this->FontFamily;
        }
        $fontdata = $this->AddFont($font, $style);
        $fontinfo = $this->fontBuffer($fontdata['fontkey']);
        return (isset($fontinfo['cw'][intval($char)]));
    }

    /**
     * Replace missing font characters on selected font with specified substitutions.
     * @param string $text  Text to process.
     * @param string $font  Font name (family name).
     * @param string $style Font style.
     * @param array  $subs  Array of possible character substitutions. The key is the character to check (integer value) and the value is a single intege value or an array of possible substitutes.
     * @return string Processed text.
     * @public
     * @since 5.9.153 (2012-03-28)
     */
    public function replaceMissingChars($text, $font = '', $style = '', $subs = [])
    {
        if (empty($subs)) {
            return $text;
        }
        if (TCPDF_STATIC::empty_string($font)) {
            $font = $this->FontFamily;
        }
        $fontdata = $this->AddFont($font, $style);
        $fontinfo = $this->getFontBuffer($fontdata['fontkey']);
        $uniarr   = $this->utf8StringToArray($text);
        foreach ($uniarr as $k => $chr) {
            if (!isset($fontinfo['cw'][$chr])) {
                // this character is missing on the selected font
                if (isset($subs[$chr])) {
                    // we have available substitutions
                    if (is_array($subs[$chr])) {
                        foreach ($subs[$chr] as $s) {
                            if (isset($fontinfo['cw'][$s])) {
                                $uniarr[$k] = $s;
                                break;
                            }
                        }
                    }
                    elseif (isset($fontinfo['cw'][$subs[$chr]])) {
                        $uniarr[$k] = $subs[$chr];
                    }
                }
            }
        }
        return $this->unicodeSliceToString($this->utf8ArrayToUnicodeArray($uniarr));
    }

    /**
     * Return the current column number
     * @return int current column number
     * @public
     * @since 5.5.011 (2010-07-08)
     */
    public function getColumn()
    {
        return $this->currentColumn;
    }

    /**
     * Remove columns and reset page margins.
     * @public
     * @since 5.9.072 (2011-04-26)
     */
    public function resetColumns()
    {
        $this->lMargin = $this->original_lMargin;
        $this->rMargin = $this->original_rMargin;
        $this->setEqualColumns();
    }

    /**
     * Set columns array.
     * Each column is represented by an array of arrays with the following keys: (w = width, s = space between columns, y = column top position).
     * @param array $columns
     * @public
     * @since 4.9.001 (2010-03-28)
     */
    public function setColumnsArray($columns)
    {
        $this->columns           = $columns;
        $this->columnCount       = count($columns);
        $this->currentColumn     = 0;
        $this->column_start_page = $this->page;
        $this->selectColumn(0);
    }

    /**
     * Return the current number of columns.
     * @return int number of columns
     * @public
     * @since 5.8.018 (2010-08-25)
     */
    public function getNumberOfColumns()
    {
        return $this->columnCount;
    }

    /**
     * Return normalized font name
     * @param string $fontfamily property string containing font family names
     * @return string normalized font name
     * @author Nicola Asuni
     * @public
     * @since  5.8.004 (2010-08-17)
     */
    public function getFontFamilyName($fontfamily)
    {
        // remove spaces and symbols
        $fontfamily = preg_replace('/[^a-z0-9_\,]/', '', strtolower($fontfamily));
        // extract all font names
        $fontslist = preg_split('/[,]/', $fontfamily);
        // find first valid font name
        foreach ($fontslist as $font) {
            // replace font variations
            $font = preg_replace('/regular$/', '', $font);
            $font = preg_replace('/italic$/', 'I', $font);
            $font = preg_replace('/oblique$/', 'I', $font);
            $font = preg_replace('/bold([I]?)$/', 'B\\1', $font);
            // replace common family names and core fonts
            $pattern       = [];
            $replacement   = [];
            $pattern[]     = '/^serif|^cursive|^fantasy|^timesnewroman/';
            $replacement[] = 'times';
            $pattern[]     = '/^sansserif/';
            $replacement[] = 'helvetica';
            $pattern[]     = '/^monospace/';
            $replacement[] = 'courier';
            $font          = preg_replace($pattern, $replacement, $font);
            if (in_array(strtolower($font), $this->fontlist) or in_array($font, $this->fontkeys)) {
                return $font;
            }
        }
        // return current font as default
        return $this->CurrentFont['fontkey'];
    }

    /**
     * Set additional XMP data to be added on the default XMP data just before the end of "x:xmpmeta" tag.
     * IMPORTANT: This data is added as-is without controls, so you have to validate your data before using this method!
     * @param string $xmp Custom XMP data.
     * @since 5.9.128 (2011-10-06)
     * @public
     */
    public function setExtraXMP($xmp)
    {
        $this->custom_xmp = $xmp;
    }

    /**
     * Set additional XMP data to be added on the default XMP data just before the end of "rdf:RDF" tag.
     * IMPORTANT: This data is added as-is without controls, so you have to validate your data before using this method!
     * @param string $xmp Custom XMP RDF data.
     * @since 6.3.0 (2019-09-19)
     * @public
     */
    public function setExtraXMPRDF($xmp)
    {
        $this->custom_xmp_rdf = $xmp;
    }

    /**
     * Set the document creation timestamp
     * @param mixed $time Document creation timestamp in seconds or date-time string.
     * @public
     * @since 5.9.152 (2012-03-23)
     */
    public function setDocCreationTimestamp($time)
    {
        if (is_string($time)) {
            $time = TCPDF_STATIC::getTimestamp($time);
        }
        $this->doc_creation_timestamp = intval($time);
    }

    /**
     * Set the document modification timestamp
     * @param mixed $time Document modification timestamp in seconds or date-time string.
     * @public
     * @since 5.9.152 (2012-03-23)
     */
    public function setDocModificationTimestamp($time)
    {
        if (is_string($time)) {
            $time = TCPDF_STATIC::getTimestamp($time);
        }
        $this->doc_modification_timestamp = intval($time);
    }

    /**
     * Returns document creation timestamp in seconds.
     * @return int Creation timestamp in seconds.
     * @public
     * @since 5.9.152 (2012-03-23)
     */
    public function getDocCreationTimestamp()
    {
        return $this->doc_creation_timestamp;
    }

    /**
     * Returns document modification timestamp in seconds.
     * @return int Modfication timestamp in seconds.
     * @public
     * @since 5.9.152 (2012-03-23)
     */
    public function getDocModificationTimestamp()
    {
        return $this->doc_modification_timestamp;
    }

    /**
     * Turn on/off Unicode mode for document information dictionary (meta tags).
     * This has effect only when unicode mode is set to false.
     * @param boolean $unicode if true set the meta information in Unicode
     * @since 5.9.027 (2010-12-01)
     * @public
     */
    public function setDocInfoUnicode($unicode = true)
    {
        $this->docinfounicode = $unicode ? true : false;
    }

    /**
     * Activates or deactivates page compression. When activated, the internal representation of each page is compressed, which leads to a compression ratio of about 2 for the resulting document. Compression is on by default.
     * Note: the Zlib extension is required for this feature. If not present, compression will be turned off.
     * @param boolean $compress Boolean indicating if compression must be enabled.
     * @public
     * @since 1.4
     */
    public function setCompression($compress = true)
    {
        $this->compress = false;
        if (function_exists('gzcompress')) {
            if ($compress) {
                if (!$this->pdfa_mode) {
                    $this->compress = true;
                }
            }
        }
    }

    /**
     * Return the right-bottom (or left-bottom for RTL) corner X coordinate of last inserted image
     * @return float
     * @public
     */
    public function getImageRBX()
    {
        return $this->img_rb_x;
    }

    /**
     * Return the right-bottom (or left-bottom for RTL) corner Y coordinate of last inserted image
     * @return float
     * @public
     */
    public function getImageRBY()
    {
        return $this->img_rb_y;
    }

    /**
     * Sets the font used to print character strings.
     * The font can be either a standard one or a font added via the AddFont() method. Standard fonts use Windows encoding cp1252 (Western Europe).
     * The method can be called before the first page is created and the font is retained from page to page.
     * If you just wish to change the current font size, it is simpler to call SetFontSize().
     * Note: for the standard fonts, the font metric files must be accessible. There are three possibilities for this:<ul><li>They are in the current directory (the one where the running script lies)</li><li>They are in one of the directories defined by the include_path parameter</li><li>They are in the directory defined by the K_PATH_FONTS constant</li></ul><br />
     * @param string     $family   Family font. It can be either a name defined by AddFont() or one of the standard Type1 families (case insensitive):<ul><li>times (Times-Roman)</li><li>timesb (Times-Bold)</li><li>timesi (Times-Italic)</li><li>timesbi (Times-BoldItalic)</li><li>helvetica (Helvetica)</li><li>helveticab (Helvetica-Bold)</li><li>helveticai (Helvetica-Oblique)</li><li>helveticabi (Helvetica-BoldOblique)</li><li>courier (Courier)</li><li>courierb (Courier-Bold)</li><li>courieri (Courier-Oblique)</li><li>courierbi (Courier-BoldOblique)</li><li>symbol (Symbol)</li><li>zapfdingbats (ZapfDingbats)</li></ul> It is also possible to pass an empty string. In that case, the current family is retained.
     * @param string     $style    Font style. Possible values are (case insensitive):<ul><li>empty string: regular</li><li>B: bold</li><li>I: italic</li><li>U: underline</li><li>D: line through</li><li>O: overline</li></ul> or any combination. The default value is regular. Bold and italic styles do not apply to Symbol and ZapfDingbats basic fonts or other fonts when not defined.
     * @param float|null $size     Font size in points. The default value is the current size. If no size has been specified since the beginning of the document, the value taken is 12
     * @param string     $fontfile The font definition file. By default, the name is built from the family and style, in lower case with no spaces.
     * @param mixed      $subset   if true embedd only a subset of the font (stores only the information related to the used characters); if false embedd full font; if 'default' uses the default value set using setFontSubsetting(). This option is valid only for TrueTypeUnicode fonts. If you want to enable users to change the document, set this parameter to false. If you subset the font, the person who receives your PDF would need to have your same font in order to make changes to your PDF. The file size of the PDF would also be smaller because you are embedding only part of a font.
     * @param boolean    $out      if true output the font size command, otherwise only set the font properties.
     * @author Nicola Asuni
     * @public
     * @since  1.0
     * @see    AddFont(), SetFontSize()
     */
    public function setFont($family, $style = '', $size = null, $fontfile = '', $subset = 'default', $out = true)
    {
        //Select a font; size given in points
        if ($size === null) {
            $size = $this->FontSizePt;
        }
        if ($size < 0) {
            $size = 0;
        }
        // try to add font (if not already added)
        $fontdata         = $this->AddFont($family, $style, $fontfile, $subset);
        $this->FontFamily = $fontdata['family'];
        $this->FontStyle  = $fontdata['style'];
        if (isset($this->CurrentFont['fontkey']) and isset($this->CurrentFont['subsetchars'])) {
            // save subset chars of the previous font
            $this->setFontSubBuffer($this->CurrentFont['fontkey'], 'subsetchars', $this->CurrentFont['subsetchars']);
        }
        $this->CurrentFont = $this->getFontBuffer($fontdata['fontkey']);
        $this->setFontSize($size, $out);
    }

    /**
     * Returns the bounding box of the current font in user units.
     * @return array
     * @public
     * @since 5.9.152 (2012-03-23)
     */
    public function getFontBBox()
    {
        $fbbox = [];
        if (isset($this->CurrentFont['desc']['FontBBox'])) {
            $tmpbbox = explode(' ', substr($this->CurrentFont['desc']['FontBBox'], 1, -1));
            $fbbox   = array_map(array($this, 'getAbsFontMeasure'), $tmpbbox);
        }
        else {
            // Find max width
            if (isset($this->CurrentFont['desc']['MaxWidth'])) {
                $maxw = $this->getAbsFontMeasure(intval($this->CurrentFont['desc']['MaxWidth']));
            }
            else {
                $maxw = 0;
                if (isset($this->CurrentFont['desc']['MissingWidth'])) {
                    $maxw = max($maxw, $this->CurrentFont['desc']['MissingWidth']);
                }
                if (isset($this->CurrentFont['desc']['AvgWidth'])) {
                    $maxw = max($maxw, $this->CurrentFont['desc']['AvgWidth']);
                }
                if (isset($this->CurrentFont['dw'])) {
                    $maxw = max($maxw, $this->CurrentFont['dw']);
                }
                foreach ($this->CurrentFont['cw'] as $char => $w) {
                    $maxw = max($maxw, $w);
                }
                if ($maxw == 0) {
                    $maxw = 600;
                }
                $maxw = $this->getAbsFontMeasure($maxw);
            }
            $fbbox = array(0, (0 - $this->FontDescent), $maxw, $this->FontAscent);
        }
        return $fbbox;
    }

    /**
     * Convert a relative font measure into absolute value.
     * @param int $s Font measure.
     * @return float Absolute measure.
     * @since 5.9.186 (2012-09-13)
     */
    public function getAbsFontMeasure($s)
    {
        return ($s * $this->fontSize / 1000);
    }

    /**
     * Returns the glyph bounding box of the specified character in the current font in user units.
     * @param int $char Input character code.
     * @return false|array array(xMin, yMin, xMax, yMax) or FALSE if not defined.
     * @since 5.9.186 (2012-09-13)
     */
    public function getCharBBox($char)
    {
        $c = intval($char);
        if (isset($this->CurrentFont['cw'][$c])) {
            // glyph is defined ... use zero width & height for glyphs without outlines
            $result = array(0, 0, 0, 0);
            if (isset($this->CurrentFont['cbbox'][$c])) {
                $result = $this->CurrentFont['cbbox'][$c];
            }
            return array_map(array($this, 'getAbsFontMeasure'), $result);
        }
        return false;
    }

    /**
     * Returns the current font style.
     * @return string current font style
     * @public
     * @since 4.3.008 (2008-12-05)
     */
    public function getFontStyle()
    {
        return $this->FontStyle;
    }

#endregion
}