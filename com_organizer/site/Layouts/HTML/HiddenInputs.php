<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\HTML;

use THM\Organizer\Views\HTML\{GridView, ListView};

/**
 * Outputs grouped hidden inputs together.
 */
class HiddenInputs
{
    /**
     * Renders any hidden fields specific to this list.
     *
     * @param   GridView|ListView  $view
     */
    public static function render(GridView|ListView $view): void
    {
        if (empty($view->filterForm)) {
            return;
        }

        if (!$fields = $view->filterForm->getGroup('hidden')) {
            return;
        }

        foreach ($fields as $field) {
            // Undo Joomla packaging of names by group
            $name = str_replace(['hidden[', ']'], '', $field->__get('name'));
            echo '<input type="hidden" name="' . $name . '" value="' . $field->__get('value') . '"/>';
        }
    }
}