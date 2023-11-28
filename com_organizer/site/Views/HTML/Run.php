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

/**
 * Class loads the run form into display context.
 */
class Run extends FormView
{
    /**
     * Method to generate buttons for user interaction
     *
     * @param   array  $buttons  *
     *
     * @return void
     */
    protected function addToolBar(array $buttons = []): void
    {
        parent::addToolbar(['apply', 'save', 'save2copy']);
    }
}
