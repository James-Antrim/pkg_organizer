<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\XLS;

use THM\Organizer\Adapters\{Application, Input, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Layouts\Exported;
use THM\Organizer\Models\BaseModel;

/**
 * Class creates a XLS file for the display of the filtered schedule information.
 */
class Instances extends ListView
{
    use Exported;

    // RoleIDs
    private const SPEAKERS = 4, SUPERVISORS = 3, TEACHERS = 1, TUTORS = 2;

    public array $groups = [];

    public array $rooms = [];

    public BaseModel $model;

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (Application::backend() and !Helpers\Can::scheduleTheseOrganizations()) {
            Application::error(403);
        }

        if (Input::getBool('my') and !Helpers\Users::getID()) {
            Application::error(401);
        }
    }

    /**
     * Filters out deprecated items and distracting property values.
     *
     * @param   string  $key       the resource array key name
     * @param   array   $persons   the person resource structures
     * @param   int     $personID  the id of the person being currently iterated
     * @param   array   $container
     *
     * @return void
     */
    private function filterResources(string $key, array &$persons, int $personID, array &$container): void
    {
        $person =& $persons[$personID];

        if (array_key_exists($key, $person)) {
            $resources =& $persons[$personID][$key];

            foreach ($resources as $resourceID => $resource) {
                if ($resource['status'] === 'removed') {
                    unset($resources[$resourceID]);
                    continue;
                }

                unset($resources[$resourceID]['status'], $resources[$resourceID]['statusDate']);
            }

            if (!in_array($resources, $container)) {
                $container[] = $resources;
            }
        }
    }

    /**
     * Gets the person resource text for a resource type $key
     *
     * @param   array   $persons   the data for all persons
     * @param   int     $personID  the id of the person being currently iterated
     * @param   string  $key       the resource key
     * @param   string  $oKey      the key of the array index with the display value
     * @param   string  $rKey      $rKey the key against which the displayed key is resolved
     *
     * @return string
     */
    private function getPersonResources(array $persons, int $personID, string $key, string $oKey, string $rKey = ''): string
    {
        $names = [];

        foreach ($persons[$personID][$key] as $resource) {
            $names[] = $resource[$oKey];

            if ($rKey) {
                $this->$key[$resource[$oKey]] = $resource[$rKey];
            }
        }

        $lastName = array_pop($names);

        return $names ? ' - ' . implode(', ', $names) . ' & ' . $lastName : ' - ' . $lastName;
    }

    /**
     * Supplements the person names in a role as necessary.
     *
     * @param   array  $container   the container with role persons
     * @param   array  $persons     the array with data on all persons
     * @param   bool   $showGroups  whether or not groups should be shown for individuals
     * @param   bool   $showRooms   whether or not rooms should be shown for individuals
     *
     * @return string[]
     */
    private function getPersonTexts(array $container, array $persons, bool $showGroups, bool $showRooms): array
    {
        $names = [];

        foreach ($container as $personID => $name) {
            $displayName = $name;

            if ($showGroups and !empty($persons[$personID]['groups'])) {
                $displayName .= $this->getPersonResources($persons, $personID, 'groups', 'code', 'fullName');
            }

            if ($showRooms and !empty($persons[$personID]['rooms'])) {
                $displayName .= $this->getPersonResources($persons, $personID, 'rooms', 'room');
            }

            $names[$name] = $displayName;
        }

        return $names;
    }

    /**
     * @inheritDoc
     */
    protected function setHeaders(): void
    {
        $this->headers = [
            'date'         => [
                'text'  => Text::_('ORGANIZER_DATE'),
                'width' => 12.5
            ],
            'times'        => [
                'text'  => Text::_('ORGANIZER_TIME'),
                'width' => 15
            ],
            'organization' => [
                'text'  => Text::_('ORGANIZER_ORGANIZATION'),
                'width' => 15
            ],
            'title'        => [
                'text'  => Text::_('ORGANIZER_NAME'),
                'width' => 30
            ],
            'method'       => [
                'text'  => Text::_('ORGANIZER_METHOD'),
                'width' => 15
            ],
            'groups'       => [
                'text'  => Text::_('ORGANIZER_GROUPS'),
                'width' => 70
            ],
            'rooms'        => [
                'text'  => Text::_('ORGANIZER_ROOMS'),
                'width' => 12.5
            ],
            'teachers'     => [
                'text'  => Helpers\Roles::getLabel(self::TEACHERS, 2),
                'width' => 30
            ],
            'supervisors'  => [
                'text'  => Helpers\Roles::getLabel(self::SUPERVISORS, 2),
                'width' => 30
            ],
            'tutors'       => [
                'text'  => Helpers\Roles::getLabel(self::TUTORS, 2),
                'width' => 30
            ],
            'speakers'     => [
                'text'  => Helpers\Roles::getLabel(self::SPEAKERS, 2),
                'width' => 30
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    protected function structureItems(): void
    {
        $conditions = $this->model->conditions;

        $this->setFlags($conditions);

        // For organizations with no service function this can be a false flag. => Double check.
        if ($this->showOrganizations) {
            $organizations = [];

            foreach ($this->items as $item) {
                $organizations[$item->organization] = $item->organization;
            }

            if (count($organizations) === 1) {
                $this->showOrganizations = false;
            }
        }

        if (!$this->showOrganizations) {
            unset($this->headers['organization']);
        }

        if (!empty($conditions['roleID'])) {
            switch ($conditions['roleID']) {
                case Helpers\Roles::SPEAKER:
                    unset($this->headers['supervisors'], $this->headers['teachers'], $this->headers['tutors']);
                    break;
                case Helpers\Roles::SUPERVISOR:
                    unset($this->headers['speakers'], $this->headers['teachers'], $this->headers['tutors']);
                    break;
                case Helpers\Roles::TEACHER:
                    unset($this->headers['speakers'], $this->headers['supervisors'], $this->headers['tutors']);
                    break;
                case Helpers\Roles::TUTOR:
                    unset($this->headers['speakers'], $this->headers['supervisors'], $this->headers['teachers']);
                    break;
            }
        }

        if (!$this->showRooms) {
            unset($this->headers['rooms']);
        }

        parent::structureItems();
    }

    /**
     * @inheritDoc
     */
    protected function structureItem(object $item): array
    {
        // Aggregation containers
        $groups      = [];
        $persons     = (array) $item->resources;
        $speakers    = [];
        $supervisors = [];
        $teachers    = [];
        $tutors      = [];
        $rooms       = [];

        foreach ($persons as $personID => $person) {
            // No delta display in xls
            if ($person['status'] === 'removed') {
                unset($persons[$personID]);
                continue;
            }

            switch ($person['roleID']) {
                case self::SPEAKERS:
                    $speakers[$personID] = $person['person'];
                    break;
                case self::SUPERVISORS:
                    $supervisors[$personID] = $person['person'];
                    break;
                case self::TEACHERS:
                    $teachers[$personID] = $person['person'];
                    break;
                case self::TUTORS:
                    $tutors[$personID] = $person['person'];
                    break;
                default:
                    unset($persons[$personID]);
                    continue 2;
            }

            $this->filterResources('groups', $persons, $personID, $groups);
            $this->filterResources('rooms', $persons, $personID, $rooms);
        }

        $groupNames = [];
        $roomNames  = [];

        foreach ($groups as $keys) {
            foreach ($keys as $values) {
                $groupNames[$values['fullName']] = $values['fullName'];
            }
        }

        foreach ($rooms as $keys) {
            foreach ($keys as $values) {
                $roomNames[$values['room']] = $values['room'];
            }
        }

        $showGroups = count($groups) > 1;
        $showRooms  = count($rooms) > 1;

        return [
            'date'         => Helpers\Dates::formatDate($item->date),
            'groups'       => $groupNames,
            'method'       => $item->method,
            'organization' => $this->showOrganizations ? $item->organization : '',
            'rooms'        => $roomNames,
            'teachers'     => $this->getPersonTexts($teachers, $persons, $showGroups, $showRooms),
            'times'        => "$item->startTime - $item->endTime",
            'title'        => $item->name,
            'speakers'     => $this->getPersonTexts($speakers, $persons, $showGroups, $showRooms),
            'supervisors'  => $this->getPersonTexts($supervisors, $persons, $showGroups, $showRooms),
            'tutors'       => $this->getPersonTexts($tutors, $persons, $showGroups, $showRooms)
        ];
    }
}
