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

use THM\Organizer\Adapters\{Application, Text, Toolbar, User};

/** @inheritDoc */
class Profile extends FormView
{
    use Abstracted;

    /** @inheritDoc */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        $this->title('MY_PROFILE');
        $toolbar = Toolbar::instance();
        $toolbar->save('profile.save', Text::_('APPLY'));
    }

    /** @inheritDoc */
    protected function authorize(): void
    {
        if (!User::id()) {
            Application::error(401);
        }
    }
}
