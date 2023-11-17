<?php
/**
 * @package     Organizer\Layouts\XLS
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\XLS;


use Exception;
use THM\Organizer\Views\XLS\ListView;
use THM\Organizer\Views\XLS\XLConstants;

abstract class ListLayout extends BaseLayout
{

    public array $borders = [
        'cell'   => [
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
        'header' => [
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
        ]
    ];

    /**
     * @var array[] Fill definitions
     */
    public array $fills = [
        'even'   => [
            'type'  => XLConstants::SOLID,
            'color' => ['rgb' => 'FFFFFF']
        ],
        'header' => [
            'type'  => XLConstants::SOLID,
            'color' => ['rgb' => '80BA24']
        ],
        'odd'    => [
            'type'  => XLConstants::SOLID,
            'color' => ['rgb' => 'DFEEC8']
        ]
    ];

    /**
     * @var ListView
     */
    protected $view;

    /**
     * Adds the main list sheet
     *
     * @param   string      $name
     * @param   string      $orientation
     * @param   string|int  $paper
     *
     * @return void
     * @throws Exception
     */
    protected function addListSheet(
        string $name,
        string $orientation = XLConstants::LANDSCAPE,
        string $paper = XLConstants::A4
    )
    {
        $view = $this->view;
        $view->createSheet();
        $view->setActiveSheetIndex();
        $sheet = $view->getActiveSheet();
        $sheet->setTitle($name);

        $pageSetUp = $sheet->getPageSetup();
        $pageSetUp->setOrientation($orientation);
        $pageSetUp->setPaperSize($paper);
        $pageSetUp->setFitToPage();

        $this->fillHeaders();
        $this->fillItems();
    }

    /**
     * Adds list headers to the active sheet.
     * @return void
     * @throws Exception
     */
    protected function fillHeaders()
    {
        $view  = $this->view;
        $sheet = $view->getActiveSheet();
        $style = [
            'alignment' => ['horizontal' => XLConstants::CENTER, 'vertical' => XLConstants::CENTER],
            'borders'   => $this->borders['header'],
            'fill'      => $this->fills['header'],
            'font'      => ['size' => 12]
        ];

        $column = 'A';

        foreach ($view->headers as &$header) {
            $header['column'] = $column;
            $coords           = "{$column}1";
            $sheet->getColumnDimension($header['column'])->setWidth($header['width']);
            $sheet->setCellValue($coords, $header['text']);
            $sheet->getStyle($coords)->applyFromArray($style);
            $column++;
        }

        $sheet->getRowDimension()->setRowHeight(22.5);
    }

    /**
     * Adds list items to the active sheet.
     * @return void
     * @throws Exception
     */
    protected function fillItems()
    {
        $lastColumn = 'A';
        $lastRow    = 1;
        $row        = 2;
        $view       = $this->view;
        $sheet      = $view->getActiveSheet();

        foreach ($view->items as $instance) {
            $fill  = $row % 2 === 0 ? 'even' : 'odd';
            $lines = 1;
            $style = [
                'alignment' => ['horizontal' => XLConstants::CENTER, 'vertical' => XLConstants::CENTER, 'wrap' => true],
                'borders'   => $this->borders['cell'],
                'fill'      => $this->fills[$fill]
            ];

            foreach ($instance as $property => $value) {
                if (empty($view->headers[$property])) {
                    continue;
                }

                if (is_array($value)) {
                    $count = count($value);
                    $lines = max($count, $lines);
                    $value = implode("\n", $value);
                }

                $header     = $view->headers[$property];
                $column     = $header['column'];
                $lastColumn = max($column, $lastColumn);
                $coords     = "$column$row";
                $sheet->getStyle($coords)->applyFromArray($style);

                $sheet->setCellValue($coords, $value);
            }

            $height = ($lines * 13) + 10;
            $sheet->getRowDimension($row)->setRowHeight($height);
            $lastRow = $row;
            $row++;
        }

        $sheet->setAutoFilter("A1:$lastColumn$lastRow");
    }

}