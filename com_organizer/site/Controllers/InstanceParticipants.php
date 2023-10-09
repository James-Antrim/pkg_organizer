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

use Joomla\CMS\Factory;
use Joomla\CMS\Router\Route;
use Organizer\Controller;
use Organizer\Helpers;
use Organizer\Helpers\OrganizerHelper;
use Organizer\Models;

/**
 * Class provides methods for participant interation with instances.
 */
class InstanceParticipants extends Controller
{
    private const BLOCK = 2, SELECTED = 0, THIS = 1;

    protected $listView = 'instance_participants';

    protected $resource = 'instance_participant';

    /**
     * Class constructor
     *
     * @param array $config An optional associative [] of configuration settings.
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->registerTask('add', 'add');
    }

    /**
     * Triggers the model to add instances to the participant's personal schedule.
     * @return void
     */
    public function bookmark(int $method = self::SELECTED)
    {
        $model = new Models\InstanceParticipant();
        $model->bookmark($method);
        $referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));
    }

    /**
     * Triggers the model to add instances of a unique block (dow/times) and event to a participant's personal schedule.
     * @return void
     */
    public function bookmarkBlock()
    {
        $this->bookmark(self::BLOCK);
    }

    /**
     * Triggers the model to add the current instance to a participant's personal schedule.
     * @return void
     */
    public function bookmarkThis()
    {
        $this->bookmark(self::THIS);
    }

    /**
     * Triggers the model to deregister the participant from instances.
     * @return void
     */
    public function deregister(int $method = self::SELECTED)
    {
        $model = new Models\InstanceParticipant();
        $model->deregister($method);
        $referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));
    }

    /**
     * Triggers the model to deregister the participant from the current instance.
     * @return void
     */
    public function deregisterThis()
    {
        $this->deregister(self::THIS);
    }

    /**
     * Triggers the model to register the participant to instances.
     * @return void
     */
    public function register(int $method = self::SELECTED)
    {
        $model = new Models\InstanceParticipant();
        $model->register($method);
        $referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));
    }

    /**
     * Triggers the model to register the participant to the current instance.
     * @return void
     */
    public function registerThis()
    {
        $this->register(self::THIS);
    }

    /**
     * Triggers the model to remove instances from the participant's personal schedule.
     * @return void
     */
    public function removeBookmark(int $method = self::SELECTED)
    {
        $model = new Models\InstanceParticipant();
        $model->removeBookmark($method);
        $referrer = Helpers\Input::getInput()->server->getString('HTTP_REFERER');
        $this->setRedirect(Route::_($referrer, false));
    }

    /**
     * Triggers the model to remove instances of a unique block (dow/times) and event tuple from the participant's
     * personal schedule.
     * @return void
     */
    public function removeBookmarkBlock()
    {
        $this->removeBookmark(self::BLOCK);
    }

    /**
     * Triggers the model to remove the current instance from the participant's personal schedule.
     * @return void
     */
    public function removeBookmarkThis()
    {
        $this->removeBookmark(self::THIS);
    }

    /**
     * Save form data to the database.
     * @return void
     */
    public function save()
    {
        $model = new Models\InstanceParticipant();

        if ($model->save()) {
            OrganizerHelper::message('ORGANIZER_SAVE_SUCCESS', 'success');
            Factory::getSession()->set('organizer.participation.referrer', '');
            $referrer = Helpers\Input::getString('referrer');
            $this->setRedirect(Route::_($referrer, false));
        } else {
            OrganizerHelper::message('ORGANIZER_SAVE_FAIL', 'error');
        }
    }
}