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

use THM\Organizer\Adapters\{Application, Database, Input, Text};
use THM\Organizer\Helpers;
use stdClass;

/**
 * Class retrieves information for a filtered set of colors.
 */
class ContactTracking extends ListModel
{
    protected $defaultLimit = 0;

    /**
     * Adds entries to the items structure.
     *
     * @param   array  &$items  the items to be displayed
     * @param   array   $data   the data from the resource
     *
     * @return void
     */
    private function addItem(array &$items, array $data)
    {
        $date  = $data['date'];
        $index = "{$data['surname']}-{$data['forename']}-{$data['username']}";

        if (empty($items[$index])) {
            $name             = $data['surname'];
            $name             .= $data['forename'] ? ", {$data['forename']}" : '';
            $item             = new stdClass();
            $item->address    = empty($data['address']) ? '' : $data['address'];
            $item->bookingIDs = [$data['bookingID'] => $data['bookingID']];
            $item->city       = empty($data['city']) ? '' : $data['city'];
            $item->dates      = [];
            $item->email      = empty($data['email']) ? '' : $data['email'];
            $item->instances  = $data['instances'];
            $item->person     = $name;
            $item->role       = empty($data['role']) ? '' : $data['role'];
            $item->rooms      = empty($data['rooms']) ? [] : $data['rooms'];
            $item->telephone  = empty($data['telephone']) ? '' : $data['telephone'];
            $item->username   = empty($data['username']) ? '' : $data['username'];
            $item->zipCode    = empty($data['zipCode']) ? '' : $data['zipCode'];

            $items[$index] = $item;
        }
        else {
            $item            = $items[$index];
            $item->instances = $item->instances + $data['instances'];
            $item->rooms     = empty($data['rooms']) ? $item->rooms : $item->rooms + $data['rooms'];

            // The same booking is being reprocessed because of additional rooms and should not be summed
            if (array_key_exists($data['bookingID'], $item->bookingIDs)) {
                return;
            }
        }

        if (empty($item->dates[$date])) {
            $item->dates[$date] = [];
        }

        if (empty($item->dates[$date][$data['name']])) {
            $item->dates[$date][$data['name']] = $data['minutes'];
        }
        else {
            $item->dates[$date][$data['name']] += $data['minutes'];
        }
    }

