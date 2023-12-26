<?php

namespace THM\Organizer\Tables;

use Exception;
use Joomla\CMS\Table\Table as Base;
use ReflectionClass;
use THM\Organizer\Adapters\Application;

/**
 * Models the resource alluded to in the inheriting class name.
 * Wrapper to prevent unnecessary try/catch handling in client objects and standardized property retrieval after Joomla
 * declared their implementation deprecated.
 */
abstract class Table extends Base
{
    /**
     * Generally: INT(11) UNSIGNED
     *
     * Frequent associatons will have a larger definition, rudimentary resources smaller.
     * Participants table is not explicilty UNSIGNED because of the reference to the #__users table.
     *
     * @var int
     * @noinspection PhpMissingFieldTypeInspection
     */
    public $id;

    /**
     * Wraps the parent load function in a try catch clause to avoid redundant handling in other classes.
     *
     * @param   mixed  $keys     An optional primary key value to load the row by, or an array of fields to match.
     *                           If not set the instance property value is used.
     * @param   bool   $reset    True to reset the default values before loading the new row.
     *
     * @return  bool
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
     * Returns an associative array of object properties.
     *
     * @return  array
     */
    public function properties(): array
    {
        $properties = get_object_vars($this);

        // These are never internal
        foreach ($properties as $key => $value) {
            if (str_starts_with($key, '_')) {
                unset($properties[$key]);
            }
        }

        return $properties;
    }

    /**
     * Method to reset class properties to the defaults set in the class
     * definition.
     * - Ignores the primary key and private class properties.
     * - Override fixes problem that NOT NULL is being ignored by the 'Default' value from 'SHOW FULL COLUMNS' statement.
     * -- Consequently allows inheriting tables to complete their property typing regardless of whether they are default null.
     *
     * @return  void
     */
    public function reset(): void
    {
        $reflection = new ReflectionClass($this);

        // Get the default values for the class from the table.
        foreach ($this->getFields() as $column => $definition) {
            // If the property is not the primary key or private, skip it.
            if (in_array($column, $this->_tbl_keys) OR (str_starts_with($column, '_'))) {
                continue;
            }

            if ($definition->Null === 'NO' and $definition->Default === null) {
                try {
                    if ($property = $reflection->getProperty($column))
                    {
                        if ($default = $property->getDefaultValue()) {
                            $definition->Default = $default;
                            continue;
                        }

                        if ($type = $property->getType()) {
                            switch ($type->getName()) {
                                case 'bool':
                                    $definition->Default = false;
                                    break;
                                case 'float':
                                    $definition->Default = 0.0;
                                    break;
                                case 'int':
                                    $definition->Default = 0;
                                    break;
                                case 'string':
                                    $definition->Default = '';
                                    break;
                            }
                        }
                    }
                }
                catch (Exception $exception) {
                    Application::handleException($exception);
                }

            }
            $this->$column = $definition->Default;
        }
    }

    /**
     * @inheritDoc
     */
    public function store($updateNulls = true): bool
    {
        /** @noinspection PhpMultipleClassDeclarationsInspection */
        return parent::store($updateNulls);
    }
}