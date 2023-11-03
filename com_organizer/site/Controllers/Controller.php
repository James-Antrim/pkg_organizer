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
use Joomla\Application\WebApplicationInterface;
use Joomla\Input\Input as JInput;
use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\{Controller\BaseController, Factory\MVCFactoryInterface, Model\BaseDatabaseModel as BDBM};
use Joomla\CMS\Router\Route;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Application, Input, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Can;
use THM\Organizer\Helpers\OrganizerHelper;

/**
 * Class provides ...
 */
class Controller extends BaseController
{
    /**
     * The URL to redirection into this component.
     * @var string
     */
    protected string $baseURL = '';

    /**
     * @inheritDoc
     */
    public function __construct(
        $config = [],
        MVCFactoryInterface $factory = null,
        ?CMSApplication $app = null,
        ?JInput $input = null
    )
    {
        $this->baseURL = $this->baseURL ?: Uri::base() . 'index.php?option=com_organizer';
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
            echo Text::_('403');
            $this->app->close();
        }
    }

    /**
     * @inheritDoc
     */
    public function display($cachable = false, $urlparams = []): BaseController
    {
        $format = Input::getFormat();
        $view   = $this->input->get('view', 'Organizer');

        if (!class_exists("\\THM\\Organizer\\Views\\$format\\$view")) {
            Application::error(503);
        }

        if (!Can::view($view)) {
            Application::error(403);
        }

        return parent::display($cachable, $urlparams);
    }

    /**
     * Performs access checks, makes call to the models's merge function, and
     * redirects to the room manager view
     * @return void
     */
    public function merge(): void
    {
        $modelName = 'THM\\Organizer\\Models\\' . OrganizerHelper::getClass($this->resource);
        $model     = new $modelName();

        if ($model->merge($this->resource)) {
            Application::message('ORGANIZER_MERGE_SUCCESS');
        }
        else {
            Application::message('ORGANIZER_MERGE_FAIL', Application::ERROR);
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
    public function mergeView(): void
    {
        $url = "index.php?option=com_organizer&view=$this->listView";

        if (JDEBUG) {
            Application::message('ORGANIZER_DEBUG_ON', Application::ERROR);
            $this->setRedirect($url);

            return;
        }

        $selectedIDs = Input::getSelectedIDs();
        if (count($selectedIDs) == 1) {
            $msg = Text::_('ORGANIZER_TOO_FEW');
            $this->setRedirect(Route::_($url, false), $msg, Application::NOTICE);

            return;
        }

        // Reliance on POST requires a different method of redirection
        Input::set('view', "{$this->resource}_merge");
        $this->display();
    }

    /**
     * Creates a pdf file based on form data.
     * @return void
     * @throws Exception
     */
    public function pdf(): void
    {
        Input::set('format', 'pdf');
        $this->display();
    }

    /**
     * Creates an xls file based on form data.
     * @return void
     * @throws Exception
     */
    public function xls(): void
    {
        Input::set('format', 'xls');
        $this->display();
    }
}
