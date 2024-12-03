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
class Instance extends FormView
{
    protected string $layout = 'instance-wrapper';

    /** @inheritDoc
     * @param   array   $buttons
     * @param   string  $constant
     */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        //$appointment = Input::getCMD('layout') === 'appointment';

        if ($this->item->id) {
            $title = 'ORGANIZER_INSTANCE_EDIT';
        }
        else {
            $title = 'ORGANIZER_INSTANCE_NEW';
        }

        $this->title($title);
        $toolbar = Toolbar::getInstance();
        $toolbar->save('instance.save');

        $layout  = Input::getCMD('layout');
        $layouts = ['appointment', 'simple'];
        $layout  = in_array($layout, $layouts) ? $layout : 'appointment';

        // One less button for the people Ralph wants to present as mentally impaired.
        if ($layout !== 'appointment') {
            $toolbar->standardButton('reset', Text::_('RESET'), 'instance.reset')->icon('fa fa-undo');
        }

        $toolbar->cancel('instance.cancel');
    }

    /** @inheritDoc */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        HTML::_('formbehavior.chosen', 'select');
    }
}