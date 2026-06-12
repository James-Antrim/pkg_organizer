<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Joomla\CMS\Application\{CMSApplication, CMSApplicationInterface};
use Joomla\CMS\Component\Router\{RouterFactoryInterface, RouterInterface};
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use THM\Organizer\Services\Router;

/**
 * Router factory.
 */
class RouterFactory implements RouterFactoryInterface
{
    private DatabaseInterface $database;

    /**
     * Creates the router factory
     *
     * @param   ?DatabaseInterface $db The database object
     */
    public function __construct(DatabaseInterface $db = null)
    {
        $this->database = $db;
    }

    /**
     * Creates a router.
     *
     * @param CMSApplicationInterface $application The application
     * @param AbstractMenu            $menu        The menu object to work with
     *
     * @return  RouterInterface
     */
    public function createRouter(CMSApplicationInterface $application, AbstractMenu $menu): RouterInterface
    {
        /** @var CMSApplication $application */
        return new Router($application, $menu, $this->database);
    }
}
