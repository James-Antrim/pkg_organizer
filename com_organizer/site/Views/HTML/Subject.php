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
class Subject extends EditViewOld
{
    use Subordinate;

    protected string $layout = 'tabs';

    protected array $todo = [
        'Remove fields and handling for deprecated competence values.',
        'Rename the curricula field programIDs'
    ];

    /**
     * Method to generate buttons for user interaction
     * @return void
     */
    protected function addToolBar(): void
    {
        if (empty($this->item->id)) {
            $apply       = Text::_('ORGANIZER_CREATE');
            $applyImport = Text::_('ORGANIZER_CREATE_IMPORT');
            $cancel      = Text::_('ORGANIZER_CLOSE');
            $save        = Text::_('ORGANIZER_CREATE_CLOSE');
            $saveImport  = Text::_('ORGANIZER_CREATE_IMPORT_CLOSE');
            $title       = Text::_('ORGANIZER_SUBJECT_NEW');

        }
        else {
            $apply       = Text::_('ORGANIZER_APPLY');
            $applyImport = Text::_('ORGANIZER_APPLY_UPDATE');
            $cancel      = Text::_('ORGANIZER_CANCEL');
            $save        = Text::_('ORGANIZER_SAVE');
            $saveImport  = Text::_('ORGANIZER_SAVE_UPDATE');
            $title       = Text::_('ORGANIZER_SUBJECT_EDIT');
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save-new', $apply, 'subjects.apply', false);
        $toolbar->appendButton('Standard', 'file-add', $applyImport, 'subjects.applyImport', false);
        $toolbar->appendButton('Standard', 'publish', $save, 'subjects.save', false);
        $toolbar->appendButton('Standard', 'file-check', $saveImport, 'subjects.saveImport', false);
        $toolbar->appendButton('Standard', 'cancel', $cancel, 'subjects.cancel', false);
    }
}