    /**
     * @inheritdoc
     */
    public function getItems(): array
    {
        $items = [];
        $now   = date('H:i:s');
        $then  = date('Y-m-d', strtotime("-28 days"));
        $today = date('Y-m-d');

        $bookings = [];

        if ($participantID = $this->state->get('participantID')) {
            $query = Database::getQuery();
            $query->from('#__organizer_instance_participants AS ipa')
                ->select('ipa.instanceID, ipa.roomID, ipa.seat')
                ->where('ipa.attended = 1')
                ->where("ipa.participantID = $participantID")
                ->innerJoin('#__organizer_instances AS i ON i.id = ipa.instanceID')
                ->innerJoin('#__organizer_blocks AS bl ON bl.id = i.blockID')
                ->select('bl.date, bl.startTime AS defaultStart, bl.endTime AS defaultEnd')
                ->where("bl.date >= '$then'")
                ->where("(bl.date < '$today' OR (bl.date = '$today' AND bl.endTime <= '$now'))")
                ->innerJoin('#__organizer_bookings AS bo ON bo.blockID = bl.id AND bo.unitID = i.unitID')
                ->select('bo.id AS bookingID, bo.startTime, bo.endTime')
                ->order('bl.date DESC, bl.startTime DESC');
            Database::setQuery($query);

            foreach (Database::loadAssocList() as $result) {
                $date      = $result['date'];
                $endTime   = $result['endTime'] ?: $result['defaultEnd'];
                $startTime = $result['startTime'] ?: $result['defaultStart'];
                $index     = $date . '-' . $startTime . '-' . $endTime;

                if (empty($bookings[$index])) {
                    $bookings[$index] = [
                        'bookingID' => $result['bookingID'],
                        'date'      => $date,
                        'endTime'   => $endTime,
                        'instances' => [$result['instanceID'] => $result['instanceID']],
                        'name'      => Helpers\Bookings::getName($result['bookingID']),
                        'rooms'     => $result['roomID'] ? [$result['roomID'] => $result['roomID']] : [],
                        'seat'      => $result['seat'],
                        'startTime' => $startTime,
                    ];
                }
                else {
                    $bookings[$index]['instances'][$result['instanceID']] = $result['instanceID'];

                    if ($result['roomID']) {
                        $bookings[$index]['rooms'][$result['roomID']] = $result['roomID'];
                    }
                }
            }
        }

        if ($personID = $this->state->get('personID', 0)) {
            $query = Database::getQuery();
            $query->from('#__organizer_instance_persons AS ipe')
                ->select('ipe.instanceID')
                ->where("ipe.personID = $personID")
                ->innerJoin('#__organizer_instance_rooms AS ir ON ir.assocID = ipe.id')
                ->select('ir.roomID')
                ->innerJoin('#__organizer_instances AS i ON i.id = ipe.instanceID')
                ->innerJoin('#__organizer_blocks AS bl ON bl.id = i.blockID')
                ->select('bl.date, bl.startTime AS defaultStart, bl.endTime AS defaultEnd')
                ->where("bl.date >= '$then'")
                ->where("(bl.date < '$today' OR (bl.date = '$today' AND bl.endTime <= '$now'))")
                ->innerJoin('#__organizer_bookings AS bo ON bo.blockID = bl.id AND bo.unitID = i.unitID')
                ->select('bo.id AS bookingID, bo.startTime, bo.endTime')
                ->order('bl.date DESC, bl.startTime DESC');
            Database::setQuery($query);

            foreach (Database::loadAssocList() as $result) {
                $date      = $result['date'];
                $endTime   = $result['endTime'] ?: $result['defaultEnd'];
                $startTime = $result['startTime'] ?: $result['defaultStart'];
                $index     = $date . '-' . $startTime . '-' . $endTime;

                if (empty($bookings[$index])) {
                    $bookings[$index] = [
                        'bookingID' => $result['bookingID'],
                        'date'      => $date,
                        'endTime'   => $endTime,
                        'instances' => [$result['instanceID'] => $result['instanceID']],
                        'name'      => Helpers\Bookings::getName($result['bookingID']),
                        'rooms'     => $result['roomID'] ? [$result['roomID'] => $result['roomID']] : [],
                        'seat'      => null,
                        'startTime' => $startTime,
                    ];
                }
                else {
                    $bookings[$index]['instances'][$result['instanceID']] = $result['instanceID'];

                    if ($result['roomID']) {
                        $bookings[$index]['rooms'][$result['roomID']] = $result['roomID'];
                    }
                }
            }
        }

        krsort($bookings);

        $paQuery = Database::getQuery();
        $paQuery->from('#__organizer_participants AS p')
            ->select('p.forename, p.surname, p.address, p.city, p.telephone, p.zipCode')
            ->innerJoin('#__users AS u ON u.id = p.id')
            ->select('u.username, u.email')
            ->innerJoin('#__organizer_instance_participants AS ipa ON ipa.participantID = p.id')
            ->select('ipa.participantID, ipa.roomID, ipa.seat');

        $tag     = Application::getTag();
        $peQuery = Database::getQuery();
        $peQuery->from('#__organizer_persons AS pe')
            ->select('pe.forename AS defaultForename, pe.surname AS defaultSurname')
            ->innerJoin('#__organizer_instance_persons AS ipe ON ipe.personID = pe.id')
            ->innerJoin('#__organizer_roles AS r ON r.id = ipe.roleID')
            ->select("r.name_$tag AS role")
            ->leftJoin('#__organizer_instance_rooms AS ir ON ir.assocID = ipe.id')
            ->select('ir.roomID')
            ->leftJoin('#__users AS u ON u.username = pe.username')
            ->select('u.username, u.email')
            ->leftJoin('#__organizer_participants AS pa ON pa.id = u.id')
            ->select('pa.forename, pa.surname, pa.address, pa.city, pa.telephone, pa.zipCode');

        foreach ($bookings as $booking) {
            // +60 Seconds to be inclusive of the last minute.
            $booking['minutes'] = ceil((strtotime($booking['endTime']) + 60 - strtotime($booking['startTime'])) / 60);

            $paQuery->clear('where');
            $paQuery->where('ipa.instanceID IN (' . implode(',', $booking['instances']) . ')')->where('ipa.attended = 1');

            if ($booking['rooms']) {
                $paQuery->where('ipa.roomID IN (' . implode(',', $booking['rooms']) . ')');
            }

            if ($participantID) {
                $paQuery->where("ipa.participantID != $participantID");
            }

            Database::setQuery($paQuery);

            foreach (Database::loadAssocList() as $person) {
                $data = [
                    'address'   => $person['address'],
                    'city'      => $person['city'],
                    'email'     => $person['email'],
                    'forename'  => $person['forename'],
                    'rooms'     => $person['roomID'] ? [$person['roomID'] => $person['roomID']] : [],
                    'seat'      => $person['seat'],
                    'surname'   => $person['surname'],
                    'telephone' => $person['telephone'],
                    'username'  => $person['username'],
                    'zipCode'   => $person['zipCode']
                ];

                $this->addItem($items, array_merge($booking, $data));
            }

            $peQuery->clear('where');
            $peQuery->where('ipe.instanceID IN (' . implode(',', $booking['instances']) . ')')
                ->where("ipe.delta != 'removed'")
                ->where("ir.delta != 'removed'");

            if ($booking['rooms']) {
                $paQuery->where('ir.roomID IN (' . implode(',', $booking['rooms']) . ')');
            }

            if ($personID) {
                $peQuery->where("ipe.personID != $personID");
            }

            Database::setQuery($peQuery);

            foreach (Database::loadAssocList() as $person) {
                $data = [
                    'address'   => $person['address'] ?: '',
                    'city'      => $person['city'] ?: '',
                    'email'     => $person['email'] ?: '',
                    'forename'  => $person['forename'] ?: $person['defaultForename'],
                    'rooms'     => $person['roomID'] ? [$person['roomID'] => $person['roomID']] : [],
                    'seat'      => '',
                    'surname'   => $person['surname'] ?: $person['defaultSurname'],
                    'telephone' => $person['telephone'] ?: '',
                    'username'  => $person['username'] ?: '',
                    'zipCode'   => $person['zipCode'] ?: ''
                ];
                $data = array_merge($booking, $data);

                if ($person['role']) {
                    $data['name'] .= ' *';
                }

                $this->addItem($items, array_merge($booking, $data));
            }
        }

        if ($items) {
            ksort($items);
        }
        elseif ($search = $this->state->get('filter.search')) {
            $none = Text::sprintf('ORGANIZER_EMPTY_CONTACT_RESULT_SET', $search);
            Application::message($none, Application::NOTICE);
        }

        return $items;
    }

