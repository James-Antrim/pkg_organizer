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
        if (empty($this->item->id)) {
            $applyImport = Text::_('ORGANIZER_CREATE_IMPORT');
            $saveImport  = Text::_('ORGANIZER_CREATE_IMPORT_CLOSE');
            $title       = Text::_('ORGANIZER_ADD_SUBJECT');

        }
        else {
            $applyImport = Text::_('ORGANIZER_APPLY_AND_UPDATE');
            $saveImport  = Text::_('ORGANIZER_SAVE_UPDATE');
            $title       = Text::_('ORGANIZER_EDIT_SUBJECT');
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save-new', 'button-text', 'subjects.apply', false);
        $toolbar->appendButton('Standard', 'file-add', $applyImport, 'subjects.applyImport', false);
        $toolbar->appendButton('Standard', 'publish', 'button-text', 'subjects.save', false);
        $toolbar->appendButton('Standard', 'file-check', $saveImport, 'subjects.saveImport', false);
        $toolbar->appendButton('Standard', 'cancel', 'button-text', 'subjects.cancel', false);
    }
}
