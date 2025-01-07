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
use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Toolbar\{Toolbar as Core, ToolbarHelper as Helper};

/**
 * Class integrates toolbar and toolbar helper into one interface for dealing with toolbars.
 */
class Toolbar
{
    /**
     * Returns the GLOBAL Toolbar object, only creating it if it doesn't already exist. The parent documentation says
     * deprecated => use the container, but the container is explicitly not allowed to set toolbars because they are
     * GLOBAL and used by joomla to display component items outside the component context.
     *
     * @param   string  $name  The name of the toolbar.
     *
     * @return  Core  The Toolbar object.
     * @see HtmlDocument::getToolbar()
     */
    public static function getInstance(string $name = 'toolbar'): Core
    {
        return Document::getToolbar($name);
    }

    /**
     * Renders a toolbar. Wraps the base function due to errors thrown by button rendering.
     *
     * @param   string  $name     the name of the toolbar to render, defaults to global 'toolbar'
     * @param   array   $options  the options used to render the toolbar
     *
     * @return string
     * @see Core::render()
     */
    public static function render(string $name = 'toolbar', array $options = []): string
    {
        $bar  = self::getInstance($name);
        $html = '';

        try {
            $html = $bar->render($options);
        }
        catch (Exception $exception) {
            Application::handleException($exception);
        }

        return $html;
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