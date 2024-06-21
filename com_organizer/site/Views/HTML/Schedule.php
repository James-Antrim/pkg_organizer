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
 * Class loads the schedule upload form into display context.
 */
class Schedule extends FormView
{
    /** @inheritDoc */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        $this->setTitle('ORGANIZER_ADD_SCHEDULE');
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('schedule.upload', Text::_('UPLOAD'));
        $toolbar->cancel('schedule.cancel');
    }
}
