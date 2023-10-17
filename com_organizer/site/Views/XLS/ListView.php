<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\XLS;

use JetBrains\PhpStorm\NoReturn;
use Joomla\Registry\Registry;
use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers;

abstract class ListView extends BaseView
{
    public array $headers = [];
    public array $items = [];
    protected array $rowStructure = [];
    public Registry $state;

    /**
     * Checks user authorization and initiates redirects accordingly.
     * @return void
     */
    protected function authorize(): void
    {
        if (!Helpers\Can::administrate()) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    #[NoReturn] public function display(): void
    {
        $this->authorize();
        $this->state = $this->model->getState();
        $this->setHeaders();
        $this->items = $this->model->getItems();

        if ($this->items) {
            $this->structureItems();
        }

        parent::display();
    }

    /**
     * Function to set the object's headers property
     * @return void sets the object headers property
     */
    abstract protected function setHeaders(): void;

    /**
     * Processes an individual list item resolving it to an array of table data values.
     *
     * @param object $item the item to be displayed in a table row
     *
     * @return array an array of property columns with their values
     */
    abstract protected function structureItem(object $item): array;

    /**
     * Processes the items in a manner specific to the view, so that a generalized  output in the layout can occur.
     * @return void processes the class items property
     */
    protected function structureItems(): void
    {
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item) {
            $structuredItems[$index] = $this->structureItem($item);
            $index++;
        }

        $this->items = $structuredItems;
    }
}