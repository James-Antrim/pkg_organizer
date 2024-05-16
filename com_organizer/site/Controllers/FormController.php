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

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\Table\Table as JTable;
use Joomla\Input\Input as JInput;
use ReflectionNamedType;
use ReflectionObject;
use ReflectionUnionType;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Tables\Table;

/**
 * Handles authorization, display, data persistence and redirection for form views.
 */
abstract class FormController extends Controller
{
    /** @var array The prepared form data. */
    protected array $data;

    /** @var string The list view to redirect to after completion of form view functions. */
    protected string $list = '';

    /** @inheritDoc */
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
        $this->setRedirect("$this->baseURL&view=" . strtolower($this->name) . "&id=$id&layout=edit");
    }

    /**
     * Closes the form view without saving changes.
     * @return void
     */
    public function cancel(): void
    {
        $this->setRedirect("$this->baseURL&view=" . strtolower($this->list));
    }

    /**
     * Filters field data for actual letters and accepted special characters.
     *
     * @param   string  $value  the raw value
     *
     * @return string
     */
    protected static function cleanAlpha(string $value): string
    {
        return preg_replace('/[^A-ZÀ-ÖØ-Þa-zß-ÿ\p{N}_.\-\']/', ' ', $value);
    }

    /**
     * Filters field data for actual letters, accepted special characters and numbers.
     *
     * @param   string  $value  the raw value
     *
     * @return string
     */
    protected static function cleanAlphaNum(string $value): string
    {
        return preg_replace('/[^A-ZÀ-ÖØ-Þa-zß-ÿ\d\p{N}_.\-\']/', ' ', $value);
    }

    /**
     * Instances a table object corresponding to the registered list.
     * @return JTable
     */
    protected function getTable(): JTable
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
        $data      = [];
        $formItems = Input::getFormItems();

        /** @var Table $table */
        $table      = $this->getTable();
        $properties = $table->getProperties();
        $reflection = new ReflectionObject($table);

        foreach ($properties as $column => $default) {

            try {
                $property = $reflection->getProperty($column);
            }
            catch (Exception $exception) {
                Application::handleException($exception);
            }

            $comment = $property->getDocComment();

            // If there is no documented default value the potential return value of null as default is meaningless.
            $defaults = str_contains($comment, 'DEFAULT');

            /** @var ReflectionNamedType|ReflectionUnionType $type */
            $rType = $property->getType();

            // <type>|null get the first one
            if (get_class($rType) === 'ReflectionUnionType') {
                $rType = $rType->getTypes()[0];
            }

            $type  = $rType->getName();
            $value = !isset($formItems[$column]) ? null : $formItems[$column];

            switch ($type) {
                case 'float':
                    $default       = $defaults ? $property->getDefaultValue() : 0.0;
                    $data[$column] = is_null($value) ? $default : Input::filter($value, 'float');
                    break;
                case 'int':
                    // SQL doesn't technically have bool, so it has to be mapped over int. I've used the comment for this.
                    if (str_contains($comment, '@bool')) {
                        $default       = ($defaults and $property->getDefaultValue());
                        $data[$column] = is_null($value) ? (int) $default : (int) Input::filter($value, 'bool');
                    }
                    else {
                        // Global implicit id validation
                        if ($column === 'id' and !is_numeric($value)) {
                            Application::error(400);
                        }

                        $default       = $defaults ? $property->getDefaultValue() : 0;
                        $data[$column] = is_null($value) ? $default : Input::filter($value, 'int');
                    }
                    break;
                case 'string':
                    $default       = $defaults ? $property->getDefaultValue() : '';
                    $data[$column] = is_null($value) ? $default : Input::filter($value);
                    break;
            }
        }

        return $data;
    }

    /**
     * Code common in storing resource data.
     * @return int
     */
    protected function process(): int
    {
        $this->checkToken();
        $this->authorize();

        $id         = Input::getID();
        $this->data = $this->prepareData();

        // For save to copy, will otherwise be identical.
        $this->data['id'] = $id;
        $table            = $this->getTable();

        return $this->store($table, $this->data, $id);
    }

    /**
     * Saves resource data and redirects to the list view.
     * @return void
     */
    public function save(): void
    {
        $this->process();
        $this->setRedirect("$this->baseURL&view=" . strtolower($this->list));
    }

    /**
     * Saves resource data and redirects to the form view for the copy.
     * @return void
     */
    public function save2copy(): void
    {
        // Force new attribute creation
        Input::set('id', 0);
        $this->process();
        $this->setRedirect("$this->baseURL&view=" . strtolower($this->list));
    }

    /**
     * Saves resource data and redirects to an empty form view.
     * @return void
     */
    public function save2new(): void
    {
        $this->process();
        $this->setRedirect("$this->baseURL&view=" . strtolower($this->name) . '&id=0&layout=edit');
    }

    /**
     * Reusable function to store data in an Incremented table.
     *
     * @param   JTable  $table  an Incremented table
     * @param   array   $data   the data to store
     * @param   int     $id     the id of the row in which to store the data
     *
     * @return int the id of the table row on success, otherwise the id parameter
     * @uses Table
     */
    protected function store(JTable $table, array $data, int $id = 0): int
    {
        if ($id and !$table->load($id)) {
            Application::message('412', Application::ERROR);

            return $id;
        }

        if (!$table->save($data)) {
            Application::message('NOT_SAVED');
            return $id;
        }
        else {
            Application::message('SAVED');
            /** @var Table $table */
            return $table->id;
        }
    }

    /**
     * Removes excess spaces from a form value.
     *
     * @param   string  $value
     *
     * @return string
     */
    protected static function trim(string $value): string
    {
        // Replace ideographic space
        $value = str_replace(chr(0xE3) . chr(0x80) . chr(0x80), ' ', $value);
        // Replace no-break space
        $value = str_replace(chr(0xC2) . chr(0xA0), ' ', $value);
        // Remove leading & trailing spaces
        $value = trim($value);
        // Remove surfeit spaces
        return preg_replace('/ +/', ' ', $value);
    }

    /**
     * Validates the form data beyond the implicit type validation performed during prepareData.
     *
     * @param   array  $data      the form data to validate
     * @param   array  $required  the required fields
     *
     * @return void
     */
    protected function validate(array &$data, array $required = []): void
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $required) and empty($value)) {
                Application::error(400);
                return;
            }
        }
    }
}