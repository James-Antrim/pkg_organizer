<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer;

use THM\Organizer\Adapters\Application;

defined('_JEXEC') or die;

spl_autoload_register(function ($originalClassName) {
    if ($originalClassName === 'TCPDF') {
        require_once JPATH_LIBRARIES . '/tcpdf/tcpdf.php';

        return;
    }

    $classNameParts = explode('\\', $originalClassName);

    if (array_shift($classNameParts) !== 'THM' or array_shift($classNameParts) !== 'Organizer') {
        return;
    }

    //THM\Groups\Plugin\<Type>\Groups
    if (reset($classNameParts) === 'Plugin') {
        Application::error(503);
        /* No Organizer plugins migrated. */
        array_shift($classNameParts);
        $type     = strtolower(array_shift($classNameParts));
        $filepath = JPATH_ROOT . "/plugins/$type/organizer/Organizer.php";
    }
    //THM\Groups\Module\<Name>\Path..
    elseif (reset($classNameParts) === 'Module') {
        Application::error(503);
        array_shift($classNameParts);
        $name      = strtolower(array_shift($classNameParts));
        $extension = array_search($name, []);
        $filepath  = JPATH_ROOT . "/modules/$extension/" . implode('/', $classNameParts) . '.php';
    }
    else {
        // Namespaced classes are all in the site directory
        if (reset($classNameParts) === 'Admin') {
            array_shift($classNameParts);
        }
        $className        = array_pop($classNameParts);
        $classNameParts[] = empty($className) ? 'Organizer' : $className;
        $filepath         = JPATH_ROOT . '/components/com_organizer/' . implode('/', $classNameParts) . '.php';
    }

    if (is_file($filepath)) {
        require_once $filepath;
    }
});
