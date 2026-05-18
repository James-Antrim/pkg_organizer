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

/** @inheritDoc */
class Program extends FormView
{
    /** @inheritDoc */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        $applyImport = ['apply-import', 'APPLY_AND_IMPORT', 'applyImport', 'fa fa-file-download'];
        $saveImport  = ['save-import', 'SAVE_AND_IMPORT', 'saveImport', 'fa fa-file-download'];
        parent::addToolbar(['apply', 'save', 'save2copy', $applyImport, $saveImport]);
    }
}
