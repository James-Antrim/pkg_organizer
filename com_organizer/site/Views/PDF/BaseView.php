<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

/** @noinspection PhpTooManyParametersInspection */

namespace THM\Organizer\Views\PDF;

define('K_PATH_IMAGES', JPATH_ROOT . '/components/com_organizer/images/');

use Joomla\CMS\Application\ApplicationHelper;
use THM\Organizer\Adapters\{Application, Input, User};
use THM\Organizer\Helpers;
use THM\Organizer\Layouts\PDF\BaseLayout;
use THM\Organizer\Models;
use THM\Organizer\Views\Named;
use TCPDF;

/**
 * Base class for a Joomla View
 * Class holding methods for displaying presentation data.
 */
abstract class BaseView extends TCPDF
{
    use Named;

    // Alignment & Borders
    public const ALL = 1,
        BOTTOM = 'B',
        CENTER = 'C',
        GINSBERG = 'RB',
        HORIZONTAL = 'BT',
        JUSTIFY = 'J',
        LEFT = 'L',
        MIDDLE = 'M',
        NONE = 0,
        RIGHT = 'R',
        TOP = 'T',
        VERTICAL = 'LR';

    // Colors
    public const BLACK = [0, 0, 0], WHITE = [255, 255, 255];

    // Destinations
    public const DOWNLOAD = 'D', INLINE = 'I';

    // Font Families
    public const COURIER = 'courier', CURRENT_FAMILY = '', HELVETICA = 'helvetica', TIMES = 'times';

    /**
     * @see FontSizePt
     */
    public const CURRENT_SIZE = 12;

    // Font Styles
    public const BOLD = 'B',
        BOLD_ITALIC = 'BI',
        BOLD_UNDERLINE = 'BU',
        ITALIC = 'I',
        OVERLINE = 'O',
        REGULAR = '',
        STRIKE_THROUGH = 'D',
        UNDERLINE = 'U';

    // Orientation
    public const LANDSCAPE = 'l', PORTRAIT = 'p';

    // UOM point (~0.35 mm)
    public const CENTIMETER = 'cm', INCH = 'in', MILLIMETER = 'mm', POINT = 'pt';

    public $border = ['width' => '.1', 'color' => 220];

    public $dataFont = ['helvetica', '', 8];

    protected $filename;

    protected $headerFont = ['helvetica', '', 10];

    /**
     * @var BaseLayout
     */
    protected $layout;

    /**
     * @var Models\BaseModel
     */
    protected $model;

    /**
     * Performs initial construction of the TCPDF Object.
     *
     * @param   string  $orientation  page orientation
     * @param   string  $unit         unit of measure
     * @param   mixed   $format       page format; possible values: string - common format name, array - parameters
     *
     * @see \TCPDF_STATIC::getPageSizeFromFormat(), setPageFormat()
     */
    public function __construct($orientation = self::PORTRAIT, $unit = 'mm', $format = 'A4')
    {
        $this->authorize();

        parent::__construct($orientation, $unit, $format);
        $this->SetAuthor(User::name());
        $this->setCellPaddings(1, 1.5, 1, 1.5);
        $this->SetCreator('THM Organizer');
        $this->setFooterFont($this->dataFont);
        $this->setHeaderFont($this->headerFont);
        $this->setImageScale(1.25);

        $name   = $this->getName();
        $layout = Input::getCMD('layout', $name);
        $layout = Helpers\OrganizerHelper::classDecode($layout);
        $layout = "THM\\Organizer\\Layouts\\PDF\\$name\\$layout";
        $model  = "THM\\Organizer\\Models\\$name";

        $this->layout = new $layout($this);
        $this->model  = new $model();
    }

    /**
     * Checks user authorization and initiates redirects accordingly.
     * @return void
     */
    protected function authorize()
    {
        if (!Helpers\Can::administrate()) {
            Application::error(403);
        }
    }

    /**
     * Changes the current font settings used for rendering.
     *
     * @param   string  $style   the font style abbreviation
     * @param   int     $size    the font size, document default is 12
     * @param   string  $family  the font family name
     *
     * @return void sets the font attribute values for use in rendering until set otherwise
     */
    public function changeFont(string $style = self::REGULAR,
        int $size = self::CURRENT_SIZE,
        string $family = self::CURRENT_FAMILY)
    {
        $this->SetFont($family, $style, $size);
    }

    /**
     * Defines the abscissa and ordinate of the current position.
     * If the passed values are negative, they are relative respectively to the right and bottom of the page.
     *
     * @param   int  $horizontal  the horizontal coordinate
     * @param   int  $vertical    the vertical coordinate
     *
     * @return void repositions the documents point of reference
     */
    public function changePosition(int $horizontal, int $vertical)
    {
        $this->SetXY($horizontal, $vertical);
    }

    /**
     * Changes the current font size used for rendering.
     *
     * @param   int  $size  the font size
     *
     * @return void sets the font size value for use in rendering until set otherwise
     */
    public function changeSize(int $size)
    {
        $this->SetFontSize($size);
    }

