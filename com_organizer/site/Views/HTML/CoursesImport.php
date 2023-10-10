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
 * Class provides an interface for uploading a file containing room data.
 */
class CoursesImport extends EditView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar()
    {
        $this->setTitle('ORGANIZER_COURSES_IMPORT');
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton(
            'Standard',
            'upload',
            Text::_('ORGANIZER_UPLOAD'),
            'courses.import',
            false
        );
        $toolbar->appendButton(
            'Standard',
            'cancel',
            Text::_('ORGANIZER_CANCEL'),
            'courses.cancel',
            false
        );
    }
}
