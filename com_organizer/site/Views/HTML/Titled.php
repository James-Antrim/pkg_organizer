<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Views\HTML;

use THM\Organizer\Adapters\{Application, Document, Input, Text};
use Joomla\CMS\Application\WebApplication;
use Joomla\CMS\Layout\FileLayout;

trait Titled
{
    public string $subtitle = '';
    public string $supplement = '';
    public string $title = '';

    /**
     * Creates a subtitle element for the resource.
     * @return void
     */
    protected function subTitle(): void
    {
        // Overwritten as necessary.
    }

    /**
     * Adds supplemental information typically related to the context of the resource to its output.
     * @return void
     */
    protected function supplement(): void
    {
        // Overwritten as necessary.
    }

    /**
     * Prepares the title for standard HTML output. (Localizes)
     *
     * @param string $standard    the title to display
     * @param string $conditional the conditional title to display
     * @param string $icon        the icon class
     *
     * @return void
     */
    protected function title(string $standard, string $conditional = '', string $icon = ''): void
    {
        $params = Input::parameters();

        if ($params->get('show_page_heading')) {
            $title = $params->get('page_heading') ?: $params->get('page_title');
        }
        else {
            $title = empty($conditional) ? Text::_($standard) : Text::_($conditional);
        }

        // Internally implemented title & toolbar output for frontend use.
        $this->title = $title;

        // @todo Remove with 7.0
        /** @var WebApplication $app */
        $app                  = Application::instance();
        $layout               = new FileLayout('joomla.toolbar.title');
        $app->JComponentTitle = $layout->render(['title' => $title, 'icon' => $icon]);

        // Title for the document / the browser tab
        $title = strip_tags($title) . ' - ' . Application::instance()->get('sitename');
        $title .= Application::backend() ? ' - ' . Text::_('SITE_ADMINISTRATION') : '';

        Document::title($title);
    }
}