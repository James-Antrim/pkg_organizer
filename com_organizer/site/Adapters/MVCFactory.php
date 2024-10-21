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
use Joomla\CMS\{Application\CMSApplicationInterface, Form\FormFactoryAwareInterface, MVC\View\ViewInterface};
use Joomla\CMS\MVC\Factory\{MVCFactory as Base, MVCFactoryInterface};
use Joomla\CMS\Table\Table;
use Joomla\Event\DispatcherAwareInterface;
use Joomla\Input\Input as JInput;
use THM\Organizer\Controllers\Controller;

/**
 * Factory for MVC Object creation.
 */
class MVCFactory extends Base
{
    /**
     * Sets the internal event dispatcher on the given object. Parent has private access. :(
     *
     * @param   object  $object  the object
     *
     * @return  void
     * @todo check this
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
     * @todo check this
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
     * @param   JInput                   $input   The input
     *
     * @return  Controller
     */
    public function createController($name, $prefix, array $config, CMSApplicationInterface $app, JInput $input): Controller
    {
        $className      = 'THM\Organizer\Controllers\\' . Application::ucClass($name);
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
        $className   = 'THM\Organizer\Models\\' . Application::ucClass($name);
        $formFactory = $this->getFormFactory();
        $model       = new $className($config, $this, $formFactory);
        $this->addDispatcher($model);
        $this->addFormFactory($model);

        return $model;
    }

    /**
     * @inheritDoc
     */
    public function createView($name, $prefix = '', $type = 'HTML', array $config = []): ViewInterface
    {
        $format = strtoupper(Input::format());
        $view   = "THM\Organizer\Views\\$format\\" . Application::ucClass($name);
        $view   = new $view($config);
        $this->addDispatcher($view);
        $this->addFormFactory($view);

        return $view;
    }

    /**
     * Method to load and return a table object. This function is not yet to my knowledge used, but necessary to fulfill
     * the MVCFactoryInterface.
     *
     * @param   string  $name    The name of the table.
     * @param   string  $prefix  Optional table prefix.
     * @param   array   $config  Optional configuration array for the table.
     *
     * @return  Table  The table object
     * @see MVCFactoryInterface
     */
    public function createTable($name, $prefix = '', array $config = []): Table
    {
        $caller = Application::ucClass($name);
        $table  = str_ends_with($caller, 's') ? $caller : match ($caller) {
            // Unusual plurals
            'Campus' => 'Campuses',
            'Category' => 'Categories',
            'Course' => 'Courses',
            'Frequency' => 'Frequencies',
            // Potentially derivative classes: Import-, Merge-, Select-
            default => $caller . 's'
        };

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