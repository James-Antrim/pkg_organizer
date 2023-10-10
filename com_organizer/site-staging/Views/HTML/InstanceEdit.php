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

/**
 * Class loads the instance form into display context.
 */
class InstanceEdit extends EditView
{
    protected $layout = 'instance-wrapper';

    /**
     * Method to generate buttons for user interaction
     * @return void
     */
    protected function addToolBar()
    {
        //$appointment = Input::getCMD('layout') === 'appointment';

        if ($this->item->id) {
            $cancel = 'ORGANIZER_CLOSE';
            $save   = 'ORGANIZER_SAVE';
            $title  = 'ORGANIZER_INSTANCE_EDIT';
        } else {
            $cancel = 'ORGANIZER_CANCEL';
            $save   = 'ORGANIZER_CREATE';
            $title  = 'ORGANIZER_INSTANCE_NEW';
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save', Text::_($save), 'instances.save', false);

        $layout  = Input::getCMD('layout');
        $layouts = ['appointment', 'simple'];
        $layout  = in_array($layout, $layouts) ? $layout : 'appointment';

        // One less button for the people Ralph wants to present as mentally impaired.
        if ($layout !== 'appointment') {
            $toolbar->appendButton('Standard', 'reset', Text::_('ORGANIZER_RESET'), 'instances.reset', false);
        }

        $toolbar->appendButton('Standard', 'cancel', Text::_($cancel), 'instances.cancel', false);
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument()
    {
        parent::modifyDocument();

        Helpers\HTML::_('formbehavior.chosen', 'select');
    }
}