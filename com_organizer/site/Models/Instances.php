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

use JDatabaseQuery;
use Joomla\CMS\Factory;
use Joomla\CMS\Form\Form;
use Organizer\Adapters\Queries\QueryMySQLi;
use Organizer\Helpers;
use Organizer\Helpers\Input;
use Organizer\Helpers\Instances as Helper;
use Organizer\Helpers\Languages;
use Organizer\Tables;

/**
 * Class retrieves information for a filtered set of instances.
 */
class Instances extends ListModel
{
    private const MONDAY = 1, TUESDAY = 2, WEDNESDAY = 3, THURSDAY = 4, FRIDAY = 5, SATURDAY = 6, SUNDAY = 7;

    public array $conditions = [];
    protected $defaultOrdering = 'name';
    protected $filter_fields = [
        'campusID',
        'categoryID',
        'groupID',
        'methodID',
        'organizationID',
        'personID',
        'roomID'
    ];
    public array $grid;
    public int $gridID;
    public array $holidays;
    public int $layout = Helper::LIST;
    public bool $noDate = false;

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        $session = Factory::getSession();
        $session->set('organizer.instance.item.referrer', '');
        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    public function filterFilterForm(Form $form)
    {
        parent::filterFilterForm($form);

        $params     = Input::getParams();
        $getLayout  = Input::getString('layout');
        $getSet     = ($getLayout and in_array($getLayout, ['grid', 'list']));
        $menuLayout = $params->get('layout');
        $menuSet    = (is_numeric($menuLayout) and in_array($menuLayout, [Helper::LIST, Helper::GRID]));

        // Layout set in the menu
        if ($getSet or $menuSet) {
            $form->removeField('layout', 'list');
        }

        if ($this->layout === Helper::LIST) {
            $form->removeField('gridID', 'list');
        } else {
            $form->removeField('interval', 'list');
            $form->removeField('limit', 'list');
        }

        if ($this->adminContext) {
            if (count(Helpers\Can::scheduleTheseOrganizations()) === 1) {
                $form->removeField('organizationID', 'filter');
                unset($this->filter_fields['organizationID']);
            }
        } elseif ($params->get('my')) {
            $form->removeField('campusID', 'filter');
            $form->removeField('categoryID', 'filter');
            $form->removeField('groupID', 'filter');
            $form->removeField('methodID', 'filter');
            $form->removeField('organizationID', 'filter');
            $form->removeField('personID', 'filter');
            $form->removeField('roomID', 'filter');
            $form->removeField('search', 'filter');
            $form->removeField('status', 'filter');
            $this->filter_fields = [];
        } else {
            if (!Helpers\Users::getID()) {
                $form->removeField('my', 'list');
            }

            if ($params->get('campusID')) {
                $form->removeField('campusID', 'filter');
                unset($this->filter_fields[array_search('campusID', $this->filter_fields)]);
            }

            if ($this->state->get('filter.eventID')) {
                $form->removeField('campusID', 'filter');
                $form->removeField('categoryID', 'filter');
                $form->removeField('organizationID', 'filter');
                $form->removeField('roomID', 'filter');
            }

            if ($params->get('organizationID') or Input::getInt('organizationID')) {
                $form->removeField('campusID', 'filter');
                $form->removeField('organizationID', 'filter');
                unset($this->filter_fields[array_search('organizationID', $this->filter_fields)]);
            } elseif (Input::getInt('categoryID')) {
                $form->removeField('campusID', 'filter');
                $form->removeField('organizationID', 'filter');
                $form->removeField('categoryID', 'filter');
                unset(
                    $this->filter_fields[array_search('organizationID', $this->filter_fields)],
                    $this->filter_fields[array_search('categoryID', $this->filter_fields)]
                );
            } elseif (Input::getInt('groupID')) {
                $form->removeField('campusID', 'filter');
                $form->removeField('organizationID', 'filter');
                $form->removeField('categoryID', 'filter');
                $form->removeField('groupID', 'filter');
                unset(
                    $this->filter_fields[array_search('organizationID', $this->filter_fields)],
                    $this->filter_fields[array_search('categoryID', $this->filter_fields)],
                    $this->filter_fields[array_search('groupID', $this->filter_fields)]
                );
            }

            $dow       = $params->get('dow');
            $endDate   = $params->get('endDate');
            $methodIDs = $params->get('methodIDs');
            $methodIDs = $methodIDs ? array_filter($methodIDs) : null;

            if ($dow or $endDate or $methodIDs) {
                $this->noDate = true;
                $form->removeField('date', 'list');
                $form->removeField('interval', 'list');

                if ($methodIDs) {
                    $form->removeField('methodID', 'filter');
                    unset($this->filter_fields[array_search('methodID', $this->filter_fields)]);
                }
            }
        }

        if (!$this->adminContext and $this->mobile) {
            $form->removeField('limit', 'list');
        }

        if ($this->state->get('list.interval') === 'quarter') {
            $form->removeField('date', 'list');
        }
    }

