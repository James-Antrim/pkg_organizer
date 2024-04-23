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

use Exception;
use Joomla\CMS\Form\FormField;
use Joomla\Database\ParameterType;
use THM\Organizer\Adapters\{Database as DB, Document, Input, Text, Toolbar};
use THM\Organizer\Helpers;

/**
 * Class creates a box for managing subordinated curriculum elements. Change order, remove, add empty element.
 */
class SubOrdinates extends FormField
{
    /**
     * Creates a button template for a given function.
     *
     * @param   string  $function  the name of the javascript function to call on click
     * @param   string  $icon      the class name of the icon to be displayed in the button
     * @param   string  $toolTip   the tooltip for the button
     *
     * @return string the HTML of the button to be displayed
     */
    private function getButton(string $function, string $icon, string $toolTip): string
    {
        $class   = "class=\"btn btn-primary\"";
        $icon    = "<span class=\"$icon\"></span>";
        $onClick = "onclick=\"$function('XORDERINGX');\"";
        $tip     = "title=\"$toolTip\"";
        $type    = "type=\"button\"";
        return "<button $class $onClick $tip $type>$icon</button>";
    }

    /**
     * Generates a text for the management of subordinate elements
     * @return string  the HTML for the input
     * @throws Exception
     */
    public function getInput(): string
    {
        Document::script('subordinates');
        Document::style('subordinates');

        // The other localizations are added during template creation.
        Text::useLocalization('EMPTY_PANEL');

        $input = Toolbar::getInstance('subordinates')->render(['title' => Text::_('SUBORDINATE_TOOLBAR')]);

        $input .= '<table id="so-table" class="so-table form-select table">';
        $input .= '<thead><tr>';
        $input .= '<th class="sub-name">' . Text::_('NAME') . '</th>';
        $input .= '<th class="sub-order">' . Text::_('ORDER') . '</th>';
        $input .= '</tr></thead>';
        $input .= '<tbody id="so-body">';

        $input .= implode($this->getRows());

        $input .= '</tbody>';
        $input .= '</table>';

        return $input;
    }

    /**
     * Generates the HTML output for the subordinate entries
     * @return string[] the HTML strings for the subordinate resources
     */
    private function getRows(): array
    {
        $rows = [];

        if (!$subOrdinates = $this->getSubordinates()) {
            return $rows;
        }

        $maxOrdering = max(array_keys($subOrdinates));
        $rowTemplate = $this->getRowTemplate();

        for ($ordering = 1; $ordering <= $maxOrdering; $ordering++) {
            if (empty($subOrdinates[$ordering])) {
                $icon  = 'far fa-square';
                $name  = Text::_('EMPTY_PANEL');
                $subID = '';
            }
            elseif (empty($subOrdinates[$ordering]['subjectID'])) {
                $poolID = $subOrdinates[$ordering]['poolID'];
                $icon   = 'fa fa-list';
                $name   = Helpers\Pools::getFullName($poolID);
                $subID  = $poolID . 'p';
            }
            else {
                $subjectID = $subOrdinates[$ordering]['subjectID'];
                $icon      = 'fa fa-book';
                $name      = Helpers\Subjects::name($subjectID, true);
                $subID     = $subjectID . 's';
            }

            $row = str_replace('XICONX', $icon, $rowTemplate);
            $row = str_replace('XNAMEX', $name, $row);
            $row = str_replace('XORDERINGX', $ordering, $row);
            $row = str_replace('XSUBIDX', $subID, $row);

            $rows[] = $row;
        }

        return $rows;
    }

    /**
     * Generates a template to be used in the creation of rows.
     * @return string the template to be used for row generation
     */
    private function getRowTemplate(): string
    {
        $rowTemplate   = '<tr id="subRowXORDERINGX">';
        $rowTemplate   .= '<td class="sub-name">';
        $rowTemplate   .= '<span id="subXORDERINGXIcon" class="XICONX"></span>';
        $rowTemplate   .= '<span id="subXORDERINGXName">XNAMEX</span>';
        $rowTemplate   .= '<input type="hidden" name="subXORDERINGX" id="subXORDERINGX" value="XSUBIDX" />';
        $rowTemplate   .= '</td>';
        $rowTemplate   .= '<td class="sub-order">';
        $rowTemplate   .= $this->getButton('setFirst', 'fa fa-fast-backward', Text::useLocalization('MAKE_FIRST'));
        $rowTemplate   .= $this->getButton('moveUp', 'fa fa-step-backward', Text::useLocalization('MOVE_UP'));
        $orderTemplate = '<input type="text" title="Ordering" name="subXORDERINGXOrder" id="subXORDERINGXOrder" ';
        $orderTemplate .= 'value="XORDERINGX" onChange="moveTo(XORDERINGX);"/>';
        $rowTemplate   .= $orderTemplate;
        $rowTemplate   .= $this->getButton('insertBlank', 'far fa-plus-square', Text::useLocalization('ADD_EMPTY_PANEL'));
        $rowTemplate   .= $this->getButton('trash', 'fa fa-times', Text::useLocalization('DELETE'));
        $rowTemplate   .= $this->getButton('moveDown', 'fa fa-step-forward', Text::useLocalization('MOVE_DOWN'));
        $rowTemplate   .= $this->getButton('setLast', 'fa fa-fast-forward', Text::useLocalization('MAKE_LAST'));
        $rowTemplate   .= '</td>';
        $rowTemplate   .= '</tr>';

        return $rowTemplate;
    }

    /**
     * Retrieves resources subordinate to the resource being edited
     * @return array[]  empty if no subordinates were found
     */
    private function getSubordinates(): array
    {
        $query    = DB::getQuery();
        $column   = DB::qn(strtolower(Input::getView()) . 'ID');
        $parentID = Input::getID();

        $query->select(DB::qn('id'))
            ->from(DB::qn('#__organizer_curricula'))
            ->where("$column = :parentID")
            ->bind(':parentID', $parentID, ParameterType::INTEGER)
            ->group('id');
        DB::setQuery($query);

        if (!$parentID = DB::loadInt()) {
            return [];
        }

        $column = DB::qn('parentID');
        $query  = DB::getQuery();
        $query->select('*')
            ->from(DB::qn('#__organizer_curricula'))
            ->where("$column = :parentID")
            ->bind(':parentID', $parentID, ParameterType::INTEGER)
            ->order(DB::qn('lft'));
        DB::setQuery($query);

        return DB::loadAssocList('ordering');
    }
}
