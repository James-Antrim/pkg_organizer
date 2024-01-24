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


use SimpleXMLElement;

interface Stubby
{
    /**
     * Processes a resource stub from a program manifest, creating resource  and curricula table entries as necessary.
     * Creates an entry if none exists and calls
     *
     * @param   SimpleXMLElement  $XMLObject       a SimpleXML object containing rudimentary subject data
     * @param   int               $organizationID  the id of the organization to which this data belongs
     * @param   int               $parentID        the id of the parent entry
     *
     * @return bool  true on success, otherwise false
     */
    public function processStub(SimpleXMLElement $XMLObject, int $organizationID, int $parentID): bool;

}