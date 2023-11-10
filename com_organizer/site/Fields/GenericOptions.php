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

use stdClass;
use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input, Text};

/**
 * Class replaces form field type sql by using Joomla's database objects to avoid database language dependency. While
 * the display text can be localized, the value cannot be.
 */
class GenericOptions extends Options
{
    /**
     * Type
     * @var    String
     */
    public $type = 'GenericList';

    /**
     * Method to get the field input markup for a generic list.
     * Use the multiple attribute to enable multiselect.
     * @return string  The field input markup.
     */
    protected function getInput(): string
    {
        $html = [];
        $attr = '';

        // Initialize some field attributes.
        $attr        .= !empty($this->class) ? ' class="' . $this->class . '"' : '';
        $attr        .= !empty($this->size) ? ' size="' . $this->size . '"' : '';
        $attr        .= $this->multiple ? ' multiple' : '';
        $attr        .= $this->required ? ' required aria-required="true"' : '';
        $attr        .= $this->autofocus ? ' autofocus' : '';
        $placeHolder = $this->getAttribute('placeholder', '');
        $attr        .= empty($placeHolder) ? '' : ' placeholder="' . Text::_($placeHolder) . '"';

        $isReadOnly     = ($this->readonly == '1' or $this->readonly == 'true');
        $this->readonly = (string) $isReadOnly;
        $isDisabled     = ($this->disabled == '1' or $this->disabled == 'true');
        $this->disabled = (string) $isDisabled;
        // To avoid user's confusion, readonly="true" should imply disabled="true".
        if ($isReadOnly or $isDisabled) {
            $attr .= ' disabled="disabled"';
        }

        // Initialize JavaScript field attributes.
        $attr .= $this->onchange ? ' onchange="' . $this->onchange . '"' : '';

        // Get the field options.
        $options = $this->getOptions();

        // Create a read-only list (no name) with hidden input(s) to store the value(s).
        if ($isReadOnly) {
            $html[] = HTML::_(
                'select.genericlist',
                $options,
                '',
                trim($attr),
                'value',
                'text',
                $this->value,
                $this->id
            );

            // E.g. form field type tag sends $this->value as array
            if ($this->multiple && is_array($this->value)) {
                if (!count($this->value)) {
                    $this->value[] = '';
                }

                foreach ($this->value as $value) {
                    $value  = htmlspecialchars($value, ENT_COMPAT);
                    $html[] = '<input type="hidden" name="' . $this->name . '" value="' . $value . '"/>';
                }
            }
            else {
                $value  = htmlspecialchars($this->value, ENT_COMPAT);
                $html[] = '<input type="hidden" name="' . $this->name . '" value="' . $value . '"/>';
            }
        }
        else // Create a regular list.
        {
            $html[] = HTML::_(
                'select.genericlist',
                $options,
                $this->name,
                trim($attr),
                'value',
                'text',
                $this->value,
                $this->id
            );
        }

        return implode($html);
    }

    /**
     * Retrieve an array of options by building and executing a database query.
     * @return stdClass[]
     */
    protected function getOptions(): array
    {
        $defaultOptions = parent::getOptions();

        $query = DB::getQuery();

        $glue = $this->getAttribute('glue');

        if (!$textColumn = $this->getAttribute('textcolumn')
            or !$valueColumn = $this->getAttribute('valuecolumn')
            or !$table = $this->getAttribute('table')) {
            return $defaultOptions;
        }

        // SELECT
        $textColumns = explode(',', $textColumn);
        $tag         = $this->getAttribute('localized', false) ? '_' . Application::getTag() : '';

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

        DB::setQuery($query);

        if (!$resources = DB::loadAssocList()) {
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
