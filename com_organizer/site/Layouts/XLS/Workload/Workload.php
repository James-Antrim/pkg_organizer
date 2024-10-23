<?php
/**
 * @package     Organizer\Layouts\XLS\Rooms
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\XLS\Workload;

use Exception;
use PHPExcel_Exception;
use PHPExcel_Worksheet_Drawing;
use THM\Organizer\Adapters\{Input, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Layouts\XLS\BaseLayout;
use THM\Organizer\Models\Workload as Model;
use THM\Organizer\Views\XLS\BaseView;
use THM\Organizer\Views\XLS\XLConstants;

/**
 * Class generates the room statistics XLS file.
 */
class Workload extends BaseLayout
{
    private const ELECTIVE = 'J', EVENTS = 'C', GROUPS = 'I', HOURS = 'K', METHOD = 'F', PROGRAMS = 'H', SWS_IS = 'M',
        SWS_SHOULD = 'G', SUBJECTNOS = 'B';

    /**
     * @var \array[][] Border definitions
     */
    private array $borders = [
        'cell'      => [
            'left'   => [
                'style' => XLConstants::THIN
            ],
            'right'  => [
                'style' => XLConstants::THIN
            ],
            'bottom' => [
                'style' => XLConstants::THIN
            ],
            'top'    => [
                'style' => XLConstants::THIN
            ]
        ],
        'data'      => [
            'left'   => [
                'style' => XLConstants::MEDIUM
            ],
            'right'  => [
                'style' => XLConstants::MEDIUM
            ],
            'bottom' => [
                'style' => XLConstants::THIN
            ],
            'top'    => [
                'style' => XLConstants::THIN
            ]
        ],
        'header'    => [
            'left'   => [
                'style' => XLConstants::MEDIUM
            ],
            'right'  => [
                'style' => XLConstants::MEDIUM
            ],
            'bottom' => [
                'style' => XLConstants::MEDIUM
            ],
            'top'    => [
                'style' => XLConstants::MEDIUM
            ]
        ],
        'signature' => [
            'left'   => [
                'style' => XLConstants::NONE
            ],
            'right'  => [
                'style' => XLConstants::NONE
            ],
            'bottom' => [
                'style' => XLConstants::NONE
            ],
            'top'    => [
                'style' => XLConstants::MEDIUM
            ]
        ]
    ];

    /**
     * @var array[] Fill definitions
     */
    private array $fills = [
        'header' => [
            'type'  => XLConstants::SOLID,
            'color' => ['rgb' => '80BA24']
        ],
        'index'  => [
            'type'  => XLConstants::SOLID,
            'color' => ['rgb' => 'FFFF00']
        ],
        'data'   => [
            'type'  => XLConstants::SOLID,
            'color' => ['rgb' => 'DFEEC8']
        ]
    ];

    /**
     * @var string[] Height definitions
     */
    private array $heights = [
        'basicField'    => '18.75',
        'sectionHead'   => '13.5',
        'sectionSpacer' => '8.25',
        'spacer'        => '6.25',
        'sum'           => '18.75'
    ];

    /**
     * @var int the id of the organization selected
     */
    private int $organizationID;

    /**
     * @var int the id of the person whose workload this displays
     */
    private int $personID;

    private bool $separate;

    private array $sumCoords = [];

    /**
     * @var int the id of the term where the workload was valid
     */
    private int $termID;

    private int $weeks;

    /**
     * Workload constructor.
     *
     * @param   BaseView  $view
     */
    public function __construct(BaseView $view)
    {
        parent::__construct($view);
        $this->organizationID = Input::getInt('organizationID');
        $this->personID       = Input::getInt('personID');
        $this->separate       = Input::getBool('separate');
        $this->termID         = Input::getInt('termID');
        $this->weeks          = Input::getInt('weeks', 13);
    }

    /**
     * Adds the arrow to the end of a function header.
     *
     * @param   string  $cell  the cell coordinates
     *
     * @return void
     * @throws Exception
     */
    private function addArrow(string $cell): void
    {
        $objDrawing = new PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('Summe Pfeil');
        $objDrawing->setDescription('Pfeil');
        $objDrawing->setPath(JPATH_COMPONENT_SITE . '/images/redarrow.png');
        $objDrawing->setCoordinates($cell);
        $objDrawing->setHeight($this->heights['sum']);
        $objDrawing->setOffsetX(5);
        $objDrawing->setOffsetY(5);
        $objDrawing->setWorksheet($this->view->getActiveSheet());
    }

    /**
     * Adds a basic field (label and input box)
     *
     * @param   int     $row    the row where the cells should be edited
     * @param   string  $label  the field label
     *
     * @return void
     * @throws Exception
     */
    private function addBasicField(int $row, string $label): void
    {
        $sheet     = $this->view->getActiveSheet();
        $border    = $this->borders['header'];
        $coords    = "B$row";
        $fill      = $this->fills['header'];
        $cellStyle = $sheet->getStyle($coords);
        $cellStyle->applyFromArray(['borders' => $border, 'fill' => $fill]);
        $cellStyle->getAlignment()->setVertical(XLConstants::CENTER);
        $sheet->setCellValue($coords, $label);
        $cellStyle->getFont()->setSize('11');
        $cellStyle->getFont()->setBold(true);
        $sheet->getStyle("C$row:D$row")->applyFromArray(['borders' => $border]);
    }

