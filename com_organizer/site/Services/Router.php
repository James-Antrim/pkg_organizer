<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Services;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\Router\RouterView;
use Joomla\CMS\Menu\AbstractMenu;
use Joomla\Database\DatabaseInterface;
use THM\Organizer\Services\Rules\MenuRules;

/** @inheritDoc */
class Router extends RouterView
{
    private DatabaseInterface $database;

    /**
     * Content Component router constructor
     *
     * @param CMSApplication    $app      The application object
     * @param AbstractMenu      $menu     The menu object to work with
     * @param DatabaseInterface $database The database object
     */
    public function __construct(CMSApplication $app, AbstractMenu $menu, DatabaseInterface $database)
    {
        $this->database = $database;
        /**
         * Instance
         * -stand-alone only => new tab
         */
        /**
         * Instances
         * -stand-alone or under campus, department, category, group, person or room
         * -layouts: list default
         */
        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
    }
}
/*
contact_tracking:
course: (own)/edit
courses: (list)
curriculum:
export:
lesson_statistics:
organizer:
instance:
instances: grid, list
profile: (edit)
room_statistics:
roomoverview:
rooms:
search:
statistics:
subject: (own)/edit
subjects: (list)
workload:
https://www.thm.de/organizer/images/thm_organizer/hoersaal-kapazitaeten-friedberg.pdf, {"menu_show":1}
*/