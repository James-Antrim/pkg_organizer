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
use THM\Organizer\Adapters\{Application, HTML, Input, Text, Toolbar};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Can;
use THM\Organizer\Helpers\Users;

/**
 * Class which loads data into the view output context
 */
class Courses extends ListView
{
    private bool $preparatory;

    private bool $manages = false;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $structure = [
            'name'         => 'link',
            'campus'       => 'value',
            'dates'        => 'value',
            'courseStatus' => 'value'
        ];

        if (Helpers\Can::scheduleTheseOrganizations() or Helpers\Can::manage('courses')) {
            $this->manages = true;
            $structure     = ['checkbox' => ''] + $structure + ['registrationStatus' => 'value'];
            unset($structure['registrationStatus']);
        }
        else {
            $structure = $structure + ['registrationStatus' => 'link'];
        }

        $this->rowStructure = $structure;

        $getPrep           = Input::getBool('preparatory');
        $menuPrep          = Input::getBool('onlyPrepCourses');
        $this->preparatory = ($getPrep or $menuPrep);
    }

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $resourceName = '';
        if (!Application::backend() and $this->preparatory) {
            $resourceName .= Text::_('PREP_COURSES');
            if ($campusID = $this->state->get('filter.campusID', 0)) {
                $resourceName .= ' ' . Text::_('CAMPUS') . ' ' . Helpers\Campuses::getName($campusID);
            }
        }

        $this->setTitle('COURSES', $resourceName);

        if (!Users::getID()) {
            return;
        }

        $toolbar = Toolbar::getInstance();

        if ($this->manages) {
            $toolbar->standardButton('edit', Text::_('EDIT'), 'Course.edit', true);
            $toolbar->appendButton(
                'NewTab',
                'users',
                Text::_('PARTICIPANTS'),
                'courses.participants',
                true
            );

            $toolbar->delete('Courses.delete')->message(Text::_('DELETE_CONFIRM'));

            return;
        }

        if (!Application::backend()) {
            if (Helpers\Participants::exists()) {
                $toolbar->standardButton('participant', Text::_('PROFILE_EDIT'), 'Participant.edit')->icon('fa fa-address-card');
            }
            else {
                $toolbar->standardButton('participant', Text::_('PROFILE_NEW'), 'Participant.edit')->icon('fa fa-user-plus');
            }
        }

        if (Application::backend() and Can::administrate()) {
            $toolbar = Toolbar::getInstance();
            $toolbar->preferences('com_organizer');
        }
    }

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (!Application::backend()) {
            return;
        }

        if (!Helpers\Can::scheduleTheseOrganizations()) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(): void
    {
        $url = Uri::base() . '?option=com_organizer';
        $url .= Application::backend() ? '&view=course_edit&id=' : '&view=course_item&id=';

        $structuredItems = [];

        $today  = Helpers\Dates::standardizeDate();
        $userID = Users::getID();

        foreach ($this->items as $course) {
            $campusID   = (int) $course->campusID;
            $campusName = Helpers\Campuses::getName($campusID);
            $pin        = Application::backend() ? '' : ' ' . Helpers\Campuses::getPin($campusID);

            $course->campus = $campusName . $pin;

            $course->dates = Helpers\Courses::getDateDisplay($course->id);

            $expired = $course->endDate < $today;
            $ongoing = ($course->startDate <= $today and !$expired);

            if ($course->deadline) {
                $deadline = date('Y-m-d', strtotime("-$course->deadline Days", strtotime($course->startDate)));
            }
            else {
                $deadline = $course->startDate;
            }

            $closed   = (!$expired and !$ongoing and $deadline <= $today);
            $deadline = Helpers\Dates::formatDate($deadline);

            $full   = $course->participants >= $course->maxParticipants;
            $ninety = (!$full and ($course->participants / (int) $course->maxParticipants) >= .9);

            if ($expired) {
                $attributes = ['class' => 'status-display center grey'];

                $course->courseStatus = [
                    'attributes' => $attributes,
                    'value'      => Text::_('EXPIRED')
                ];

                if (!$this->manages) {
                    $course->registrationStatus = [
                        'attributes' => $attributes,
                        'value'      => Text::_('DEADLINE_EXPIRED_SHORT')
                    ];
                }
            }
            else {
                $class                = 'status-display center hasTip';
                $course->courseStatus = [];
                $capacityText         = Text::_('PARTICIPANTS');
                $capacityText         .= ": $course->participants / $course->maxParticipants<br>";

                if ($ongoing) {
                    $courseAttributes = [
                        'class' => $class . ' red',
                        'title' => Text::_('COURSE_ONGOING')
                    ];
                }
                elseif ($closed) {
                    $courseAttributes = [
                        'class' => $class . ' yellow',
                        'title' => Text::_('COURSE_CLOSED')
                    ];
                }
                elseif ($full) {
                    $courseAttributes = ['class' => $class . ' red', 'title' => Text::_('COURSE_FULL')];
                }
                elseif ($ninety) {
                    $courseAttributes = [
                        'class' => $class . ' yellow',
                        'title' => Text::_('COURSE_LIMITED')
                    ];
                }
                else {
                    $courseAttributes = [
                        'class' => $class . ' green',
                        'title' => Text::_('COURSE_OPEN')
                    ];
                }

                $course->courseStatus['attributes'] = $courseAttributes;

                if ($ongoing or $closed) {
                    $courseText = Text::_('DEADLINE_EXPIRED_SHORT');
                }
                else {
                    $courseText = Text::sprintf('DEADLINE_TEXT_SHORT', $deadline);
                }

                $course->courseStatus['value'] = $capacityText . $courseText;

                if (!$this->manages) {
                    if ($userID) {
                        if ($course->registered) {
                            $course->registrationStatus = [
                                'attributes' => ['class' => 'status-display center green'],
                                'value'      => Text::_('REGISTERED')
                            ];
                        }
                        else {
                            $color                      = ($ongoing or $closed) ? 'red' : 'yellow';
                            $course->registrationStatus = [
                                'attributes' => ['class' => "status-display center $color"],
                                'value'      => Text::_('NOT_REGISTERED')
                            ];
                        }
                    }
                    else {
                        $course->registrationStatus = [
                            'attributes' => ['class' => 'status-display center grey'],
                            'value'      => Text::_('NOT_LOGGED_IN')
                        ];
                    }
                }
            }

            $index = "$course->startDate $course->name $campusName";

            $structuredItems[$index] = $this->completeItem($index, $course, $url . $course->id);
        }

        $this->items = $structuredItems;
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
            'name'         => HTML::sort('NAME', 'name', $direction, $ordering),
            'campus'       => Text::_('CAMPUS'),
            'dates'        => HTML::sort('DATES', 'dates', $direction, $ordering),
            'courseStatus' => [
                'attributes' => ['class' => 'center'],
                'value'      => Text::_('COURSE_STATUS')
            ]
        ];

        if ($this->manages) {
            $headers = ['checkbox' => ''] + $headers;
        }
        else {
            $headers = $headers + [
                    'registrationStatus' => [
                        'attributes' => ['class' => 'center'],
                        'value'      => Text::_('REGISTRATION_STATUS')
                    ]
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

        if (!Users::getID()) {
            $currentURL       = Uri::getInstance()->toString() . '#login-anchor';
            $this->supplement .= '<div class="tbox-yellow">';
            $this->supplement .= Text::sprintf('COURSE_LOGIN_WARNING', $currentURL);
            $this->supplement .= '</div>';
        }
    }
}
