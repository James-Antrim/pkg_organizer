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

use Joomla\CMS\Form\{Form};
use THM\Organizer\Adapters\{Application, Database as DB, FormFactory, Input, MVCFactory, Text};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\{Can, Dates, Instances, Organizations, Persons, Terms};

/**
 * Class retrieves information for a filtered set of participants.
 */
class Workload extends FormModel
{
    private const CURRENT_ITEMS = 1;

    public int $bachelors = 0;
    private array $conditions;
    public int $doctors = 0;

    public array $items;
    public int $masters = 0;
    public array $methods;
    public int $organizationID = 0;
    private int $personID = 0;
    public int $projects = 0;
    public array $programs;

    /** @inheritDoc */
    public function __construct($config, MVCFactory $factory, FormFactory $formFactory)
    {
        // Integrated authorization
        if (Can::basic() === false) {
            Application::error(401);
        }

        $myPersonID     = Persons::getIDByUserID();
        $organizationID = Input::getInt('organizationID');
        $personID       = Input::getInt('personID');

        if ($authOIDs = Organizations::manageableIDs()) {
            if ($organizationID) {
                if (in_array($organizationID, $authOIDs)) {
                    $this->organizationID = $organizationID;
                    Input::set('organizationID', $this->organizationID);
                }
                else {
                    Application::error(403);
                }
            }
            // Default
            else {
                $this->organizationID = reset($authOIDs);
                Input::set('organizationID', $this->organizationID);
            }

            // Persons can only be selected in an organization context.
            if ($personID) {
                if (in_array($this->organizationID, Persons::organizationIDs($personID)) or $personID === $myPersonID) {
                    $this->personID = $personID;
                    Input::set('personID', $this->personID);
                }
                else {
                    Application::error(403);
                }
            }
        }
        // Default with no authorization
        elseif ($myPersonID) {
            $this->personID = $myPersonID;
            Input::set('personID', $this->personID);
        }
        else {
            Application::error(403);
        }

        if (Input::format() === 'xls' and !$this->personID) {
            Application::error(400);
        }

        $this->programs();

        parent::__construct($config, $factory, $formFactory);
    }

