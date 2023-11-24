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
class ScheduleEdit extends EditViewOld
{
    /**
     * @inheritDoc
     */
    protected function addToolBar()
    {
        $this->setTitle('ORGANIZER_SCHEDULE_UPLOAD');
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton(
            'Standard',
            'upload',
            Text::_('ORGANIZER_UPLOAD'),
            'schedules.upload',
            false
        );
        $toolbar->appendButton(
            'Standard',
            'cancel',
            Text::_('ORGANIZER_CANCEL'),
            'schedules.cancel',
            false
        );
    }
}
