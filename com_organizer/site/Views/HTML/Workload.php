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
use THM\Organizer\Buttons\FormTarget;

/**
 * Class loads personnel workload statistics into the display context.
 */
class Workload extends FormView
{
    protected string $defaultTask = 'workload.display';

    /** @inheritDoc */
    protected function authorize(): void
    {
        // Authorization performed in the model constructor to avoid redundancy and set context variables used later by the model.
    }

    /** @inheritDoc */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        $this->title('ORGANIZER_WORKLOAD');
        $toolbar = Toolbar::getInstance();

        if ($this->form->getValue('personID')) {
            $button = new FormTarget('spreadsheet', Text::_('DOWNLOAD'));
            $button->icon('fa fa-file-excel');
            $button->task = 'workload.spreadsheet';
            $toolbar->appendButton($button);
        }
    }

    /** @inheritDoc */
    protected function initializeView(): void
    {
        $this->form  = $this->get('Form');
        $this->item  = $this->get('Item');
        $this->state = $this->get('State');

        // Overwritten so as not to add a non-existent table instance to the object properties.
    }
}