    /**
     * Aggregates by concurrent blocks.
     *
     * @param   array  $units
     *
     * @return array[]
     */
    private function byBlock(array $units): array
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
     * @param   array  &$items
     *
     * @return void
     */
    private function byDate(array &$items): void
    {
        $count = count($items);

        for ($index = 0; $index < $count; $index++) {
            $blocks = [];

            foreach ($items[$index]['blocks'] as $block) {
                $block['minutes'] = ceil((strtotime($block['endTime']) - strtotime($block['startTime'])) / 60);

                if (!empty($blocks[$block['date']])) {
                    $block['endTime']   = max($block['endTime'], $blocks[$block['date']]['endTime']);
                    $block['minutes']   += $blocks[$block['date']]['minutes'];
                    $block['startTime'] = min($block['startTime'], $blocks[$block['date']]['startTime']);
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
     * @param   array  $items
     *
     * @return void
     */
    private function byEvent(array &$items): void
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
     * @param   array  $instances  the instances to be aggregated
     *
     * @return array[]
     */
    private function byUnit(array $instances): array
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
                $this->doctors = $this->doctors + 1;
                unset($instances[$instanceID]);
                continue;
            }

            if ($instance['code'] === 'KOL.M') {
                $this->masters = $this->masters + 1;
                unset($instances[$instanceID]);
                continue;
            }

            if ($instance['code'] === 'KOL.P') {
                $this->projects = $this->projects + 1;
                unset($instances[$instanceID]);
                continue;
            }

            if (empty($units[$instance['unitID']])) {
                $units[$instance['unitID']] = [
                    'blocks'        => [],
                    'events'        => [],
                    'method'        => $instance['method'],
                    'organizations' => $instance['organizations'],
                    'programs'      => $instance['programs']
                ];
            }

            $unit =& $units[$instance['unitID']];

            if (empty($unit['blocks'][$instance['blockID']])) {
                $unit['blocks'][$instance['blockID']] = [
                    'date'      => $instance['date'],
                    'dow'       => $instance['dow'],
                    'endTime'   => date('H:i:s', strtotime('+1 minute', strtotime($instance['endTime']))),
                    'startTime' => $instance['startTime']
                ];
            }

            if (empty($unit['events'][$instance['eventID']])) {
                $unit['events'][$instance['eventID']] = [
                    'code'      => $instance['code'],
                    'name'      => $instance['event'],
                    'subjectNo' => $instance['subjectNo']
                ];
            }

            foreach ($instance['organizations'] as $organizationID => $shortName) {
                $unit['organizations'][$organizationID] = $shortName;
            }
        }

        // Replace the actual unit ids which complicate iteration.
        return array_values($units);
    }

    /**
     * Creates workload entry items.
     *
     * @return void
     */
    private function calculate(): void
    {
        $conditions = $this->conditions;
        $tag        = Application::tag();

        $aliased     = DB::qn(['b.id', 'e.id', "e.name_$tag", 'm.code', 'u.id'],
            ['blockID', 'eventID', 'event', 'method', 'unitID']);
        $mConditions = DB::qc('m.id', 'i.methodID');
        $mTable      = DB::qn('#__organizer_methods', 'm');
        $order       = DB::qn(['b.date', 'b.startTime', 'b.endTime']);
        $restriction = DB::qcs([['ipe.roleID', 1], ['m.relevant', 1]]);
        $selected    = DB::qn(['b.date', 'b.dow', 'b.endTime', 'b.startTime', 'e.code', 'e.subjectNo']);

        $query = Instances::getInstanceQuery($this->conditions);
        $query->select(array_merge(['DISTINCT ' . DB::qn('i.id', 'instanceID')], $aliased, $selected))
            ->innerJoin($mTable, $mConditions)
            ->order($order)
            ->where($restriction);
        DB::set($query);
        $instances = DB::arrays('instanceID');

        $aliased = DB::qn(
            ['d.abbreviation', "g.name_$tag", 'i.id', "o.shortName_$tag", "p.name_$tag"],
            ['degree', 'group', 'instanceID', 'organization', 'program']
        );

        $query = Instances::getInstanceQuery($this->conditions);
        $query->select(array_merge(['a.organizationID'], $aliased))
            ->innerJoin($mTable, $mConditions)
            ->leftJoin(DB::qn('#__organizer_programs', 'p'), DB::qc('p.categoryID', 'g.categoryID'))
            ->leftJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('d.id', 'p.degreeID'))
            ->leftJoin(DB::qn('#__organizer_associations', 'a'), DB::qc('a.programID', 'p.id'))
            ->leftJoin(DB::qn('#__organizer_organizations', 'o'), DB::qc('o.id', 'a.organizationID'))
            ->order($order)
            ->where($restriction);
        Dates::betweenValues($query, 'b.date', $conditions['startDate'], $conditions['endDate']);
        DB::set($query);

        $this->supplement($instances, DB::arrays());
        $units      = $this->byUnit($instances);
        $aggregates = $this->byBlock($units);
        $this->byEvent($aggregates);
        $this->byDate($aggregates);
        $items = $this->itemize($aggregates);
        $this->structureOutliers($items);
        $this->structureRepeaters($items);

        $this->items = $items;
    }

    /**
     * Builds the array of parameters used for instance retrieval.
     *
     * @return void
     */
    private function conditions(): void
    {
        $termID = Input::getInt('termID', Terms::currentID());

        $conditions              = [];
        $conditions['date']      = Terms::startDate($termID);
        $conditions['delta']     = false;
        $conditions['endDate']   = Terms::endDate($termID);
        $conditions['interval']  = 'term';
        $conditions['my']        = false;
        $conditions['personIDs'] = [$this->personID];
        $conditions['startDate'] = $conditions['date'];
        $conditions['status']    = self::CURRENT_ITEMS;

        $this->conditions = $conditions;
    }

