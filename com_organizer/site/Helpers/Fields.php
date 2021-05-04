<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Organizer\Adapters\Database;
use Organizer\Tables;

/**
 * Provides general functions for room type access checks, data retrieval and display.
 */
class Fields extends ResourceHelper implements Selectable
{
    /**
     * Returns the color value associated with the field.
     *
     * @param   int  $fieldID         the id of the field
     * @param   int  $organizationID  the id of the organization
     *
     * @return string the hexadecimal color value associated with the field
     */
    public static function getColor(int $fieldID, int $organizationID): string
    {
        $table  = new Tables\FieldColors();
        $exists = $table->load(['fieldID' => $fieldID, 'organizationID' => $organizationID]);

        if (!$exists or empty($table->colorID)) {
            return Input::getParams()->get('backgroundColor', '#f2f5f6');
        }

        return Colors::getColor($table->colorID);
    }

    /**
     * Creates the display for a field item as used in a list view.
     *
     * @param   int  $fieldID         the id of the field
     * @param   int  $organizationID  the id of the organization
     *
     * @return string the HTML output of the field attribute display
     */
    public static function getFieldColorDisplay(int $fieldID, $organizationID = 0): string
    {
        if (!$fieldID) {
            return '';
        }

        $organizationIDs = $organizationID ? [$organizationID] : Organizations::getIDs();
        $return          = '';

        foreach ($organizationIDs as $organizationID) {
            $table = new Tables\FieldColors();
            if ($table->load(['fieldID' => $fieldID, 'organizationID' => $organizationID])) {
                $link         = 'index.php?option=com_organizer&view=field_color_edit&id=' . $table->id;
                $organization = Organizations::getShortName($organizationID);
                $text         = HTML::_('link', $link, $organization);
                $return       .= Colors::getListDisplay($text, $table->colorID);
            }
        }

        return $return;
    }

    /**
     * @inheritDoc
     */
    public static function getOptions(): array
    {
        $options = [];
        foreach (self::getResources() as $field) {
            $options[] = HTML::_('select.option', $field['id'], $field['name']);
        }

        return $options;
    }

    /**
     * @inheritDoc
     */
    public static function getResources(): array
    {
        $query = Database::getQuery(true);
        $tag   = Languages::getTag();
        $query->select("DISTINCT *, name_$tag AS name")
            ->from('#__organizer_fields')
            ->order('name');

        $ranges = [];

        if ($poolID = Input::getFilterID('pool') ? Input::getFilterID('pool') : Input::getInt('poolID')) {
            $ranges = Pools::getSubjects($poolID);
        } elseif ($programID = Input::getFilterID('program') ? Input::getFilterID('program') : Input::getInt('programID')) {
            $ranges = Programs::getSubjects($programID);
        }

        if ($ranges and $fieldIDs = self::getRelevantIDs($ranges)) {
            $string = implode(',', $fieldIDs);
            $query->where("id IN ($string)");
        }

        Database::setQuery($query);

        return Database::loadAssocList('id');
    }

    /**
     * Retrieves the relevant field ids for the given curriculum context.
     *
     * @param   array  $subjectRanges  the mapped subject ranges
     *
     * @return array the field ids associated with the subjects in the given context
     */
    private static function getRelevantIDs(array $subjectRanges): array
    {
        $fieldIDs = [];

        foreach ($subjectRanges as $subject) {
            $table = new Tables\Subjects();

            if ($table->load($subject['subjectID']) and !empty($table->fieldID)) {
                $fieldIDs[$table->fieldID] = $table->fieldID;
            }
        }

        return $fieldIDs;
    }
}
