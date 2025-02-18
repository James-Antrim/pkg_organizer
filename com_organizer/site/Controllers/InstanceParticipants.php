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
    use Participated;

    private const BLOCK = 2, SELECTED = 0, THIS = 1;

    protected string $context = 'instanceID';

    /**
     * Triggers the model to add instances to the participant's personal schedule.
     *
     * @param   int  $method
     *
     * @return void
     */
    public function bookmark(int $method = self::SELECTED): void
    {
        $model = new InstanceParticipant();
        $model->bookmark($method);
        $referrer = Input::getInput()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));
    }

    /**
     * Triggers the model to add instances of a unique block (dow/times) and event to a participant's personal schedule.
     * @return void
     */
    public function bookmarkBlock(): void
    {
        $this->bookmark(self::BLOCK);
    }

    /**
     * Triggers the model to add the current instance to a participant's personal schedule.
     * @return void
     */
    public function bookmarkThis(): void
    {
        $this->bookmark(self::THIS);
    }

    /**
     * Triggers the model to deregister the participant from instances.
     *
     * @param   int  $method
     *
     * @return void
     */
    public function deregister(int $method = self::SELECTED): void
    {
        $model = new InstanceParticipant();
        $model->deregister($method);
        $referrer = Input::getInput()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));
    }

    /**
     * Triggers the model to deregister the participant from the current instance.
     * @return void
     */
    public function deregisterThis(): void
    {
        $this->deregister(self::THIS);
    }

    /**
     * Triggers the model to register the participant to instances.
     *
     * @param   int  $method
     *
     * @return void
     */
    public function register(int $method = self::SELECTED): void
    {
        $model = new InstanceParticipant();
        $model->register($method);
        $referrer = Input::getInput()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));
    }

    /**
     * Triggers the model to register the participant to the current instance.
     * @return void
     */
    public function registerThis(): void
    {
        $this->register(self::THIS);
    }

    /**
     * Triggers the model to remove instances from the participant's personal schedule.
     *
     * @param   int  $method
     *
     * @return void
     */
    public function removeBookmark(int $method = self::SELECTED): void
    {
        $model = new InstanceParticipant();
        $model->removeBookmark($method);
        $referrer = Input::getInput()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));
    }

    /**
     * Triggers the model to remove instances of a unique block (dow/times) and event tuple from the participant's
     * personal schedule.
     * @return void
     */
    public function removeBookmarkBlock(): void
    {
        $this->removeBookmark(self::BLOCK);
    }

    /**
     * Triggers the model to remove the current instance from the participant's personal schedule.
     * @return void
     */
    public function removeBookmarkThis(): void
    {
        $this->removeBookmark(self::THIS);
    }

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
            $referrer = Input::getString('referrer');
            $this->setRedirect(Route::_($referrer, false));
        }
        else {
            Application::message('ORGANIZER_SAVE_FAIL', Application::ERROR);
        }
    }
}