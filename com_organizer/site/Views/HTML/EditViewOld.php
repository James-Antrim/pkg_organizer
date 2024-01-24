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
use THM\Organizer\Adapters\{Input, Toolbar};
use THM\Organizer\Helpers;
use THM\Organizer\Models\EditModelOld;

/**
 * Class loads the resource form into display context. Specific resource determined by extending class.
 */
abstract class EditViewOld extends OldFormView
{
    public $item = null;

    /**
     * @var EditModelOld
     */
    protected BaseDatabaseModel $model;

    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $resource   = Helpers\OrganizerHelper::classEncode($this->getName());
        $constant   = strtoupper($resource);
        $controller = Helpers\OrganizerHelper::getPlural($resource);

        if ($this->item->id) {
            $title = "ORGANIZER_{$constant}_EDIT";
        }
        else {
            $title = "ORGANIZER_{$constant}_NEW";
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save', 'sevetextfrombutton', "$controller.save", false);
        $toolbar->appendButton('Standard', 'cancel', 'canceltextfrombutton', "$controller.cancel", false);
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
