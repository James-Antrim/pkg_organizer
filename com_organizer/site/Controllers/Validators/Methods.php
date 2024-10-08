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
use THM\Organizer\Adapters\Text;
use THM\Organizer\Controllers\ImportSchedule as Schedule;
use THM\Organizer\Tables\Methods as Table;

/**
 * Provides functions for XML description validation and modeling.
 */
class Methods implements UntisXMLValidator
{
    /**
     * @inheritDoc
     */
    public static function setID(Schedule $controller, string $code): void
    {
        $method = new Table();

        // These are set by the administrator, so there is no case for saving a new resource on upload.
        if ($method->load(['code' => $code])) {
            $controller->tMethods->$code = $method->id;
        }
        else {
            $controller->errors[] = Text::sprintf('METHOD_INVALID', $code);
        }
    }

    /**
     * @inheritDoc
     */
    public static function validate(Schedule $controller, SimpleXMLElement $node): void
    {
        $typeFlag = strtolower(trim((string) $node->flags));

        // Untis 'description' for the resource Unterricht
        if (empty($typeFlag) or $typeFlag !== 'u') {
            return;
        }

        $untisID = str_replace('DS_', '', trim((string) $node[0]['id']));
        $name    = trim((string) $node->longname);

        if (empty($name)) {
            $controller->errors[] = Text::sprintf('DESCRIPTION_NAME_MISSING', $untisID);

            return;
        }

        self::setID($controller, $untisID);
    }
}
