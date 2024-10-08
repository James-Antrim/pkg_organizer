<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers\Validators;

use SimpleXMLElement;
use stdClass;
use THM\Organizer\Adapters\Text;
use THM\Organizer\Controllers\{Program, ImportSchedule as Schedule};
use THM\Organizer\Tables\{Associations, Categories as Table, Degrees};

/**
 * Provides general functions for campus access checks, data retrieval and display.
 */
class Categories implements UntisXMLValidator
{
    /**
     * Determines whether the data conveyed in the untisID is plausible for finding a real program.
     *
     * @param   string  $untisID  the id used in untis for this program
     *
     * @return string[] empty if the id is implausible
     */
    private static function parseProgramData(string $untisID): array
    {
        $pieces = explode('.', $untisID);
        if (count($pieces) !== 3) {
            return [];
        }

        // Two uppercase letter code for the degree. First letter is B (Bachelor) or M (Master)
        $implausibleDegree = (!ctype_upper($pieces[1]) or !preg_match('/^[B|M][A-Z]{1,2}$/', $pieces[1]));
        if ($implausibleDegree) {
            return [];
        }

        // Some degree program 'subject' identifiers have a number
        $plausibleCode = preg_match('/^[A-Z]+[0-9]*$/', $pieces[0]);

        // Degrees are a managed resource
        $degrees  = new Degrees();
        $degreeID = $degrees->load(['code' => $pieces[1]]) ? $degrees->id : null;

        // Should be year of accreditation, but ITS likes to pick random years
        $plausibleVersion = (ctype_digit($pieces[2]) and preg_match('/^[2][0-9]{3}$/', $pieces[2]));

        return ($plausibleCode and $degreeID and $plausibleVersion) ?
            ['code' => $pieces[0], 'degreeID' => $degreeID, 'accredited' => $pieces[2]] : [];
    }

    /**
     * @inheritDoc
     */
    public static function setID(Schedule $controller, string $code): void
    {
        $category = $controller->categories->$code;
        $table    = new Table();

        if ($exists = $table->load(['code' => $code])) {
            $altered = false;

            foreach ($category as $key => $value) {
                if (property_exists($table, $key) and empty($table->$key) and !empty($value)) {
                    $table->$key = $value;
                    $altered     = true;
                }
            }

            if ($altered) {
                $table->store();
            }

        }

        if (!$exists) {
            $table->save($category);
        }

        $association = new Associations();
        if (!$association->load(['categoryID' => $table->id])) {
            $association->save(['categoryID' => $table->id, 'organizationID' => $controller->organizationID]);
        }

        $category->id = $table->id;
    }

    /**
     * @inheritDoc
     */
    public static function validate(Schedule $controller, SimpleXMLElement $node): void
    {
        $code = str_replace('DP_', '', trim((string) $node[0]['id']));

        if (!$name = (string) $node->longname) {
            $controller->errors[] = Text::sprintf('CATEGORY_NAME_MISSING', $code);

            return;
        }

        $category          = new stdClass();
        $category->name_de = $name;
        $category->name_en = $name;
        $category->code    = $code;

        $controller->categories->$code = $category;
        self::setID($controller, $code);

        if ($keys = self::parseProgramData($code) and $name = trim(substr($name, 0, strpos($name, '(')))) {
            $program = new Program();
            $program->fromSchedule($keys, $name, $category->id);
        }
    }
}
