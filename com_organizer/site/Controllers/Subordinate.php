<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2024 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Controllers;

use stdClass;

interface Subordinate
{
    /**
     * Processes a resource stub from a program manifest, creating resource  and curricula table entries as necessary.
     * Creates an entry if none exists and calls
     *
     * @param stdClass $XMLObject      a SimpleXML object containing rudimentary subject data
     * @param int      $organizationID the id of the organization to which this data belongs
     * @param int      $parentID       the id of the parent entry
     * @param int      $programCID     the id of the program entry
     *
     * @return bool  true on success, otherwise false
     */
    public function subordinate(stdClass $XMLObject, int $organizationID, int $parentID, int $programCID): bool;

}