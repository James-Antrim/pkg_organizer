<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Organizer\Adapters\Toolbar;
use Organizer\Helpers;

/**
 * Class loads the instance form into display context.
 */
class InstanceEdit extends EditView
{
    protected $_layout = 'instance-wrapper';

    /**
     * Method to generate buttons for user interaction
     *
     * @return void
     */
    protected function addToolBar()
    {
        $appointment = Helpers\Input::getCMD('layout') === 'appointment';

        if ($this->item->id) {
            $cancel = 'ORGANIZER_CLOSE';
            $save   = 'ORGANIZER_SAVE';
            $title  = 'ORGANIZER_INSTANCE_EDIT';
        } else {
            $cancel = 'ORGANIZER_CANCEL';
            $save   = 'ORGANIZER_CREATE';
            $title  = 'ORGANIZER_INSTANCE_NEW';
        }

        Helpers\HTML::setTitle(Helpers\Languages::_($title), 'contract-2');
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save', Helpers\Languages::_($save), 'instances.save', false);

        $layout  = Helpers\Input::getCMD('layout');
        $layouts = ['appointment', 'simple'];
        $layout  = in_array($layout, $layouts) ? $layout : 'appointment';

        // One less button for the people Ralph wants to present as mentally impaired.
        if ($layout !== 'appointment') {
            $toolbar->appendButton('Standard', 'reset', Helpers\Languages::_('ORGANIZER_RESET'), 'instances.reset', false);
        }

        $toolbar->appendButton('Standard', 'cancel', Helpers\Languages::_($cancel), 'instances.cancel', false);
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