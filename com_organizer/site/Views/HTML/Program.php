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

use THM\Organizer\Adapters\{Document, Input};

/**
 * Class loads the (degree) program form into display context.
 */
class Program extends FormView
{
    protected array $todo = [
        'Set the default value for accredited to the current year.'
    ];

    /**
     * @inheritDoc
     */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        Input::set('hidemainmenu', true);
        $new = empty($this->item->id);

        $title = $new ? "ORGANIZER_ADD_PROGRAM" : "ORGANIZER_EDIT_PROGRAM";
        $this->setTitle($title);

        $toolbar = Document::getToolbar();

        $saveGroup = $toolbar->dropdownButton('save-group');
        $saveBar   = $saveGroup->getChildToolbar();

        $saveBar->apply('Program.apply');
        $saveBar->save('Program.save');

        if (!$new) {
            $saveBar->save2copy('Program.save2copy');

            $url = "index.php?option=com_organizer&view=PoolSelection&tmpl=component&type=program&id={$this->item->id}";
            $toolbar->popupButton('pools', 'ORGANIZER_ADD_POOL')
                ->popupType('iframe')
                ->url($url)
                ->modalWidth('800px')
                ->modalHeight('500px')
                ->icon('fa fa-list');
        }

        $toolbar->cancel('Program.cancel');
    }
}
