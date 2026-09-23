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
use THM\Organizer\Adapters\Text;
use THM\Organizer\Layouts\XLS\ListLayout;
use THM\Organizer\Models\Instances as Model;
use THM\Organizer\Views\XLS\{BaseView, Instances as View, XLConstants};

class Instances extends ListLayout
{
    /**
     * @var View
     */
    protected BaseView $view;

    /**
     * Adds a pa
     *
     * @param $pageNo
     *
     * @return void
     * @throws Exception
     */
    private function addGroupsSheet($pageNo): void
    {
        $view = $this->view;
        $view->createSheet();
        $view->setActiveSheetIndex($pageNo);
        $sheet = $view->getActiveSheet();
        $title = Text::_('GLOSSARY') . ' - ' . Text::_('GROUPS');
        $sheet->setTitle($title);

        $sheet->getColumnDimension()->setWidth(20);
        $sheet->getColumnDimension('B')->setWidth(120);

        $style = [
            'alignment' => ['horizontal' => XLConstants::CENTER, 'vertical' => XLConstants::CENTER],
            'borders'   => $this->borders['header'],
            'fill'      => $this->fills['header'],
            'font'      => ['size' => 12]
        ];
        $sheet->getStyle('A1:B1')->applyFromArray($style);
        $sheet->setCellValue("A1", Text::_('ABBREVIATION'));
        $sheet->setCellValue("B1", Text::_('GROUP'));
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

    /** @inheritDoc */
    public function fill(): void
    {
        $view = $this->view;
        $view->getDefaultStyle()->getFont()->setName('Arial')->setSize();
        $this->addListSheet(Text::_('INSTANCES'));

        // So that the pages are later extensible
        $page = 1;

        if ($view->groups) {
            $this->addGroupsSheet($page);
        }

        $this->view->setActiveSheetIndex();
    }

    /** @inheritDoc */
    public function getDescription(): string
    {
        /** @var Model $model */
        $model = $this->view->model;
        return $model->title();
    }

    /** @inheritDoc */
    public function getTitle(): string
    {
        /** @var Model $model */
        $model = $this->view->model;
        return $model->title();
    }
}