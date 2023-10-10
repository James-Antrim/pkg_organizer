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
use THM\Organizer\Adapters\{Application, Input, Toolbar};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Languages;

/**
 * Class which loads data into the view output context
 */
class Courses extends ListView
{
    private $preparatory;

    private $manages = false;

    /**
     * @inheritdoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $structure = [
            'name' => 'link',
            'campus' => 'value',
            'dates' => 'value',
            'courseStatus' => 'value'
        ];

        if (Helpers\Can::scheduleTheseOrganizations() or Helpers\Can::manage('courses')) {
            $this->manages = true;
            $structure     = ['checkbox' => ''] + $structure + ['registrationStatus' => 'value'];
            unset($structure['registrationStatus']);
        } else {
            $structure = $structure + ['registrationStatus' => 'link'];
        }

        $this->rowStructure = $structure;

        $getPrep           = Input::getBool('preparatory', false);
        $menuPrep          = Input::getBool('onlyPrepCourses', false);
        $this->preparatory = ($getPrep or $menuPrep);
    }

    /**
     * @inheritDoc
     */
    protected function addSupplement()
    {
        $this->supplement = '';

        if ($this->preparatory) {
            $this->supplement .= '<div>' . Languages::_('ORGANIZER_PREP_COURSE_SUPPLEMENT') . '</div>';
        }

        if (!Helpers\Users::getID()) {
            $currentURL       = Uri::getInstance()->toString() . '#login-anchor';
            $this->supplement .= '<div class="tbox-yellow">';
            $this->supplement .= sprintf(Languages::_('ORGANIZER_COURSE_LOGIN_WARNING'), $currentURL);
            $this->supplement .= '</div>';
        }
    }

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true)
    {
        $resourceName = '';
        if (!$this->adminContext and $this->preparatory) {
            $resourceName .= Languages::_('ORGANIZER_PREP_COURSES');
            if ($campusID = $this->state->get('filter.campusID', 0)) {
                $resourceName .= ' ' . Languages::_('ORGANIZER_CAMPUS') . ' ' . Helpers\Campuses::getName($campusID);
            }
        }

        $this->setTitle('ORGANIZER_COURSES', $resourceName);

        if (Helpers\Users::getID()) {
            $toolbar = Toolbar::getInstance();
            if (!$this->adminContext and !$this->manages) {
                if (Helpers\Participants::exists()) {
                    $toolbar->appendButton(
                        'Standard',
                        'vcard',
                        Languages::_('ORGANIZER_PROFILE_EDIT'),
                        'participants.edit',
                        false
                    );
                } else {
                    $toolbar->appendButton(
                        'Standard',
                        'user-plus',
                        Languages::_('ORGANIZER_PROFILE_NEW'),
                        'participants.edit',
                        false
                    );
                }
            }

            if ($this->manages) {
                $toolbar->appendButton('Standard', 'edit', Languages::_('ORGANIZER_EDIT'), 'courses.edit', true);
                $toolbar->appendButton(
                    'NewTab',
                    'users',
                    Languages::_('ORGANIZER_PARTICIPANTS'),
                    'courses.participants',
                    true
                );

                $toolbar->appendButton(
                    'Confirm',
                    Languages::_('ORGANIZER_DELETE_CONFIRM'),
                    'delete',
                    Languages::_('ORGANIZER_DELETE'),
                    'courses.delete',
                    true
                );
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function authorize()
    {
        if (!$this->adminContext) {
            return;
        }

        if (!Helpers\Can::scheduleTheseOrganizations()) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null)
    {
        $params = Input::getParams();

        if ($params->get('onlyPrepCourses')) {
            $this->empty = Languages::_('ORGANIZER_PREP_COURSE_PLANNING_INCOMPLETE');
        }

        parent::display($tpl);
    }

    /**
     * @inheritDoc
     */
    public function setHeaders()
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $headers = [
            'name' => Helpers\HTML::sort('NAME', 'name', $direction, $ordering),
            'campus' => Languages::_('ORGANIZER_CAMPUS'),
            'dates' => Helpers\HTML::sort('DATES', 'dates', $direction, $ordering),
            'courseStatus' => [
                'attributes' => ['class' => 'center'],
                'value' => Languages::_('ORGANIZER_COURSE_STATUS')
            ]
        ];

        if ($this->manages) {
            $headers = ['checkbox' => ''] + $headers;
        } else {
            $headers = $headers + [
                    'registrationStatus' => [
                        'attributes' => ['class' => 'center'],
                        'value' => Languages::_('ORGANIZER_REGISTRATION_STATUS')
                    ]
                ];
        }

        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    protected function structureItems()
    {
        $url = Uri::base() . '?option=com_organizer';
        $url .= $this->adminContext ? '&view=course_edit&id=' : '&view=course_item&id=';

        $structuredItems = [];

        $today  = Helpers\Dates::standardizeDate();
        $userID = Helpers\Users::getID();

        foreach ($this->items as $course) {
            $campusID   = (int) $course->campusID;
            $campusName = Helpers\Campuses::getName($campusID);
            $pin        = $this->adminContext ? '' : ' ' . Helpers\Campuses::getPin($campusID);

            $course->campus = $campusName . $pin;

            $course->dates = Helpers\Courses::getDateDisplay($course->id);

            $expired = $course->endDate < $today;
            $ongoing = ($course->startDate <= $today and !$expired);

            if ($course->deadline) {
                $deadline = date('Y-m-d', strtotime("-$course->deadline Days", strtotime($course->startDate)));
            } else {
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
                    'value' => Languages::_('ORGANIZER_EXPIRED')
                ];

                if (!$this->manages) {
                    $course->registrationStatus = [
                        'attributes' => $attributes,
                        'value' => Languages::_('ORGANIZER_DEADLINE_EXPIRED_SHORT')
                    ];
                }
            } else {
                $class                = 'status-display center hasTip';
                $course->courseStatus = [];
                $capacityText         = Languages::_('ORGANIZER_PARTICIPANTS');
                $capacityText         .= ": $course->participants / $course->maxParticipants<br>";

                if ($ongoing) {
                    $courseAttributes = [
                        'class' => $class . ' red',
                        'title' => Languages::_('ORGANIZER_COURSE_ONGOING')
                    ];
                } elseif ($closed) {
                    $courseAttributes = [
                        'class' => $class . ' yellow',
                        'title' => Languages::_('ORGANIZER_COURSE_CLOSED')
                    ];
                } elseif ($full) {
                    $courseAttributes = ['class' => $class . ' red', 'title' => Languages::_('ORGANIZER_COURSE_FULL')];
                } elseif ($ninety) {
                    $courseAttributes = [
                        'class' => $class . ' yellow',
                        'title' => Languages::_('ORGANIZER_COURSE_LIMITED')
                    ];
                } else {
                    $courseAttributes = [
                        'class' => $class . ' green',
                        'title' => Languages::_('ORGANIZER_COURSE_OPEN')
                    ];
                }

                $course->courseStatus['attributes'] = $courseAttributes;

                if ($ongoing or $closed) {
                    $courseText = Languages::_('ORGANIZER_DEADLINE_EXPIRED_SHORT');
                } else {
                    $courseText = sprintf(Languages::_('ORGANIZER_DEADLINE_TEXT_SHORT'), $deadline);
                }

                $course->courseStatus['value'] = $capacityText . $courseText;

                if (!$this->manages) {
                    if ($userID) {
                        if ($course->registered) {
                            $course->registrationStatus = [
                                'attributes' => ['class' => 'status-display center green'],
                                'value' => Languages::_('ORGANIZER_REGISTERED')
                            ];
                        } else {
                            $color                      = ($ongoing or $closed) ? 'red' : 'yellow';
                            $course->registrationStatus = [
                                'attributes' => ['class' => "status-display center $color"],
                                'value' => Languages::_('ORGANIZER_NOT_REGISTERED')
                            ];
                        }
                    } else {
                        $course->registrationStatus = [
                            'attributes' => ['class' => 'status-display center grey'],
                            'value' => Languages::_('ORGANIZER_NOT_LOGGED_IN')
                        ];
                    }
                }
            }

            $index = "$course->startDate $course->name $campusName";

            $structuredItems[$index] = $this->structureItem($index, $course, $url . $course->id);
        }

        $this->items = $structuredItems;
    }
}
