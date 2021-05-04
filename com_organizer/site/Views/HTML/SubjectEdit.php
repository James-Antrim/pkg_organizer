<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Organizer\Adapters\Toolbar;
use Organizer\Helpers;

/**
 * Class loads persistent information about a subject into the display context.
 */
class SubjectEdit extends EditView
{
    use Subordinate;

    protected $_layout = 'tabs';

    /**
     * Method to generate buttons for user interaction
     *
     * @return void
     */
    protected function addToolBar()
    {
        if ($new = empty($this->item->id)) {
            $apply       = Helpers\Languages::_('ORGANIZER_CREATE');
            $applyImport = Helpers\Languages::_('ORGANIZER_CREATE_IMPORT');
            $cancel      = Helpers\Languages::_('ORGANIZER_CLOSE');
            $save        = Helpers\Languages::_('ORGANIZER_CREATE_CLOSE');
            $saveImport  = Helpers\Languages::_('ORGANIZER_CREATE_IMPORT_CLOSE');
            $title       = Helpers\Languages::_('ORGANIZER_SUBJECT_NEW');

        } else {
            $apply       = Helpers\Languages::_('ORGANIZER_APPLY');
            $applyImport = Helpers\Languages::_('ORGANIZER_APPLY_UPDATE');
            $cancel      = Helpers\Languages::_('ORGANIZER_CANCEL');
            $save        = Helpers\Languages::_('ORGANIZER_SAVE');
            $saveImport  = Helpers\Languages::_('ORGANIZER_SAVE_UPDATE');
            $title       = Helpers\Languages::_('ORGANIZER_SUBJECT_EDIT');
        }

        Helpers\HTML::setTitle(Helpers\Languages::_($title), 'book');
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save-new', $apply, 'subjects.apply', false);
        $toolbar->appendButton('Standard', 'file-add', $applyImport, 'subjects.applyImport', false);
        $toolbar->appendButton('Standard', 'publish', $save, 'subjects.save', false);
        $toolbar->appendButton('Standard', 'file-check', $saveImport, 'subjects.saveImport', false);
        $toolbar->appendButton('Standard', 'cancel', $cancel, 'subjects.cancel', false);
    }
}
