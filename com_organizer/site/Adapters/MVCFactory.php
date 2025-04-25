<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Exception;
use Joomla\CMS\{Application\CMSApplicationInterface, Form\FormFactoryAwareInterface};
use Joomla\CMS\MVC\{Factory\MVCFactory as Core, Model\ModelInterface, View\ViewInterface};
use Joomla\CMS\Table\Table;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Input\Input as CoreInput;
use THM\Organizer\Controllers\Controller;

/**
 * Factory for MVC Object creation.
 */
class MVCFactory extends Core
{
    /**
     * Sets the internal event dispatcher on the given object. Parent has private access. :(
     *
     * @param   object  $object  the object
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
        }
        catch (Exception $exception) {
            Application::handleException($exception);
        }
    }

    /**
     * Sets the internal form factory on the given object. Parent has private access. :(
     *
     * @param   object  $object  the object
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
        }
        catch (Exception $exception) {
            Application::handleException($exception);
        }
    }

    /**
     * Method to load and return a controller object.
     *
     * @param   string                   $name    The name of the controller
     * @param   string                   $prefix  The controller prefix
     * @param   array                    $config  The configuration array for the controller
     * @param   CMSApplicationInterface  $app     The app
     * @param   CoreInput                $input   The input
     *
     * @return  Controller
     */
    public function createController($name, $prefix, array $config, CMSApplicationInterface $app, CoreInput $input): Controller
    {
        $className = 'THM\Organizer\Controllers\\' . Application::ucClass($name);

        if (!class_exists($className)) {
            $className = 'THM\Organizer\Controllers\Controller';
        }

        $config['name'] = $name;
        $controller     = new $className($config, $this, $app, $input);
        $this->addDispatcher($controller);
        $this->addFormFactory($controller);

        return $controller;
    }

    /** @inheritDoc */
    public function createModel($name, $prefix = '', array $config = []): ModelInterface
    {
        $className   = 'THM\Organizer\Models\\' . Application::ucClass($name);
        $formFactory = $this->getFormFactory();
        $model       = new $className($config, $this, $formFactory);
        $this->addDispatcher($model);
        $this->addFormFactory($model);

        return $model;
    }

    /** @inheritDoc */
    public function createView($name, $prefix = '', $type = 'HTML', array $config = []): ViewInterface
    {
        $format = strtoupper(Input::format());
        $view   = "THM\Organizer\Views\\$format\\" . Application::ucClass($name);
        $view   = new $view($config);
        $this->addDispatcher($view);
        $this->addFormFactory($view);

        return $view;
    }

    /** @inheritDoc */
    public function createTable($name, $prefix = '', array $config = []): Table
    {
        $caller = Application::ucClass($name);

        // Typically class names already in plural, which match table class names
        if (str_ends_with($caller, 's')) {
            $singlesWithS = ['Campus' => 'Campuses'];
            $table        = array_key_exists($caller, $singlesWithS) ? $singlesWithS[$caller] : $caller;
        }
        else {
            $irregularPlurals = ['Category' => 'Categories', 'Course' => 'Courses', 'Frequency' => 'Frequencies'];
            $table            = array_key_exists($caller, $irregularPlurals) ? $irregularPlurals[$caller] : $caller . 's';
        }

        $tables = [];
        foreach (glob(JPATH_SITE . '/components/com_organizer/Tables/*') as $file) {
            $file = str_replace(JPATH_SITE . '/components/com_organizer/Tables/', '', $file);
            if (str_ends_with($file, '.php')) {
                $tables[] = str_replace('.php', '', $file);
            }
        }

        if (!in_array($table, $tables)) {
            Application::error(503);
        }

        $fqName = "THM\Organizer\Tables\\$table";
        $dbo    = array_key_exists('dbo', $config) ? $config['dbo'] : $this->getDatabase();

        return new $fqName($dbo);
    }
}