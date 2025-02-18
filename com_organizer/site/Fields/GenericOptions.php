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

use Joomla\CMS\Form\Field\ListField;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input};

/** @inheritDoc */
class GenericOptions extends ListField
{
    /** @inheritDoc */
    protected function getOptions(): array
    {
        $defaultOptions = parent::getOptions();

        $query = DB::query();

        $glue = $this->getAttribute('glue');

        if (!$textColumn = $this->getAttribute('textcolumn')
            or !$valueColumn = $this->getAttribute('valuecolumn')
            or !$table = $this->getAttribute('table')) {
            return $defaultOptions;
        }

        // SELECT
        $textColumns = explode(',', $textColumn);
        $tag         = $this->getAttribute('localized', false) ? '_' . Application::tag() : '';

        foreach ($textColumns as $key => $value) {
            $textColumns[$key] = DB::qn($value . $tag);
        }

        if (count($textColumns) === 1 or empty($glue)) {
            $textColumn = $textColumns[0];
        }
        else {
            $textColumn = '( ' . $query->concatenate($textColumns, $glue) . ' )';
        }

        $textColumn  = $textColumn . ' AS ' . DB::qn('text');
        $valueColumn = 'DISTINCT ' . DB::qn($valueColumn, 'value');

        $query->select([$valueColumn, $textColumn]);

        // FROM & INNER JOIN
        $from   = true;
        $tables = explode(',', $table);

        foreach ($tables as $table) {
            if (str_contains($table, ' AS ')) {
                [$table, $predicate] = explode(' AS ', $table);

                $conditional = str_contains($predicate, ' ON ');

                // Join conditions in from table or no conditions in join table => error
                if (($conditional and $from) or (!$conditional and !$from)) {
                    return $defaultOptions;
                }

                if ($from) {
                    $query->from(DB::qn('#__' . $table, $predicate));
                    $from = false;
                    continue;
                }

                [$alias, $condition] = explode(' AS ', $predicate);
                $pieces = explode(' ', $condition);

                // column1Value = column2Value condition format was not adhered to
                if (count($pieces) !== 3) {
                    return $defaultOptions;
                }

                [$leftColumn, $operator, $rightColumn] = $pieces;
                $query->innerJoin(DB::qn('#__' . $table, $alias), DB::qc($leftColumn, $rightColumn, $operator));
            }
            // simple clause on first iteration => one and done
            elseif ($from) {
                $query->from(DB::qn('#__' . $table));
                break;
            }
            // simple clause on subsequent iteration => error
            else {
                return $defaultOptions;
            }

        }

        // Where
        if ($conditions = $this->getAttribute('conditions')) {
            $conditions = explode(',', $conditions);

            foreach ($conditions as $condition) {
                $pieces = explode(' ', $condition);

                // column1Value = column2Value condition format was not adhered to
                if (count($pieces) !== 3) {
                    return $defaultOptions;
                }

                [$column, $operator, $value] = $pieces;
                $query->where(DB::qn($column) . "$operator $value");
            }
        }

        // Order By
        $orderParts = explode(' ', $this->getAttribute('order', 'text ASC'));
        if (!empty($orderParts[0])) {
            $orderColumn    = DB::qn($orderParts[0]);
            $orderDirection = empty($orderParts[1]) ? 'ASC' : $orderParts[1];
            $query->order("$orderColumn $orderDirection");
        }

        DB::set($query);

        if (!$resources = DB::arrays()) {
            return $defaultOptions;
        }

        $options = [];
        foreach ($resources as $resource) {
            // Removes glue from the end of entries
            $glue = $this->getAttribute('glue', '');
            if (!empty($glue)) {
                $glueSize = strlen($glue);
                $textSize = strlen($resource['text']);
                if (strpos($resource['text'], $glue) == $textSize - $glueSize) {
                    $resource['text'] = str_replace($glue, '', $resource['text']);
                }
            }

            $options[$resource['text']] = HTML::option($resource['value'], $resource['text']);
        }

        $this->setValueParameters($options);

        return array_merge($defaultOptions, $options);
    }

    /**
     * Sets value oriented parameters from component settings
     *
     * @param   array &$options  the input options
     *
     * @return void  sets option values
     */
    private function setValueParameters(array &$options): void
    {
        if (!$valueParameter = $this->getAttribute('valueParameter', '')) {
            return;
        }

        $valueParameters     = explode(',', $valueParameter);
        $componentParameters = Input::getParams();

        foreach ($valueParameters as $parameter) {
            $componentParameter = $componentParameters->get($parameter);

            if (empty($componentParameter)) {
                continue;
            }

            $options[$componentParameter] = HTML::option($componentParameter, $componentParameter);
        }

        ksort($options);
    }
}
