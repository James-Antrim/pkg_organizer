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

use THM\Organizer\Adapters\{Database as DB, HTML, Input, Text};
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
            $options[] = HTML::option('', Text::_('NONE_GIVEN'));
        }
        elseif (count($options) > 1) {
            /* @var Options $this */
            $this->required = true;
            array_unshift(
                $options,
                HTML::option('', Text::_('SELECT_VALUE'))
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
        $column = DB::qn($this->getAttribute('name'), 'value');
        $query  = DB::query();
        $table  = strtolower($this->resource);
        $query->select(["DISTINCT BINARY $column"])
            ->from(DB::qn("#__organizer_$table"))
            ->whereIn(DB::qn('id'), $this->selectedIDs)
            ->order(DB::qn('value') . ' ASC');
        DB::set($query);

        return DB::column();
    }

    /**
     * Validates the context from which the field was called.
     *
     * @return  bool
     */
    protected function validateContext(): bool
    {
        $this->selectedIDs = Input::selectedIDs();
        $this->resource    = str_replace('Merge', '', Input::view());

        $validResources = [
            'Categories',
            'Fields',
            'Groups',
            'Events',
            'Methods',
            'Rooms',
            'Roomtypes',
            'Participants',
            'Persons'
        ];

        return !(empty($this->selectedIDs) or empty($this->resource) or !in_array($this->resource, $validResources));
    }
}