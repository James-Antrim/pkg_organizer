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

/**
 * @inheritDoc
 */
class Holiday extends FormView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(array $buttons = []): void
    {
        $buttons = empty($this->item->id) ? [] : ['save', 'save2copy'];
        parent::addToolbar($buttons);
    }
}