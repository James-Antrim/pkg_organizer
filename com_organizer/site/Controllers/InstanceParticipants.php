<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

use Joomla\CMS\Router\Route;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Models\InstanceParticipant;

/**
 * Class provides methods for participant interaction with instances.
 */
class InstanceParticipants extends Controller
{
    use Booked;
    use Participated;

    protected string $context = 'instanceID';

    /**
     * Save form data to the database.
     * @return void
     */
    public function save(): void
    {
        $model = new InstanceParticipant();

        if ($model->save()) {
            Application::message('ORGANIZER_SAVE_SUCCESS');
            Application::session()->set('organizer.participation.referrer', '');
            $referrer = Input::string('referrer');
            $this->setRedirect(Route::_($referrer, false));
        }
        else {
            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);
        }
    }
}