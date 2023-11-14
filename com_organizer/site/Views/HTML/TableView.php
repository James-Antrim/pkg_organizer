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

use Joomla\Registry\Registry;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\Document;
use THM\Organizer\Adapters\Form;

/**
 * Class loads a filtered set of resources into the display context. Specific resource determined by extending class.
 */
abstract class TableView extends BaseView
{
    protected string $layout = 'table';

    public array $activeFilters;

    public int $columnCount = 0;

    public Form $filterForm;

    public array $headers;

    public int $labelCount = 1;

    public $pagination = null;

    public array $rows;

    public Registry $state;

    /**
     * Adds a toolbar and title to the view.
     * @return void  sets context variables
     */
    abstract protected function addToolBar(): void;

    /**
     * @inheritdoc
     */
    public function display($tpl = null): void
    {
        $this->state         = $this->get('State');
        $this->filterForm    = $this->get('FilterForm');
        $this->activeFilters = $this->get('ActiveFilters');

        $this->setOverrides();
        $this->setHeaders();
        $this->setRows($this->get('Items'));
        $this->pagination = $this->get('Pagination');

        $this->addToolBar();
        $this->setSubtitle();
        $this->setSupplement();
        $this->modifyDocument();

        parent::display($tpl);
    }

    /**
     * Creates the HTML which will later be rendered. This is not output directly to allow for multilevel headers.
     *
     * @param   array   $cell       the data to use for HTML generation
     * @param   string  $dataClass  the class denoting the displayed information's column type
     * @param   int     $colSpan    the number of columns the generated header cell will span
     *
     * @return string
     */
    private function getHeaderCell(array $cell, string $dataClass, int $colSpan = 0): string
    {
        $attributes = "class=\"$dataClass\"";
        $attributes .= $colSpan ? " colspan=\"$colSpan\"" : '';
        $text       = !empty($cell['text']) ? $cell['text'] : '';

        return "<th $attributes>" . $text . '</th>';
    }

    /**
     * Processes an individual list item resolving it to an array of table data values.
     *
     * @param   object  $resource  the resource whose information is displayed in the row
     *
     * @return array an array of property columns with their values
     */
    abstract protected function getRow(object $resource): array;

    /**
     * Creates a label with tooltip for the resource row.
     *
     * @param   object  $resource  the resource to be displayed in the row
     *
     * @return string[]  the label inclusive tooltip to be displayed
     */
    abstract protected function getRowLabel(object $resource): array;

    /**
     * Creates a table cell.
     *
     * @param   array  $data  the data used to structure the cell
     *
     * @return string[] structured cell information
     */
    abstract protected function getDataCell(array $data): array;

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        //Document::style('table');
    }

    /**
     * Generates the HTML output for the table headers.
     * @return void outputs HTML
     */
    public function renderHeaders(): void
    {
        $levelOne = '';
        $levelTwo = '';

        foreach ($this->headers as $header) {
            $colspan   = 1;
            $dataClass = $header['text'] ? 'data-column' : 'resource-column';

            if (isset($header['columns'])) {
                if ($header['columns']) {
                    $colspan           = count($header['columns']) ?: 1;
                    $this->columnCount += $colspan;
                    foreach ($header['columns'] as $column) {
                        $levelTwo .= $this->getHeaderCell($column, $dataClass);
                    }
                }
                else {
                    $levelTwo .= $this->getHeaderCell([], $dataClass);
                }

            }
            elseif ($header['text']) {
                $this->columnCount++;
            }

            $levelOne .= $this->getHeaderCell($header, $dataClass, $colspan);
        }

        $this->columnCount = max($this->columnCount, count($this->headers));
        $columnClass       = "columns-$this->columnCount";
        echo "<tr class=\"$columnClass\">$levelOne</tr>";

        if ($levelTwo) {
            echo "<tr class=\"level-2 $columnClass\">$levelTwo</tr>";
        }
    }

    /**
     * Generates the HTML output for the individual rows.
     * @return void outputs HTML
     */
    public function renderRows(): void
    {
        $columnClass = "class=\"columns-$this->columnCount\"";

        foreach ($this->rows as $row) {
            echo "<tr $columnClass>";
            foreach ($row as $cell) {
                if (isset($cell['label'])) {
                    echo "<th class=\"resource-column\">{$cell['label']}</th>";
                }
                elseif (isset($cell['text'])) {
                    echo "<td class=\"data-column\">{$cell['text']}</td>";
                }
            }
            echo "</tr>";
        }
    }

    /**
     * Sets the table header information
     * @return void sets the headers property
     */
    abstract protected function setHeaders(): void;

    /**
     * Sets class properties of inheriting views necessary for individualized table definitions.
     * @return void sets properties of inheriting classes
     */
    abstract protected function setOverrides(): void;

    /**
     * Processes the resources for display in rows.
     *
     * @param   array  $resources  the resources to be displayed
     *
     * @return void processes the class rows property
     */
    protected function setRows(array $resources): void
    {
        $rows = [];

        foreach ($resources as $resource) {
            $rows[$resource->id] = $this->getRow($resource);
        }

        $this->rows = $rows;
    }
}
