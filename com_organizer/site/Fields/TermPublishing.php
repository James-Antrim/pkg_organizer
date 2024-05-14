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

use Joomla\CMS\Form\FormField;
use THM\Organizer\Adapters\{Application, HTML, Text};
use THM\Organizer\Helpers\Terms;

/**
 * Class creates a form field for enabling or disabling publishing for specific plan (subject) pools for specific
 * terms.
 */
class TermPublishing extends FormField
{
    use Translated;

    /**
     * Returns a select box where resource attributes can be selected
     * @return string  the HTML select box
     */
    protected function getInput(): string
    {
        $input      = '';
        $nameColumn = 'name_' . Application::getTag();
        $today      = date('Y-m-d');
        $container  = '<div class="publishing-container">XXXX</div>';

        $no      = (object) ['disable' => false, 'text' => Text::_('NO'), 'value' => 0];
        $yes     = (object) ['disable' => false, 'text' => Text::_('YES'), 'value' => 1];
        $options = [$yes, $no];

        foreach (Terms::resources() as $term) {
            if ($term['endDate'] < $today) {
                continue;
            }

            $subFieldID   = $this->id . "_{$term['id']}";
            $subFieldName = $this->name . "[{$term['id']}]";

            $input .= '<div class="control-group">';
            $input .= "<div class=\"control-label\"><label for=\"$subFieldID\">$term[$nameColumn]</label></div>";
            $input .= '<div class="controls">';
            $input .= HTML::selectBox($subFieldName, $options, [], ['class' => 'form-select'], 'text', 'value', $subFieldID);
            $input .= '</div>';
            $input .= '</div>';
        }

        return $input ? str_replace('XXXX', $input, $container) : $input;
    }
}