    /**
     * Standardizes date value retrieval across views and request methods.
     * @return string
     */
    private function getDate(): string
    {
        $app = Helpers\OrganizerHelper::getApplication();

        // Instances view
        $date = $app->getUserStateFromRequest("$this->context.list.date", "list_date", '', 'string');

        // Export view, GET, POST
        $date = Input::getString('date', $date);

        // Defaults to today
        return Helpers\Dates::standardizeDate($date);
    }

    /**
     * Standardizes interval value retrieval across views and request methods.
     * @return string
     */
    private function getInterval(): string
    {
        $app = Helpers\OrganizerHelper::getApplication();

        // Instances view
        $interval = $app->getUserStateFromRequest("$this->context.list.interval", "list_interval", '', 'string');

        // Export view, GET, POST
        return Input::getString('interval', $interval);
    }

    /**
     * @inheritDoc.
     */
    public function getItems(): array
    {
        $items = parent::getItems();

        // Prevents out of memory errors.
        if (count($items) >= 12500) {
            Helpers\OrganizerHelper::error(413);
        }

        $usedGrids = [];

        foreach ($items as $key => $instance) {
            $instance                       = Helper::getInstance($instance->id);
            $usedGrids[$instance['gridID']] = empty($usedGrids[$instance['gridID']]) ? 1 : $usedGrids[$instance['gridID']] + 1;
            Helper::fill($instance, $this->conditions);
            $items[$key] = (object) $instance;
        }

        if ($this->layout === Helper::GRID) {
            if (!$gridID = $this->state->get('list.gridID')) {
                if ($usedGrids) {
                    $gridID = array_search(max($usedGrids), $usedGrids);
                } else {
                    $gridID = Helpers\Grids::getDefault();
                }
            }

            $grid = new Tables\Grids();
            $grid->load($gridID);
            $this->grid   = json_decode($grid->grid, true);
            $this->gridID = $gridID;

            $this->holidays = Helpers\Holidays::getRelevant($this->conditions['startDate'], $this->conditions['endDate']);
        }

        return $items;
    }

    /**
     * @inheritDoc
     */
    protected function getListQuery(): JDatabaseQuery
    {
        /* @var QueryMySQLi $query */
        $query = Helper::getInstanceQuery($this->conditions);
        $query->select("DISTINCT i.id")->order('b.date, b.startTime, b.endTime');
        $this->setSearchFilter($query, ['e.name_de', 'e.name_en']);
        $this->setValueFilters($query, ['b.dow', 'i.methodID']);

        if ($this->state->get('filter.campusID')) {
            $query->innerJoin('#__organizer_rooms AS r ON r.id = ir.roomID')
                ->innerJoin('#__organizer_buildings AS bd ON bd.id = r.buildingID');
            $this->setCampusFilter($query, 'bd');
        }

        return $query;
    }

