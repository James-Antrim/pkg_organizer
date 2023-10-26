<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Exception;
use JLoader;
use Joomla\CMS\Filesystem\Path;
use Joomla\CMS\Filter\InputFilter;
use Joomla\CMS\Log\Log;
use Joomla\CMS\Toolbar\{Toolbar as Base, ToolbarButton as Button, ToolbarFactoryInterface, ToolbarHelper as Helper};
use Joomla\DI\Exception\KeyNotFoundException;
use THM\Organizer\Helpers\OrganizerHelper;

class Toolbar extends Base
{
    /**
     * @inheritDoc
     */
    public function __construct($name = 'toolbar')
    {
        parent::__construct($name);

        $this->_buttonPath[] = JPATH_COMPONENT_SITE . '/Buttons';
    }

    /**
     * Returns the GLOBAL Toolbar object, only creating it if it doesn't already exist. The parent documentation says
     * deprecated => use the container, but the container is explicitly not allowed to set toolbars because they are
     * GLOBAL and used by joomla to display component items outside the component context.
     *
     * @param   string  $name  The name of the toolbar.
     *
     * @return Toolbar The Toolbar object.
     */
    public static function getInstance($name = 'toolbar'): Base
    {
        if (empty(self::$instances[$name])) {
            $container = Application::getContainer();

            try {
                $tbFactory              = $container->get(ToolbarFactoryInterface::class);
                self::$instances[$name] = $tbFactory->createToolbar($name);
            }
            catch (KeyNotFoundException $exception) {
                Application::handleException($exception);
            }
        }

        return self::$instances[$name];
    }

    /**
     * Load the button class including the deprecated ones.
     *
     * @param   string  $type  Button Type
     *
     * @return  string|null
     */
    private function loadButtonClass(string $type): ?string
    {
        $buttonClasses = [
            'THM\\Organizer\\Buttons\\' . OrganizerHelper::classDecode($type),
            'Joomla\\CMS\\Toolbar\\Button\\' . ucfirst($type) . 'Button'
        ];

        foreach ($buttonClasses as $buttonClass) {
            if (!class_exists($buttonClass)) {
                continue;
            }

            return $buttonClass;
        }

        return null;
    }

    /**
     * @inheritDoc
     * @return Button|bool
     */
    public function loadButtonType($type, $new = false): Button|bool
    {
        $signature = md5($type);

        if ($new === false && isset($this->_buttons[$signature])) {
            return $this->_buttons[$signature];
        }

        $buttonClass = $this->loadButtonClass($type);

        if (!$buttonClass) {
            $dirs = $this->_buttonPath ?? [];

            $file = InputFilter::getInstance()->clean(str_replace('_', DIRECTORY_SEPARATOR, strtolower($type)) . '.php', 'path');

            JLoader::import('joomla.filesystem.path');

            if ($buttonFile = Path::find($dirs, $file)) {
                include_once $buttonFile;
            }
            else {
                Log::add(Text::sprintf('JLIB_HTML_BUTTON_NO_LOAD', $buttonClass, $buttonFile), Log::WARNING, 'jerror');

                return false;
            }

            $buttonClass = $this->loadButtonClass($type);

            if (!$buttonClass) {
                return false;
            }
        }

        $this->_buttons[$signature] = new $buttonClass($this);

        return $this->_buttons[$signature];
    }

    /**
     * Render a toolbar. Wraps the parent class to avoid exception handling when the layout file is not found.
     *
     * @param   array  $options  The options of toolbar.
     *
     * @return  string  HTML for the toolbar.
     */
    public function render(array $options = []): string
    {
        try {
            return parent::render($options);
        }
        catch (Exception $exception) {
            Application::message($exception->getMessage(), Application::ERROR);

            return '';
        }
    }

    /**
     * Sets the application (view) title to a pre-rendered title layout with the given text and optional icon. Also sets
     * the document title.
     *
     * @param   string  $title  the view title
     * @param   string  $icon   the icon class name
     *
     * @return  void
     * @see Helper::title()
     */
    public static function setTitle(string $title, string $icon = ''): void
    {
        Helper::title(Text::_($title), $icon);
    }
}