<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use Joomla\CMS\Form\Form as FormAlias;
use THM\Organizer\Adapters\{Application, Database as DB, Input, Text, User};
use THM\Organizer\Helpers\{Campuses, Courses as Helper, CourseParticipants as CP, Persons, Roles};
use Joomla\Database\ParameterType;

/**
 * Class which manages stored course data.
 */
class Course extends EditModel
{
    protected string $tableClass = 'Courses';

    /** @inheritDoc */
    public function getForm($data = [], $loadData = true): ?FormAlias
    {
        $form = parent::getForm($data, $loadData);

        if (empty($this->item->id)) {
            $form->removeField('campusID');
        }

        return $form ?: null;
    }

    /** @inheritDoc */
    public function getItem(): object
    {
        if (!$item = $this->item) {
            $item = parent::getItem();

            // Reformat values for labeled display of a standing course
            if ($courseID = $item->id and !Input::getCMD('layout')) {

                $eProperties = $this->eventProperties($courseID);

                $endDate   = is_array($eProperties['endDate']) ? max($eProperties['endDate']) : $eProperties['endDate'];
                $startDate = is_array($eProperties['startDate']) ? min($eProperties['startDate']) : $eProperties['startDate'];
                $tag       = Application::getTag();
                $userID    = User::id();

                $model = [
                    'id'   => $courseID,
                    'name' => Helper::name($courseID),

                    'deadline'           => $item->deadline,
                    'endDate'            => $endDate,
                    'events'             => !empty($eProperties['events']) ? $eProperties['events'] : [],
                    // todo: Add to output
                    'groups'             => $item->groups,
                    'maxParticipants'    => $item->maxParticipants,
                    'participants'       => count(Helper::participantIDs($courseID)),
                    // Should come from events.
                    'preparatory'        => $eProperties['preparatory'],
                    // Registration status text if still relevant
                    'registrationStatus' => $userID ? CP::state($item->id, $userID) : null,
                    'registrationType'   => $item->registrationType,
                    'startDate'          => $startDate,
                    'termID'             => $item->termID
                ];

                if ($item->fee) {
                    $model[Text::_('FEE')] = $item->fee . ' €';
                }

                if ($item->campusID) {
                    $model['campusID']   = $item->campusID;
                    $model['campusName'] = Campuses::name($item->campusID);

                    $model[Text::_('CAMPUS')] = Campuses::getPin($item->campusID) . ' ' . $model['campusName'];
                }

                if ($eProperties['organization']) {
                    $model[Text::_('ORGANIZATIONAL')] = $eProperties['organization'];
                }

                if ($eProperties['speakers']) {
                    $model[Text::_('SPEAKERS')] = $eProperties['speakers'];
                }

                if ($eProperties['teachers']) {
                    $model[Text::_('TEACHERS')] = $eProperties['teachers'];
                }

                if ($eProperties['tutors']) {
                    $model[Text::_('TUTORS')] = $eProperties['tutors'];
                }

                $description = "description_$tag";
                if ($item->$description) {
                    $model[Text::_('SHORT_DESCRIPTION')] = $eProperties['description'];
                }
                elseif ($eProperties['description']) {
                    $model[Text::_('SHORT_DESCRIPTION')] = $eProperties['description'];
                }

                if ($eProperties['content']) {
                    $model[Text::_('CONTENT')] = $eProperties['content'];
                }

                $model[Text::_('REGISTRATION')] =
                    $item->registrationType === Helper::MANUAL ? Text::_('REGISTRATION_MANUAL') : Text::_('REGISTRATION_FIFO');

                if ($eProperties['pretests']) {
                    $model[Text::_('PRETESTS')] = $eProperties['pretests'];
                }

                if ($eProperties['courseContact']) {
                    $model[Text::_('COURSE_POC')] = $eProperties['courseContact'];
                }

                if ($eProperties['contact']) {
                    $model[Text::_('POC')] = $eProperties['contact'];
                }

                $item       = (object) $model;
                $this->item = $item;
            }
        }
        return $item;
    }