    /**
     * Creates a dynamic title for the instances view.
     * @return string
     */
    public function getTitle(): string
    {
        $params = Input::getParams();

        if ($params->get('show_page_heading') and $title = $params->get('page_title')) {
            return $title;
        }

        $methods   = '';
        $suffix    = '';
        $title     = $this->layout === Helper::GRID ? Languages::_('ORGANIZER_SCHEDULE') : Languages::_("ORGANIZER_INSTANCES");
        $methodIDs = $params->get('methodIDs') ?: Input::getIntCollection('methodID');

        if ($methodIDs and $methodIDs = array_filter($methodIDs)) {
            if (count($methodIDs) === 1) {
                $methods = Helpers\Methods::getPlural($methodIDs[0]);
            } else {
                $methods = [];

                foreach ($methodIDs as $methodID) {
                    $methods[] = Helpers\Methods::getPlural($methodID);
                }

                $lastName = array_pop($methods);
                $methods  = implode(', ', $methods) . " & $lastName";
            }
        }

        if ($my = (int) $this->state->get('list.my')) {
            $username = ($user = Helpers\Users::getUser() and $user->username) ? " ($user->username)" : '';

            if ($methods) {
                $title = Languages::_('ORGANIZER_MY') . ' ' . $methods;
            } else {
                $title = $my === Helper::BOOKMARKS ?
                    Languages::_("ORGANIZER_MY_INSTANCES") : Languages::_("ORGANIZER_MY_REGISTRATIONS");
            }
            $title .= $username;
        } else {
            // Replace the title
            if ($dow = $params->get('dow')) {
                switch ($dow) {
                    case self::MONDAY:
                        $title = Languages::_("ORGANIZER_MONDAY_INSTANCES");
                        break;
                    case self::TUESDAY:
                        $title = Languages::_("ORGANIZER_TUESDAY_INSTANCES");
                        break;
                    case self::WEDNESDAY:
                        $title = Languages::_("ORGANIZER_WEDNESDAY_INSTANCES");
                        break;
                    case self::THURSDAY:
                        $title = Languages::_("ORGANIZER_THURSDAY_INSTANCES");
                        break;
                    case self::FRIDAY:
                        $title = Languages::_("ORGANIZER_FRIDAY_INSTANCES");
                        break;
                    case self::SATURDAY:
                        $title = Languages::_("ORGANIZER_SATURDAY_INSTANCES");
                        break;
                    case self::SUNDAY:
                        $title = Languages::_("ORGANIZER_SUNDAY_INSTANCES");
                        break;
                }
            } elseif ($methods) {
                $title = $methods;
            }

            // Which resource
            if ($eventID = $this->state->get('filter.eventID')) {
                $suffix .= ': ' . Helpers\Events::getName($eventID);
            } elseif ($personID = $this->state->get('filter.personID')) {
                $suffix .= ': ' . Helpers\Persons::getDefaultName($personID);
            } elseif ($groupID = $this->state->get('filter.groupID')) {
                $suffix .= ': ' . Helpers\Groups::getFullName($groupID);
            } elseif ($categoryID = $this->state->get('filter.categoryID')) {
                $suffix .= ': ' . Helpers\Categories::getName($categoryID);
            } elseif ($organizationID = $params->get('organizationID', Input::getInt('organizationID'))) {
                $fullName  = Helpers\Organizations::getFullName($organizationID);
                $shortName = Helpers\Organizations::getShortName($organizationID);
                $name      = ($this->mobile or strlen($fullName) > 40) ? $shortName : $fullName;
                $suffix    .= ': ' . $name;
            } elseif ($campusID = $params->get('campusID')) {
                $suffix .= ': ' . Languages::_("ORGANIZER_CAMPUS") . ' ' . Helpers\Campuses::getName($campusID);
            }

            if ($roleID = Input::getInt('roleID')) {
                $plural = Helpers\Roles::getPlural($roleID);
                $suffix .= $suffix ? " - $plural" : ": $plural";
            } elseif ($instances = Input::getCMD('instances') and $instances === 'person') {
                $persons = Languages::_('ORGANIZER_PERSONS');
                $suffix  .= $suffix ? " - $persons" : ": $persons";
            }

        }

        return $title . $suffix;
    }

    /**
     * @inheritdoc
     */
    public function getTotal($idColumn = null)
    {
        return parent::getTotal('i.id');
    }

