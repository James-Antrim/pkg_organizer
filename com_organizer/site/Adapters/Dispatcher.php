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
use Joomla\CMS\Dispatcher\ComponentDispatcher;
use Joomla\CMS\MVC\Controller\BaseController;
use THM\Organizer\Controllers\Controller;

/**
 * @inheritDoc
 * Adjusts the component dispatcher which kept calling for a controller named 'display'.
 * Adds the singular language path for the component localizations.
 */
class Dispatcher extends ComponentDispatcher
{
    /** @inheritDoc */
    protected $mvcFactory;

    /** @inheritDoc */
    protected $option = 'com_organizer';

    /** @inheritDoc */
    public function dispatch(): void
    {
        // Check component access permission
        $this->checkAccess();

        $command = Input::task();
        $args    = [];
        $task    = '';

        // Check for a controller.task command.
        if (str_contains($command, '.')) {
            $commands   = explode('.', $command);
            $controller = array_shift($commands);
            $this->input->set('controller', $controller);
            $task = array_shift($commands);
            $this->input->set('task', $task);

            while ($commands) {
                $args[] = array_shift($commands);
            }

            Input::set('args', $args);

        }
        elseif (!$controller = Input::controller()) {
            if (Application::backend()) {
                $controller = 'Organizer';
            }
            else {
                Application::redirect();
            }
        }

        $task = $task ?? $command;

        $config['option'] = $this->option;
        $config['name']   = $controller;

        $controller = $this->getController($controller, ucfirst($this->app->getName()), $config);

        try {
            /** @var Controller $controller */
            $controller->execute($task);
            $controller->redirect();
        } catch (Exception $exception) {
            Application::handleException($exception);
        }
    }

    /** @inheritDoc */
    public function getController(string $name, string $client = '', array $config = []): BaseController
    {
        // Set up the client
        $client = $client ?: ucfirst($this->app->getName());

        // Get the controller instance
        return $this->mvcFactory->createController(
            $name,
            $client,
            $config,
            $this->app,
            $this->input
        );
    }

    /** @inheritDoc */
    protected function loadLanguage(): void
    {
        $language = Application::language();
        $language->load($this->option);
        $language->load($this->option, JPATH_ADMINISTRATOR . '/components/com_organizer');
    }
}
