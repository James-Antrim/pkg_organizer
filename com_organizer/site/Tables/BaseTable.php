<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Tables;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\Table\Table;
use Joomla\Registry\Registry;
use THM\Organizer\Adapters\Application;

/**
 * Abstract class extending Table.
 */
abstract class BaseTable extends Table
{
    /**
     * The primary key.
     * INT (UN)SIGNED (11|20) NOT NULL AUTO_INCREMENT
     * @var int
     */
    public $id;

    /**
     * @inheritDoc
     */
    public function __construct(string $table)
    {
        $dbo = Factory::getDbo();
        parent::__construct($table, 'id', $dbo);
    }

    /**
     * Binds the table properties with data stored in a registry.
     *
     * @param   Registry  $registry  the registry object
     *
     * @return bool
     */
    public function bindRegistry(Registry $registry): bool
    {
        // Bind the source value, excluding the ignored fields.
        foreach (array_keys($this->getProperties()) as $property) {
            if ($registry->exists($property)) {
                $this->$property = $registry->get($property);
            }
        }

        return true;
    }

    /**
     * Wraps the parent load function in a try catch clause to avoid redundant handling in other classes.
     *
     * @param   mixed  $keys     An optional primary key value to load the row by, or an array of fields to match.
     *                           If not set the instance property value is used.
     * @param   bool   $reset    True to reset the default values before loading the new row.
     *
     * @return  bool  True if successful, otherwise false
     */
    public function load($keys = null, $reset = true): bool
    {
        try {
            return parent::load($keys, $reset);
        }
        catch (Exception $exception) {
            Application::message($exception->getMessage(), Application::ERROR);

            return false;
        }
    }

    /**
     * Sets a given property from a table, loading the table as necessary.
     *
     * @param   string  $column   the name of the property to set
     * @param   mixed   $value    the value to set the property to
     * @param   mixed   $default  the default value to use if the value parameter is empty
     *
     * @return void modifies the column property value
     */
    public function setColumn(string $column, $value, $default)
    {
        if (property_exists($this, $column)) {
            $this->$column = empty($value) ? $default : $value;
        }
    }

    /**
     * @inheritDoc
     */
    public function store($updateNulls = true): bool
    {
        return parent::store($updateNulls);
    }
}
