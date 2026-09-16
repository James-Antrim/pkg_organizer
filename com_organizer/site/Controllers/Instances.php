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

use Exception;
use THM\Organizer\Adapters\{Application, Input};

/** @inheritDoc */
class Instances extends ListsReferred implements Books
{
    use Booked;

    /** @inheritDoc */
    public function bookmarkBlock(): void
    {
        $this->bookmark(self::BLOCK);
    }

    /** @inheritDoc */
    public function bookmarkSelected(): void
    {
        $this->bookmark(self::SELECTED);
    }

    /** @inheritDoc */
    public function bookmarkThis(): void
    {
        $this->bookmark(self::THIS);
    }

    /** @inheritDoc */
    public function deregisterSelected(): void
    {
        $this->deregister(self::SELECTED);
    }

    /** @inheritDoc */
    public function deregisterThis(): void
    {
        $this->deregister(self::THIS);
    }

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function gridA3(): void
    {
        Input::format('pdf');
        Input::set('layout', 'GridA3');
        parent::display();
    }

    /**
     * Prints badges for the selected participants.
     * @return void
     * @throws Exception
     */
    public function gridA4(): void
    {
        Input::format('pdf');
        Input::set('layout', 'GridA4');
        parent::display();
    }

    /** @inheritDoc */
    public function registerSelected(): void
    {
        $this->register(self::SELECTED);
    }

    /** @inheritDoc */
    public function registerThis(): void
    {
        $this->register(self::THIS);
    }

    /** @inheritDoc */
    public function removeBookmarkBlock(): void
    {
        $this->register(self::BLOCK);
    }

    /** @inheritDoc */
    public function removeBookmarkSelected(): void
    {
        $this->removeBookmark(self::SELECTED);
    }

    /** @inheritDoc */
    public function removeBookmarkThis(): void
    {
        $this->removeBookmark(self::THIS);
    }


    /**
     * Removed all properties stored in the session
     * @return void
     */
    public function reset(): void
    {
        $session  = Application::session();
        $instance = $session->get('organizer.instance', []);

        if (!empty($instance['referrer'])) {
            $instance = ['referrer' => $instance['referrer']];
        }

        $session->set('organizer.instance', $instance);

        parent::cancel();
    }

    /**
     * Creates a xls file based on form data.
     * @return void
     * @throws Exception
     */
    public function xls(): void
    {
        // prevents parameter name from biting here
        Input::format('xls');
        Input::set('layout', 'Instances');
        $this->display();
    }
}
