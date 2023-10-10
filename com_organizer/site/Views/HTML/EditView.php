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

use THM\Organizer\Adapters\{Input, Text, Toolbar};
use THM\Organizer\Helpers;
use THM\Organizer\Models\EditModel;

/**
 * Class loads the resource form into display context. Specific resource determined by extending class.
 */
abstract class EditView extends FormView
{
    public $item = null;

    /**
     * @var EditModel
     */
    protected $model;

    /**
     * Method to generate buttons for user interaction
     * @return void
     */
    protected function addToolBar()
    {
        $resource   = Helpers\OrganizerHelper::classEncode($this->getName());
        $constant   = strtoupper($resource);
        $controller = Helpers\OrganizerHelper::getPlural($resource);

        if ($this->item->id) {
            $cancel = 'ORGANIZER_CLOSE';
            $save   = 'ORGANIZER_SAVE_CLOSE';
            $title  = "ORGANIZER_{$constant}_EDIT";
        } else {
            $cancel = 'ORGANIZER_CANCEL';
            $save   = 'ORGANIZER_CREATE_CLOSE';
            $title  = "ORGANIZER_{$constant}_NEW";
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save', Text::_($save), "$controller.save", false);
        $toolbar->appendButton('Standard', 'cancel', Text::_($cancel), "$controller.cancel", false);
    }

    /**
     * Method to get display
     *
     * @param Object $tpl template  (default: null)
     *
     * @return void
     */
    public function display($tpl = null)
    {
        $this->item = $this->getModel()->getItem(Input::getSelectedID());
        parent::display($tpl);
    }
}
