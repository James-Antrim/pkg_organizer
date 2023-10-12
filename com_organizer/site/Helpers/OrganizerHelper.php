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

require_once JPATH_ROOT . '/components/com_jce/editor/libraries/classes/mobile.php';

use Exception;
use THM\Organizer\Adapters\{Application, Text};
use THM\Organizer\Controllers\Controller;
use Wf_Mobile_Detect;

/**
 * Class provides generalized functions useful for several component files.
 */
class OrganizerHelper
{
    /**
     * Converts a camel cased class name into a lower cased, underscore separated string
     *
     * @param string $className the original class name
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
     * @param string $encoded the encoded class name
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
     * Determines whether the view was called from a dynamic context
     * @return bool true if the view was called dynamically, otherwise false
     */
    public static function dynamic(): bool
    {
        $app = Application::getApplication();

        return (empty($app->getMenu()) or empty($app->getMenu()->getActive()));
    }

    /**
     * Gets the name of an object's class without its namespace.
     *
     * @param object|string $object the object whose namespace free name is requested or the fq name of the class to be
     *                              loaded
     *
     * @return string the name of the class without its namespace
     */
    public static function getClass($object): string
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
     * @param string $resource the resource for which the plural is needed
     *
     * @return string the plural of the resource name
     */
    public static function getPlural(string $resource): string
    {
        switch ($resource) {
            case 'equipment':
            case 'organizer':
                return $resource;
            case mb_substr($resource, -1) == 's':
                return $resource . 'es';
            case mb_substr($resource, -2) == 'ry':
                return mb_substr($resource, 0, mb_strlen($resource) - 1) . 'ies';
            default:
                return $resource . 's';
        }
    }

    /**
     * Resolves a view name to the corresponding resource.
     *
     * @param string $view the view for which the resource is needed
     *
     * @return string the resource name
     */
    public static function getResource(string $view): string
    {
        $initial       = strtolower($view);
        $withoutSuffix = preg_replace('/_?(edit|import|item|manager|merge|statistics)$/', '', $initial);
        if ($withoutSuffix !== $initial) {
            return $withoutSuffix;
        }

        $listViews = [
            'campuses' => 'campus',
            'categories' => 'category',
            'courses' => 'course',
            'colors' => 'color',
            'degrees' => 'degree',
            'grids' => 'grid',
            'groups' => 'group',
            'equipment' => 'equipment',
            'events' => 'event',
            'fields' => 'field',
            'fieldcolors' => 'fieldcolor',
            'holidays' => 'holiday',
            'methods' => 'method',
            'organizations' => 'organization',
            'participants' => 'participant',
            'persons' => 'person',
            'pools' => 'pool',
            'programs' => 'program',
            'rooms' => 'room',
            'roomtypes' => 'roomtype',
            'schedules' => 'schedule',
            'search' => 'search',
            'subjects' => 'subject',
            'terms' => 'term',
            'trace' => '',
            'units' => 'unit'
        ];

        return $listViews[$initial];
    }

    /**
     * TODO: Including this (someday) to the Joomla Core!
     * Checks if the device is a smartphone, based on the 'Mobile Detect' library
     * @return bool
     */
    public static function isSmartphone(): bool
    {
        $checker = new Wf_Mobile_Detect();

        return ($checker->isMobile() and !$checker->isTablet());
    }

    /**
     * Instantiates the controller.
     * @return void
     */
    public static function setUp()
    {
        $handler = explode('.', Input::getTask());

        if (count($handler) == 2) {
            $possibleController = self::classDecode($handler[0]);
            $filepath           = JPATH_ROOT . "/components/com_organizer/Controllers/$possibleController.php";

            if (is_file($filepath)) {
                $namespacedClassName = "Organizer\\Controllers\\" . $possibleController;
                $controllerObj       = new $namespacedClassName();
            }

            $task = $handler[1];
        } else {
            $task = $handler[0];
        }

        if (empty($controllerObj)) {
            $controllerObj = new Controller();
        }

        try {
            $controllerObj->execute($task);
        } catch (Exception $exception) {
            Application::message($exception->getMessage(), Application::ERROR);
        }

        $controllerObj->redirect();
    }
}
