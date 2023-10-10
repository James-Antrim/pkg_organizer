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
use THM\Organizer\Adapters\{Application, Database, Input, Text};
use THM\Organizer\Helpers;

/**
 * Class creates a form field for enabling or disabling publishing for specific plan (subject) pools for specific
 * terms.
 */
class TermPublishingField extends FormField
{
    use Translated;

    /**
     * @var  string
     */
    protected $type = 'TermPublishing';

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

        $no      = (object) ['disable' => false, 'text' => Text::_('ORGANIZER_NO'), 'value' => 0];
        $yes     = (object) ['disable' => false, 'text' => Text::_('ORGANIZER_YES'), 'value' => 1];
        $options = [$yes, $no];

        $values = [];
        if ($groupID = Input::getID()) {
            $query = Database::getQuery();
            $query->select('termID, published')->from('#__organizer_group_publishing')->where("groupID = $groupID");
            Database::setQuery($query);

            $values = Database::loadAssocList('termID');
        }

        foreach (Helpers\Terms::getResources() as $term) {
            if ($term['endDate'] < $today) {
                continue;
            }

            $subFieldID   = $this->id . "_{$term['id']}";
            $subFieldName = $this->name . "[{$term['id']}]";
            $value        = empty($values[$term['id']]) ? 1 : $values[$term['id']]['published'];

            $input .= '<div class="term-container">';
            $input .= "<div class=\"term-label\"><label for=\"$subFieldName\">$term[$nameColumn]</label></div>";
            $input .= '<div class="term-input">';
            $input .= Helpers\HTML::_(
                'select.genericlist',
                $options,
                $subFieldName,
                null,
                'value',
                'text',
                $value,
                $subFieldID
            );
            $input .= '</div>';
            $input .= '</div>';
        }

        return $input ? str_replace('XXXX', $input, $container) : $input;
    }
}
