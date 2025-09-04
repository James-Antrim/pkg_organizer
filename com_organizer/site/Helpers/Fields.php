<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use THM\Organizer\Adapters\{Application, Database as DB, HTML, Input};
use THM\Organizer\Tables;

/**
 * Provides general functions for room type access checks, data retrieval and display.
 */
class Fields extends ResourceHelper implements Selectable
{
    /**
     * Gets the hexadecimal color value associated with the field.
     *
     * @param   int  $fieldID         the id of the field
     * @param   int  $organizationID  the id of the organization
     *
     * @return string
     */
    public static function color(int $fieldID, int $organizationID): string
    {
        $table  = new Tables\FieldColors();
        $exists = $table->load(['fieldID' => $fieldID, 'organizationID' => $organizationID]);

        if (!$exists or empty($table->colorID)) {
            return Input::parameters()->get('backgroundColor', '#f2f5f6');
        }

        return Colors::color($table->colorID);
    }

    /**
     * @inheritDoc
     */
    public static function options(): array
    {
        $options = [];
        foreach (self::resources() as $field) {
            $options[] = HTML::option($field['id'], $field['name']);
        }

        return $options;
    }

    /**
     * Extracts field ids assigned to the given subjects.
     *
     * @param   array  $subjects  the mapped subject ranges
     *
     * @return int[]
     */
    private static function relevantIDs(array $subjects): array
    {
        $fieldIDs = [];

        foreach ($subjects as $subject) {
            $table = new Tables\Subjects();

            if ($table->load($subject['subjectID']) and !empty($table->fieldID)) {
                $fieldIDs[$table->fieldID] = $table->fieldID;
            }
        }

        return $fieldIDs;
    }

    /**
     * @inheritDoc
     */
    public static function resources(): array
    {
        $query = DB::query();
        $tag   = Application::tag();
        $query->select('DISTINCT *, ' . DB::qn("name_$tag", 'name'))
            ->from(DB::qn('#__organizer_fields'))
            ->order(DB::qn('name'));

        $rows = [];

        if ($poolID = Input::integer('poolID')) {
            $rows = Pools::subjects($poolID);
        }
        elseif ($programID = Input::integer('programID')) {
            $rows = Programs::subjects($programID);
        }

        if ($rows and $fieldIDs = self::relevantIDs($rows)) {
            $string = implode(',', $fieldIDs);
            $query->where("id IN ($string)");
        }

        DB::set($query);

        return DB::arrays('id');
    }

    /**
     * Creates a swatch panel with organizational assignments of colors to fields.
     *
     * @param   int  $fieldID         the id of the field
     * @param   int  $organizationID  the id of the organization
     *
     * @return string
     */
    public static function swatch(int $fieldID, int $organizationID = 0): string
    {
        if (!$fieldID) {
            return '';
        }

        $organizationIDs = $organizationID ? [$organizationID] : Organizations::getIDs();
        $return          = '';

        foreach ($organizationIDs as $organizationID) {
            $table = new Tables\FieldColors();
            if ($table->load(['fieldID' => $fieldID, 'organizationID' => $organizationID])) {
                $link         = 'index.php?option=com_organizer&view=FieldColor&id=' . $table->id;
                $organization = Organizations::getShortName($organizationID);
                $text         = HTML::_('link', $link, $organization);
                $return       .= Colors::swatch($text, $table->colorID);
            }
        }

        return $return;
    }
}
