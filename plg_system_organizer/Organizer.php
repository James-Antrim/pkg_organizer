<?php
/**
 * @package     Organizer
 * @extension   plg_organizer_system
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Plugin\System;

use Joomla\CMS\{Application\CMSApplication, Component\ComponentHelper, Form\Form, Plugin\CMSPlugin, Uri\Uri};
use Joomla\CMS\Event\User\BeforeSaveEvent;
use Joomla\Event\{Event, SubscriberInterface};
use THM\Organizer\Adapters\{Application, Input, Text};

/**
 * Organizer system plugin
 */
final class Organizer extends CMSPlugin implements SubscriberInterface
{
    // todo remove the language toggle from dynamic content

    private static bool $called = false;

    /** @inheritDoc */
    public static function getSubscribedEvents(): array
    {
        return [
            'onContentPrepareForm' => 'loadPaths',
            'onUserAfterSave'      => 'registerRedirect',
            'onUserBeforeSave'     => 'enforceCredentials',
        ];
    }

    /**
     * Package the user's credentials.
     *
     * @return array
     */
    private function credentials(): array
    {
        $form = Input::post();

        return ['username' => $form['username'], 'password' => $form['password1']];
    }

    /**
     * Ensures that users with existing credentials use those during the account creation process.
     *
     * @param BeforeSaveEvent $event
     * @return bool
     */
    public function enforceCredentials(BeforeSaveEvent $event): bool
    {
        // Irrelevant or already called
        if (!$task = Input::task() or $task !== 'register' or self::$called) {
            return true;
        }

        $user = $event->getUser();

        /**
         * No filter configured or the configured filter does not apply => everything else is irrelevant.
         * Component helper is used because outside the component context component parameters are not loaded automatically.
         */
        if (!$filter = ComponentHelper::getParams('com_organizer')->get('emailFilter') or !str_contains($user['email'], $filter)) {
            return true;
        }

        // Prevent infinite loops by noting that the function has already been called
        self::$called = true;

        $query  = $this->parseQuery();
        $return = array_key_exists('return', $query) ? base64_decode($query['return']) : Uri::base();

        // An attempt was made to register using official credentials.
        /** @var CMSApplication $app */
        $app = Application::instance();

        if ($app->login($this->credentials())) {
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

    /**
     * Loads the form and field paths for organizer menu items.
     *
     * @param Event $event
     * @return  void
     */
    public function loadPaths(Event $event): void
    {
        /**
         * @var   Form  $form The form to be altered.
         * @var   mixed $data The associated data for the form.
         */
        [$form, $data] = array_values($event->getArguments());

        if ($form->getName() === 'com_menus.item') {
            // Invalid
            if (!is_object($data) or empty($data->request) or empty($data->request['option']) or empty($data->request['view'])) {
                return;
            }

            if ($data->request['option'] === 'com_organizer') {
                Form::addFieldPath(JPATH_ROOT . '/components/com_organizer/Fields');
                Form::addFormPath(JPATH_ROOT . '/components/com_organizer/Forms/MenuItems');
                $form->loadFile($data->request['view']);
            }
        }
    }

    /**
     * Resolves the url query into an associative array.
     *
     * @return array
     */
    private function parseQuery(): array
    {
        $referrer = Input::instance()->server->get('HTTP_REFERER', '', 'raw');
        $query    = parse_url($referrer, PHP_URL_QUERY);
        parse_str($referrer, $query);

        return $query ?: [];
    }

    /**
     * Ensures the users are logged in and redirected appropriately after registering.
     *
     * @return void
     */
    public function registerRedirect(): void
    {
        // Not a save from a registration or the function has already been called.
        if (!$task = Input::task() or $task !== 'register' or self::$called) {
            return;
        }

        $query  = $this->parseQuery();
        $return = array_key_exists('return', $query) ? base64_decode($query['return']) : '';

        /** @var CMSApplication $app */
        $app = Application::instance();

        if ($app->login($this->credentials()) and $return) {
            $app->redirect($return);
        }
    }
}
