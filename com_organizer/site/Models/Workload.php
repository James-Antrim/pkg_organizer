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

use THM\Organizer\Adapters\{Application, Database, Input};
use THM\Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of participants.
 */
class Workload extends FormModel
{
    private const CURRENT_ITEMS = 1;

    public $bachelors = 0;

    public $diplomas = 0;

    public $doctors = 0;

    private $conditions;

    /**
     * @var array
     */
    public $items;

    /**
     * @var array
     */
    public $methods;

    private $organizationID;

    public $masters = 0;

    private $personID;

    public $projects = 0;

    /**
     * @var array
     */
    public $programs;

    private $termID;

    /**
     * @var int
     */
    private $weeks;

    /**
     * Aggregates by concurrent blocks.
     *
     * @param array $units
     *
     * @return array[]
     */
    private function aggregateByBlock(array $units): array
    {
        $count = count($units);

        for ($index = 0; $index < $count; $index++) {
            // Removed in a previous iteration
            if (empty($units[$index])) {
                continue;
            }

            $current =& $units[$index];
            $keys    = array_keys($current['blocks']);
            $method  = $current['method'];

            for ($nIndex = $index + 1; $nIndex < $count; $nIndex++) {
                // Removed in a previous iteration or inconsistent methods
                if (empty($units[$nIndex]) or $units[$nIndex]['method'] !== $method) {
                    continue;
                }

                $next  = $units[$nIndex];
                $nKeys = array_keys($next['blocks']);

                // The blocks are a true subset in at least one direction
                if (empty(array_diff($keys, $nKeys)) or empty(array_diff($nKeys, $keys))) {
                    $current['events']        = $current['events'] + $next['events'];
                    $current['groups']        = $current['groups'] + $next['groups'];
                    $current['organizations'] = $current['organizations'] + $next['organizations'];
                    $current['programs']      = $current['programs'] + $next['programs'];
                    unset($units[$nIndex]);
                }
            }
        }

        // Re-key after potential removal.
        return array_values($units);
    }

    /**
     * Aggregates planned blocks by date.
     *
     * @param array  &$items
     *
     * @return void
     */
    private function aggregateByDate(array &$items)
    {
        $count = count($items);

        for ($index = 0; $index < $count; $index++) {
            $blocks = [];

            foreach ($items[$index]['blocks'] as $block) {
                $block['minutes'] = ceil((strtotime($block['endTime']) - strtotime($block['startTime'])) / 60);

                if (!empty($blocks[$block['date']])) {
                    $block['endTime']   = $block['endTime'] > $blocks[$block['date']]['endTime'] ?
                        $block['endTime'] : $blocks[$block['date']]['endTime'];
                    $block['minutes']   += $blocks[$block['date']]['minutes'];
                    $block['startTime'] = $block['startTime'] < $blocks[$block['date']]['startTime'] ?
                        $block['startTime'] : $blocks[$block['date']]['startTime'];
                }

                $blocks[$block['date']] = $block;
            }

            ksort($blocks);

            $items[$index]['blocks'] = $blocks;
        }
    }

    /**
     * Aggregates by event and method identity.
     *
     * @param array $items
     *
     * @return void
     */
    private function aggregateByEvent(array &$items)
    {
        $count = count($items);

        for ($index = 0; $index < $count; $index++) {
            // Removed in a previous iteration
            if (empty($items[$index])) {
                continue;
            }

            $current =& $items[$index];
            $keys    = array_keys($current['events']);
            $method  = $current['method'];

            for ($nIndex = $index + 1; $nIndex < $count; $nIndex++) {
                // Removed in a previous iteration or inconsistent methods
                if (empty($items[$nIndex]) or $items[$nIndex]['method'] !== $method) {
                    continue;
                }

                $next  = $items[$nIndex];
                $nKeys = array_keys($next['events']);

                // Identity
                if (!array_diff($keys, $nKeys) and !array_diff($nKeys, $keys)) {
                    $current['blocks'] = $current['blocks'] + $next['blocks'];
                    unset($items[$nIndex]);
                }
            }
        }

        // Re-key after potential removal.
        $items = array_values($items);
    }