    /**
     * Adds a column header to the sheet
     *
     * @param   string  $startCell  the coordinates of the column headers top left most cell
     * @param   string  $endCell    the coordinates of the column header's bottom right most cell
     * @param   string  $text       the column header
     * @param   array   $comments   the comments necessary for clarification of the column's contents
     * @param   int     $height     the comment height
     *
     * @return void
     * @throws Exception
     */
    private function addColumnHeader(
        string $startCell,
        string $endCell,
        string $text,
        array $comments = [],
        int $height = 200): void
    {
        $view  = $this->view;
        $style = [
            'alignment' => ['horizontal' => XLConstants::CENTER, 'vertical' => XLConstants::TOP, 'wrap' => true],
            'borders'   => $this->borders['header'],
            'fill'      => $this->fills['header'],
            'font'      => ['bold' => true]
        ];
        $view->addRange($startCell, $endCell, $style, $text);

        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $this->addComment($startCell, $comment, $height);
            }
        }
    }

    /**
     * Adds a comment to a specific cell
     *
     * @param   string  $cell     the cell coordinates
     * @param   array   $comment  an associative array with a title and or text
     * @param   int     $height   the comment height
     *
     * @return void
     * @throws Exception
     */
    private function addComment(string $cell, array $comment, int $height): void
    {
        if (empty($comment['title']) and empty($comment['text'])) {
            return;
        }

        $sheet = $this->view->getActiveSheet();
        $sheet->getComment($cell)->setWidth(320);
        $sheet->getComment($cell)->setHeight($height);

        if (!empty($comment['title'])) {
            $commentTitle = $sheet->getComment($cell)->getText()->createTextRun($comment['title']);
            $commentTitle->getFont()->setBold(true);

            if (!empty($comment['text'])) {
                $sheet->getComment($cell)->getText()->createTextRun("\r\n");
            }
        }

        if (!empty($comment['text'])) {
            $sheet->getComment($cell)->getText()->createTextRun($comment['text']);
        }

        $sheet->getComment($cell)->getText()->createTextRun("\r\n");
    }

    /**
     * Adds an event row at the given row number.
     *
     * @param   int    &$row   the row number
     * @param   array   $item  the item to display in the row
     *
     * @return void
     * @throws Exception
     */
    private function addEventRow(int &$row, array $item = []): void
    {
        $sheet = $this->view->getActiveSheet();

        $alignment  = ['vertical' => XLConstants::TOP, 'wrap' => true];
        $border     = $this->borders['data'];
        $dataStyle  = ['alignment' => $alignment, 'borders' => $border, 'fill' => $this->fills['data']];
        $indexStyle = ['alignment' => $alignment, 'borders' => $border, 'fill' => $this->fills['index']];

        $sheet->mergeCells("C$row:E$row");
        $sheet->mergeCells("K$row:L$row");

        $groups   = '';
        $programs = empty($item['programs']) ? [] : array_keys($item['programs']);

        if ($programs) {
            if ($this->separate) {
                $groups = $item['programs'][reset($programs)];
            }
            else {
                $groups = [];

                foreach ($item['programs'] as $theseGroups) {
                    $groups = array_merge($groups, $theseGroups);
                }

                ksort($groups);
            }
        }

        $lines = 1;

        for ($current = 'B'; $current <= 'M'; $current++) {
            $coords    = "$current$row";
            $cellStyle = $sheet->getStyle($coords);

            switch ($current) {
                case self::EVENTS:
                    $cellStyle->applyFromArray($dataStyle);
                    if (!empty($item['names'])) {
                        $count = count($item['names']);
                        $lines = max($count, $lines);
                        $sheet->setCellValue($coords, implode("\n", $item['names']));
                    }
                    break;
                case self::GROUPS:
                    $cellStyle->applyFromArray($indexStyle);
                    if (!empty($groups)) {
                        $count = count($groups);
                        $lines = max($count, $lines);
                        $sheet->setCellValue($coords, implode("\n", $groups));
                    }
                    break;
                case self::HOURS:
                    $cellStyle->applyFromArray($dataStyle);
                    if (!empty($item['items'])) {
                        $count = count($item['items']);
                        $lines = max($count, $lines);
                        $sheet->setCellValue($coords, implode("\n", $item['items']));
                    }
                    break;
                case self::METHOD:
                    $cellStyle->applyFromArray($dataStyle);
                    if (!empty($item['method'])) {
                        $sheet->setCellValue($coords, $item['method']);
                    }
                    break;
                case self::PROGRAMS:
                    $cellStyle->applyFromArray($indexStyle);
                    if ($programs) {
                        if ($this->separate) {
                            $sheet->setCellValue($coords, reset($programs));
                        }
                        else {
                            $count = count($item['programs']);
                            $lines = max($count, $lines);
                            $sheet->setCellValue($coords, implode("\n", array_keys($item['programs'])));
                        }
                    }
                    break;
                case self::SUBJECTNOS:
                    $cellStyle->applyFromArray($indexStyle);
                    if (!empty($item['subjectNos'])) {
                        $count = count($item['subjectNos']);
                        $lines = max($count, $lines);
                        $sheet->setCellValue($coords, implode("\n", $item['subjectNos']));
                    }
                    break;
                case self::SWS_IS:
                    $cellStyle->applyFromArray($dataStyle);
                    if (!empty($item['minutes'])) {
                        $hours = ceil($item['minutes'] / 45);
                        $sheet->setCellValue($coords, $hours / $this->weeks);
                    }
                    $cellStyle->getNumberFormat()->setFormatCode(XLConstants::NUMBER_00);
                    break;
                case self::ELECTIVE:
                case self::SWS_SHOULD:
                default:
                    $cellStyle->applyFromArray($dataStyle);
                    break;
            }
        }

        $height = $lines * 12.75;
        $sheet->getRowDimension($row)->setRowHeight($height);

        if ($this->separate and $programs) {
            $programs = $item['programs'];

            // The first one has already been taken care of.
            array_shift($programs);

            foreach ($programs as $program => $groups) {
                $row++;
                $this->addProgramRow($row, $program, $groups, $dataStyle, $indexStyle);
            }
        }

        $row++;
    }

    /**
     * Adds an event sub header row at the given row number
     *
     * @param   int     $row   the row number
     * @param   string  $text  the text for the labeling column
     *
     * @return void
     * @throws Exception
     */
    private function addEventSubHeadRow(int $row, string $text): void
    {
        $sheet = $this->view->getActiveSheet();
        $sheet->mergeCells("C$row:E$row");
        $sheet->mergeCells("K$row:L$row");

        for ($current = 'B'; $current <= 'M'; $current++) {
            $sheet->getStyle("$current$row")->applyFromArray([
                'borders' => $this->borders['data'],
                'fill'    => $this->fills['header']
            ]);

            if ($current === 'B') {
                $sheet->setCellValue("B$row", $text);
                $sheet->getStyle("B$row")->getFont()->setBold(true);
                $sheet->getStyle("B$row")->getAlignment()->setWrapText(true);
            }
        }
    }

    /**
     * Creates a section header
     *
     * @param   int     $row       the row number
     * @param   string  $text      the section header text
     * @param   string  $function  the function to execute
     *
     * @return void
     * @throws Exception
     */
    private function addFunctionHeader(int $row, string $text, string $function): void
    {
        $view  = $this->view;
        $sheet = $view->getActiveSheet();
        $sheet->getRowDimension($row)->setRowHeight($this->heights['sum']);
        $style = [
            'alignment' => ['vertical' => XLConstants::CENTER],
            'borders'   => $this->borders['header'],
            'fill'      => $this->fills['header'],
            'font'      => ['bold' => true]
        ];
        $view->addRange("B$row", "L$row", $style, $text);

        $style['numberformat'] = ['code' => XLConstants::NUMBER_00];
        $sheet->getStyle("M$row")->applyFromArray($style);
        $sheet->setCellValue("M$row", $function);
        $this->addArrow("N$row");
        $sheet->getRowDimension(++$row)->setRowHeight($this->heights['sectionSpacer']);
    }

    /**
     * Adds an instruction cell to the active sheet.
     *
     * @param   int     $row     the row number
     * @param   float   $height  the row height
     * @param   string  $text    the cell text
     * @param   bool    $bold    whether the text should be displayed in a bold font
     *
     * @return void
     * @throws Exception
     */
    private function addInstruction(int $row, float $height, string $text, bool $bold = false): void
    {
        $sheet  = $this->view->getActiveSheet();
        $coords = 'B' . $row;
        $sheet->getRowDimension($row)->setRowHeight($height);
        $sheet->setCellValue($coords, $text);
        $cellStyle = $sheet->getStyle($coords);
        $cellStyle->getAlignment()->setWrapText(true);

        if ($bold) {
            $cellStyle->getFont()->setBold(true);
        }

        $cellStyle->getAlignment()->setVertical(XLConstants::TOP);
        $sheet->getStyle($coords)->getFont()->setSize('14');
    }

    /**
     * Creates an instructions sheet
     *
     * @return void
     * @throws Exception
     */
    private function addInstructionSheet(): void
    {
        $view = $this->view;
        $view->setActiveSheetIndex();
        $sheet     = $view->getActiveSheet();
        $pageSetup = $sheet->getPageSetup();
        $pageSetup->setOrientation(XLConstants::PORTRAIT);
        $pageSetup->setPaperSize(XLConstants::A4);
        $pageSetup->setFitToPage();

        $sheet->setTitle('Anleitung');
        $sheet->setShowGridlines();
        $sheet->getColumnDimension()->setWidth(5);
        $sheet->getColumnDimension('B')->setWidth(75);
        $sheet->getColumnDimension('C')->setWidth(5);
        $sheet->getRowDimension('1')->setRowHeight('85');

        $this->addLogo(25);

        $text = 'Mit dem ablaufenden Wintersemester 2017/18 wird ein leicht veränderter B-Bogen in Umlauf ';
        $text .= 'gesetzt. Er dient einer dezi\ndieteren Kostenrechnung. Bitte nutzen Sie ausschließlich diesen ';
        $text .= 'Bogen.';
        $this->addInstruction(2, 90, $text);

        $this->addInstruction(3, 35, 'Hinweise:', true);

        $text = 'In der Spalte "Studiengang" ist eine Auswahlliste für Ihren Fachbereich hinterlegt. ';
        $text .= 'Bitte klicken Sie den entsprechenden Studiengang an.';
        $this->addInstruction(4, 55, $text);

        $text = 'Sollten Sie in der Auswahlliste einen Studiengang nicht finden, so nutzen Sie bitte die ';
        $text .= 'letzte Rubrik "nicht vorgegeben". ';
        $this->addInstruction(5, 55, $text);

        $text = 'Sollte eine Lehrveranstaltung in mehreren Studiengängen sein, so können Sie, dann über ';
        $text .= 'mehrere Zeilen, nach Ihrem Ermessen quoteln.';
        $this->addInstruction(6, 55, $text);

        $this->addInstruction(7, 45, 'So können alle Studiengänge berücksichtigt werden. ');

        $text = 'Sollten Sie eine Lehrveranstaltung gehalten haben, die in mehreren Fachbereichen ';
        $text .= 'angeboten wird, so verfahren Sie bitte analog, nutzen aber die Rubrik "mehrere ';
        $text .= 'Fachbereiche", da dort eine  Auswahlliste hinterlegt ist, die alle Studiengänge ';
        $text .= 'der THM enthält.';
        $this->addInstruction(8, 90, $text);

        $this->addInstruction(9, 20, 'Die Liste ist nach Fachbereichen geordnet.');
        $sheet->getRowDimension('10')->setRowHeight('20');
        $this->addInstruction(11, 20, 'Für Ihre Mühe danke ich Ihnen,');
        $this->addInstruction(12, 20, 'Prof. Olaf Berger');

        $noOutline = ['borders' => ['outline' => ['style' => XLConstants::NONE]]];
        $sheet->getStyle('A1:C12')->applyFromArray($noOutline);
    }

    /**
     * Adds the THM Logo to a cell.
     *
     * @param   int  $offsetY  the offset from the top of the cell
     *
     * @return void
     * @throws Exception
     */
    private function addLogo(int $offsetY): void
    {
        $objDrawing = new PHPExcel_Worksheet_Drawing();
        $objDrawing->setName('THM Logo');
        $objDrawing->setDescription('THM Logo');
        $objDrawing->setPath(JPATH_COMPONENT_SITE . '/images/logo.png');
        $objDrawing->setCoordinates('B1');
        $objDrawing->setHeight(60);
        $objDrawing->setOffsetY($offsetY);
        $objDrawing->setWorksheet($this->view->getActiveSheet());
    }

    /**
     * Adds a supplementary row for events held for multiple degree programs.
     *
     * @param   int     $row         the row number
     * @param   string  $program     the name of the degree program
     * @param   array   $groups      the names of the program subordinate groups
     * @param   array   $dataStyle   the style to use for date fields
     * @param   array   $indexStyle  the style to use for index fields
     *
     * @return void
     * @throws PHPExcel_Exception
     */
    private function addProgramRow(int $row, string $program, array $groups, array $dataStyle, array $indexStyle): void
    {
        $sheet = $this->view->getActiveSheet();

        $sheet->mergeCells("C$row:E$row");
        $sheet->mergeCells("K$row:L$row");

        $lines = 1;

        for ($current = 'B'; $current <= 'M'; $current++) {
            $coords    = "$current$row";
            $cellStyle = $sheet->getStyle($coords);

            switch ($current) {
                case self::GROUPS:
                    $cellStyle->applyFromArray($indexStyle);
                    if (!empty($groups)) {
                        $count = count($groups);
                        $lines = max($count, $lines);
                        $sheet->setCellValue($coords, implode("\n", $groups));
                    }
                    break;
                case self::PROGRAMS:
                    $cellStyle->applyFromArray($indexStyle);
                    $sheet->setCellValue($coords, $program);
                    break;
                case self::SUBJECTNOS:
                    $cellStyle->applyFromArray($indexStyle);
                    break;
                default:
                    $cellStyle->applyFromArray($dataStyle);
                    break;
            }
        }

        $height = $lines * 12.75;
        $sheet->getRowDimension($row)->setRowHeight($height);
    }

    /**
     * Adds the main work sheet to the document.
     *
     * @return void
     * @throws Exception
     */
    private function addMethodsSheet(): void
    {
        $view = $this->view;
        $view->createSheet();
        $view->setActiveSheetIndex(3);
        $sheet = $view->getActiveSheet();
        $sheet->setTitle('Lehrmethoden');

        $sheet->getColumnDimension()->setWidth(15);
        $sheet->getColumnDimension('B')->setWidth(40);

        $style = [
            'alignment' => ['code' => XLConstants::CENTER],
            'border'    => $this->borders['header'],
            'fill'      => $this->fills['header']
        ];

        $sheet->getStyle('A1:B1')->applyFromArray($style);
        $sheet->setCellValue("A1", Text::_('ORGANIZER_CODE'));
        $sheet->setCellValue("B1", Text::_('ORGANIZER_METHOD'));

        $row = 2;

        /** @var Model $model */
        $model = $view->model;

        foreach ($model->methods as $code => $method) {
            $sheet->setCellValue("A$row", $code);
            $sheet->setCellValue("B$row", $method);

            if ($row % 2 === 1) {
                $sheet->getStyle("A$row:B$row")->applyFromArray(['fill' => $this->fills['data']]);
            }

            $row++;
        }
    }

    /**
     * Adds the main work sheet to the document.
     *
     * @return void
     * @throws Exception
     */
    private function addProgramSheet(): void
    {
        $view = $this->view;
        $view->createSheet();
        $view->setActiveSheetIndex(2);
        $sheet = $view->getActiveSheet();
        $sheet->setTitle('Studiengänge');

        $sheet->getColumnDimension()->setWidth(90);
        $sheet->getColumnDimension('B')->setWidth(25);
        $sheet->getColumnDimension('C')->setWidth(40);
        $sheet->getColumnDimension('D')->setWidth(25);

        $style = [
            'alignment' => ['code' => XLConstants::CENTER],
            'border'    => $this->borders['header'],
            'fill'      => $this->fills['header']
        ];

        $sheet->getStyle('A1:D1')->applyFromArray($style);
        $sheet->setCellValue("A1", Text::_('ORGANIZER_PROGRAMS'));
        $sheet->setCellValue("B1", Text::_('ORGANIZER_PROGRAM_RESTRICTIONS'));
        $sheet->setCellValue("C1", Text::_('ORGANIZER_PROGRAMS'));
        $sheet->setCellValue("D1", Text::_('ORGANIZER_ORGANIZATIONS'));

        $row = 2;

        /** @var Model $model */
        $model = $view->model;

        foreach ($model->programs as $name => $program) {
            $sheet->setCellValue("A$row", $name);

            if ($program['frequencyID']) {
                $sheet->setCellValue("B$row", Helpers\Frequencies::name($program['frequencyID']));
            }

            $restrictions = [];

            if ($program['fee']) {
                $restrictions[] = Text::_('ORGANIZER_PROGRAM_FEE');
            }

            if ($program['nc']) {
                $restrictions[] = Text::_('ORGANIZER_NC');
            }

            if ($program['special']) {
                $restrictions[] = Text::_('ORGANIZER_PROGRAM_SPECIAL');
            }

            $restrictions = $restrictions ? implode(', ', $restrictions) : '-----';
            $sheet->setCellValue("C$row", $restrictions);

            $organizations = $program['organizations'] ? implode(', ', $program['organizations']) : '';
            $sheet->setCellValue("D$row", $organizations);

            if ($row % 2 === 1) {
                $sheet->getStyle("A$row:D$row")->applyFromArray(['fill' => $this->fills['data']]);
            }

            $row++;
        }
    }

    /**
     * Creates and formats a row to be used for a workload relevant role listing.
     *
     * @param   int  $row  the row to add
     *
     * @return void
     * @throws Exception
     */
    private function addRoleRow(int $row): void
    {
        $view   = $this->view;
        $sheet  = $view->getActiveSheet();
        $border = $this->borders['cell'];
        $fill   = $this->fills['data'];

        $view->addRange("B$row", "C$row", ['borders' => $border]);
        $view->addRange("D$row", "G$row", ['borders' => $border]);
        $view->addRange("H$row", "L$row", ['borders' => $border, 'fill' => $fill]);
        $sheet->getStyle("M$row")->applyFromArray(['borders' => $border, 'fill' => $fill]);
    }

    /**
     * Adds the section which lists held lessons to the worksheet
     *
     * @param   int  &$row  the current row number
     *
     * @return void
     * @throws Exception
     */
    private function addSectionA(int &$row): void
    {
        $this->addSectionHeader($row, "A. Lehrveranstaltungen", true);

        $startRow = $row + 2;
        $endRow   = $row + 4;

        $this->addColumnHeader("B14", "B16", 'ModulNr');

        $text = '„Die Lehrende teilen jeweils am Ende eines Semesters unter thematischer Bezeichnung der ';
        $text .= 'einzelnen Lehrveranstaltungen Art und Umfang ihrer Lehrtätigkeit und die Zahl der ';
        $text .= 'gegebenenfalls mitwirkenden Lehrkräfte, bei Lehrveranstaltungen mit beschränkter ';
        $text .= 'Teilnehmerzahl auch die Zahl der teilnehmenden Studierenden sowie der betreuten ';
        $text .= 'Abschlussarbeiten und vergleichbaren Studienarbeiten der Fachbereichsleitung schriftlich mit. ';
        $text .= 'Wesentliche Unterbrechungen, die nicht ausgeglichen worden sind, sind anzugeben. Bei ';
        $text .= 'Nichterfüllung der Lehrverpflichtung unterrichtet die Fachbereichsleitung die ';
        $text .= 'Hochschulleitung.“';

        $comments = [
            ['title' => 'Nur auszufüllen, wenn entsprechende Module definiert und bezeichnet sind.'],
            ['title' => 'LVVO vom 10.9.2013, § 4 (5)', 'text' => $text]
        ];

        $this->addColumnHeader("C$startRow", "E$endRow", 'Lehrveranstaltung', $comments, 345);

        $comments = [
            ['title' => 'Veranstaltungsarten sind:'],
            ['text' => 'V – Vorlesung'],
            ['text' => 'Ü – Übung'],
            ['text' => 'P – Praktikum'],
            ['text' => 'S – Seminar']
        ];

        $this->addColumnHeader("F$startRow", "F$endRow", 'Art (Kürzel)', $comments, 105);

        $text1 = '"Nach Prüfungsordnungen, Studienordnungen oder Studienplänen nicht vorgesehene Lehrveranstaltungen ';
        $text1 .= 'werden angerechnet, wenn alle nach diesen Vorschriften vorgesehenen Lehrveranstaltungen eines ';
        $text1 .= 'Faches durch hauptberuflich oder nebenberuflich an der Hochschule tätiges wissenschaftliches und ';
        $text1 .= 'künstlerisches Personal angeboten werden."';
        $text2 = 'Damit das Dekanat hier eine entsprechende Zuordnung erkennen kann, sind Lehrumfang, Studiengang und ';
        $text2 .= 'Semester sowie Pflichtstatus anzugeben.';

        $comments = [
            ['title' => 'LVVO vom 10.9.2013, § 2 (2):', 'text' => $text1],
            ['text' => ' '],
            ['text' => $text2]
        ];

        $this->addColumnHeader("G$startRow", "G$endRow", "Lehrumfang gemäß SWS Lehrumfang", $comments);

        // TODO what is LVVO?
        // TODO what does the difference in dates say when presenting the same text?

        $comments = [
            ['title' => 'LVVO vom 2.8.2006, § 2 (2):', 'text' => $text1],
            ['text' => ' '],
            ['text' => $text2]
        ];

        $this->addColumnHeader("H$startRow", "H$endRow", "Studiengang", $comments);
        $this->addColumnHeader("I$startRow", "I$endRow", 'Semester');

        $comments = [
            ['title' => 'LVVO vom 10.9.2013, § 2 (2):', 'text' => $text1],
            ['text' => ' '],
            ['text' => $text2],
            ['text' => 'P: Pflichtmodul'],
            ['text' => 'WP: Wahlpflichtmodul'],
            ['text' => 'W: Wahlmodul']
        ];

        $this->addColumnHeader("J$startRow", "J$endRow", "Pflicht-\nstatus\n(Kürzel)", $comments);

        $text1 = 'explizite Angabe der Wochentage und Stunden nötig; der Verweis auf den Stundenplan reicht nicht aus! ';
        $text1 .= 'Bei Block-Veranstaltungen bitte die jeweiligen Datumsangaben machen!';
        $text2 = '"Lehrveranstaltungen, die nicht in Wochenstunden je Semester ausgedrückt sind, sind entsprechend ';
        $text2 .= 'umzurechnen; je Tag werden höchstens acht Lehrveranstaltungsstunden berücksichtigt."';

        $comments = [
            ['text' => $text1],
            ['title' => 'LVVO § 2 (7):', 'text' => $text2]
        ];

        $this->addColumnHeader("K$startRow", "L$endRow", "Wochentag u. Stunde\n(bei Blockveranst. Datum)", $comments);

        $comments = [
            ['title' => 'LVVO § 2 (7):', 'text' => $text2]
        ];

        $this->addColumnHeader("M$startRow", "M$endRow", "Gemeldetes\nDeputat\n(SWS)", $comments, 105);

        /** @var Model $model */
        $model = $this->view->model;
        $row   = $row + 5;
        $start = $row;

        foreach ($model->items as $key => $item) {
            if (empty($item['organizations'])) {
                continue;
            }

            if (count($item['organizations']) > 1 or !array_key_exists($this->organizationID, $item['organizations'])) {
                continue;
            }

            $this->addEventRow($row, $item);
            unset($model->items[$key]);
        }

        $own = ['start' => $start, 'end' => $row];

        // Add a blank for user additions
        $this->addEventRow($row);

        $this->addEventSubHeadRow($row++, 'Mehrere Fachbereiche');
        $start = $row;

        foreach ($model->items as $key => $item) {
            if (empty($item['organizations'])) {
                continue;
            }

            $this->addEventRow($row, $item);
            unset($model->items[$key]);
        }

        $other = ['start' => $start, 'end' => $row];

        // Add a blank for user additions
        $this->addEventRow($row);

        $this->addEventSubHeadRow($row++, 'Nicht vorgegeben');
        $start = $row;

        foreach ($model->items as $item) {
            $this->addEventRow($row, $item);
        }

        $end     = $row;
        $unknown = ['start' => $start, 'end' => $end];

        for ($current = $row; $current <= $end; $current++) {
            $this->addEventRow($current);
            $row++;
        }

        $ranges = [$own, $other, $unknown];

        $this->addSumRow($row++, 'A', $ranges);

        $sheet = $this->view->getActiveSheet();
        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);
    }

    /**
     * Adds the section which lists thesis supervisions
     *
     * @param   int  &$row  the current row number
     *
     * @return void
     * @throws Exception
     */
    private function addSectionB(int &$row): void
    {
        $comments = [
            ['title' => 'Olaf Berger:', 'text' => 'Laut LVVO und HMWK ist eine maximale Grenze von 2 SWS zu beachten.']
        ];

        $this->addSectionHeader($row, "B. Betreuung von Studien- und Abschlussarbeiten", true, $comments, 70);

        $startRow = $row + 2;
        $endRow   = $row + 4;

        $text = '"Die Betreuung von Abschlussarbeiten und vergleichbaren Studienarbeiten kann durch die Hochschule ';
        $text .= 'unter Berücksichtigung des notwendigen Aufwandes bis zu einem Umfang von zwei ';
        $text .= 'Lehrveranstaltungsstunden auf die Lehrverpflichtung angerechnet werden;…“';

        $comments = [
            ['title' => 'LVVO vom 10.9.2013, §2 (5):', 'text' => $text]
        ];

        $this->addColumnHeader("B$startRow", "B$endRow", 'Rechtsgrundlage gemäß LVVO', $comments, 150);

        $label = 'Art der Abschlussarbeit (nur bei Betreuung als Referent/in)';
        $this->addColumnHeader("C$startRow", "F$endRow", $label, $comments, 150);
        $label = 'Umfang der Anrechnung in SWS je Arbeit (insgesamt max. 2 SWS)';
        $this->addColumnHeader("G$startRow", "J$endRow", $label);
        $this->addColumnHeader("K$startRow", "L$endRow", "Anzahl der Arbeiten");
        $this->addColumnHeader("M$startRow", "M$endRow", "Gemeldetes\nDeputat\n(SWS)");
        $row = $endRow + 1;

        /** @var Model $model */
        $model = $this->view->model;

        $startRow = $row;
        $bachelor = ['text' => 'Betreuung von Bachelorarbeit(en) ', 'weight' => .3, 'value' => $model->bachelors];
        $this->addSupervisionRow($row++, $bachelor);
        $master = ['text' => 'Betreuung von Masterarbeit(en)', 'weight' => .6, 'value' => $model->masters];
        $this->addSupervisionRow($row++, $master);
        $projects = ['text' => 'Betreuung von Projekt- und Studienarbeiten, BPS', 'weight' => .15, 'value' => $model->projects];
        $this->addSupervisionRow($row++, $projects);
        $endRow = $row;
        $doctor = ['text' => 'Betreuung von Promotionen (bis max 6 Semester)', 'weight' => .65, 'value' => $model->doctors];
        $this->addSupervisionRow($row++, $doctor);

        $ranges = [['start' => $startRow, 'end' => $endRow]];
        $this->addSumRow($row++, 'B', $ranges, 2);

        $sheet = $this->view->getActiveSheet();
        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);
    }

    /**
     * Adds the section which lists roles for which workload is calculated
     *
     * @param   int  &$row  the current row number
     *
     * @return void
     * @throws Exception
     */
    private function addSectionC(int &$row): void
    {
        $sheet = $this->view->getActiveSheet();
        $this->addSectionHeader($row++, "C. Deputatsfreistellungen", true);

        // For the table headers
        $startRow = ++$row;
        $endRow   = ++$row;

        $this->addColumnHeader("B$startRow", "C$endRow", 'Rechtsgrundlage gemäß LVVO');
        $this->addColumnHeader("D$startRow", "G$endRow", 'Grund für Deputatsfreistellung');
        $label = 'Bezeichnung aus dem Genehmigungsschreiben bzw. Dekanatsunterlagen';
        $this->addColumnHeader("H$startRow", "L$endRow", $label);
        $this->addColumnHeader("M$startRow", "M$endRow", "Gemeldetes\nDeputat\n(SWS)");
        $row++;

        // For the table
        $startRow = $row;

        $this->addRoleRow($row);
        $sheet->setCellValue("B$row", 'LVVO § 5 (1)');
        $title = 'LVVO § 5 (1):';
        $text  = '"Bei Wahrnehmung einer Funktion in der Hochschulleitung kann die Lehrverpflichtung um bis zu 100 ';
        $text  .= 'Prozent, bei Wahrnehmung einer Funktion in der Fachbereichsleitung um bis zu 75 Prozent ermäßigt ';
        $text  .= 'werden. Soweit eine Ermäßigung für mehrere Personen in der Fachbereichsleitung erfolgt, ';
        $text  .= 'darf die durchschnittliche Ermäßigung 50 Prozent nicht übersteigen."';
        $this->addComment("B$row", ['title' => $title, 'text' => $text], 200);

        $sheet->setCellValue("D$row", 'Dekanatsamt (Dekan, Studiendekan, Prodekan)');
        $row++;

        $this->addRoleRow($row);
        $sheet->setCellValue("B$row", "LVVO § 5 (2, 4 und 5)");
        $title = "LVVO vom 10.9.2013, § 5 (2):";
        $text  = '"Die Lehrverpflichtung kann für die Wahrnehmung weiterer Aufgaben und Funktionen innerhalb der ';
        $text  .= 'Hochschule, insbesondere für besondere Aufgaben der Studienreform, für die Leitung von ';
        $text  .= 'Sonderforschungsbereichen und für Studienfachberatung unter Berücksichtigung des Lehrbedarfs im ';
        $text  .= 'jeweiligen Fach ermäßigt werden; die Ermäßigung soll im Einzelfall zwei Lehrveranstaltungsstunden ';
        $text  .= 'nicht überschreiten. Für die Teilnahme an der Entwicklung und Durchführung von hochschuleigenen ';
        $text  .= 'Auswahlverfahren und von Verfahren nach § 54 Abs. 4 des Hessischen Hochschulgesetzes sowie für die ';
        $text  .= 'Wahrnehmung der Mentorentätigkeit nach § 14 Satz 5 des Hessischen Hochschulgesetzes erhalten ';
        $text  .= 'Professorinnen und Professoren keine Ermäßigung der Lehrverpflichtung."';
        $this->addComment("B$row", ['title' => $title, 'text' => $text], 200);
        $sheet->getComment("B$row")->getText()->createTextRun("\r\n");
        $title = 'LVVO vom 10.9.2006, §5 (4):';
        $text  = '"An Fachhochschulen kann die Lehrverpflichtung für die Wahrnehmung von Forschungs- und ';
        $text  .= 'Entwicklungsaufgaben, für die Leitung und Verwaltung von zentralen Einrichtungen der Hochschule, ';
        $text  .= 'die Betreuung von Sammlungen einschließlich Bibliotheken sowie die Leitung des Praktikantenamtes ';
        $text  .= 'ermäßigt werden; die Ermäßigung soll zwölf Prozent der Gesamtheit der Lehrverpflichtungen der ';
        $text  .= 'hauptberuflich Lehrenden und bei einzelnen Professorinnen und Professoren vier ';
        $text  .= 'Lehrveranstaltungsstunden nicht überschreiten. Die personenbezogene Höchstgrenze gilt nicht im ';
        $text  .= 'Falle der Wahrnehmung von Forschungs- und Entwicklungsaufgaben. Soweit aus Einnahmen von ';
        $text  .= 'Drittmitteln für Forschungs- und Entwicklungsaufträge oder Projektdurchführung Lehrpersonal ';
        $text  .= 'finanziert wird, kann die Lehrverpflichtung von Professorinnen und Professoren in dem ';
        $text  .= 'entsprechenden Umfang auf bis zu vier Lehrveranstaltungsstunden reduziert werden; diese ';
        $text  .= 'Ermäßigungen sind auf die zulässige Höchstgrenze der Ermäßigung der Gesamtlehrverpflichtung ';
        $text  .= 'nicht anzurechnen. Voraussetzung für die Übernahme von Verwaltungsaufgaben ist, dass ';
        $text  .= 'diese Aufgaben von der Hochschulverwaltung nicht übernommen werden können und deren Übernahme ';
        $text  .= 'zusätzlich zu der Lehrverpflichtung wegen der damit verbundenen Belastung nicht zumutbar ist."';
        $this->addComment("B$row", ['title' => $title, 'text' => $text], 200);
        $sheet->getComment("B$row")->getText()->createTextRun("\r\n");
        $title = 'LVVO vom 10.9.2013, § 5 (5):';
        $text  = '"Liegen mehrere Ermäßigungsvoraussetzungen nach Abs. 1 bis 4 Satz 2 vor, soll die Lehrtätigkeit im ';
        $text  .= 'Einzelfall während eines Semesters 50 vom Hundert der jeweiligen Lehrverpflichtung nicht ';
        $text  .= 'unterschreiten."';
        $this->addComment("B$row", ['title' => $title, 'text' => $text], 200);

        $sheet->setCellValue("D$row", 'Weitere Deputatsreduzierungen');
        $row++;
        $endRow = $row;

        $this->addRoleRow($row);

        $sheet->setCellValue("B$row", "LVVO § 6");
        $title = "LVVO vom 10.9.2013, § 6:";
        $text  = "„Die Lehrverpflichtung schwerbehinderter Menschen im Sinne des Neunten Buches Sozialgesetzbuch - ";
        $text  .= "Rehabilitation und Teilhabe behinderter Menschen - vom 19. Juni 2001 (BGBl. I S. 1046, 1047), ";
        $text  .= "zuletzt geändert durch Gesetz vom 14. Dezember 2012 (BGBl. I S. 2598), kann auf Antrag von der ";
        $text  .= "Hochschulleitung ermäßigt werden.“";
        $this->addComment("B$row", ['title' => $title, 'text' => $text], 200);

        $sheet->setCellValue("D$row", 'Schwerbehinderung');
        $row++;

        $ranges = [['start' => $startRow, 'end' => $endRow]];
        $this->addSumRow($row++, 'C', $ranges);

        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);
    }

    /**
     * Creates a section header
     *
     * @param   int     $row       the row number
     * @param   string  $text      the section header text
     * @param   bool    $break     whether a break should be displayed
     * @param   array   $comments  an array of tips with title and/or text
     * @param   int     $cHeight   the comment height
     *
     * @return void
     * @throws Exception
     */
    private function addSectionHeader(int $row, string $text, bool $break = false, array $comments = [], int $cHeight = 200): void
    {
        $view  = $this->view;
        $sheet = $view->getActiveSheet();
        $sheet->getRowDimension($row)->setRowHeight($this->heights['sectionHead']);
        $style = [
            'alignment' => ['vertical' => XLConstants::CENTER],
            'borders'   => $this->borders['header'],
            'fill'      => $this->fills['header'],
            'font'      => ['bold' => true]
        ];
        $view->addRange("B$row", "M$row", $style, $text);

        if (!empty($comments)) {
            foreach ($comments as $comment) {
                $this->addComment("B$row", $comment, $cHeight);
            }
        }

        if ($break) {
            $sheet->getRowDimension(++$row)->setRowHeight($this->heights['sectionSpacer']);
        }
    }

    /**
     * Adds a row summing section values
     *
     * @param   int     $row      the row where the sum will be added
     * @param   string  $section  the section being summed (used for the label)
     * @param   array   $ranges   the row ranges to be summed
     * @param   int     $max      the maximum value of the sum
     *
     * @return void
     * @throws PHPExcel_Exception
     */
    private function addSumRow(int $row, string $section, array $ranges = [], int $max = 0): void
    {
        $sheet     = $this->view->getActiveSheet();
        $border    = $this->borders['header'];
        $dataStyle = [
            'borders'      => $border,
            'fill'         => $this->fills['index'],
            'numberformat' => ['code' => XLConstants::NUMBER_00]
        ];

        $sheet->getStyle("L$row")->applyFromArray(['borders' => $border, 'fill' => $this->fills['header']]);
        $sheet->getStyle("L$row")->getAlignment()->setHorizontal(XLConstants::CENTER);
        $sheet->setCellValue("L$row", "Summe $section:");
        $sheet->getStyle("L$row")->getFont()->setBold(true);
        $sheet->getStyle("M$row")->applyFromArray($dataStyle);

        if (count($ranges) === 1) {
            $rangeSum = $max ? "=IF(SUM(MXXX:MYYY)<=2,SUM(MXXX:MYYY),$max)" : "=SUM(MXXX:MYYY)";
            $formula  = str_replace('YYY', $ranges[0]['end'], str_replace('XXX', $ranges[0]['start'], $rangeSum));
        }
        else {
            $sums = [];
            foreach ($ranges as $range) {
                $sums[] = "SUM(M{$range['start']}:M{$range['end']})";
            }
            $formula = '=SUM(' . implode(',', $sums) . ')';
        }

        $sheet->setCellValue("M$row", $formula);
        $this->sumCoords[] = "M$row";
    }

    /**
     * Creates a row evaluating the valuation of a type and quantity of supervisions
     *
     * @param   int    $row       the row number
     * @param   array  $category  an array containing the category text and its calculation weight
     *
     * @return void
     * @throws Exception
     */
    private function addSupervisionRow(int $row, array $category): void
    {
        $view   = $this->view;
        $sheet  = $view->getActiveSheet();
        $border = $this->borders['cell'];

        $sheet->getStyle("B$row")->applyFromArray(['borders' => $border]);
        $sheet->setCellValue("B$row", 'LVVO § 2 (5)');
        $view->addRange("C$row", "F$row", ['borders' => $border], $category['text']);
        $view->addRange("G$row", "J$row", ['borders' => $border], $category['weight']);
        $sheet->getStyle("G$row")->getNumberFormat()->setFormatCode(XLConstants::NUMBER_00);
        $view->addRange("K$row", "L$row", ['borders' => $border], $category['value']);
        $sheet->getStyle("K$row")->getNumberFormat()->setFormatCode(XLConstants::NUMBER_00);
        $sheet->getStyle("M$row")->applyFromArray(['borders' => $border]);
        $sheet->getStyle("M$row")->getNumberFormat()->setFormatCode(XLConstants::NUMBER_00);
        $sheet->setCellValue("M$row", '=IF(K' . $row . '<>0,G' . $row . '*K' . $row . ',0)');
    }

    /**
     * Adds the main work sheet to the document.
     * @return void
     * @throws Exception
     */
    private function addWorkSheet(): void
    {
        $view = $this->view;
        $view->createSheet();
        $view->setActiveSheetIndex(1);

        $sheet = $view->getActiveSheet();
        $sheet->setTitle('B-Bogen');
        $this->formatWorkSheet();

        $sheet->getRowDimension('1')->setRowHeight('66');
        $this->addLogo(10);

        $sheet->getRowDimension('2')->setRowHeight('22.5');
        $style = [
            'alignment' => ['horizontal' => XLConstants::CENTER, 'vertical' => XLConstants::CENTER],
            'borders'   => $this->borders['header'],
            'fill'      => $this->fills['header'],
            'font'      => ['bold' => true, 'size' => 14]
        ];
        $text  = 'Bericht über die Erfüllung der Lehrverpflichtung gemäß § 4 (5) LVVO (Version 1.4; Stand 07.02.2018)';
        $view->addRange("B2", "M2", $style, $text);

        $sheet->getRowDimension('3')->setRowHeight($this->heights['sectionSpacer']);

        $sheet->getRowDimension('4')->setRowHeight($this->heights['basicField']);
        $this->addBasicField(4, 'Fachbereich');
        $sheet->setCellValue('C4', Helpers\Organizations::getShortName($this->organizationID));
        $sheet->getRowDimension('5')->setRowHeight($this->heights['spacer']);

        $sheet->getRowDimension('6')->setRowHeight($this->heights['basicField']);
        $this->addBasicField(6, 'Semester');
        $sheet->setCellValue('C6', Helpers\Terms::name($this->termID));
        $sheet->getRowDimension('7')->setRowHeight($this->heights['spacer']);

        $sheet->getRowDimension('8')->setRowHeight($this->heights['basicField']);
        $this->addBasicField(8, 'Name');
        $sheet->setCellValue('C8', Helpers\Persons::surname($this->personID));
        $sheet->getRowDimension('9')->setRowHeight($this->heights['spacer']);

        $sheet->getRowDimension('10')->setRowHeight($this->heights['basicField']);
        $this->addBasicField(10, 'Vorname');
        $sheet->setCellValue('C10', Helpers\Persons::forename($this->personID));
        $sheet->getRowDimension('11')->setRowHeight($this->heights['spacer']);

        $color = '9C132E';
        $style = [
            'alignment' => ['horizontal' => XLConstants::CENTER, 'vertical' => XLConstants::CENTER, 'wrap' => true],
            'borders'   => [
                'left'   => ['style' => XLConstants::THIN, 'color' => ['rgb' => $color]],
                'right'  => ['style' => XLConstants::THIN, 'color' => ['rgb' => $color]],
                'bottom' => ['style' => XLConstants::THIN, 'color' => ['rgb' => $color]],
                'top'    => ['style' => XLConstants::THIN, 'color' => ['rgb' => $color]]
            ],
            'font'      => ['bold' => true, 'color' => ['rgb' => $color]]
        ];
        $text  = 'Die Tabelle soll in Excel ausgefüllt werden. Durch Kontakt des Cursors mit der kleinen roten ';
        $text  .= 'Markierung in einem entsprechenden Feld öffnet sich ein Infofeld und Sie erhalten weiterführende ';
        $text  .= 'Informationen.';
        $view->addRange('G4', 'K10', $style, $text);

        // The last static row.
        $row = 12;

        $this->addSectionA($row);
        $this->addSectionB($row);
        $this->addSectionC($row);

        $function = '=SUM(' . implode(',', $this->sumCoords) . ')';
        $dRow     = $row;
        $this->addFunctionHeader($row++, 'D. Gemeldetes Gesamtdeputat (A + B + C) für das Semester', $function);
        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);

        $this->addSectionHeader($row++, "E. Deputatsübertrag aus den Vorsemestern");
        $eRow = $row;
        $view->addRange("B$row", "L$row", ['borders' => $this->borders['cell']], 'Deputatsüberhang / -defizit');
        $sheet->getStyle("M$row")->applyFromArray(['borders' => $this->borders['cell'], 'fill' => $this->fills['data']]);
        $sheet->getStyle("M$row")->getNumberFormat()->setFormatCode(XLConstants::NUMBER_00);
        $sheet->setCellValue("M$row", 0);
        $row++;
        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);

        $this->addSectionHeader($row++, "F. Soll-Deputat");
        $fRow = $row;
        $sheet->getStyle("M$row")->applyFromArray(['borders' => $this->borders['cell'], 'fill' => $this->fills['data']]);
        $sheet->getStyle("M$row")->getNumberFormat()->setFormatCode(XLConstants::NUMBER_00);
        $sheet->setCellValue("M$row", 18);
        $row++;
        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);

        $function = "=SUM(M$dRow,M$eRow)-M$fRow";
        $this->addFunctionHeader($row, 'G. Saldo zum Ende des Semesters und Deputatsübertrag für Folgesemester', $function);
        $sheet->getStyle("M$row")->applyFromArray(['fill' => $this->fills['index']]);
        $row++;
        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);

        $style = ['borders' => $this->borders['cell'], 'fill' => $this->fills['data']];
        $this->addSectionHeader($row++, "H. Sonstige Mitteilungen");
        $view->addRange("B$row", "M$row", $style);
        $row++;
        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);

        $style = ['font' => ['bold' => true, 'size' => 11]];
        $view->addRange("B$row", "G$row", $style, 'Ich versichere die Richtigkeit der vorstehenden Angaben:');
        $view->addRange("I$row", "M$row", $style, 'Gegenzeichnung Dekanat:');
        $row++;
        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);

        $view->addRange("B$row", "G$row", [], 'Gießen/Friedberg, den');
        $view->addRange("I$row", "M$row", [], 'Gießen/Friedberg, den');
        $row = $row + 3;

        $style = ['borders' => $this->borders['signature'], 'font' => ['size' => 11]];
        $view->addRange("C$row", "F$row", $style, 'Datum, Unterschrift');
        $view->addRange("J$row", "M$row", $style, 'Datum, Unterschrift');
        $row++;
        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);

        $sheet->setCellValue("B$row", 'Hinweise');
        $row++;
        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);
        $sheet->setCellValue("B$row", 'Prozessbeschreibung zum Umgang mit diesem Berichtsformular:');
        $row++;
        $sheet->getRowDimension($row++)->setRowHeight($this->heights['spacer']);

        $text = '(1) Ausfüllen des Excel-Deputatsberichts durch die berichtende Professorin/ den berichtenden ';
        $text .= 'Professor.';
        $sheet->setCellValue("B$row", $text);
        $row++;
        $text = '(2) Ausdruck des Formulars auf Papier durch die berichtende Professorin/ den berichtenden Professor.';
        $sheet->setCellValue("B$row", $text);
        $row++;
        $sheet->setCellValue("B$row", '(3) Versehen mit handschriftlich geleisteter Unterschrift.');
        $row++;
        $text = '(4) Rückgabe des Ausdrucks in Papierform an das Dekanat bis zum jeweils gesetzten Termin. ';
        $text .= 'Archivierung desselben im Dekanat.';
        $sheet->setCellValue("B$row", $text);
        $row++;
        $text = 'Ob der B-Bogen noch in elektronischer Form dem Dekanat zugesandt werden sollen, bleibt diesem ';
        $text .= 'überlassen, dies anzuordnen.';
        $sheet->setCellValue("B$row", $text);
        $row++;
    }

    /**
     * @inheritDoc
     */
    public function fill(): void
    {
        $this->view->getDefaultStyle()->getFont()->setName('Arial')->setSize();
        $this->addInstructionSheet();
        $this->addWorkSheet();
        $this->addProgramSheet();
        $this->addMethodsSheet();
        $this->view->setActiveSheetIndex(1);
    }

    /**
     * Adds formatting attributes for the work sheet.
     * @return void
     * @throws Exception
     */
    private function formatWorkSheet(): void
    {
        $sheet = $this->view->getActiveSheet();

        $pageSetUp = $sheet->getPageSetup();
        $pageSetUp->setOrientation(XLConstants::PORTRAIT);
        $pageSetUp->setPaperSize(XLConstants::A4);
        $pageSetUp->setFitToPage();

        $sheet->setShowGridlines();
        $sheet->getColumnDimension()->setWidth(1);
        $sheet->getColumnDimension('B')->setWidth(13.5);
        $sheet->getColumnDimension('C')->setWidth(10.71);
        $sheet->getColumnDimension('D')->setWidth(10.71);
        $sheet->getColumnDimension('E')->setWidth(5.71);
        $sheet->getColumnDimension('F')->setWidth(8.71);
        $sheet->getColumnDimension('G')->setWidth(11.71);
        $sheet->getColumnDimension('H')->setWidth(16.86);
        $sheet->getColumnDimension('I')->setWidth(16.6);
        $sheet->getColumnDimension('J')->setWidth(8.71);
        $sheet->getColumnDimension('K')->setWidth(16.43);
        $sheet->getColumnDimension('L')->setWidth(13.29);
        $sheet->getColumnDimension('M')->setWidth(12.29);
        $sheet->getColumnDimension('N')->setWidth(1);

    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        $person = Helpers\Persons::defaultName($this->personID);
        $term   = Helpers\Terms::getFullName($this->termID);
        $date   = Helpers\Dates::formatDate(date('Y-m-d'));

        return Text::sprintf('ORGANIZER_WORKLOAD_XLS_DESCRIPTION', $person, $term, $date);
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        $person = Helpers\Persons::lastNameFirst($this->personID);
        $term   = Helpers\Terms::name($this->termID);

        return Text::_('ORGANIZER_WORKLOAD') . ": $person - $term";
    }

}
