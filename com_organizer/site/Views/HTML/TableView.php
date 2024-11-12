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
 * Class loads information into a tabular grid. The list template is kept largely to continue use of the web asset
 * 'table.columns'.
 */
abstract class TableView extends ListView
{
    public array $rows = [];

    /** @inheritDoc */
    public function __construct(array $config)
    {
        $this->toDo[] = 'Add automatic row re-summation after the table.columns WA hides a column.';
        parent::__construct($config);
    }

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
