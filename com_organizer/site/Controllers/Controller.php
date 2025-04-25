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
use Joomla\CMS\{Application\CMSApplication, Uri\Uri};
use Joomla\CMS\MVC\{Controller\BaseController, Factory\MVCFactoryInterface};
use Joomla\Input\Input as CoreInput;
use THM\Organizer\Adapters\{Application, Input, Text};
use THM\Organizer\Helpers\Can;

/**
 * Class provides basic component functionality.
 */
class Controller extends BaseController
{
    /**
     * The URL to redirection into this component.
     * @var string
     */
    protected string $baseURL = '';

    /** @inheritDoc */
    public function __construct($config = [],
        ?MVCFactoryInterface $factory = null,
        ?CMSApplication $app = null,
        ?CoreInput $input = null
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
     * Checks for a form token in the request. Wraps the parent function to add direct exception handling.
     *
     * @param   string  $method    the optional request method in which to look for the token key.
     * @param   bool    $redirect  whether to implicitly redirect user to the referrer page on failure or simply return false.*
     *
     * @return bool
     */
    public function checkToken($method = 'post', $redirect = true): bool
    {
        $valid = false;
        try {
            $valid = parent::checkToken($method, $redirect);
        }
        catch (Exception $exception) {
            Application::handleException($exception);
        }

        return $valid;
    }

    /** @inheritDoc */
    public function display($cachable = false, $urlparams = []): BaseController
    {
        $format = strtoupper(Input::format());
        $view   = Application::ucClass($this->name);

        if (!class_exists("\\THM\\Organizer\\Views\\$format\\$view")) {
            Application::error(503);
        }

        if (!Can::view($view)) {
            Application::error(403);
        }

        return parent::display($cachable, $urlparams);
    }

    /**
     * Creates a pdf file based on form data.
     * @return void
     * @throws Exception
     */
    public function pdf(): void
    {
        Input::format('pdf');
        $this->display();
    }

    /**
     * Creates a xls file based on form data.
     * @return void
     * @throws Exception
     */
    public function xls(): void
    {
        Input::format('xls');
        $this->display();
    }
}