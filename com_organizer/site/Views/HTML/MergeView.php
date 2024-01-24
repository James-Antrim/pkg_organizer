<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use THM\Organizer\Adapters\{Input, Text, Toolbar};

/**
 * Class loads form data from selected list resources into the HTML view context for selection of values.
 */
abstract class MergeView extends FormView
{
    /**
     * @inheritDoc
     */
    protected function addToolbar(array $buttons = [], string $constant = ''): void
    {
        Input::set('hidemainmenu', true);
        $controller = $this->getName();
        $key        = str_replace('Merge', 'MERGE_', $controller);
        $this->setTitle(strtoupper($key));

        $toolbar = Toolbar::getInstance();
        $toolbar->save($controller . '.save', Text::_('MERGE'))->icon('fa fa-code-merge');
        $toolbar->cancel("$controller.cancel");
    }

    /**
     * @inheritDoc
     * Overrides to avoid calling getItem and getTable as neither makes sense in a merge context.
     */
    protected function initializeView(): void
    {
        $this->form  = $this->get('Form');
        $this->state = $this->get('State');
    }
}
