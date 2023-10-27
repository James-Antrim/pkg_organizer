<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use THM\Organizer\Adapters\{Database, HTML, Input, Text};
use stdClass;

/**
 * Trait for fields whose output should be suppressed if no options beyond those defined in the manifest were found.
 */
trait Mergeable
{
    protected $resource;

    protected $selectedIDs;

    /**
     * Creates an array of Joomla option objects from the given array of values.
     *
     * @param   array  $values
     *
     * @return stdClass[]
     */
    protected function createOptions(array $values): array
    {
        $options = [];
        foreach ($values as $value) {
            if (empty($value)) {
                continue;
            }
            $options[] = HTML::option($value, $value);
        }

        if (empty($options)) {
            $options[] = HTML::option('', Text::_('ORGANIZER_NONE_GIVEN'));
        }
        elseif (count($options) > 1) {
            /* @var OptionsField $this */
            $this->required = true;
            array_unshift(
                $options,
                HTML::option('', Text::_('ORGANIZER_SELECT_VALUE'))
            );
        }

        return $options;
    }

    /**
     * Gets the saved values for the selected resource IDs.
     * @return array
     */
    protected function getValues(): array
    {
        $column = $this->getAttribute('name');
        $query  = Database::getQuery();
        $table  = $this->resource === 'category' ? 'categories' : "{$this->resource}s";
        $query->selectX(["DISTINCT BINARY $column AS value"], $table, 'id', $this->selectedIDs)
            ->order('value ASC');
        Database::setQuery($query);

        return Database::loadColumn();
    }

    /**
     * Validates basic information needed to merge values.
     * @return bool
     */
    protected function validate(): bool
    {
        $this->selectedIDs = Input::getSelectedIDs();
        $this->resource    = str_replace('_merge', '', Input::getView());
        $validResources    = ['category', 'field', 'group', 'event', 'method', 'room', 'roomtype', 'participant', 'person'];

        return !(empty($this->selectedIDs) or empty($this->resource) or !in_array($this->resource, $validResources));
    }
}