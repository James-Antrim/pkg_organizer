<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use Joomla\CMS\MVC\View\ListView as Grandpa;
use THM\Organizer\Adapters\Text;

/**
 * Class loads information into a tabular grid. The table format is kept largely in part to continue use of the web asset
 * 'table.columns'.
 */
abstract class TableView extends ListView
{
    public array $rows = [];

    /**
     * Generates the HTML output for the table headers.
     * @return void outputs HTML
     */
//    public function renderHeaders(): void
//    {
//        $levelOne = '';
//        $levelTwo = '';
//
//        foreach ($this->headers as $header) {
//            $colspan   = 1;
//            $dataClass = $header['text'] ? 'data-column' : 'resource-column';
//
//            if (isset($header['columns'])) {
//                if ($header['columns']) {
//                    $colspan           = count($header['columns']) ?: 1;
//                    $this->columnCount += $colspan;
//                    foreach ($header['columns'] as $column) {
//                        $levelTwo .= $this->getHeaderCell($column, $dataClass);
//                    }
//                }
//                else {
//                    $levelTwo .= $this->getHeaderCell([], $dataClass);
//                }
//
//            }
//            elseif ($header['text']) {
//                $this->columnCount++;
//            }
//
//            $levelOne .= $this->getHeaderCell($header, $dataClass, $colspan);
//        }
//
//        $this->columnCount = max($this->columnCount, count($this->headers));
//        $columnClass       = "columns-$this->columnCount";
//        echo "<tr class=\"$columnClass\">$levelOne</tr>";
//
//        if ($levelTwo) {
//            echo "<tr class=\"level-2 $columnClass\">$levelTwo</tr>";
//        }
//    }

    /**
     * Generates the HTML output for the individual rows.
     * @return void outputs HTML
     */
//    public function renderRows(): void
//    {
//        $columnClass = "class=\"columns-$this->columnCount\"";
//
//        foreach ($this->rows as $row) {
//            echo "<tr $columnClass>";
//            foreach ($row as $cell) {
//                if (isset($cell['label'])) {
//                    echo "<th class=\"resource-column\">{$cell['label']}</th>";
//                }
//                elseif (isset($cell['text'])) {
//                    echo "<td class=\"data-column\">{$cell['text']}</td>";
//                }
//            }
//            echo "</tr>";
//        }
//    }

    /**
     * Initializes the rows after the form and state properties have been initialized.
     * @return void
     */
    abstract protected function initializeRows(): void;

    /**
     * @inheritDoc
     */
    protected function initializeView(): void
    {
        Grandpa::initializeView();

        $this->empty = $this->empty ?: Text::_('EMPTY_RESULT_SET');

        $this->setSubTitle();
        $this->setSupplement();
        $this->initializeColumns();
        $this->initializeRows();
        $this->completeItems();

        // Layouts expect an array of object items named items
        $this->items = [];
        foreach ($this->rows as $key => $row) {
            $this->items[$key] = (object) $row;
        }
        $this->rows = [];

        $this->modifyDocument();
    }
}