    /** @inheritDoc */
    protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = null): Form
    {
        $options['load_data'] = true;

        $form = parent::loadForm($name, $source, $options, $clear, $xpath);

        if (empty($this->organizationID)) {
            $form->removeField('organizationID');
        }

        return $form;
    }

    /** @inheritDoc */
    protected function loadFormData(): array
    {
        return [
            'organizationID' => $this->organizationID,
            'personID'       => $this->personID,
            'termID'         => Input::getInt('termID', Terms::currentID()),
            'weeks'          => Input::getInt('weeks', 13)
        ];
    }

    /**
     * Turns aggregates into itemized events.
     *
     * @param   array  $aggregates
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
                'blocks'        => [],
                'method'        => $aggregate['method'],
                'names'         => $names,
                'organizations' => $aggregate['organizations'],
                'programs'      => $programs,
                'subjectNos'    => $subjectNos
            ];

            $blocks =& $items[$eIndex]['blocks'];

            foreach ($aggregate['blocks'] as $block) {
                $bIndex = "{$block['dow']}-{$block['startTime']}-{$block['endTime']}";
                $date   = $block['date'];

                if (empty($blocks[$bIndex])) {
                    unset($block['date']);
                    $blocks[$bIndex] = $block;
                }
                else {
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
     * Sets program data.
     *
     * @return void
     */
    private function methods(): void
    {
        $tag   = Application::tag();
        $query = DB::query();
        $query->select([DB::qn('code'), DB::qn("name_$tag", 'method')])
            ->from(DB::qn('#__organizer_methods'))
            ->where(DB::qc('relevant', 1));
        DB::set($query);

        $methods = [];

        foreach (DB::arrays() as $method) {
            $methods[$method['code']] = $method['method'];
        }

        ksort($methods);

        $this->methods = $methods;
    }

    /**
     * Set dynamic data.
     *
     * @return void
     */
    public function setUp(): void
    {
        $this->programs();
        $this->methods();
        $this->conditions();
        $this->calculate();
    }

    /**
     * Sets program data.
     *
     * @return void
     */
    private function programs(): void
    {
        $aliased = DB::qn(['p.name_' . Application::tag(), 'd.abbreviation'], ['program', 'degree']);
        $select  = DB::qn(['p.id', 'categoryID', 'p.degreeID', 'fee', 'frequencyID', 'nc', 'special']);
        $query   = DB::query();
        $query->select(array_merge($select, $aliased))
            ->from(DB::qn('#__organizer_programs', 'p'))
            ->innerJoin(DB::qn('#__organizer_degrees', 'd'), DB::qc('d.id', 'p.degreeID'))
            ->where(DB::qc('active', 1));
        DB::set($query);

        $results = DB::arrays();

        $programs = [];

        foreach ($results as &$program) {
            $organizationIDs = Helpers\Programs::organizationIDs($program['id']);

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
     * @param   array  $items
     *
     * @return void
     */
    private function structureOutliers(array &$items): void
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
                    $endDate                   = Dates::formatDate($data['endDate']);
                    $startDate                 = Dates::formatDate($data['startDate']);
                    $items[$eIndex]['minutes'] += $data['minutes'];
                    $dateDisplay               = $startDate !== $endDate ? "$startDate - $endDate" : $startDate;
                    $hoursDisplay              = ceil($data['minutes'] / 45) . ' ' . Text::_('ORGANIZER_HOURS_ABBR');
                    $items[$eIndex]['items'][] = "$dateDisplay $hoursDisplay";
                }
            }
        }
    }

    /**
     * Adds associated structure items to the instances results.
     *
     * @param   array  $instances  the instances
     * @param   array  $structure  the structure items associated with the instance results
     *
     * @return void
     */
    private function supplement(array &$instances, array $structure): void
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
            }
            else {
                $programs[$program] = [$data['group'] => $data['group']];
            }

            $instances[$data['instanceID']]['organizations'] = $organizations;
            $instances[$data['instanceID']]['programs']      = $programs;
        }

        foreach ($instances as &$instance) {
            $instance['organizations'] = empty($instance['organizations']) ? [] : $instance['organizations'];
            $instance['programs']      = empty($instance['programs']) ? [] : $instance['programs'];
        }
    }

    /**
     * Turns outlying event items into block event items.
     *
     * @param   array  $items
     *
     * @return void
     */
    private function structureRepeaters(array &$items): void
    {
        foreach ($items as $eIndex => $item) {
            foreach ($item['blocks'] as $block) {
                $minutes = array_sum($block['dates']);
                $hours   = ceil($minutes / 45);

                $items[$eIndex]['minutes'] += $minutes;

                $suffix       = strtoupper(date('l', strtotime("Sunday +{$block['dow']} days")));
                $day          = Text::_("ORGANIZER_$suffix");
                $hoursDisplay = $hours . ' ' . Text::_('ORGANIZER_HOURS_ABBR');
                $endTime      = Dates::formatTime($block['endTime']);
                $startTime    = Dates::formatTime($block['startTime']);

                $items[$eIndex]['items'][] = "$day $startTime-$endTime $hoursDisplay";
            }

            unset($items[$eIndex]['blocks']);
        }
    }
}
