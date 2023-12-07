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

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\Component\ComponentHelper;
use Joomla\CMS\Form\{Form, FormHelper};
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Application, Input, Text};
use THM\Organizer\Controllers\InstanceParticipants as Controller;
use THM\Organizer\Helpers\Users;

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
        $referrer = Input::getInput()->server->get('HTTP_REFERER', '', 'raw');
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
        $form = Input::getFormItems();

        return ['username' => $form['username'], 'password' => $form['password1']];
    }

    /**
     * Loads the form path for organizer menu items.
     *
     * @param   Form   $form  The form to be altered.
     * @param   mixed  $data  The associated data for the form.
     *
     * @return  bool
     */
    public function onContentPrepareForm(Form $form, mixed $data): bool
    {
        switch ($form->getName()) {
            // Menu item => Load form path
            case 'com_menus.item' :

                // Invalid
                if (!is_object($data) or empty($data->request) or empty($data->request['option']) or empty($data->request['view'])) {
                    return false;
                }

                if ($data->request['option'] === 'com_organizer') {
                    FormHelper::addFormPath(JPATH_ROOT . '/components/com_organizer/Forms/MenuItems');
                    $form->loadFile($data->request['view']);
                }

                break;
            // Configuration => Load field path
            case 'com_config.component':

                if (Input::getView() === 'component' and Input::getString('component') === 'com_organizer') {
                    Form::addFieldPath(JPATH_SITE . '/components/com_organizer/Fields');
                }

                break;
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
        $user = Users::getUser();

        if ($user->authorise('core.admin')) {
            Controller::truncate();
        }

        return true;
    }

    /**
     * Ensures the users are logged in and redirected appropriately after registering.
     *
     * @return void
     */
    public function onUserAfterSave(): void
    {
        // Not a save from a registration or the function has already been called.
        if (!$task = Input::getTask() or $task !== 'register' or self::$called) {
            return;
        }

        $query  = $this->getAssocQuery();
        $return = array_key_exists('return', $query) ? base64_decode($query['return']) : '';

        /** @var CMSApplication $app */
        $app = Application::getApplication();

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
        // Irrelevant or already called
        if (!$task = Input::getTask() or $task !== 'register' or self::$called) {
            return true;
        }

        /**
         * No filter configured or the configured filter does not apply => everything else is irrelevant.
         * Component helper is used because outide the component context component parameters are not loaded automatically.
         */
        if (!$filter = ComponentHelper::getParams('com_organizer')->get('emailFilter')
            or !str_contains($user['email'], $filter)) {
            return true;
        }

        // Prevent infinite loops by noting that the function has already been called
        self::$called = true;

        $query  = $this->getAssocQuery();
        $return = array_key_exists('return', $query) ? base64_decode($query['return']) : Uri::base();

        // An attempt was made to register using official credentials.
        /** @var CMSApplication $app */
        $app = Application::getApplication();

        if ($app->login($this->getCredentials())) {
            Application::message(sprintf(Text::_('REGISTER_INTERNAL_SUCCESS'), $filter), 'success');
            $app->redirect($return);

            return true;
        }

        // Clear the standard error messages from the login routine.
        $app->getMessageQueue(true);

        Application::message(sprintf(Text::_('REGISTER_INTERNAL_FAIL'), $filter), 'warning');
        $app->redirect($return);

        return false;
    }
}
