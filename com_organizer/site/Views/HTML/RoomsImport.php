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

use THM\Organizer\Adapters\Toolbar;
use THM\Organizer\Helpers\Languages;

/**
 * Class provides an interface for uploading a file containing room data.
 */
class RoomsImport extends EditView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar()
    {
        $this->setTitle('ORGANIZER_ROOMS_IMPORT');
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton(
            'Standard',
            'upload',
            Languages::_('ORGANIZER_UPLOAD'),
            'rooms.import',
            false
        );
        $toolbar->appendButton(
            'Standard',
            'cancel',
            Languages::_('ORGANIZER_CANCEL'),
            'rooms.cancel',
            false
        );
    }
}
