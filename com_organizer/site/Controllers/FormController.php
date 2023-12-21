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
use Joomla\CMS\Table\Table as JTable;
use Joomla\Input\Input as JInput;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Tables\Table;

/**
 * Handles authorization, display, data persistence and redirection for form views.
 */
abstract class FormController extends Controller
{
    protected const NULL_VALUE = -1;

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
        foreach ($data = Input::getFormItems() as $key => $value) {
            $data[$key] = self::trim($value);
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

        $id   = Input::getID();
        $data = $this->prepareData();

        // For save to copy, will otherwise be identical.
        $data['id'] = $id;
        $table      = $this->getTable();

        if ($result = $this->store($table, $data, $id)) {
            Application::message('SAVED');
        }
        else {
            Application::message('NOT_SAVED');
        }
        return $result;
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
        $this->process();
        $this->setRedirect("$this->baseURL&view=$this->list");
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
     * @param   JTable  $table  an Incremented table
     * @param   array   $data   the data to store
     * @param   int     $id     the id of the row in which to store the data
     *
     * @return int the id of the table row on success, otherwise the id parameter
     * @uses Incremented
     */
    protected function store(JTable $table, array $data, int $id = 0): int
    {
        if ($id and !$table->load($id)) {
            Application::message('412', Application::ERROR);

            return $id;
        }

        if ($table->save($data)) {
            /** @var Table $table */
            return $table->id;
        }

        return $id;
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
     * Validates form
     *
     * @param   array  $data
     * @param   array  $required
     * @param   array  $nullable
     * @param   array  $numeric
     *
     * @return void
     */
    protected function validate(array &$data, array $required = [], array $nullable = [], array $numeric = []): void
    {
        foreach ($data as $key => $value) {
            if (in_array($key, $required) and empty($value)) {
                Application::error(400);
                return;
            }

            // Implicit null => no option had to be selected  / Explicit null => the null value option was selected
            if (in_array($key, $nullable) and (empty($value) or (int) $value === self::NULL_VALUE)) {
                $data[$key] = null;
                continue;
            }

            if (in_array($key, $numeric)) {
                if (!is_numeric($value)) {
                    Application::message('400');
                    return;
                }

                if (str_contains($value, '.')) {
                    $data[$key] = (float) $value;
                    continue;
                }

                $data[$key] = (int) $value;
            }
        }
    }
}