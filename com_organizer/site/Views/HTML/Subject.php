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
 * Class loads persistent information about a subject into the display context.
 */
class Subject extends FormView
{
    protected array $todo = [
        'Remove fields and handling for deprecated competence values.',
        'Rename the curricula field programIDs'
    ];

    /**
     * @inheritDoc
     */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        $this->setTitle(empty($this->item->id) ? Text::_('ADD_SUBJECT') : Text::_('EDIT_SUBJECT'));
        $toolbar   = Toolbar::getInstance();
        $saveGroup = $toolbar->dropdownButton('save-group');
        $saveBar   = $saveGroup->getChildToolbar();
        $saveBar->apply('Subject.apply');
        $saveBar->apply('Subject.applyImport', Text::_('APPLY_AND_IMPORT'))->icon('fa fa-file-import');
        $saveBar->save('Subject.save');
        $saveBar->save('Subject.saveImport', Text::_('SAVE_AND_IMPORT'))->icon('fa fa-file-import');
        $toolbar->cancel("Subject.cancel");
    }
}
