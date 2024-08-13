<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use stdClass;
use THM\Organizer\Adapters\{Application, HTML, Input, Text, Toolbar, User};
use THM\Organizer\Buttons\FormTarget;
use THM\Organizer\Helpers\{Campuses, Can, Courses as Helper, Dates, Organizations, Participants};
use THM\Organizer\Layouts\HTML\ListItem;

/** @inheritDoc */
class Courses extends ListView
{
    private bool $preparatory;

    private bool $manages = false;

    /** @inheritDoc */
    public function __construct($config = [])
    {
        $this->toDo[] = 'Simulate front-end and adjust output.';
        $this->toDo[] = 'Check the non-edit display after squaring away the edit display. (Temp edit to ListView)';

        parent::__construct($config);

        // GET or menu item settings
        $this->preparatory = (Input::getBool('preparatory') or Input::getBool('onlyPrepCourses'));
    }

    /** @inheritDoc */
    protected function addToolBar(bool $delete = true): void
    {
        $resourceName = '';
        if (!Application::backend() and $this->preparatory) {
            $resourceName .= Text::_('PREP_COURSES');
            if ($campusID = $this->state->get('filter.campusID', 0)) {
                $resourceName .= ' ' . Text::_('CAMPUS') . ' ' . Campuses::name($campusID);
            }
        }

        $this->setTitle('COURSES', $resourceName);

        if (!User::id()) {
            return;
        }

        $toolbar = Toolbar::getInstance();

        if ($this->manages) {
            $button = new FormTarget('participants', Text::_('PARTICIPANTS'));
            $button->icon('fa fa-users')->listCheck(true)->task('Courses.participants');
            $toolbar->appendButton($button);

            $this->addDelete();

            return;
        }

        if (!Application::backend()) {
            if (Participants::exists()) {
                $toolbar->standardButton('participant', Text::_('EDIT_PROFILE'), 'Participant.edit')->icon('fa fa-address-card');
            }
            else {
                $toolbar->standardButton('participant', Text::_('NEW_PROFILE'), 'Participant.edit')->icon('fa fa-user-plus');
            }
        }

        if (Application::backend() and Can::administrate()) {
            $toolbar = Toolbar::getInstance();
            $toolbar->preferences('com_organizer');
        }
    }

    /** @inheritDoc */
    protected function authorize(): void
    {
        if (Application::backend() and !Organizations::schedulableIDs()) {
            Application::error(403);
        }
    }

    /** @inheritDoc */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $backend = Application::backend();
        list($today, $userID) = $options;

        if ($item->access) {
            $this->manages = true;
        }

        $campusID   = (int) $item->campusID;
        $pin        = $backend ? '' : ' ' . Campuses::getPin($campusID);
        $item->campus = Campuses::name($campusID) . $pin;

        $item->dates = Helper::displayDate($item->id);

        $expired = $item->endDate < $today;
        $ongoing = ($item->startDate <= $today and !$expired);

        if ($item->deadline) {
            $deadline = date('Y-m-d', strtotime("-$item->deadline Days", strtotime($item->startDate)));
        }
        else {
            $deadline = $item->startDate;
        }

        $closed   = (!$expired and !$ongoing and $deadline <= $today);
        $deadline = Dates::formatDate($deadline);

        $full   = $item->participants >= $item->maxParticipants;
        $ninety = (!$full and ($item->participants / (int) $item->maxParticipants) >= .9);

