<?php

namespace THM\Organizer\Controllers;

use Joomla\CMS\Application\CMSApplication;
use Joomla\CMS\MVC\Factory\MVCFactoryInterface;
use Joomla\Input\Input as JInput;
use THM\Organizer\Adapters\{Application, Input};

/**
 * Extends FormController to allow for redirection to multiple referencing list views on process completion.
 */
abstract class ListsReferred extends FormController
{
    /** @inheritDoc */
    public function __construct(
        $config = [],
        MVCFactoryInterface $factory = null,
        ?CMSApplication $app = null,
        ?JInput $input = null
    )
    {
        $this->setReferrer();
        parent::__construct($config, $factory, $app, $input);
    }

    /** @inheritDoc */
    public function cancel(): void
    {
        $this->setRedirect("$this->baseURL&view=" . $this->unsetReferrer());
    }

    /** @inheritDoc */
    public function save(): void
    {
        $this->process();
        $this->setRedirect("$this->baseURL&view=" . $this->unsetReferrer());
    }

    /** @inheritDoc */
    public function save2copy(): void
    {
        // Force new attribute creation
        Input::set('id', 0);
        $this->process();
        $this->setRedirect("$this->baseURL&view=" . $this->unsetReferrer());
    }

    /**
     * Sets a referrer session variable for forms called by multiple list views.
     *
     * @return void
     */
    private function setReferrer(): void
    {
        $class   = strtolower(Application::getClass($this));
        $session = Application::getSession();
        if (!$session->get("organizer.$class.referrer")) {
            $query = explode('?', Input::getReferrer())[1];
            parse_str($query, $pairs);
            $referrer = $pairs['view'] ?? $this->list;
            $session->set("organizer.$class.referrer", $referrer);
        }
    }

    /**
     * Unsets the session referrer on process completion, returníng the value for redirection.
     *
     * @return string
     */
    private function unsetReferrer(): string
    {
        $class   = strtolower(Application::getClass($this));
        $key     = "organizer.$class.referrer";
        $session = Application::getSession();

        $referrer = $session->get($key, $this->list);
        $session->set($key, null);

        return $referrer;
    }

}