    /**
     * Performs final the final integrity check between the participants and persons result sets.
     *
     * @param   array  $participantIDs  the participants id results
     * @param   array  $personIDs       the persons id results
     *
     * @return void
     */
    private function finalCheck(array $participantIDs, array $personIDs)
    {
        $participantID = $participantIDs ? $participantIDs[0] : 0;
        $personID      = $personIDs ? $personIDs[0] : 0;
        $filters       = Input::getFilterItems();
        $search        = $filters->get('search');
        $tooMany       = Text::sprintf('ORGANIZER_TOO_MANY_RESULTS', $search);

        // User and person resource usernames don't resolve to the same physical person.
        if ($participantID and $personID and (int) $personID !== Helpers\Persons::getIDByUserID($participantID)) {
            $this->forceEmpty();
            Application::message($tooMany, Application::NOTICE);

            return;
        }

        $this->state->set('participantID', $participantID);
        $this->state->set('personID', $personID);
    }

    /**
     * Sets selection criteria to empty values to avoid positive results from previous queries.
     * @return void
     */
    private function forceEmpty()
    {
        $this->state->set('participantID', 0);
        $this->state->set('personID', 0);
    }

    /**
     * @inheritDoc
     */
    protected function populateState($ordering = null, $direction = null)
    {
        parent::populateState();

        $filters = Input::getFilterItems();

        if (!$search = $filters->get('search')) {
            $this->forceEmpty();

            return;
        }

        $tooMany = Text::sprintf('ORGANIZER_TOO_MANY_RESULTS', $search);
        $search  = explode(' ', $search);

        // Users/participants by username
        $query = Database::getQuery();
        $query->select('p.id')
            ->from('#__organizer_participants AS p')
            ->innerJoin('#__users AS u ON u.id = p.id')
            ->where("(u.username = '" . implode("' OR u.username = '", $search) . "')");
        Database::setQuery($query);

        if ($participantIDs = Database::loadColumn() and count($participantIDs) > 1) {
            $this->forceEmpty();
            Application::message($tooMany, Application::NOTICE);

            return;
        }

        // Person by username
        $query = Database::getQuery();
        $query->select('id')
            ->from('#__organizer_persons')
            ->where("(username = '" . implode("' OR username = '", $search) . "')");
        Database::setQuery($query);

        if ($personIDs = Database::loadColumn() and count($personIDs) > 1) {
            $this->forceEmpty();
            Application::message($tooMany, Application::NOTICE);

            return;
        }

        if ($participantIDs or $personIDs) {
            $this->finalCheck($participantIDs, $personIDs);

            return;
        }

        // Participants by full name
        $subQuery = Database::getQuery();
        $subQuery->select('id, ' . $subQuery->concatenate(['surname', 'forename'], ' ') . ' AS fullName')
            ->from('#__organizer_participants');
        $query = Database::getQuery();
        $query->select('p1.id, p2.fullname')
            ->from('#__organizer_participants AS p1')
            ->innerJoin("($subQuery) AS p2 ON p2.id = p1.id")
            ->where("(p2.fullName LIKE '%" . implode("%' AND p2.fullName LIKE '%", $search) . "%')");
        Database::setQuery($query);

        if ($participantIDs = Database::loadColumn() and count($participantIDs) > 1) {
            $this->forceEmpty();
            Application::message($tooMany, Application::NOTICE);

            return;
        }

        // Persons by full name
        $subQuery = Database::getQuery();
        $subQuery->select('id, ' . $subQuery->concatenate(['surname', 'forename'], ' ') . ' AS fullName')
            ->from('#__organizer_persons');
        $query = Database::getQuery();
        $query->select('p1.id, p2.fullname')
            ->from('#__organizer_persons AS p1')
            ->innerJoin("($subQuery) AS p2 ON p2.id = p1.id")
            ->where("(p2.fullName LIKE '%" . implode("%' AND p2.fullName LIKE '%", $search) . "%')");
        Database::setQuery($query);

        if ($personIDs = Database::loadColumn() and count($personIDs) > 1) {
            $this->forceEmpty();
            Application::message($tooMany, Application::NOTICE);

            return;
        }

        if ($participantIDs or $personIDs) {
            $this->finalCheck($participantIDs, $personIDs);

            return;
        }

        // No resolution
        $this->forceEmpty();
    }
}