    /**
     * Method to get data from a registered model or a property of the view.
     *
     * @param   string  $property  the model function name or view property to access
     * @param   mixed   $default   the optional default value
     *
     * @return  mixed  The return value of the method
     */
    public function get(string $property, $default = null)
    {
        if ($this->model) {
            $method = 'get' . ucfirst($property);

            if (method_exists($this->model, $method)) {
                return $this->model->$method();
            }
        }

        if (isset($this->$property)) {
            return $this->$property;
        }

        return $default;
    }

    /**
     * Method to generate output. Overwriting functions should place class specific code before the parent call.
     *
     * @param   string  $destination
     *
     * @return void
     */
    public function display(string $destination = self::DOWNLOAD)
    {
        $this->Output($this->filename, $destination);
        ob_flush();
    }

    /**
     * Defines the left, top and right margins.
     *
     * @param   int  $left    the left margin
     * @param   int  $top     the top margin
     * @param   int  $right   the right margin (defaults to left value)
     * @param   int  $bottom  the bottom margin
     * @param   int  $header  the header margin
     * @param   int  $footer  the footer margin
     *
     * @see   SetAutoPageBreak(), SetFooterMargin(), setHeaderMargin(), SetLeftMargin(), SetRightMargin(),
     *        SetTopMargin()
     */
    public function margins(int $left = 15, int $top = 27, int $right = -1, int $bottom = 25, int $header = 5, int $footer = 10)
    {
        $this->SetAutoPageBreak(true, $bottom);
        $this->setFooterMargin($footer);
        $this->setHeaderMargin($header);
        $this->SetMargins($left, $top, $right);
    }

    /**
     * Renders a cell. Borders
     *
     * @param   int     $width   the cell width
     * @param   int     $height  the cell height
     * @param   string  $text    the cell text
     * @param   string  $hAlign  the cell's horizontal alignment
     * @param   mixed   $border  number 0/1: none/all,
     *                           string B/L/R/T: corresponding side
     *                           array border settings coded by side
     * @param   bool    $fill    true if the cell should render a background color, otherwise false
     * @param   string  $vAlign  the cell's vertical alignment
     * @param   mixed   $link    URL or identifier returned by AddLink().
     *
     * @return void renders the cell
     * @see   AddLink()
     */
    public function renderCell(
        int $width,
        int $height,
        string $text,
        string $hAlign = self::LEFT,
        $border = self::NONE,
        bool $fill = false,
        string $vAlign = self::CENTER,
        $link = ''
    )
    {
        $this->Cell($width, $height, $text, $border, 0, $hAlign, $fill, $link, 0, false, self::TOP, $vAlign);
    }

    /**
     * This method allows printing text with line breaks.
     * They can be automatic (as soon as the text reaches the right border of the cell) or explicit (via the \n
     * character). As many cells as necessary are output, one below the other.<br /> Text can be aligned, centered or
     * justified. The cell block can be framed and the background painted.
     *
     * @param   int     $width      the cell width
     * @param   int     $height     the cell height
     * @param   string  $text       the cell text
     * @param   string  $hAlign     the cell's horizontal alignment
     * @param   mixed   $border     number 0/1: none/all,
     *                              string B/L/R/T: corresponding side
     *                              array border settings coded by side
     * @param   bool    $fill       true if the cell should render a background color, otherwise false
     * @param   string  $vAlign     the cell's vertical alignment
     * @param   int     $maxHeight  the maximum height, explicitly set to ensure correct line height in a multi-cell
     *                              row
     *
     * @return int Return the number of cells or 1 for html mode.
     * @see   SetFont(), SetDrawColor(), SetFillColor(), SetTextColor(), SetLineWidth(), Cell(), Write(),
     *        SetAutoPageBreak()
     */
    public function renderMultiCell(
        int $width,
        int $height,
        string $text,
        string $hAlign = self::LEFT,
        $border = self::NONE,
        bool $fill = false,
        string $vAlign = self::MIDDLE,
        int $maxHeight = 0
    ): int
    {
        return $this->MultiCell(
            $width,
            $height,
            $text,
            $border,
            $hAlign,
            $fill,
            0,
            '',
            '',
            true,
            0,
            false,
            true,
            $maxHeight,
            $vAlign,
            false
        );
    }

    /**
     * Wraps the protected function setPageFormat to make it publicly accessible.
     *
     * @param   array|string  $format  the format to set the page to string format constant, [width, height], [options]
     *
     * @return void
     * @see TCPDF::setPageOrientation(), TCPDF::getPageSizeFromFormat()
     */
    public function setFormat($format)
    {
        $this->setPageFormat($format, $this->CurOrientation);
    }

    /**
     * Sets the document title and file name properties. File name defaults to a safe revision of the document title.
     *
     * @param   string  $documentTitle  the document title
     * @param   string  $fileName       the file name
     */
    public function setNames(string $documentTitle, string $fileName = '')
    {
        $this->title    = $documentTitle;
        $fileName       = $fileName ?: $documentTitle;
        $fileName       = preg_replace('/ +/', ' ', $fileName);
        $this->filename = ApplicationHelper::stringURLSafe($fileName) . '.pdf';
    }

    /**
     * Enables display of the document header and footer.
     *
     * @param   bool  $display  true if the document should display a header and footer, otherwise false
     *
     * @see SetPrintFooter(), SetPrintHeader()
     */
    public function showPrintOverhead(bool $display)
    {
        $this->setPrintHeader($display);
        $this->setPrintFooter($display);
    }
}