    /**
     * @inheritdoc
     */
    protected function populateState($ordering = null, $direction = null)
    {
        parent::populateState($ordering, $direction);

        $app         = Helpers\OrganizerHelper::getApplication();
        $conditions  = ['delta' => date('Y-m-d', strtotime('-14 days'))];
        $filterItems = Input::getFilterItems();
        $listItems   = Input::getListItems();
        $params      = Input::getParams();

        $fc = "$this->context.filter.";
        $fp = "filter_";
        $lc = "$this->context.list.";
        $lp = "list_";

        // What? Personal...
        $personal         = (int) $params->get('my');
        $personal         = ($personal or $app->getUserStateFromRequest("{$lc}my", "{$lp}my", 0, 'int'));
        $personal         = ($personal or Input::getInt('my'));
        $personal         = ($personal or (int) $listItems->get('my'));
        $conditions['my'] = $personal;
        $listItems->set('my', $personal);
        $this->state->set('list.my', $personal);

        if ($personal) {
            // I should be able to see my planned.
            $conditions['showUnpublished'] = true;
        } // or attribute/resource based.
        else {
            $campusID = $params->get('campusID', 0);
            $campusID = $app->getUserStateFromRequest("{$fc}campusID", "{$fp}campusID", $campusID, 'int');
            $campusID = Input::getInt('campusID', $campusID);

            if ($campusID) {
                $conditions['campusIDs'] = [$campusID];
                $filterItems->set('campusID', $campusID);
                $this->state->set('filter.campusID', $campusID);
            }

            $organizationID = 0;

            if ($this->adminContext) {
                // Empty would have already resulted in a redirect from the view authorization check.
                $authorized = Helpers\Can::scheduleTheseOrganizations();
                if (count($authorized) === 1) {
                    $organizationID = $authorized[0];
                }
            } else {
                $organizationID = $app->getUserStateFromRequest("{$fc}organizationID", "{$fp}organizationID", 0, 'int');
                $organizationID = Input::getInt('organizationID', $organizationID);
                $organizationID = $params->get('organizationID', $organizationID);
            }

            $byPerson   = false;
            $categoryID = $app->getUserStateFromRequest("{$fc}categoryID", "{$fp}categoryID", 0, 'int');
            $categoryID = Input::getInt('categoryID', $categoryID);
            $groupID    = $app->getUserStateFromRequest("{$fc}groupID", "{$fp}groupID", 0, 'int');
            $groupID    = Input::getInt('groupID', $groupID);

            $conditions['roleID'] = Input::getInt('roleID');

            if ($organizationID) {
                $conditions['organizationIDs'] = [$organizationID];
                $filterItems->set('organizationID', $organizationID);
                $this->state->set('filter.organizationID', $organizationID);
                Helper::setPublishingAccess($conditions);

                $instances = Input::getCMD('instances');
                if (Helpers\Can::view('organization', $organizationID) and $instances === 'person') {
                    $conditions['personIDs'] = Helpers\Organizations::getPersonIDs($organizationID);
                    $byPerson                = true;
                }
            } else {
                if ($categoryID) {
                    $organizationID                = Helpers\Categories::getOrganizationIDs($categoryID)[0];
                    $conditions['organizationIDs'] = [$organizationID];
                } elseif ($groupID) {
                    $categoryID                    = Helpers\Groups::getCategoryID($groupID);
                    $organizationID                = Helpers\Categories::getOrganizationIDs($categoryID)[0];
                    $conditions['organizationIDs'] = [$organizationID];
                }

                $conditions['showUnpublished'] = Helpers\Can::administrate();
            }

            if (!$byPerson) {
                if ($categoryID) {
                    $conditions['categoryIDs'] = [$categoryID];
                    $filterItems->set('categoryID', $categoryID);
                    $this->state->set('filter.categoryID', $categoryID);
                }
                if ($groupID) {
                    $conditions['groupIDs'] = [$groupID];
                    $filterItems->set('groupID', $groupID);
                    $this->state->set('filter.groupID', $groupID);
                }

                if ($eventID = Input::getInt('eventID')) {
                    $conditions['eventIDs'] = [$eventID];
                    $this->state->set('filter.eventID', $eventID);
                }

                $personID = $app->getUserStateFromRequest("{$fc}personID", "{$fp}personID", 0, 'int');
                if ($personID = Input::getInt('personID', $personID)) {
                    $personIDs = [$personID];
                    $userID    = Helpers\Users::getID();
                    Helper::filterPersonIDs($personIDs, $userID);

                    if ($personIDs) {
                        $conditions['personIDs'] = $personIDs;
                        $filterItems->set('personID', $personID);
                        $this->state->set('filter.personID', $personID);

                        if (empty($conditions['showUnpublished'])) {
                            $conditions['showUnpublished'] = Helpers\Persons::getIDByUserID($userID) === $personID;
                        }
                    } else {
                        // Unauthorized access to personal information.
                        Helpers\OrganizerHelper::error(403);
                    }
                }

                $roomID = $app->getUserStateFromRequest("{$fc}roomID", "{$fp}roomID", 0, 'int');
                if ($roomID = Input::getInt('roomID', $roomID)) {
                    $conditions['roomIDs'] = [$roomID];
                    $filterItems->set('roomID', $roomID);
                    $this->state->set('filter.roomID', $roomID);
                }
            }
        }

        $methodIDs = $params->get('methodIDs') ?: Input::getIntCollection('methodID');
        if ($methodIDs = array_filter($methodIDs)) {
            $conditions['methodIDs'] = $methodIDs;
            $filterItems->set('methodID', $methodIDs);
            $this->state->set('filter.methodID', $methodIDs);
        }

        // When/how
        $date    = date('Y-m-d');
        $endDate = $params->get('endDate');
        $status  = Helper::CURRENT;

        if ($dynamic = Helpers\OrganizerHelper::dynamic()) {
            $dow       = null;
            $startDate = null;
            $bound     = false;

            if ($instances = Input::getCMD('instances')) {
                $conditions['instances'] = $instances;
            }
        } else {
            $dow       = $params->get('dow');
            $methodIDs = array_filter($params->get('methodIDs'));
            $startDate = $params->get('startDate');
            $bound     = ($dow or $endDate or $methodIDs);
        }

        switch ($format = Input::getCMD('format')) {
            case 'ics':
                // When/how is fixed in this format
                $interval = 'half';
                $layout   = Helper::LIST;
                break;

            case 'json':
                // Always GET
                $date      = Input::getString('date', $date);
                $interval  = Input::getString('interval');
                $intervals = ['day', 'half', 'month', 'quarter', 'term', 'week'];
                $interval  = in_array($interval, $intervals) ? $interval : 'week';
                $layout    = Helper::LIST;
                break;

            case 'pdf':
                $conditions['separate'] = Input::getBool('separate');

                $date      = $this->getDate();
                $interval  = $bound ? 'half' : $this->getInterval();
                $intervals = ['half', 'month', 'quarter', 'term', 'week'];
                $interval  = in_array($interval, $intervals) ? $interval : 'week';
                $layout    = Helper::GRID;
                break;

            case 'xls':
                $date      = $this->getDate();
                $interval  = $bound ? 'half' : $this->getInterval();
                $intervals = ['day', 'half', 'month', 'quarter', 'term', 'week'];
                $interval  = in_array($interval, $intervals) ? $interval : 'week';
                $layout    = Helper::LIST;
                $status    = $app->getUserStateFromRequest("{$fc}status", "{$fp}status", Helper::CURRENT, 'int');
                break;

            case 'html':
            default:
                $date     = $this->getDate();
                $status   = $app->getUserStateFromRequest("{$fc}status", "{$fp}status", Helper::CURRENT, 'int');
                $status   = Input::getInt('status', $status);
                $statuses = [Helper::CHANGED, Helper::CURRENT, Helper::NEW, Helper::REMOVED];
                $status   = in_array($status, $statuses) ? $status : Helper::CURRENT;

                if ($dynamic) {
                    $sLayout = $app->getUserStateFromRequest("{$lc}layout", "{$lp}layout", Helper::LIST, 'int');
                    $gLayout = strpos(strtolower(Input::getString('layout')), 'grid') === 0;
                    $layout  = ($sLayout or $gLayout) ? Helper::GRID : Helper::LIST;

                    if ($layout === Helper::GRID) {
                        $interval = $this->mobile ? 'day' : 'week';
                    } else {
                        $interval  = $this->getInterval();
                        $intervals = ['day', 'month', 'quarter', 'term', 'week'];
                        $interval  = in_array($interval, $intervals) ? $interval : 'day';
                    }
                } else {
                    $layout = (int) $params->get('layout');
                    $layout = in_array($layout, [Helper::LIST, Helper::GRID]) ? $layout : Helper::LIST;

                    if ($layout === Helper::GRID) {
                        $interval = $this->mobile ? 'day' : 'week';

                        // Parameter bleed can potentially cause a 0-division error in the Joomla ListModel here.
                        $this->state->set('list.start', 0);
                    } else {

                        // Menu constricted list conditions
                        if ($bound) {
                            $date     = ($startDate and $startDate > $date) ? $startDate : $date;
                            $interval = 'half';

                            if ($endDate) {
                                $listItems->set('endDate', $endDate);
                                $this->state->set('list.endDate', $endDate);
                            }

                            if ($dow) {
                                $filterItems->set('dow', $dow);
                                $this->state->set('filter.dow', $dow);
                            }
                        } else {
                            $sInterval = $app->getUserStateFromRequest("{$lc}interval", "{$lp}interval", '', 'string');
                            $interval  = Input::getString('interval', $sInterval);
                            $intervals = ['day', 'month', 'quarter', 'term', 'week'];
                            $interval  = in_array($interval, $intervals) ? $interval : 'day';
                        }
                    }
                }

                break;
        }

        $conditions['date']     = $date;
        $conditions['interval'] = $interval;
        $listItems->set('date', $date);
        $listItems->set('interval', $interval);
        $this->state->set('list.date', $date);
        $this->state->set('list.interval', $interval);
        Helper::setDates($conditions);

        if ($endDate) {
            $conditions['endDate'] = $endDate;
        }

        $this->layout = $layout;
        $listItems->set('layout', $layout);
        $this->state->set('list.layout', $layout);

        $conditions['status'] = $status;
        $filterItems->set('status', $status);
        $this->state->set('filter.status', $status);

        // No pagination or implicit restriction to a day/week
        if ($format !== 'html' or $layout === Helper::GRID) {
            $this->state->set('list.limit', 0);
        }

        $this->conditions = $conditions;
    }
}