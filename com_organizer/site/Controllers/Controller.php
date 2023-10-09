<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Exception;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Factory;
use Joomla\CMS\MVC\Controller\BaseController;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Application, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Can;
use THM\Organizer\Helpers\OrganizerHelper;
use THM\Organizer\Views\HTML\BaseView as HTMLView;
use THM\Organizer\Views\JSON\BaseView as JSONView;
use THM\Organizer\Views\PDF\BaseView as PDFView;
use THM\Organizer\Views\XLS\BaseView as XLSView;
use THM\Organizer\Views\XML\BaseView as XMLView;

class Controller extends BaseController
{
    /**
     * Flag for calling context.
     * @var bool
     */
    protected bool $backend;

    /**
     * The URL to redirection into this component.
     * @var string
     */
    protected string $baseURL = '';

    /**
     * @inheritDoc
     */
    public function __construct($config = [], MVCFactoryInterface $factory = null, ?CMSApplication $app = null, ?JInput $input = null)
    {
        $this->backend = Application::backend();
        $this->baseURL = $this->baseURL ?: Uri::base() . 'index.php?option=com_groups';
        parent::__construct($config, $factory, $app, $input);
    }

    /**
     * Default authorization check. Level component administrator. Override for nuance.
     * @return void
     */
    protected function authorize(): void
    {
        if (!Can::administrate()) {
            Application::error(403);
        }
    }

    /**
     * Default authorization check. Level component administrator. Override for nuance.
     * @return void
     */
    protected function authorizeAJAX(): void
    {
        if (!Can::administrate()) {
            echo Text::_('GROUPS_403');
            $this->app->close();
        }
    }

    /**
     * @inheritDoc
     */
    public function display($cachable = false, $urlparams = []): BaseController
    {
        $document = Factory::getDocument();
        $format   = $this->input->get('format', $document->getType());
        $template = $this->input->get('layout', 'default', 'string');
        $view     = $this->input->get('view', 'Organizer');

        if (!class_exists("\\THM\\Organizer\\Views\\$format\\$view")) {
            Application::error(503);
        }

        if (!Can::view($view)) {
            Application::error(403);
        }

        $view = $this->getView(
            $view,
            $format,
            '',
            ['base_path' => $this->basePath, 'layout' => $template]
        );

        // Only html views require models to be loaded in this way.
        if ($format === 'html' and $model = $this->getModel($view)) {
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
     * Method to get the controller name. This commentary has to be here otherwise using functions register unhandled
     * exceptions.
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
     * @param string $name   The model name. Optional.
     * @param string $prefix The class prefix. Optional.
     * @param array  $config Configuration array for model. Optional.
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
     * @param string $name   The view name. Optional, defaults to the controller name.
     * @param string $type   The view type. Optional.
     * @param string $prefix The class prefix. Optional.
     * @param array  $config Configuration array for view. Optional.
     *
     * @return  HTMLView|JSONView|PDFView|XLSView|XMLView  the view object
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

        $viewName       = OrganizerHelper::getClass($name);
        $xmlDerivatives = ['CalDEV' => 'CALDEV', 'WebDAV' => 'WEBDAV'];
        $type           = preg_replace('/[^A-Z0-9_]/i', '', strtoupper($type));

        if ($derivative = array_search($type, $xmlDerivatives)) {
            $type     = 'XML';
            $viewName = $derivative;
        }

        $name = "Organizer\\Views\\$type\\$viewName";

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
     * @param string $response the preformatted response string
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
     * @return void
     */
    public function merge()
    {
        $modelName = "Organizer\\Models\\" . OrganizerHelper::getClass($this->resource);
        $model     = new $modelName();

        if ($model->merge($this->resource)) {
            OrganizerHelper::message('ORGANIZER_MERGE_SUCCESS', 'success');
        } else {
            OrganizerHelper::message('ORGANIZER_MERGE_FAIL', 'error');
        }

        $url = Helpers\Routing::getRedirectBase();
        $url .= "&view=$this->listView";
        $this->setRedirect($url);
    }

    /**
     * Attempts to automatically merge the selected resources, if the corresponding function is available. Redirects to
     * the merge view if the automatic merge was unavailable or implausible.
     * @return void
     * @throws Exception
     */
    public function mergeView()
    {
        $url = "index.php?option=com_organizer&view=$this->listView";

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
     * @return void
     * @throws Exception
     */
    public function pdf()
    {
        Helpers\Input::set('format', 'pdf');
        $this->display();
    }

    /**
     * Creates an xls file based on form data.
     * @return void
     * @throws Exception
     */
    public function xls()
    {
        Helpers\Input::set('format', 'xls');
        $this->display();
    }
}
