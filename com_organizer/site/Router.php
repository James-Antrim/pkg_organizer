<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer;

use Joomla\CMS\Application\SiteApplication;
use Joomla\CMS\Component\Router\{RouterView, RouterViewConfiguration};
use Joomla\CMS\Component\Router\Rules\{MenuRules, NomenuRules, StandardRules};
use Joomla\CMS\Menu\AbstractMenu;

class Router extends RouterView
{
    /**
     * Config Component router constructor
     *
     * @param   SiteApplication  $app   The application object
     * @param   AbstractMenu     $menu  The menu object to work with
     */
    public function __construct(SiteApplication $app, AbstractMenu $menu)
    {
        $categories = new RouterViewConfiguration('categories');
        $categories->setKey('id');
        $this->registerView($categories);

        $category = new RouterViewConfiguration('category');
        $category->setKey('id');
        $this->registerView($category);

        $groups = new RouterViewConfiguration('groups');
        $groups->setKey('id')->setParent($category, 'categoryID');
        $this->registerView($groups);

        $group = new RouterViewConfiguration('group');
        $group->setKey('id')->setParent($category, 'categoryID');
        $this->registerView($groups);

        $instances = new RouterViewConfiguration('termine');
        $instances->setKey('id')->setParent($category, 'categoryID')->setParent($group, 'groupID');
        $this->registerView($instances);

        parent::__construct($app, $menu);

        $this->attachRule(new MenuRules($this));
        $this->attachRule(new StandardRules($this));
        $this->attachRule(new NomenuRules($this));
    }
}