    /**
     * Aggregates instances by their unitID
     *
     * @param array $instances the instances to be aggregated
     *
     * @return array[]
     */
    private function aggregateByUnit(array $instances): array
    {
        $units = [];

        // Aggregate units in first iteration
        foreach ($instances as $instanceID => $instance) {
            if ($instance['code'] === 'KOL.B') {
                $this->bachelors = $this->bachelors + 1;
                unset($instances[$instanceID]);
                continue;
            }

            if ($instance['code'] === 'KOL.D') {
                $this->masters = $this->doctors + 1;
                unset($instances[$instanceID]);
                continue;
            }

            if ($instance['code'] === 'KOL.M') {
                $this->masters = $this->masters + 1;
                unset($instances[$instanceID]);
                continue;
            }

            if ($instance['code'] === 'KOL.P') {
                $this->masters = $this->doctors + 1;
                unset($instances[$instanceID]);
                continue;
            }

            if (empty($units[$instance['unitID']])) {
                $units[$instance['unitID']] = [
                    'blocks' => [],
                    'events' => [],
                    'method' => $instance['method'],
                    'organizations' => $instance['organizations'],
                    'programs' => $instance['programs']
                ];
            }

            $unit =& $units[$instance['unitID']];

            if (empty($unit['blocks'][$instance['blockID']])) {
                $unit['blocks'][$instance['blockID']] = [
                    'date' => $instance['date'],
                    'dow' => $instance['dow'],
                    'endTime' => date('H:i:s', strtotime('+1 minute', strtotime($instance['endTime']))),
                    'startTime' => $instance['startTime']
                ];
            }

            if (empty($unit['events'][$instance['eventID']])) {
                $unit['events'][$instance['eventID']] = [
                    'code' => $instance['code'],
                    'name' => $instance['event'],
                    'subjectNo' => $instance['subjectNo']
                ];
            }
        }

        // Replace the actual unit ids which complicate iteration.
        return array_values($units);
    }

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!Helpers\Users::getID()) {
            Application::error(401);
        }

        $organizationIDs = Helpers\Can::manageTheseOrganizations();
        $thisPersonID    = Helpers\Persons::getIDByUserID();
        $personID        = Input::getInt('personID', $thisPersonID);

        // Someone without extended access requested to access a person other than themselves
        $unAuthorized = (!$organizationIDs and $personID and $personID != $thisPersonID);

        if ((!$organizationIDs and !$thisPersonID) or $unAuthorized) {
            Application::error(403);
        }

        $organizationID       = $organizationIDs ? reset($organizationIDs) : 0;
        $this->organizationID = Input::getInt('organizationID', $organizationID);
        $this->personID       = Input::getInt('personID', $personID);
        $this->termID         = Input::getInt('termID', Helpers\Terms::getCurrentID());
        $this->weeks          = Input::getInt('weeks', 13);

        $incomplete = (!$this->personID or !$this->termID);

        if ($format = Input::getCMD('format') and $format === 'xls' and $incomplete) {
            Application::error(400);
        }
    }

    /**
     * @inheritDoc
     */
    protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = '')
    {
        $options['load_data'] = true;

        $form = parent::loadForm($name, $source, $options, $clear, $xpath);

        if (empty($this->organizationID)) {
            $form->removeField('organizationID');
        }

        return $form;
    }

    /**
     * @inheritDoc
     */
    protected function loadFormData(): array
    {
        return [
            'organizationID' => $this->organizationID,
            'personID' => $this->personID,
            'termID' => $this->termID,
            'weeks' => $this->weeks
        ];
    }

    /**
     * Turns aggregates into itemized events.
     *
     * @param array $aggregates
     *
     * @return array[]
     */
    private function itemize(array $aggregates): array
    {
        $items = [];

        foreach ($aggregates as $aggregate) {
            $names      = [];
            $subjectNos = [];

            foreach ($aggregate['events'] as $event) {
                $names[$event['name']]      = $event['name'];
                $subjectNos[$event['name']] = $event['subjectNo'];
            }

            ksort($names);
            $names = array_values($names);
            ksort($subjectNos);
            $subjectNos = array_values($subjectNos);

            $programs = $aggregate['programs'];
            ksort($programs);

            $eIndex = implode('-', $names) . "-{$aggregate['method']}";

            $items[$eIndex] = [
                'blocks' => [],
                'method' => $aggregate['method'],
                'names' => $names,
                'organizations' => $aggregate['organizations'],
                'programs' => $programs,
                'subjectNos' => $subjectNos
            ];

            $blocks =& $items[$eIndex]['blocks'];

            foreach ($aggregate['blocks'] as $block) {
                $bIndex = "{$block['dow']}-{$block['startTime']}-{$block['endTime']}";
                $date   = $block['date'];

                if (empty($blocks[$bIndex])) {
                    unset($block['date']);
                    $blocks[$bIndex] = $block;
                } else {
                    $blocks[$bIndex]['minutes'] += $block['minutes'];
                }

                $blocks[$bIndex]['dates'][$date] = $block['minutes'];
                ksort($blocks[$bIndex]['dates']);
            }

            ksort($blocks);
        }

        ksort($items);

        return $items;
    }

    /**
     * Builds the array of parameters used for instance retrieval.
     * @return void
     */
    private function setConditions()
    {
        $conditions              = [];
        $conditions['date']      = Helpers\Terms::getStartDate($this->termID);
        $conditions['delta']     = false;
        $conditions['endDate']   = Helpers\Terms::getEndDate($this->termID);
        $conditions['interval']  = 'term';
        $conditions['my']        = false;
        $conditions['personIDs'] = [$this->personID];
        $conditions['startDate'] = $conditions['date'];
        $conditions['status']    = self::CURRENT_ITEMS;

        $this->conditions = $conditions;
    }

    /**
     * Sets program data.
     * @return void
     */
    private function setMethods()
    {
        $tag   = Application::getTag();
        $query = Database::getQuery();
        $query->select("code, name_$tag AS method")->from('#__organizer_methods')->where('relevant = 1');
        Database::setQuery($query);

        $methods = [];

        foreach (Database::loadAssocList() as $method) {
            $methods[$method['code']] = $method['method'];
        }

        ksort($methods);

        $this->methods = $methods;
    }

    /**
     * Creates workload entry items.
     * @return void
     */
    private function calculate()
    {
        $conditions = $this->conditions;
        $tag        = Application::getTag();
        $query      = Helpers\Instances::getInstanceQuery($this->conditions);
        $query->select('DISTINCT i.id AS instanceID, u.id AS unitID')
            ->select('b.id AS blockID, b.date, b.dow, b.startTime, b.endTime')
            ->select("e.id AS eventID, e.code, e.name_$tag AS event, e.subjectNo")
            ->select('m.code AS method')
            ->innerJoin('#__organizer_methods AS m ON m.id = i.methodID')
            ->order('b.date, b.startTime, b.endTime')
            ->where('ipe.roleID = 1')
            ->where('m.relevant = 1');
        Database::setQuery($query);
        $instances = Database::loadAssocList('instanceID');

        $query = Helpers\Instances::getInstanceQuery($this->conditions);
        $query->select("i.id AS instanceID, g.name_$tag AS 'group'")
            ->select("a.organizationID, o.shortName_$tag AS organization")
            ->select("p.name_$tag AS program, d.abbreviation AS degree")
            ->innerJoin('#__organizer_methods AS m ON m.id = i.methodID')
            ->leftJoin('#__organizer_programs AS p ON p.categoryID = g.categoryID')
            ->leftJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
            ->leftJoin('#__organizer_associations AS a ON a.programID = p.id')
            ->leftJoin('#__organizer_organizations AS o ON o.id = a.organizationID')
            ->where("b.date BETWEEN '{$conditions['startDate']}' AND '{$conditions['endDate']}'")
            ->order('b.date, b.startTime, b.endTime')
            ->where('ipe.roleID = 1')
            ->where('m.relevant = 1');
        Database::setQuery($query);

        $this->supplement($instances, Database::loadAssocList());
        $units      = $this->aggregateByUnit($instances);
        $aggregates = $this->aggregateByBlock($units);
        $this->aggregateByEvent($aggregates);
        $this->aggregateByDate($aggregates);
        $items = $this->itemize($aggregates);
        $this->structureOutliers($items);
        $this->structureRepeaters($items);

        $this->items = $items;
    }

    /**
     * Set dynamic data.
     * @return void
     */
    public function setUp()
    {
        $this->authorize();
        $this->setPrograms();
        $this->setMethods();
        $this->setConditions();
        $this->calculate();
    }

    /**
     * Sets program data.
     * @return void
     */
    private function setPrograms()
    {
        $tag   = Application::getTag();
        $query = Database::getQuery();
        $query->select("p.id, categoryID, p.degreeID, p.name_$tag AS program, fee, frequencyID, nc, special")
            ->select('d.abbreviation AS degree')
            ->from('#__organizer_programs AS p')
            ->innerJoin('#__organizer_degrees AS d ON d.id = p.degreeID')
            ->where('active = 1');
        Database::setQuery($query);

        $results = Database::loadAssocList();

        $programs = [];

        foreach ($results as &$program) {
            $organizationIDs = Helpers\Programs::getOrganizationIDs($program['id']);

            foreach (array_keys($organizations = array_flip($organizationIDs)) as $organizationID) {
                $organizations[$organizationID] = Helpers\Organizations::getShortName($organizationID);
            }

            asort($organizations);
            $program['organizations'] = $organizations;
            $index                    = "{$program['program']} ({$program['degree']})";
            $programs[$index]         = $program;
        }

        ksort($programs);

        $this->programs = $programs;
    }

    /**
     * Turns outlying event items into block event items.
     *
     * @param array $items
     *
     * @return void
     */
    private function structureOutliers(array &$items)
    {
        foreach ($items as $eIndex => $item) {
            $dates                     = [];
            $items[$eIndex]['items']   = [];
            $items[$eIndex]['minutes'] = 0;

            foreach ($item['blocks'] as $index => $block) {
                // Arbitrary cutoff for number of repetitions for a limited event
                if (count($block['dates']) < 3) {
                    foreach ($block['dates'] as $date => $minutes) {
                        $dates[$date] = ['endDate' => $date, 'minutes' => $minutes, 'startDate' => $date];
                    }

                    unset($items[$eIndex]['blocks'][$index]);
                }
            }

            ksort($dates);

            if ($dates) {
                $skip = [];

                foreach ($dates as $date => $data) {
                    $endDate  = $data['endDate'];
                    $tomorrow = date('Y-m-d', strtotime('+1 Day', strtotime($endDate)));

                    while (!empty($dates[$tomorrow])) {
                        $skip[$tomorrow] = $tomorrow;
                        $data['endDate'] = $tomorrow;
                        $data['minutes'] += $dates[$tomorrow]['minutes'];
                        unset($dates[$tomorrow]);
                        $tomorrow = date('Y-m-d', strtotime('+1 Day', strtotime($tomorrow)));
                    }

                    if (!in_array($date, $skip)) {
                        $dates[$date] = $data;
                    }
                }

                foreach ($dates as $data) {
                    $endDate                   = Helpers\Dates::formatDate($data['endDate']);
                    $startDate                 = Helpers\Dates::formatDate($data['startDate']);
                    $items[$eIndex]['minutes'] += $data['minutes'];
                    $dateDisplay               = $startDate !== $endDate ? "$startDate - $endDate" : $startDate;
                    $hoursDisplay              = ceil($data['minutes'] / 45) . ' ' . Helpers\Languages::_('ORGANIZER_HOURS_ABBR');
                    $items[$eIndex]['items'][] = "$dateDisplay $hoursDisplay";
                }
            }
        }
    }

    /**
     * Adds associated structure items to the instances results.
     *
     * @param array $instances the instances results
     * @param array $structure the structure items associated with the instance results
     *
     * @return void
     */
    private function supplement(array &$instances, array $structure)
    {
        foreach ($structure as $data) {
            if (empty($instances[$data['instanceID']])) {
                continue;
            }

            $organizations = empty($instances[$data['instanceID']]['organizations']) ?
                [] : $instances[$data['instanceID']]['organizations'];
            $programs      = empty($instances[$data['instanceID']]['programs']) ?
                [] : $instances[$data['instanceID']]['programs'];

            if ($data['organizationID']) {
                $organizations[$data['organizationID']] = $data['organization'];
            }

            // The form doesn't get specific about the individual declarative regulation => string keys and values
            $program = ($data['program'] and $data['degree']) ? "{$data['program']} ({$data['degree']})" : '';

            if (!empty($programs[$program])) {
                $programs[$program][$data['group']] = $data['group'];
                ksort($programs[$program]);
            } else {
                $programs[$program] = [$data['group'] => $data['group']];
            }

            $instances[$data['instanceID']]['organizations'] = $organizations;
            $instances[$data['instanceID']]['programs']      = $programs;
        }
    }

    /**
     * Turns outlying event items into block event items.
     *
     * @param array $items
     *
     * @return void
     */
    private function structureRepeaters(array &$items)
    {
        foreach ($items as $eIndex => $item) {
            foreach ($item['blocks'] as $block) {
                $minutes = array_sum($block['dates']);
                $hours   = ceil($minutes / 45);

                $items[$eIndex]['minutes'] += $minutes;

                $suffix       = strtoupper(date('l', strtotime("Sunday +{$block['dow']} days")));
                $day          = Helpers\Languages::_("ORGANIZER_$suffix");
                $hoursDisplay = $hours . ' ' . Helpers\Languages::_('ORGANIZER_HOURS_ABBR');
                $endTime      = Helpers\Dates::formatTime($block['endTime']);
                $startTime    = Helpers\Dates::formatTime($block['startTime']);

                $items[$eIndex]['items'][] = "$day $startTime-$endTime $hoursDisplay";
            }

            unset($items[$eIndex]['blocks']);
        }
    }
}
