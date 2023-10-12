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

defined('_JEXEC') or die;

spl_autoload_register(function ($originalClassName)
{
    if ($originalClassName === 'TCPDF') {
        require_once JPATH_LIBRARIES . '/tcpdf/tcpdf.php';

        return;
    }

    $classNameParts = explode('\\', $originalClassName);

    if (array_shift($classNameParts) !== 'THM' or array_shift($classNameParts) !== 'Organizer') {
        return;
    }

    $className = array_pop($classNameParts);

    if (reset($classNameParts) === 'Admin') {
        array_shift($classNameParts);
    }

    $classNameParts[] = empty($className) ? 'Organizer' : $className;

    $filepath = JPATH_ROOT . '/components/com_organizer/' . implode('/', $classNameParts) . '.php';

    if (is_file($filepath)) {
        require_once $filepath;
    }
});
