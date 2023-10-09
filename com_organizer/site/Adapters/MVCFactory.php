<?php
/**
 * @package     Groups
 * @extension   com_groups
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Exception;
use Joomla\CMS\Application\CMSApplicationInterface;
use Joomla\CMS\Form\FormFactoryAwareInterface;
use Joomla\CMS\MVC\Factory\MVCFactory as Base;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Input\Input;
use THM\Organizer\Controllers\Controller;

/**
 * Factory for MVC Object creation.
 */
class MVCFactory extends Base
{
    /**
     * Maps singular model / view names to their corresponding table names.
     * @var string[]
     */
    private array $tableMap = [
        'Attribute' => 'Attributes',
        'Category' => 'Categories',
        'Group' => 'Groups',
        'Profile' => 'Profiles',
        'Role' => 'Roles',
        'Template' => 'Templates',
        'Type' => 'Types'
    ];

    /**
     * Sets the internal event dispatcher on the given object. Parent has private access. :(
     *
     * @param object $object the object
     *
     * @return  void
     */
    private function addDispatcher(object $object): void
    {
        if (!$object instanceof DispatcherAwareInterface) {
            return;
        }

        try {
            $object->setDispatcher($this->getDispatcher());
        } catch (Exception $exception) {
            Application::handleException($exception);
        }
    }

    /**
     * Sets the internal form factory on the given object. Parent has private access. :(
     *
     * @param object $object the object
     *
     * @return  void
     */
    private function addFormFactory(object $object): void
    {
        if (!$object instanceof FormFactoryAwareInterface) {
            return;
        }

        try {
            $object->setFormFactory($this->getFormFactory());
        } catch (Exception $exception) {
            Application::handleException($exception);
        }
    }

    /**
     * Method to load and return a controller object.
     *
     * @param string                  $name   The name of the controller
     * @param string                  $prefix The controller prefix
     * @param array                   $config The configuration array for the controller
     * @param CMSApplicationInterface $app    The app
     * @param Input                   $input  The input
     *
     * @return  Controller
     */
    public function createController($name, $prefix, array $config, CMSApplicationInterface $app, Input $input): Controller
    {
        $name           = preg_replace('/[^A-Z0-9_]/i', '', $name);
        $className      = "THM\Organizer\Controllers\\$name";
        $config['name'] = $name;
        $controller     = new $className($config, $this, $app, $input);
        $this->addDispatcher($controller);
        $this->addFormFactory($controller);

        return $controller;
    }

    /**
     * @inheritDoc
     */
    public function createModel($name, $prefix = '', array $config = [])
    {
        $name      = preg_replace('/[^A-Z0-9_]/i', '', $name);
        $className = "THM\Organizer\Models\\$name";
        $model     = new $className($config, $this);
        $this->addDispatcher($model);
        $this->addFormFactory($model);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function createView($name, $prefix = '', $type = 'HTML', array $config = [])
    {
        $supported = ['HTML', 'JSON', 'VCF'];
        $type      = strtoupper(preg_replace('/[^A-Z0-9_]/i', '', $type));

        if (!in_array($type, $supported)) {
            Application::error(501);
        }

        $name      = preg_replace('/[^A-Z0-9_]/i', '', $name);
        $className = "THM\Organizer\Views\\$type\\$name";
        $view      = new $className($config);
        $this->addDispatcher($view);
        $this->addFormFactory($view);

        return $view;
    }

    /**
     * @inheritDoc
     */
    public function createTable($name, $prefix = '', array $config = [])
    {
        // Clean the parameters
        $name = preg_replace('/[^A-Z0-9_]/i', '', $name);
        $name = empty($this->tableMap[$name]) ? $name : $this->tableMap[$name];

        if (!in_array($name, $this->getTableClasses())) {
            Application::error(503);
        }

        $fqName = "THM\Organizer\Tables\\$name";
        $dbo    = array_key_exists('dbo', $config) ? $config['dbo'] : $this->getDatabase();

        return new $fqName($dbo);
    }

    /**
     * Checks for the available Table classes.
     * @return array
     */
    private function getTableClasses(): array
    {
        $tables = [];
        foreach (glob(JPATH_SITE . '/components/com_groups/Tables/*') as $table) {
            $table    = str_replace(JPATH_SITE . '/components/com_groups/Tables/', '', $table);
            $tables[] = str_replace('.php', '', $table);
        }

        return $tables;
    }
}