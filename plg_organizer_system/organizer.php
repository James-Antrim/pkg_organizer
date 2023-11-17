<?php
/**
 * @package     Organizer
 * @extension   plg_organizer_system
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

require_once JPATH_ADMINISTRATOR . '/components/com_organizer/services/autoloader.php';

use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Joomla\CMS\Form\FormHelper;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters;
use THM\Organizer\Models\Participant;

defined('_JEXEC') or die;

/**
 * Organizer system plugin
 */
class PlgSystemOrganizer extends JPlugin
{
    private static bool $called = false;

    /**
     * Resolves the url query into an associative array.
     *
     * @return array
     */
    private function getAssocQuery(): array
    {
        $referrer = Adapters\Input::getInput()->server->get('HTTP_REFERER', '', 'raw');
        $query    = parse_url($referrer, PHP_URL_QUERY);
        parse_str($referrer, $query);

        return $query ?: [];
    }

    /**
     * Package the user's credentials.
     *
     * @return array
     */
    private function getCredentials(): array
    {
        $form = Adapters\Input::getFormItems();

        return ['username' => $form->get('username'), 'password' => $form->get('password1')];
    }

    /**
     * Loads the form path for organizer menu items.
     *
     * @param   Form   $form  The form to be altered.
     * @param   mixed  $data  The associated data for the form.
     *
     * @return  bool
     */
    public function onContentPrepareForm(Form $form, $data): bool
    {
        // Check we are manipulating a valid form.
        $name = $form->getName();

        // Menu item => Load form path?
        if ($name === 'com_menus.item') {
            // Invalid
            if (!is_object($data) or empty($data->request) or empty($data->request['option']) or empty($data->request['view'])) {
                return false;
            }

            if ($data->request['option'] !== 'com_organizer') {
                return true;
            }

            FormHelper::addFormPath(JPATH_ROOT . '/components/com_organizer/Layouts/HTML');
            $form->loadFile($data->request['view']);
        }
        elseif ($name === 'com_config.component') {
            $view      = Adapters\Input::getView();
            $component = Adapters\Input::getString('component');

            if ($view !== 'component' or $component !== 'com_organizer') {
                return true;
            }

            Form::addFieldPath(JPATH_SITE . '/components/com_organizer/Fields');
        }


        return true;
    }

    /**
     * Method simulating the effect of a chron job by performing tasks on superuser login.
     *
     * @return  bool  True on success.
     */
    public function onUserAfterLogin(): bool
    {
        $user = Factory::getUser();

        if ($user->authorise('core.admin')) {
            $model = new Participant();
            $model->truncateParticipation();
        }

        return true;
    }

    /**
     * Ensures the users are logged in and redirected appropriately after registering.
     *
     * @return void
     */
    public function onUserAfterSave()
    {
        // Not a save from a registration or the function has already been called.
        if (!$task = Adapters\Input::getTask() or $task !== 'register' or self::$called) {
            return;
        }

        $query  = $this->getAssocQuery();
        $return = array_key_exists('return', $query) ? base64_decode($query['return']) : '';

        $app = Adapters\Application::getApplication();

        if ($app->login($this->getCredentials()) and $return) {
            $app->redirect($return);
        }
    }

    /**
     * Ensures that users with existing credentials use those during the account creation process.
     *
     * @param   array  $existing  the exising user entry
     * @param   bool   $newFlag   a redundant flag
     * @param   array  $user      the data entered by the user in the form
     *
     * @return bool
     * @noinspection PhpUnusedParameterInspection
     */
    public function onUserBeforeSave(array $existing, bool $newFlag, array $user): bool
    {
        if (!$task = Adapters\Input::getTask() or $task !== 'register' or self::$called) {
            return true;
        }

        if (!$filter = ComponentHelper::getParams('com_organizer')->get('emailFilter')) {
            return true;
        }

        if (strpos($user['email'], $filter) === false) {
            return true;
        }

        self::$called = true;

        $query  = $this->getAssocQuery();
        $return = array_key_exists('return', $query) ? base64_decode($query['return']) : Uri::base();

        // An attempt was made to register using official credentials.
        $app = Adapters\Application::getApplication();

        if ($app->login($this->getCredentials())) {
            $message = sprintf(Adapters\Text::_('ORGANIZER_REGISTER_INTERNAL_SUCCESS'), $filter);
            Adapters\Application::message($message, 'success');
            $app->redirect($return);

            return true;
        }

        // Clear the standard error messages from the login routine.
        $app->getMessageQueue(true);
        $message = sprintf(Adapters\Text::_('ORGANIZER_REGISTER_INTERNAL_FAIL'), $filter);
        Adapters\Application::message($message, 'warning');
        $app->redirect($return);

        return false;
    }
}
