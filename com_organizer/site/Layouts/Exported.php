<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace Organizer\Layouts;


trait Exported
{
    /**
     * Whether to display the group codes globally.
     * @var bool
     */
    public bool $showGroupCodes;

    /**
     * Whether to display the instance method.
     * @var bool
     */
    public bool $showMethods;

    /**
     * Whether to display the organization responsible for instance planning.
     * @var bool
     */
    public bool $showOrganizations;

    /**
     * Whether to display the names of persons.
     * @var bool
     */
    public bool $showPersons;

    /**
     * Whether to display the names of persons.
     * @var bool
     */
    public bool $showRooms;

    /**
     * Sets flags for global suppression of specific resource displays.
     *
     * @param array $conditions
     *
     * @return void
     */
    public function setFlags(array $conditions)
    {
        $organizations = (!empty($conditions['organizationIDs']) and count($conditions['organizationIDs']) > 1);
        $standard      = (empty($conditions['instances']) or $conditions['instances'] === 'organization');

        // If there is no category context the group names may overlap.
        $this->showGroupCodes    = empty($conditions['categoryIDs']);
        $this->showMethods       = (empty($conditions['methodIDs']) or count($conditions['methodIDs']) > 1);
        $this->showOrganizations = (($standard and $organizations) or !$standard);
        $this->showPersons       = (empty($conditions['personIDs']) or count($conditions['personIDs']) > 1);
        $this->showRooms         = empty($conditions['roomIDs']);
    }
}