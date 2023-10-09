<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\XLS\Instances;

use Exception;
use Organizer\Helpers\Languages;
use Organizer\Layouts\XLS\ListLayout;
use Organizer\Views\XLS\Instances as View;
use Organizer\Views\XLS\XLConstants;

class Instances extends ListLayout
{
    /**
     * @var View
     */
    protected $view;

    /**
     * Adds a pa
     *
     * @param $pageNo
     *
     * @return void
     * @throws Exception
     */
    private function addGroupsSheet($pageNo)
    {
        $view = $this->view;
        $view->createSheet();
        $view->setActiveSheetIndex($pageNo);
        $sheet = $view->getActiveSheet();
        $title = Languages::_('ORGANIZER_GLOSSARY') . ' - ' . Languages::_('ORGANIZER_GROUPS');
        $sheet->setTitle($title);

        $sheet->getColumnDimension()->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(120);

        $style = [
            'alignment' => ['horizontal' => XLConstants::CENTER, 'vertical' => XLConstants::CENTER],
            'borders' => $this->borders['header'],
            'fill' => $this->fills['header'],
            'font' => ['size' => 12]
        ];
        $sheet->getStyle('A1:B1')->applyFromArray($style);
        $sheet->setCellValue("A1", Languages::_('ORGANIZER_ABBREVIATION'));
        $sheet->setCellValue("B1", Languages::_('ORGANIZER_GROUP'));
        $sheet->getRowDimension()->setRowHeight(22.5);

        ksort($view->groups);
        $lastRow = 2;
        $row     = 2;

        foreach ($view->groups as $abbreviation => $fullName) {
            $sheet->setCellValue("A$row", $abbreviation);
            $sheet->setCellValue("B$row", $fullName);
            $style         = ['border' => $this->borders['cell']];
            $style['fill'] = $row % 2 === 0 ? $this->fills['even'] : $this->fills['odd'];
            $sheet->getStyle("A$row:B$row")->applyFromArray($style);
            $lastRow = $row;
            $row++;
        }

        $sheet->setAutoFilter("A1:B$lastRow");
    }

    /**
     * @inheritDoc
     */
    public function fill()
    {
        $view = $this->view;
        $view->getDefaultStyle()->getFont()->setName('Arial')->setSize();
        $this->addListSheet(Languages::_('ORGANIZER_INSTANCES'));

        // So that the pages are later extensible
        $page = 1;

        if ($view->groups) {
            $this->addGroupsSheet($page);
        }

        $this->view->setActiveSheetIndex();
    }

    /**
     * @inheritDoc
     */
    public function getDescription(): string
    {
        return $this->view->model->getTitle();
    }

    /**
     * @inheritDoc
     */
    public function getTitle(): string
    {
        return $this->view->model->getTitle();
    }
}