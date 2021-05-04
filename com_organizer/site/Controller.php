<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @author      Wolf Rost, <wolf.rost@mni.thm.de>
 * @author      Florian Fenzl, <florian.fenzl@mni.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer;

use Exception;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use Organizer\Helpers;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Views\HTML\BaseView as HTMLView;
use Organizer\Views\JSON\BaseView as JSONView;
use Organizer\Views\PDF\BaseView as PDFView;
use Organizer\Views\XLS\BaseView as XLSView;

/**
 * Class receives user actions and performs access checks and redirection.
 */
class Controller extends BaseController
{
    public $adminContext;

    protected $listView = '';

    protected $resource = '';

    /**
     * Class constructor
     *
     * @param   array  $config  An optional associative [] of configuration settings.
     */
    public function __construct($config = [])
    {
        $config['base_path']    = JPATH_COMPONENT_SITE;
        $config['model_prefix'] = '';
        parent::__construct($config);

        $this->adminContext = OrganizerHelper::getApplication()->isClient('administrator');
        $this->registerTask('add', 'edit');
    }

    /**
     * Makes call to the models's save function, and redirects to the same view.
     *
     * @return void
     */
    public function apply()
    {
        $modelName = "Organizer\\Models\\" . OrganizerHelper::getClass($this->resource);
        $model     = new $modelName();

        if ($resourceID = $model->save()) {
            OrganizerHelper::message('ORGANIZER_SAVE_SUCCESS');
        } else {
            OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');
        }

        $url = Helpers\Routing::getRedirectBase() . "&view={$this->resource}_edit&id=$resourceID";
        $this->setRedirect($url);
    }

    /**
     * Redirects to the manager from the form.
     *
     * @return void
     */
    public function cancel()
    {
        $defaultView = empty($this->listView) ? '' : "&view={$this->listView}";
        $default     = Helpers\Routing::getRedirectBase() . $defaultView;
        $referrer    = Helpers\Input::getString('referrer');
        $url         = $referrer ? $referrer : $default;
        $this->setRedirect($url);
    }

    /**
     * Makes call to the model's delete function, and redirects to the manager view.
     *
     * @return void
     */
    public function delete()
    {
        $modelName = "Organizer\\Models\\" . OrganizerHelper::getClass($this->resource);
        $model     = new $modelName();

        if ($model->delete($this->resource)) {
            OrganizerHelper::message('ORGANIZER_DELETE_SUCCESS');
        } else {
            OrganizerHelper::message('ORGANIZER_DELETE_FAIL', 'error');
        }

        $url = Helpers\Routing::getRedirectBase();
        $url .= "&view={$this->listView}";
        $this->setRedirect($url);
    }

    /**
     * Typical view method for MVC based architecture.
     *
     * @param   bool   $cachable   If true, the view output will be cached
     * @param   array  $urlparams  An array of safe URL parameters and their variable types.
     *
     * @return BaseController  A BaseController object to support chaining.
     * @throws Exception
     */
    public function display($cachable = false, $urlparams = []): BaseController
    {
        $document = Factory::getDocument();
        $format   = $this->input->get('format', $document->getType());
        $name     = $this->input->get('view', 'Organizer');
        $template = $this->input->get('layout', 'default', 'string');

        $view = $this->getView(
            $name,
            $format,
            '',
            ['base_path' => $this->basePath, 'layout' => $template]
        );

        // Only html views require models to be loaded in this way.
        if ($format === 'html' and $model = $this->getModel($name)) {
            // Push the model into the view (as default)
            $view->setModel($model, true);
        }

        $view->document = $document;

        try {
            $view->display();
        } catch (Exception $exception) {
            OrganizerHelper::message($exception->getMessage(), 'error');
            $this->setRedirect(Uri::base());
        }

        return $this;
    }

    /**
     * Redirects to the edit view with an item id. Access checks performed in the view.
     *
     * @return void
     * @throws Exception
     */
    public function edit()
    {
        Helpers\Input::set('view', "{$this->resource}_edit");
        $this->display();
    }

    /**
     * Method to get the controller name. This commentary has to be here otherwise using functions register unhandled
     * exceptions.
     *
     * @return  string  The name of the dispatcher
     */
    public function getName(): string
    {
        if (empty($this->name)) {

            $this->name = OrganizerHelper::getClass($this);
        }

        return $this->name;
    }

    /**
     * Method to get a model object, loading it if required.
     *
     * @param   string  $name    The model name. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for model. Optional.
     *
     * @return  BaseDatabaseModel|false  Model object on success; otherwise false on failure.
     */
    public function getModel($name = '', $prefix = '', $config = [])
    {
        $name = empty($name) ? $this->getName() : $name;

        if (empty($name)) {
            return false;
        }

        $modelName = "Organizer\\Models\\" . OrganizerHelper::getClass($name);

        if ($model = new $modelName($config)) {
            // Task is a reserved state
            $model->setState('task', $this->task);

            // Let's get the application object and set menu information if it's available
            $menu = OrganizerHelper::getApplication()->getMenu();

            if (is_object($menu) && $item = $menu->getActive()) {
                $params = $menu->getParams($item->id);

                // Set default state data
                $model->setState('parameters.menu', $params);
            }
        }

        return $model;
    }

