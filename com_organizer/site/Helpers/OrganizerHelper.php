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

/**
 * Class provides generalized functions useful for several component files.
 */
class OrganizerHelper
{
    /**
     * Converts a camel cased class name into a lower cased, underscore separated string
     *
     * @param   string  $className  the original class name
     *
     * @return string the encoded base class name
     */
    public static function classEncode(string $className): string
    {
        $root      = str_replace(['Edit', 'Merge'], '', $className);
        $separated = preg_replace('/([a-z])([A-Z])/', '$1_$2', $root);

        return strtolower($separated);
    }

    /**
     * Converts a lower cased, underscore separated string into a camel cased class name
     *
     * @param   string  $encoded  the encoded class name
     *
     * @return string the camel cased class name
     */
    public static function classDecode(string $encoded): string
    {
        $className = '';
        foreach (explode('_', $encoded) as $piece) {
            $className .= ucfirst($piece);
        }

        return $className;
    }

    /**
     * Gets the name of an object's class without its namespace.
     *
     * @param   object|string  $object  the object whose namespace free name is requested or the fq name of the class to be
     *                                  loaded
     *
     * @return string the name of the class without its namespace
     */
    public static function getClass(object|string $object): string
    {
        $fqName   = is_string($object) ? $object : get_class($object);
        $nsParts  = explode('\\', $fqName);
        $lastItem = array_pop($nsParts);

        if (empty($lastItem)) {
            return 'Organizer';
        }

        return self::classDecode($lastItem);
    }

    /**
     * Creates the plural of the given resource.
     *
     * @param   string  $resource  the resource for which the plural is needed
     *
     * @return string the plural of the resource name
     */
    public static function getPlural(string $resource): string
    {
        return match ($resource) {
            'equipment', 'organizer' => $resource,
            mb_substr($resource, -1) === 's' => $resource . 'es',
            mb_substr($resource, -2) == 'ry' => mb_substr($resource, 0, mb_strlen($resource) - 1) . 'ies',
            default => $resource . 's',
        };
    }
}
