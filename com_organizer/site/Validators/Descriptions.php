<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Validators;

use THM\Organizer\Helpers;
use THM\Organizer\Tables;
use SimpleXMLElement;

/**
 * Provides functions for XML description validation and modeling.
 */
class Descriptions implements UntisXMLValidator
{
    // Untis: Unterricht
    private const APPOINTMENT = 'u';

    /**
     * @inheritDoc
     *
     * @param string $typeFlag the flag identifying the categorization resource
     */
    public static function setID(Schedule $model, string $code, string $typeFlag = '')
    {
        $error    = 'ORGANIZER_METHOD_INVALID';
        $method   = new Tables\Methods();
        $resource = 'Methods';

        // These are set by the administrator, so there is no case for saving a new resource on upload.
        if ($method->load(['code' => $code])) {
            $property                = strtolower($resource);
            $model->$property->$code = $method->id;
        } else {
            $model->errors[] = sprintf(Helpers\Languages::_($error), $code);
        }
    }

    /**
     * @inheritDoc
     */
    public static function validate(Schedule $model, SimpleXMLElement $node)
    {
        $typeFlag = strtolower(trim((string) $node->flags));

        // Only those explicitly used for appointments are still relevant.
        if (empty($typeFlag) or $typeFlag !== self::APPOINTMENT) {
            return;
        }

        $untisID = str_replace('DS_', '', trim((string) $node[0]['id']));
        $name    = trim((string) $node->longname);

        if (empty($name)) {
            $model->errors[] = sprintf(Helpers\Languages::_('ORGANIZER_DESCRIPTION_NAME_MISSING'), $untisID);

            return;
        }

        self::setID($model, $untisID, $typeFlag);
    }
}
