<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Exception;
use Joomla\CMS\Application\{CMSApplication, CMSApplicationInterface, WebApplication};
use Joomla\CMS\{Component\ComponentHelper, Document\Document, Factory, Language\Language};
use Joomla\CMS\{Menu\MenuItem, Plugin\PluginHelper, Session\Session, Uri\Uri};
use Joomla\CMS\Extension\{ComponentInterface, ExtensionHelper};
use Joomla\Database\DatabaseDriver;
use Joomla\DI\Container;
use Joomla\Registry\Registry;

/**
 * Aggregates various core joomla functions spread around multiple core classes and offers shortcuts to them with no
 * thrown exceptions.
 */
class Application
{
    /**
     * Predefined Joomla message types without unnecessary prefixing.
     * @see    CMSApplicationInterface
     */
    public const ERROR = 'error', MESSAGE = 'message', NOTICE = 'notice', WARNING = 'warning';

    /**
     * Unused locally, but Joomla supported.
     * @ALERT, @CRITICAL, @EMERGENCY: danger
     * @DEBUG, @INFO: info
     *
     * public const ALERT = 'alert', CRITICAL = 'critical', DEBUG = 'debug', EMERGENCY = 'emergency', INFO = 'info';
     * @noinspection GrazieInspection
     */

    /**
     * Checks whether the current context is the administrator context.
     * @return bool
     */
    public static function backend(): bool
    {
        return self::instance()->isClient('administrator');
    }

    /**
     * Shortcuts configuration access.
     * @return Registry
     */
    public static function configuration(): Registry
    {
        /** @var WebApplication $app */
        $app = self::instance();

        return $app->getConfig();
    }

    /**
     * Returns the organizer component object.
     * @return ComponentInterface
     */
    public static function component(): ComponentInterface
    {
        $component = 'organizer';
        $type      = ComponentInterface::class;

        // Check if the extension is already loaded
        if (!empty(ExtensionHelper::$extensions[$type][$component])) {
            return ExtensionHelper::$extensions[$type][$component];
        }

        $application = self::instance();
        $container   = self::container()->createChild();

        $provider = require_once JPATH_ADMINISTRATOR . '/components/com_organizer/services/provider.php';
        $provider->register($container);

        $extension                                      = $container->get($type);
        ExtensionHelper::$extensions[$type][$component] = $extension;

        return $extension;
    }

    /**
     * Shortcuts container access.
     * @return Container
     */
    public static function container(): Container
    {
        return Factory::getContainer();
    }

    /**
     * Shortcuts container access.
     * @return DatabaseDriver
     */
    public static function database(): DatabaseDriver
    {
        return self::container()->get('DatabaseDriver');
    }

    /**
     * Shortcuts document access.
     * @return Document
     */
    public static function document(): Document
    {
        /** @var WebApplication $app */
        $app = self::instance();

        return $app->getDocument();
    }

    /**
     * Determines whether the view was called from a dynamic context
     * @return bool true if the view was called dynamically, otherwise false
     */
    public static function dynamic(): bool
    {
        return !self::menuItem();
    }

    /**
     * Performs a redirect on error.
     *
     * @param   int     $code  the error code
     * @param   string  $key   the localization key for a message
     *
     * @return void
     */
    public static function error(int $code, string $key = ''): void
    {
        $current = Uri::getInstance()->toString();

        // Unauthenticated
        if ($code === 401) {
            $message  = $code;
            $return   = urlencode(base64_encode($current));
            $severity = self::NOTICE;
            $url      = Uri::base() . "?option=com_users&view=login&return=$return";
        }
        // Unauthorized
        elseif ($code === 403) {
            $message  = $code;
            $url      = Uri::base();
            $severity = self::WARNING;
        }
        else {
            // Use specific message if requested.
            $message  = $key ?: $code;
            $severity = match ($code) {
                // Form error / not found
                400, 404 => self::NOTICE,
                // Inconsistent data
                412 => self::WARNING,
                default => self::ERROR,
            };

            if ($severity === self::ERROR) {
                echo "<pre>" . print_r($message, true) . "</pre>";
                $exc = new Exception;
                echo "<pre>" . print_r($exc->getTraceAsString(), true) . "</pre>";
                die;
            }

            $url = Input::getInput()->server->getString('HTTP_REFERER', Uri::base());
        }

        self::message($message, $severity);
        self::redirect($url, $code);
    }

    /**
     * Performs handling for joomla's internal errors not handled by joomla.
     *
     * @param   Exception  $exception  the joomla internal error being thrown instead of handled
     *
     * @return void
     */
    public static function handleException(Exception $exception): void
    {
        $code    = $exception->getCode() ?: 500;
        $message = $exception->getMessage();
        echo "<pre>" . print_r($exception->getTraceAsString(), true) . "</pre>";
        self::error($code, $message);
    }

    /**
     * Surrounds the call to the application with a try catch so that not every function needs to have a throws tag. If
     * the application has an error it would have never made it to the component in the first place, so the error would
     * not have been thrown in this call regardless.
     * @return CMSApplicationInterface|null
     */
    public static function instance(): ?CMSApplicationInterface
    {
        $application = null;

        try {
            $application = Factory::getApplication();
        }
        catch (Exception $exception) {
            self::handleException($exception);
        }

        return $application;
    }

    /**
     * Method to get the application language object.
     * @return  Language  The language object
     */
    public static function language(): Language
    {
        return self::instance()->getLanguage();
    }

