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
use Joomla\CMS\Router\Route;
use THM\Organizer\Adapters\{Database, Document, Text};
use THM\Organizer\Helpers;

/**
 * Class creates a box for managing subordinated curriculum elements. Change order, remove, add empty element.
 */
class SubOrdinates extends FormField
{
    use Translated;

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
        return "<button onclick=\"$function('XORDERINGX');\" title=\"$toolTip\"><span class=\"$icon\"></span></button>";
    }

    /**
     * Generates a text for the management of subordinate elements
     * @return string  the HTML for the input
     */
    public function getInput(): string
    {
        Document::script('subordinates');

        $input = '<table class="subOrdinates table-striped">';
        $input .= '<thead><tr>';
        $input .= '<th>' . Text::_('NAME') . '</th>';
        $input .= '<th>' . Text::_('ORDER') . '</th>';
        $input .= '</tr></thead>';
        $input .= '<tbody>';

        $input .= implode($this->getRows());

        $input .= '</tbody>';
        $input .= '</table>';
        $input .= '<div class="btn-toolbar" id="subOrdinates-toolbar"></div>';

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

        $maxOrdering     = max(array_keys($subOrdinates));
        $poolEditLink    = 'index.php?option=com_organizer&view=pool_edit&id=';
        $rowTemplate     = $this->getRowTemplate();
        $subjectEditLink = 'index.php?option=com_organizer&view=subject_edit&id=';

        for ($ordering = 1; $ordering <= $maxOrdering; $ordering++) {
            if (empty($subOrdinates[$ordering])) {
                $icon = $link = $name = $subID = '';
            }
            elseif (empty($subOrdinates[$ordering]['subjectID'])) {
                $poolID = $subOrdinates[$ordering]['poolID'];
                $icon   = 'icon-list';
                $link   = Route::_($poolEditLink . $poolID, false);
                $name   = Helpers\Pools::getFullName($poolID);
                $subID  = $poolID . 'p';
            }
            else {
                $subjectID = $subOrdinates[$ordering]['subjectID'];
                $icon      = 'icon-book';
                $link      = Route::_($subjectEditLink . $subjectID, false);
                $name      = Helpers\Subjects::getName($subjectID, true);
                $subID     = $subjectID . 's';
            }

            $row = str_replace('XICONX', $icon, $rowTemplate);
            $row = str_replace('XLINKX', $link, $row);
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
        $rowTemplate = '<tr id="subRowXORDERINGX">';

        $rowTemplate .= '<td class="sub-name">';

        $rowTemplate .= '<a id="subXORDERINGXLink" href="XLINKX" target="_blank">';
        $rowTemplate .= '<span id="subXORDERINGXIcon" class="XICONX"></span>';
        $rowTemplate .= '<span id="subXORDERINGXName">XNAMEX</span>';
        $rowTemplate .= '</a>';

        $rowTemplate .= '<input type="hidden" name="subXORDERINGX" id="subXORDERINGX" value="XSUBIDX" />';

        $rowTemplate .= '</td>';
        $rowTemplate .= '<td class="sub-order">';

        $firstText   = Text::useLocalization('MAKE_FIRST');
        $rowTemplate .= $this->getButton('setFirst', 'icon-first', $firstText);
        $rowTemplate .= $this->getButton('moveUp', 'icon-previous', Text::useLocalization('MOVE_UP'));

        $orderTemplate = '<input type="text" title="Ordering" name="subXORDERINGXOrder" id="subXORDERINGXOrder" ';
        $orderTemplate .= 'value="XORDERINGX" onChange="moveTo(XORDERINGX);"/>';
        $rowTemplate   .= $orderTemplate;

        $emptyText   = Text::useLocalization('ADD_EMPTY');
        $rowTemplate .= $this->getButton('insertBlank', 'icon-download', $emptyText);
        $rowTemplate .= $this->getButton('trash', 'icon-trash', Text::useLocalization('DELETE'));
        $rowTemplate .= $this->getButton('moveDown', 'icon-next', Text::useLocalization('MOVE_DOWN'));
        $rowTemplate .= $this->getButton('setLast', 'icon-last', Text::useLocalization('MAKE_LAST'));

        $rowTemplate .= '</td>';
        $rowTemplate .= '</tr>';

        return $rowTemplate;
    }

    /**
     * Retrieves resources subordinate to the resource being edited
     * @return array[]  empty if no subordinates were found
     */
    private function getSubordinates(): array
    {
        $contextParts = explode('.', $this->form->getName());
        $query        = Database::getQuery();
        $resource     = Helpers\OrganizerHelper::getResource($contextParts[1]);
        $resourceID   = (int) $this->form->getValue('id');
        $query->select('id')
            ->from('#__organizer_curricula')
            ->where("{$resource}ID = $resourceID")
            ->group('id');
        Database::setQuery($query);

        if (!$parentID = Database::loadInt()) {
            return [];
        }

        $query = Database::getQuery();
        $query->select('*')
            ->from('#__organizer_curricula')
            ->where("parentID = $parentID")
            ->order('lft');
        Database::setQuery($query);

        return Database::loadAssocList('ordering');
    }
}
