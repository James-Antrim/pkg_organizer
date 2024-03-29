<?php

namespace THM\Organizer\Tables;

/**
 * Adds items for resources imported from LSF/HISin1
 */
trait LSFImported
{
    /**
     * The id of the entry in the LSF? software module.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $lsfID = null;

    /**
     * Sets a table column value.
     *
     * @param   string  $column   the name of the property to set
     * @param   mixed   $value    the value to set the property to
     * @param   mixed   $default  the default value to use if the value parameter is empty
     *
     * @return void
     */
    public function setColumn(string $column, mixed $value, mixed $default): void
    {
        if (property_exists($this, $column)) {
            $this->$column = empty($value) ? $default : $value;
        }
    }
}