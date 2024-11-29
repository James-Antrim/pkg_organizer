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
use THM\Organizer\Models\UnitEdit as Model;

/** @inheritDoc */
class UnitEdit extends FormView
{
    public string $orientation = 'vertical';

    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        /** @var Model $model */
        $model       = $this->getModel();
        $this->model = $model;

        $title = $model->my ? 'ORGANIZER_MANAGE_MY_UNIT' : 'ORGANIZER_UNIT_EDIT';

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save', 'saveclosetextfrombutton', "Units.save", false);
        $toolbar->appendButton('Standard', 'cancel', 'canceltextfrombutton', "Units.cancel", false);
    }
}