    /**
     * Gets the current menu item.
     * @return MenuItem|null the current menu item or null
     */
    public static function menuItem(int $itemID = 0): ?MenuItem
    {
        /** @var CMSApplication $app */
        $app = self::instance();

        if ($menu = $app->getMenu()) {
            if ($itemID) {
                return $menu->getItem($itemID);
            }

            return $menu->getActive();
        }

        return null;
    }

    /**
     * Masks the Joomla application enqueueMessage function
     *
     * @param   string  $message  the message to enqueue
     * @param   string  $type     how the message is to be presented
     *
     * @return void
     */
    public static function message(string $message, string $type = self::MESSAGE): void
    {
        self::instance()->enqueueMessage(Text::_($message), $type);
    }

    /**
     * Checks whether the client device is a mobile phone.
     * @return bool
     */
    public static function mobile(): bool
    {
        /** @var CMSApplication $app */
        $app     = self::instance();
        $client  = $app->client;
        $tablets = [$client::IPAD, $client::ANDROIDTABLET];

        return ($client->mobile and !in_array($client->platform, $tablets));
    }

    /**
     * Gets the parameter object for the component
     *
     * @param   string  $extension  the component name.
     *
     * @return  Registry
     */
    public static function parameters(string $extension = 'com_organizer'): Registry
    {
        if (str_starts_with($extension, 'plg_')) {
            return self::pluginParameters(str_replace('plg_', '', $extension));
        }
        return ComponentHelper::getParams($extension);
    }

    /**
     * Function gets plugin parameters analogous to ComponentHelper.
     *
     * @param   string  $plugin
     *
     * @return Registry
     */
    private static function pluginParameters(string $plugin): Registry
    {
        [$type, $element] = explode('_', $plugin, 2);
        $plugin = PluginHelper::getPlugin($type, $element);
        return new Registry($plugin->params);
    }

    /**
     * Redirect to another URL.
     *
     * @param   string  $url     The URL to redirect to. Can only be http/https URL
     * @param   int     $status  The HTTP 1.1 status code to be provided. 303 is assumed by default.
     *
     * @return  void
     */
    public static function redirect(string $url = '', int $status = 303): void
    {
        $url = $url ?: Uri::getInstance()::base();

        /** @var CMSApplication $app */
        $app = self::instance();
        $app->redirect($url, $status);
    }

    /**
     * Gets the session from the application container.
     * @return Session
     */
    public static function session(): Session
    {
        return self::instance()->getSession();
    }

    /**
     * Gets the language portion of the localization tag.
     * @return string
     */
    public static function tag(): string
    {
        $language = self::instance()->getLanguage();

        return explode('-', $language->getTag())[0];
    }

    /**
     * Resolves the upper case class name for the given string.
     *
     * @param   string  $name  the name of the class to resolve
     *
     * @return string
     */
    public static function ucClass(string $name = ''): string
    {
        $name = empty($name) ? Input::getView() : $name;
        $name = preg_replace('/[^A-Z0-9_]/i', '', $name);

        // First letter UC assume already correct
        if (ctype_upper($name[0])) {
            return $name;
        }

        return match ($name) {
            // Compound nouns
            'cleaninggroup' => 'CleaningGroup',
            'cleaninggroups' => 'CleaningGroups',
            'courseparticipants' => 'CourseParticipants',
            'fieldcolor' => 'FieldColor',
            'fieldcolors' => 'FieldColors',
            'importcourses' => 'ImportCourses',
            'importrooms' => 'ImportRooms',
            'importschedule' => 'ImportSchedule',
            'instanceparticipants' => 'InstanceParticipants',
            'mergeparticipants' => 'MergeParticipants',
            'mergepersons' => 'MergePersons',
            'mergerooms' => 'MergeRooms',
            'roomkey' => 'RoomKey',
            'roomkeys' => 'RoomKeys',
            'roomtype' => 'RoomType',
            'roomtypes' => 'RoomTypes',
            'selectpools' => 'SelectPools',
            'selectsubjects' => 'SelectSubjects',

            default => ucfirst($name),
        };
    }

    /**
     * Gets the name of an object's class without its namespace.
     *
     * @param   object|string  $object  the object whose namespace free name is requested or the fq name of the class to be
     *                                  loaded
     *
     * @return string the name of the class without its namespace
     */
    public static function uqClass(object|string $object): string
    {
        $fqName   = is_string($object) ? $object : get_class($object);
        $nsParts  = explode('\\', $fqName);
        $lastItem = array_pop($nsParts);

        return empty($lastItem) ? 'Organizer' : $lastItem;
    }

    /**
     * Gets the property value from the state, overwriting the value from the request if available.
     *
     * @param   string  $property  the property name
     * @param   string  $request   the name of the property as passed in a request.
     * @param   mixed   $default   the optional default value
     * @param   string  $type      the optional name of the type filter to use on the variable
     *
     * @return  mixed  The request user state.
     * @see CMSApplication::getUserStateFromRequest(), InputFilter::clean()
     */
    public static function userRequestState(
        string $property,
        string $request,
        mixed $default = null,
        string $type = 'none'
    ): mixed
    {
        /** @var CMSApplication $app */
        $app = self::instance();

        return $app->getUserStateFromRequest($property, $request, $default, $type);
    }

    /**
     * Gets the user's state's property value.
     *
     * @param   string  $property  the property name
     * @param   mixed   $default   the optional default value
     *
     * @return  mixed  the property value or null
     * @see CMSApplication::getUserState()
     */
    public static function userState(string $property, mixed $default = null): mixed
    {
        /** @var CMSApplication $app */
        $app = self::instance();

        return $app->getUserState($property, $default);
    }
}