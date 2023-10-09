<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Tables;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class CourseParticipants extends Participants
{
    protected $rowStructure = [
        'checkbox' => '',
        'fullName' => 'value',
        'email' => 'value',
        'program' => 'value',
        'status' => 'value',
        'paid' => 'value',
        'attended' => 'value'
    ];

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true)
    {
        $this->setTitle('ORGANIZER_PARTICIPANTS');

        $courseID = Helpers\Input::getID();
        $course   = new Tables\Courses();
        $course->load($courseID);

        $toolbar = Adapters\Toolbar::getInstance();

        $toolbar->appendButton(
            'Standard',
            'checkin',
            Languages::_('ORGANIZER_ACCEPT'),
            'CourseParticipants.accept',
            true
        );

        $toolbar->appendButton(
            'Standard',
            'checkbox-unchecked',
            Languages::_('ORGANIZER_WAITLIST'),
            'CourseParticipants.waitlist',
            true
        );

        $toolbar->appendButton(
            'Confirm',
            Languages::_('ORGANIZER_DELETE_CONFIRM'),
            'user-minus',
            Languages::_('ORGANIZER_DELETE'),
            'CourseParticipants.remove',
            true
        );

        $toolbar->appendButton(
            'NewTab',
            'tags-2',
            Languages::_('ORGANIZER_DOWNLOAD_BADGES'),
            'CourseParticipants.badges',
            false
        );

        $toolbar->appendButton(
            'NewTab',
            'list',
            Languages::_('ORGANIZER_ATTENDANCE'),
            'CourseParticipants.attendance',
            false
        );

        $toolbar->appendButton(
            'NewTab',
            'list-2',
            Languages::_('ORGANIZER_GROUPED_PARTICIPATION'),
            'CourseParticipants.participation',
            false
        );

        $script      = "onclick=\"jQuery('#modal-mail').modal('show'); return true;\"";
        $batchButton = "<button id=\"participant-mail\" data-toggle=\"modal\" class=\"btn btn-small\" $script>";

        $title       = Languages::_('ORGANIZER_NOTIFY');
        $batchButton .= '<span class="icon-envelope" title="' . $title . '"></span>' . " $title";

        $batchButton .= '</button>';

        $toolbar->appendButton('Custom', $batchButton, 'batch');
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Users::getID()) {
            Helpers\OrganizerHelper::error(401);
        }

        if (!$courseID = Helpers\Input::getID()) {
            Helpers\OrganizerHelper::error(400);
        }

        if (!Helpers\Can::manage('course', $courseID)) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    public function display($tpl = null)
    {
        // Set batch template path
        $this->batch = ['batch_participant_notify'];

        parent::display($tpl);
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument()
    {
        parent::modifyDocument();

        Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/modal.css');
    }

    /**
     * @inheritdoc
     */
    protected function setHeaders()
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => Helpers\HTML::_('grid.checkall'),
            'fullName' => Helpers\HTML::sort('NAME', 'fullName', $direction, $ordering),
            'email' => Helpers\HTML::sort('EMAIL', 'email', $direction, $ordering),
            'program' => Helpers\HTML::sort('PROGRAM', 'program', $direction, $ordering),
            'status' => Languages::_('ORGANIZER_STATUS'),
            'paid' => Languages::_('ORGANIZER_PAID'),
            'attended' => Languages::_('ORGANIZER_ATTENDED')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function setSubtitle()
    {
        $courseID = Helpers\Input::getID();

        $subTitle   = [];
        $subTitle[] = Helpers\Courses::getName($courseID);

        if ($campusID = Helpers\Courses::getCampusID($courseID)) {
            $subTitle[] = Helpers\Campuses::getName($campusID);
        }

        $subTitle[] = Helpers\Courses::getDateDisplay($courseID);

        $this->subtitle = '<h6 class="sub-title">' . implode('<br>', $subTitle) . '</h6>';
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=participant_edit&id=';
        $structuredItems = [];

        $admin     = Helpers\Can::administrate();
        $checked   = '<span class="icon-checkbox-checked"></span>';
        $courseID  = Helpers\Input::getID();
        $expired   = Helpers\Courses::isExpired($courseID);
        $unchecked = '<span class="icon-checkbox-unchecked"></span>';

        foreach ($this->items as $item) {
            if (!$expired) {
                $item->status = $this->getAssocToggle(
                    'course_participants',
                    'courseID',
                    $courseID,
                    'participantID',
                    $item->id,
                    $item->status,
                    Languages::_('ORGANIZER_TOGGLE_ACCEPTED'),
                    'status'
                );
            } else {
                $item->status = $item->status ? $checked : $unchecked;
            }

            if ($admin or !$item->attended) {
                $item->attended = $this->getAssocToggle(
                    'course_participants',
                    'courseID',
                    $courseID,
                    'participantID',
                    $item->id,
                    $item->attended,
                    Languages::_('ORGANIZER_TOGGLE_ATTENDED'),
                    'attended'
                );
            } else {
                $item->attended = $checked;
            }

            if ($admin or !$item->paid) {
                $item->paid = $this->getAssocToggle(
                    'course_participants',
                    'courseID',
                    $courseID,
                    'participantID',
                    $item->id,
                    $item->paid,
                    Languages::_('ORGANIZER_TOGGLE_PAID'),
                    'paid'
                );
            } else {
                $item->paid = $checked;
            }

            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
