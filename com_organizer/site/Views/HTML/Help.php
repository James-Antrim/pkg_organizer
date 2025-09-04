<?php
/**
 * @package     Organizer\Views\HTML
 * @extension   Organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use THM\Organizer\Adapters\Input;

/**
 * Generates a view explaining the calling view.
 */
class Help extends BaseView
{
    protected string $layout = 'help-wrapper';

    /**
     * Adds a toolbar and title to the view.
     * @return void  adds toolbar items to the view
     */
    protected function addToolBar(): void
    {
        $topic    = strtoupper(Input::cmd('topic', 'toc'));
        $constant = 'ORGANIZER_TOPIC_' . strtoupper($topic);
        $this->title($constant);
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        $this->addToolBar();
        $this->modifyDocument();
        parent::display($tpl);
    }
}