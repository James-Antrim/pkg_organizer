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
 * Class loads the resource form into display context. Specific resource determined by extending class.
 */
abstract class MergeView extends FormView
{
    public array $toDo = [
        'Figure out a way to make elements with values required beyond the asterix in merge fields.'
    ];

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
        $toolbar->cancel("$controller.cancel", Text::_('CANCEL'));
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
