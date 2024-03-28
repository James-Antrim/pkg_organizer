<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

/**
 * Handles code common for HTML output of resource attributes.
 */
trait Attributed
{
    /**
     * Generates an HTML list of the attributes values. Recursive as necessary.
     *
     * @param   array  $values  the attribute's values
     *
     * @return string
     */
    private function listValues(array $values): string
    {
        $return = '<ul>';

        foreach ($values as $key => $value) {
            $return .= '<li>';
            if (is_numeric($key)) {
                $return .= is_array($value) ? $this->listValues($value) : $value;
            }
            else {
                $return .= $key;
                $return .= is_array($value) ? $this->listValues($value) : " $value";
            }
            $return .= '</li>';
        }

        return $return . '</ul>';
    }

    /**
     * Creates a standardized output for resource attributes.
     *
     * @param   string                 $label  the label
     * @param   array|int|string|null  $value  the value
     *
     * @return void
     */
    public function renderAttribute(string $label, array|int|null|string $value): void
    {
        if (!$value) {
            return;
        }

        $value = is_array($value) ? $this->listValues($value) : $value;
        echo "<div class=\"attribute\"><div class=\"label\">$label</div><div class=\"value\">$value</div></div>";
    }
}