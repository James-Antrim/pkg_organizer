<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\HTML;

use Joomla\CMS\Language\Text;
use THM\Organizer\Adapters\Application;
use THM\Organizer\Views\HTML\ListView;

/**
 * Class renders elements of a modal element for batch processing.
 */
class Batch
{
    /**
     * Generates the HTML to be used as the body of the batch modal.
     *
     * @param   ListView  $view
     *
     * @return string
     */
    public static function renderBody(ListView $view): string
    {
        $return = '<div class="p-3"><form>';
        foreach ($view->filterForm->getGroup('batch') as $field) {
            $return .= '<div class="form-group">' . $field->__get('label') . $field->__get('input') . '</div>';
        }
        $return .= '</form></div>';

        return $return;
    }

    /**
     * Generates the HTML to be used as the footer of the batch modal.
     *
     * @param   ListView  $view
     *
     * @return string
     */
    public static function renderFooter(ListView $view): string
    {
        $template = '<button type="XTYPEX" class="XCLASSX" onclick="XONCLICKX" XEXTRAX>XTEXTX</button>';

        $resets = [];
        foreach ($view->filterForm->getGroup('batch') as $field) {
            $default  = $field->getAttribute('default');
            $default  = is_null($default) ? '' : $default;
            $fieldID  = $field->__get('id');
            $resets[] = "document.ElementById('$fieldID').value='$default';";
        }
        $onClick = implode('', $resets);

        $reset = str_replace('XCLASSX', 'btn btn-secondary', $template);
        $reset = str_replace('XEXTRAX', 'data-bs-dismiss="modal"', $reset);
        $reset = str_replace('XONCLICKX', $onClick, $reset);
        $reset = str_replace('XTEXTX', Text::_('GROUPS_CLOSE'), $reset);
        $reset = str_replace('XTYPEX', 'button', $reset);

        $onClick = "Joomla.submitbutton('" . Application::getClass($view) . ".batch');return false;";

        $submit = str_replace('XCLASSX', 'btn btn-success', $template);
        $submit = str_replace('XEXTRAX', '', $submit);
        $submit = str_replace('XONCLICKX', $onClick, $submit);
        $submit = str_replace('XTEXTX', Text::_('GROUPS_PROCESS'), $submit);
        $submit = str_replace('XTYPEX', 'submit', $submit);

        return $reset . $submit;
    }
}