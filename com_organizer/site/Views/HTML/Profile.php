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

/**
 * Class loads the profile form into display context.
 */
class Profile extends FormView
{
    /**
     * Adds a toolbar and title to the view.
     * @return void  adds toolbar items to the view
     */
    protected function addToolBar()
    {
        $this->setTitle('ORGANIZER_MY_PROFILE');

        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'save', Text::_('ORGANIZER_SAVE'), 'profile.save', false);
    }
}
