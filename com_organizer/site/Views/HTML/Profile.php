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

/**
 * Class loads the profile form into display context.
 */
class Profile extends OldFormView
{
    /** @inheritDoc */
    protected function addToolBar(): void
    {
        $this->title('MY_PROFILE');

        $toolbar = Toolbar::getInstance();
        $toolbar->save('profile.save');
    }
}
