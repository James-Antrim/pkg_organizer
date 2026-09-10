<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Controllers;

/**
 * Ensures implementation standardization across booking views.
 */
interface Books
{
    /**
     * Adds an instance to the participant's personal schedule.
     * @return void
     */
    public function bookmarkBlock(): void;

    /**
     * Adds the selected instances to the participant's personal schedule.
     * @return void
     */
    public function bookmarkSelected(): void;

    /**
     * Adds the current instance to the participant's personal schedule.
     * @return void
     */
    public function bookmarkThis(): void;

    /**
     * Removes the participant's registration for the selected instances.
     * @return void
     */
    public function deregisterSelected(): void;

    /**
     * Removes the participant's registration for the current instance.
     * @return void
     */
    public function deregisterThis(): void;

    /**
     * Registers the participant for the selected instances.
     * @return void
     */
    public function registerSelected(): void;

    /**
     * Registers the participant for the current instance.
     * @return void
     */
    public function registerThis(): void;

    /**
     * Removes an instance from the participant's personal schedule.
     * personal schedule.
     * @return void
     */
    public function removeBookmarkBlock(): void;

    /**
     * Removes the selected instances from the participant's personal schedule.
     * @return void
     */
    public function removeBookmarkSelected(): void;

    /**
     * Removes the current instance from the participant's personal schedule.
     * @return void
     */
    public function removeBookmarkThis(): void;
}