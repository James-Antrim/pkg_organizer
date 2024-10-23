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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use stdClass;
use THM\Organizer\Adapters\{Application, Input, Toolbar};
use THM\Organizer\Models\EditModelOld;

/**
 * Class loads the resource form into display context. Specific resource determined by extending class.
 */
abstract class EditViewOld extends OldFormView
{
    public stdClass|null $item = null;

    /**
     * @var EditModelOld
     */
    protected BaseDatabaseModel $model;

    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $resource   = Application::ucClass($this->getName());
        $constant   = strtoupper($resource);
        $controller = $this->getName();

        if ($this->item->id) {
            $title = "ORGANIZER_{$constant}_EDIT";
        }
        else {
            $title = "ORGANIZER_{$constant}_NEW";
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save', 'SAVE_BUTTON_CONSTANT', "$controller.save", false);
        $toolbar->appendButton('Standard', 'cancel', 'CANCEL_BUTTON_CONSTANT', "$controller.cancel", false);
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        /** @var EditModelOld $model */
        $model      = $this->getModel();
        $this->item = $model->getItem(Input::getSelectedID());
        parent::display($tpl);
    }
}