    /**
     * Method to get a reference to the current view and load it if necessary.
     *
     * @param   string  $name    The view name. Optional, defaults to the controller name.
     * @param   string  $type    The view type. Optional.
     * @param   string  $prefix  The class prefix. Optional.
     * @param   array   $config  Configuration array for view. Optional.
     *
     * @return  HTMLView|JSONView|PDFView|XLSView  the view object
     */
    public function getView($name = '', $type = '', $prefix = 'x', $config = []): object
    {
        // @note We use self so we only access stuff in this class rather than in all classes.
        if (!isset(self::$views)) {
            self::$views = [];
        }

        if (empty($name)) {
            $name = $this->getName();
        }

        $viewName = OrganizerHelper::getClass($name);
        $type     = strtoupper(preg_replace('/[^A-Z0-9_]/i', '', $type));
        $name     = "Organizer\\Views\\$type\\$viewName";

        $config['base_path']     = JPATH_COMPONENT_SITE . "/Views/$type";
        $config['helper_path']   = JPATH_COMPONENT_SITE . "/Helpers";
        $config['template_path'] = JPATH_COMPONENT_SITE . "/Layouts/$type";

        $key = strtolower($viewName);
        if (empty(self::$views[$key][$type][$prefix])) {
            if ($type === 'HTML' and $view = new $name($config)) {
                self::$views[$key][$type][$prefix] = &$view;
            } elseif ($view = new $name()) {
                self::$views[$key][$type][$prefix] = &$view;
            } else {
                OrganizerHelper::error(501);
            }
        }

        return self::$views[$key][$type][$prefix];
    }

    /**
     * Creates the environment for the proper output of a given JSON string.
     *
     * @param   string  $response  the preformatted response string
     *
     * @return void
     */
    protected function jsonResponse(string $response)
    {
        $app = OrganizerHelper::getApplication();

        // Send json mime type.
        $app->setHeader('Content-Type', 'application/json' . '; charset=' . $app->charSet);
        $app->sendHeaders();

        echo $response;

        $app->close();
    }

    /**
     * Performs access checks, makes call to the models's merge function, and
     * redirects to the room manager view
     *
     * @return void
     */
    public function merge()
    {
        $modelName = "Organizer\\Models\\" . OrganizerHelper::getClass($this->resource);
        $model     = new $modelName();

        if ($model->merge($this->resource)) {
            OrganizerHelper::message('ORGANIZER_MERGE_SUCCESS');
        } else {
            OrganizerHelper::message('ORGANIZER_MERGE_FAIL', 'error');
        }

        $url = Helpers\Routing::getRedirectBase();
        $url .= "&view={$this->listView}";
        $this->setRedirect($url);
    }

    /**
     * Attempts to automatically merge the selected resources, if the corresponding function is available. Redirects to
     * the merge view if the automatic merge was unavailable or implausible.
     *
     * @return void
     * @throws Exception
     */
    public function mergeView()
    {
        $url = "index.php?option=com_organizer&view={$this->listView}";

        if (JDEBUG) {
            OrganizerHelper::message('ORGANIZER_DEBUG_ON', 'error');
            $this->setRedirect($url);

            return;
        }

        $selectedIDs = Helpers\Input::getSelectedIDs();
        if (count($selectedIDs) == 1) {
            $msg = Helpers\Languages::_('ORGANIZER_TOO_FEW');
            $this->setRedirect(Route::_($url, false), $msg, 'notice');

            return;
        }

        // Reliance on POST requires a different method of redirection
        Helpers\Input::set('view', "{$this->resource}_merge");
        $this->display();
    }

    /**
     * Creates a pdf file based on form data.
     *
     * @return void
     * @throws Exception
     */
    public function pdf()
    {
        Helpers\Input::set('format', 'pdf');
        $this->display();
    }

    /**
     * Save form data to the database.
     *
     * @return void
     */
    public function save()
    {
        $modelName = "Organizer\\Models\\" . OrganizerHelper::getClass($this->resource);
        $model     = new $modelName();

        if ($model->save()) {
            OrganizerHelper::message('ORGANIZER_SAVE_SUCCESS');
        } else {
            OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');
        }

        $url = Helpers\Routing::getRedirectBase() . "&view={$this->listView}";
        $this->setRedirect(Route::_($url, false));
    }

    /**
     * Makes call to the models's save2copy function, and redirects to the manager view.
     *
     * @return void
     * @throws Exception
     */
    public function save2copy()
    {
        $modelName = "Organizer\\Models\\" . OrganizerHelper::getClass($this->resource);
        $model     = new $modelName();

        if ($newID = $model->save2copy()) {
            OrganizerHelper::message('ORGANIZER_SAVE_SUCCESS');
            Helpers\Input::set('id', $newID);

            $url = Helpers\Routing::getRedirectBase() . "&view={$this->resource}_edit&id=$newID";
            $this->setRedirect($url);
        } else {
            OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');
            $this->display();
        }
    }

    /**
     * Toggles binary resource properties from a list view.
     *
     * @return void
     */
    public function toggle()
    {
        $modelName = "Organizer\\Models\\" . OrganizerHelper::getClass($this->resource);
        $model     = new $modelName();

        if (method_exists($model, 'toggle') and $model->toggle()) {
            OrganizerHelper::message('ORGANIZER_SAVE_SUCCESS');
        } else {
            OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');
        }

        $url = Helpers\Routing::getRedirectBase() . "&view={$this->listView}";
        $this->setRedirect($url);
    }

    /**
     * Creates an xls file based on form data.
     *
     * @return void
     * @throws Exception
     */
    public function xls()
    {
        Helpers\Input::set('format', 'xls');
        $this->display();
    }
}