    /**
     * Retrieves events associated with the given course.
     *
     * @param   int  $courseID  the id of the course
     *
     * @return array[] the events associated with the course
     */
    private function eventProperties(int $courseID): array
    {
        $tag      = Application::getTag();
        $aliased  = DB::qn(
            [
                "contact_$tag",
                "content_$tag",
                "courseContact_$tag",
                "e.description_$tag",
                "e.name_$tag",
                "organization_$tag",
                "pretests_$tag",
            ],
            [
                'contact',
                'content',
                'courseContact',
                'description',
                'name',
                'organization',
                'pretests',
            ]
        );
        $selected = ['DISTINCT ' . DB::qn('e.id'), DB::qn('preparatory')];

        $endDate   = DB::qn('endDate');
        $startDate = DB::qn('startDate');
        $dates     = ["MIN($startDate) AS $startDate", "MAX($endDate) AS $endDate"];

        $query = DB::getQuery();
        $query->select(array_merge($selected, $dates, $aliased))
            ->from(DB::qn('#__organizer_events', 'e'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.eventID', 'e.id'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->where(DB::qn('u.courseID') . ' = :courseID')->bind(':courseID', $courseID, ParameterType::INTEGER)
            ->order(DB::qn('name'));
        DB::setQuery($query);

        if (!$events = DB::loadObjectList('name')) {
            return [];
        }

        $properties = [];
        foreach ($events as $name => $event) {

            $properties['events'][$event->id] = $event->name;

            // Persons are indirectly associated values.
            if ($speakers = self::persons($courseID, $event->id, [Roles::SPEAKER])) {
                $properties['speakers'][$name] = $speakers;
            }
            if ($teachers = self::persons($courseID, $event->id, [Roles::TEACHER])) {
                $properties['teachers'][$name] = $teachers;
            }
            if ($tutors = self::persons($courseID, $event->id, [Roles::TUTOR])) {
                $properties['tutors'][$name] = $tutors;
            }

            foreach ($event as $property => $value) {
                if (in_array($property, ['id', 'name'])) {
                    continue;
                }

                if (!empty($value)) {
                    $properties[$property][$name] = $value;
                }
            }
        }

        foreach ($properties as $property => $values) {

            if ($property === 'events') {
                continue;
            }

            if ($property === 'preparatory') {
                $properties[$property] = (bool) max($values);
                continue;
            }

            // Remove empty values
            $values = array_filter($values);

            // No property values with data => remove property
            if (empty($values)) {
                unset($properties[$property]);
                continue;
            }

            // Single property value speaks for the course, regardless of the associated event => remove event labeling
            if (count($values) === 1) {
                $properties[$property] = reset($values);
                continue;
            }

            $uniqueValues = array_unique($values);

            // Every event has a unique value => leave as is
            if (count($uniqueValues) === count($values)) {
                continue;
            }

            // Create and assign new indexes based on the aggregates for the event names associated with unique values.
            foreach ($uniqueValues as $uniqueValue) {
                $uvKeys = [];
                while (($key = array_search($uniqueValue, $values)) !== null) {
                    $uvKeys[$key] = $key;
                    unset($values[$key]);
                }

                // Value was only associated with one key
                if (count($uvKeys) === 1) {
                    $values[reset($uvKeys)] = $uniqueValue;
                    continue;
                }

                // Aggregate event names per unique property value.
                $last           = array_pop($uvKeys);
                $uvKey          = implode(', ', $uvKeys) . " & $last";
                $values[$uvKey] = $uniqueValue;
            }

            $properties[$property] = $values;
        }

        return $properties;
    }

    /**
     * Gets persons associated with the given course, optionally filtered by event and role.
     *
     * @param   int    $courseID  the id of the course
     * @param   int    $eventID   the id of the event
     * @param   array  $roleIDs   the id of the roles the persons should have
     *
     * @return string[] the persons matching the search criteria
     */
    private function persons(int $courseID, int $eventID = 0, array $roleIDs = []): array
    {
        $query = DB::getQuery();
        $query->select('DISTINCT ' . DB::qn('ip.personID'))
            ->from(DB::qn('#__organizer_instance_persons', 'ip'))
            ->innerJoin(DB::qn('#__organizer_instances', 'i'), DB::qc('i.id', 'ip.instanceID'))
            ->innerJoin(DB::qn('#__organizer_units', 'u'), DB::qc('u.id', 'i.unitID'))
            ->where(DB::qn('u.courseID') . ' = :courseID')->bind(':courseID', $courseID, ParameterType::INTEGER);

        if ($eventID) {
            $query->where(DB::qn('i.eventID') . ' = :eventID')->bind(':eventID', $eventID, ParameterType::INTEGER);
        }

        if ($roleIDs) {
            $query->whereIn(DB::qn('ip.roleID'), $roleIDs);
        }

        DB::setQuery($query);

        if (!$personIDs = DB::loadIntColumn()) {
            return [];
        }

        $persons = [];
        foreach ($personIDs as $personID) {
            $persons[$personID] = Persons::lastNameFirst($personID);
        }

        return $persons;
    }
}
