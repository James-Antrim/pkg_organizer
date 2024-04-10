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

use THM\Organizer\Adapters\{Text, Toolbar};

/**
 * Class loads the (subject) pool form into display context.
 */
class Pool extends FormView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        $this->toDo[] = 'SelectPools';
        $this->toDo[] = 'SelectSubjects';
        $this->toDo[] = 'Programs';
        $this->toDo[] = 'SuperOrdinates';
        $this->toDo[] = 'SubOrdinates';
        $this->toDo[] = 'Functions';
        $this->toDo[] = 'Refresh on Modal Close';

        parent::addToolbar(['apply', 'save', 'save2copy']);

        $toolbar = Toolbar::getInstance();
        $toolbar->divider();

        $baseURL = "index.php?option=com_organizer&tmpl=component&type=pool&id={$this->item->id}&view=";

        $toolbar->popupButton('pools', 'ORGANIZER_ADD_POOL')
            ->popupType('iframe')
            ->url($baseURL . 'selectpools')
            ->modalWidth('800px')
            ->modalHeight('500px')
            ->icon('fa fa-list');

        $toolbar->popupButton('pools', 'ORGANIZER_ADD_SUBJECT')
            ->popupType('iframe')
            ->url($baseURL . 'selectsubjects')
            ->modalWidth('800px')
            ->modalHeight('500px')
            ->icon('fa fa-book');
    }
}
