<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use Exception;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers\Can;
use THM\Organizer\Tables\Table;

/**
 * Class which manages stored data.
 */
abstract class BaseModel extends BaseDatabaseModel
{
    use Named;

    protected array $selected = [];

    /**
     * BaseModel constructor.
     *
     * @param   array  $config
     */
    public function __construct($config = [])
    {
        try {
            parent::__construct($config);
        }
        catch (Exception $exception) {
            Application::message($exception->getMessage(), Application::ERROR);

            return;
        }

        $this->setContext();
    }

    /**
     * Authorizes the user.
     * @return void
     */
    protected function authorize(): void
    {
        if (!Can::administrate()) {
            Application::error(403);
        }
    }

    /**
     * Removes entries from the database.
     * @return bool true on success, otherwise false
     */
    public function delete(): bool
    {
        if (!$this->selected = Input::getSelectedIDs()) {
            return false;
        }

        $this->authorize();

        $success = true;

        try {
            foreach ($this->selected as $selectedID) {
                $table   = $this->getTable();
                $success = ($success and $table->delete($selectedID));
            }
        }
        catch (Exception $exception) {
            Application::message($exception->getMessage(), Application::ERROR);

            return false;
        }

        // TODO: create a message with an accurate count of successes.

        return $success;
    }

    /**
     * Attempts to save the resource.
     *
     * @param   array  $data  the data from the form
     *
     * @return int
     */
    public function save(array $data = []): int
    {
        $this->authorize();

        $data = empty($data) ? Input::getFormItems() : $data;

        try {
            /* @var Table $table */
            $table = $this->getTable();
        }
        catch (Exception $exception) {
            Application::message($exception->getMessage(), Application::ERROR);

            return false;
        }

        return $table->save($data) ? $table->id : 0;
    }

    /**
     * Method to save an existing resource as a copy
     *
     * @param   array  $data  the data to be used to create the program when called from the program helper
     *
     * @return int
     */
    public function save2copy(array $data = []): int
    {
        $data = empty($data) ? Input::getFormItems() : $data;
        unset($data['id']);

        return $this->save($data);
    }

    /**
     * Alters the state of a binary property.
     * @return bool true on success, otherwise false
     * @throws Exception
     */
    public function toggle(): bool
    {
        if (!$resourceID = Input::getID()) {
            return false;
        }

        // Necessary for access checks in mergeable resources.
        $this->selected = [$resourceID];
        $this->authorize();

        $attribute = Input::getCMD('attribute');
        $table     = $this->getTable();

        $tableFields = $table->getFields();
        if (array_key_exists($attribute, $tableFields)) {
            if (!$table->load($resourceID)) {
                return false;
            }

            $table->$attribute = !$table->$attribute;

            return $table->store();
        }

        return false;
    }
}
