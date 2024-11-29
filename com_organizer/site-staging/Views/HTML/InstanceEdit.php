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

use THM\Organizer\Adapters\{HTML, Input, Text, Toolbar};

/** @inheritDoc */
class InstanceEdit extends FormView
{
    protected string $layout = 'instance-wrapper';

    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        //$appointment = Input::getCMD('layout') === 'appointment';

        if ($this->item->id) {
            $title = 'ORGANIZER_INSTANCE_EDIT';
        }
        else {
            $title = 'ORGANIZER_INSTANCE_NEW';
        }

        $this->setTitle($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save', 'button-text', 'instances.save', false);

        $layout  = Input::getCMD('layout');
        $layouts = ['appointment', 'simple'];
        $layout  = in_array($layout, $layouts) ? $layout : 'appointment';

        // One less button for the people Ralph wants to present as mentally impaired.
        if ($layout !== 'appointment') {
            $toolbar->appendButton('Standard', 'reset', Text::_('ORGANIZER_RESET'), 'instances.reset', false);
        }

        $toolbar->appendButton('Standard', 'cancel', 'button-text', 'instances.cancel', false);
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        HTML::_('formbehavior.chosen', 'select');
    }
}