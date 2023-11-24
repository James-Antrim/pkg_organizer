<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use Exception;
use Joomla\CMS\Object\CMSObject;
use Joomla\CMS\Table\Table;
use Joomla\Utilities\ArrayHelper;
use THM\Organizer\Adapters\{Application, Input, FormFactory, MVCFactory};

/**
 * Class for editing a single resource record, based loosely on AdminModel, but without all the extra code it now caries
 * with it.
 */
abstract class EditModel extends FormModel
{
    /**
     * The resource's table class.
     * @var string
     */
    protected string $tableClass = '';

    /**
     * @inheritDoc
     * Wraps the parent constructor to ensure inheriting classes specify their respective table classes.
     */
    public function __construct($config, MVCFactory $factory, FormFactory $formFactory)
    {
        if (empty($this->tableClass)) {
            $childClass = get_called_class();
            $exception  = new Exception("$childClass has not specified its associated table.");
            Application::handleException($exception);
        }

        parent::__construct($config, $factory, $formFactory);
    }

    /**
     * Retrieves a resource record.
     * @return  CMSObject  Object on success, false on failure.
     */
    public function getItem(): CMSObject
    {
        $rowID = Input::getSelectedID();
        $table = $this->getTable();
        $table->load($rowID);

        // Convert to the CMSObject before adding other data.
        $properties = $table->getProperties();

        /** @var CMSObject $item */
        $item = ArrayHelper::toObject($properties, CMSObject::class);

        return $item;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     the table name, unused
     * @param   string  $prefix   the class prefix, unused
     * @param   array   $options  configuration array for model, unused
     *
     * @return  Table  a table object
     */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        $fqn = "\\THM\\Organizer\\Tables\\$this->tableClass";

        return new $fqn();
    }

    /**
     * @inheritDoc
     */
    protected function loadFormData(): ?CMSObject
    {
        return $this->getItem();
    }
}