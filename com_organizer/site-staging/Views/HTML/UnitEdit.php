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
use THM\Organizer\Models\UnitEdit as Model;

/**
 * Class loads the grid form into display context.
 */
class UnitEdit extends EditView
{
    public string $orientation = 'vertical';

    /**
     * Method to generate buttons for user interaction
     * @return void
     */
    protected function addToolBar(): void
    {
        /** @var Model $model */
        $model       = $this->getModel();
        $this->model = $model;

        $title = $model->my ? 'ORGANIZER_MANAGE_MY_UNIT' : 'ORGANIZER_UNIT_EDIT';

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save', Text::_('ORGANIZER_SAVE_CLOSE'), "Units.save", false);
        $toolbar->appendButton('Standard', 'cancel', Text::_('ORGANIZER_CLOSE'), "Units.cancel", false);
    }
}
