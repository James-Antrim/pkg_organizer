<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Table\Table;
use Joomla\Input\Input as JInput;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Tables\Incremented;

abstract class FormController extends Controller
{
    /**
     * The list view to redirect to after completion of form view functions.
     * @var string
     */
    protected string $list = '';

    /**
     * @inheritDoc
     */
    public function __construct(
        $config = [],
        MVCFactoryInterface $factory = null,
        ?CMSApplication $app = null,
        ?JInput $input = null
    )
    {
        if (empty($this->list)) {
            Application::error(501);
        }

        parent::__construct($config, $factory, $app, $input);
    }

    /**
     * Saves resource data and redirects to the same view of the same resource.
     * @return void
     */
    public function apply(): void
    {
        $id = $this->process();
        $this->setRedirect("$this->baseURL&view=$this->name&id=$id");
    }

    /**
     * Closes the form view without saving changes.
     * @return void
     */
    public function cancel(): void
    {
        $this->setRedirect("$this->baseURL&view=$this->list");
    }

    /**
     * Instances a table object corresponding to the registered list.
     * @return Table
     */
    protected function getTable(): Table
    {
        $fqName = 'THM\\Organizer\\Tables\\' . $this->list;

        return new $fqName();
    }

    /**
     * Prepares the data to be saved.
     * @return array
     */
    protected function prepareData(): array
    {
        return Input::getFormItems();
    }

    /**
     * Code common in storing resource data.
     * @return int
     */
    protected function process(): int
    {
        $this->checkToken();
        $this->authorize();
        $data = $this->prepareData();

        $id    = Input::getID();
        $table = $this->getTable();

        return $this->store($table, $data, $id);
    }

    /**
     * Saves resource data and redirects to the list view.
     * @return void
     */
    public function save(): void
    {
        $this->process();
        $this->setRedirect("$this->baseURL&view=$this->list");
    }

    /**
     * Saves resource data and redirects to the form view for the copy.
     * @return void
     */
    public function save2copy(): void
    {
        // Force new attribute creation
        Input::set('id', 0);
        $id = $this->process();
        $this->setRedirect("$this->baseURL&view=$this->name&id=$id");
    }

    /**
     * Saves resource data and redirects to an empty form view.
     * @return void
     */
    public function save2new(): void
    {
        $this->process();
        $this->setRedirect("$this->baseURL&view=$this->name&id=0");
    }

    /**
     * Reusable function to store data in an Incremented table.
     *
     * @param   Table  $table  an Incremented table
     * @param   array  $data   the data to store
     * @param   int    $id     the id of the row in which to store the data
     *
     * @return int the id of the table row on success, otherwise the id parameter
     * @uses Incremented
     */
    protected function store(Table $table, array $data, int $id = 0): int
    {
        if ($id and !$table->load($id)) {
            Application::message('412', Application::ERROR);

            return $id;
        }

        if ($table->save($data)) {
            /** @var Incremented $table */
            return $table->id;
        }

        Application::message($table->getError(), Application::ERROR);

        return $id;
    }
}