        if ($expired) {
            $attributes = ['class' => 'status-display center grey'];

            $item->courseStatus = [
                'attributes' => $attributes,
                'value'      => Text::_('EXPIRED')
            ];

            if (!$this->manages) {
                $item->registrationStatus = [
                    'attributes' => $attributes,
                    'value'      => Text::_('DEADLINE_EXPIRED_SHORT')
                ];
            }
        }
        else {
            $class                = 'status-display center hasTip';
            $item->courseStatus = [];
            $capacityText         = Text::_('PARTICIPANTS');
            $capacityText         .= ": $item->participants / $item->maxParticipants<br>";

            if ($ongoing) {
                $courseAttributes = [
                    'class' => $class . ' bg-danger',
                    'title' => Text::_('COURSE_ONGOING')
                ];
            }
            elseif ($closed) {
                $courseAttributes = [
                    'class' => $class . ' bg-warning',
                    'title' => Text::_('COURSE_CLOSED')
                ];
            }
            elseif ($full) {
                $courseAttributes = ['class' => $class . ' bg-danger', 'title' => Text::_('COURSE_FULL')];
            }
            elseif ($ninety) {
                $courseAttributes = [
                    'class' => $class . ' bg-warning',
                    'title' => Text::_('COURSE_LIMITED')
                ];
            }
            else {
                $courseAttributes = [
                    'class' => $class . ' bg-success',
                    'title' => Text::_('COURSE_OPEN')
                ];
            }

            $item->courseStatus['properties'] = $courseAttributes;

            if ($ongoing or $closed) {
                $courseText = Text::_('DEADLINE_EXPIRED_SHORT');
            }
            else {
                $courseText = Text::sprintf('DEADLINE_TEXT_SHORT', $deadline);
            }

            $item->courseStatus['value'] = $capacityText . $courseText;

            if (!$this->manages) {
                if ($userID) {
                    if ($item->registered) {
                        $item->registrationStatus = [
                            'properties' => ['class' => 'status-display center bg-success'],
                            'value'      => Text::_('REGISTERED')
                        ];
                    }
                    else {
                        $color                      = ($ongoing or $closed) ? 'bg-danger' : 'bg-warning';
                        $item->registrationStatus = [
                            'properties' => ['class' => "status-display center $color"],
                            'value'      => Text::_('NOT_REGISTERED')
                        ];
                    }
                }
                else {
                    $item->registrationStatus = [
                        'properties' => ['class' => 'status-display center bg-secondary'],
                        'value'      => Text::_('NOT_LOGGED_IN')
                    ];
                }
            }
        }
    }

    /** @inheritDoc */
    protected function completeItems(array $options = []): void
    {
        parent::completeItems(['today' => Dates::standardize(), 'userID' => User::id()]);
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        $params = Input::getParams();

        if ($params->get('onlyPrepCourses')) {
            $this->empty = Text::_('PREP_COURSE_PLANNING_INCOMPLETE');
        }

        parent::display($tpl);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $headers = [
            'check'        => ['type' => 'check'],
            'name'         => [
                'link'       => Application::backend() ? ListItem::DIRECT : ListItem::TAB,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, $ordering),
                'type'       => 'text'
            ],
            'campus'       => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('CAMPUS'),
                'type'       => 'text'
            ],
            'dates'        => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('DATES', 'dates', $direction, $ordering),
                'type'       => 'text'
            ],
            'courseStatus' => [
                'properties' => ['class' => 'w-10 d-md-table-cell text-center', 'scope' => 'col'],
                'title'      => Text::_('COURSE_STATUS'),
                'type'       => 'value'
            ]
        ];

        if (!$this->manages) {
            $headers['registrationStatus'] = [
                'properties' => ['class' => 'w-10 d-md-table-cell text-center', 'scope' => 'col'],
                'title'      => Text::_('REGISTRATION_STATUS'),
                'type'       => 'value'
            ];
        }

        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    protected function setSupplement(): void
    {
        $this->supplement = '';

        if ($this->preparatory) {
            $this->supplement .= '<div>' . Text::_('PREP_COURSE_SUPPLEMENT') . '</div>';
        }

        if (!User::id()) {
            $currentURL       = Uri::getInstance()->toString() . '#login-anchor';
            $this->supplement .= '<div class="alert alert-warning">';
            $this->supplement .= Text::sprintf('COURSE_LOGIN_WARNING', $currentURL);
            $this->supplement .= '</div>';
        }
    }
}
