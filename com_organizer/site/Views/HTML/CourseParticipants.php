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

use THM\Organizer\Adapters\{Application, Document, HTML, Input, Text, Toolbar};
use THM\Organizer\Buttons\FormTarget;
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class CourseParticipants extends Participants
{
    /**
     * @inheritdoc
     */
    protected function setSubTitle(): void
    {
        $courseID = Input::getID();

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
    protected function addToolBar(bool $delete = true): void
    {
        $this->setTitle('PARTICIPANTS');

        $toolbar = Toolbar::getInstance();

        $toolbar->standardButton('checkin', Text::_('ACCEPT'), 'CourseParticipants.accept')
            ->listCheck(true)
            ->icon('fa fa-check-square');

        $toolbar->standardButton('wait', Text::_('WAITLIST'), 'CourseParticipants.waitlist')
            ->listCheck(true)
            ->icon('fa fa-square');

        $toolbar->delete('CourseParticipants.remove')->message(Text::_('DELETE_CONFIRM'))->icon('fa fa-user-minus');

        $button = new FormTarget('badges', Text::_('DOWNLOAD_BADGES'));
        $button->icon('fa fa-tags')->task('CourseParticipants.badges');
        $toolbar->appendButton($button);

        $button = new FormTarget('attendance', Text::_('ATTENDANCE'));
        $button->icon('fa fa-list')->task('CourseParticipants.attendance');
        $toolbar->appendButton($button);

        $button = new FormTarget('participation', Text::_('GROUPED_PARTICIPATION'));
        $button->icon('fa fa-list-ul')->task('CourseParticipants.participation');
        $toolbar->appendButton($button);

        // todo jq???
        $script      = "onclick=\"jQuery('#modal-mail').modal('show'); return true;\"";
        $batchButton = "<button id=\"participant-mail\" data-toggle=\"modal\" class=\"btn btn-small\" $script>";

        $title       = Text::_('ORGANIZER_NOTIFY');
        $batchButton .= '<span class="icon-envelope" title="' . $title . '"></span>' . " $title";

        $batchButton .= '</button>';

        $toolbar->appendButton('Custom', $batchButton, 'batch');
    }

    /**
     * @inheritdoc
     */
    protected function authorize(): void
    {
        if (!Helpers\Users::getID()) {
            Application::error(401);
        }

        if (!$courseID = Input::getID()) {
            Application::error(400);
        }

        if (!Helpers\Can::manage('course', $courseID)) {
            Application::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=participant_edit&id=';
        $structuredItems = [];

        $admin     = Helpers\Can::administrate();
        $checked   = '<span class="icon-checkbox-checked"></span>';
        $courseID  = Input::getID();
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
                    Text::_('ORGANIZER_TOGGLE_ACCEPTED'),
                    'status'
                );
            }
            else {
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
                    Text::_('ORGANIZER_TOGGLE_ATTENDED'),
                    'attended'
                );
            }
            else {
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
                    Text::_('ORGANIZER_TOGGLE_PAID'),
                    'paid'
                );
            }
            else {
                $item->paid = $checked;
            }

            $structuredItems[$index] = $this->completeItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }

    /**
     * @inheritdoc
     */
    public function display($tpl = null): void
    {
        // Set batch template path
        $this->batch = ['batch_participant_notify'];

        parent::display($tpl);
    }

    /**
     * @inheritdoc
     */
    protected function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => HTML::checkAll(),
            'fullName' => HTML::sort('NAME', 'fullName', $direction, $ordering),
            'email'    => HTML::sort('EMAIL', 'email', $direction, $ordering),
            'program'  => HTML::sort('PROGRAM', 'program', $direction, $ordering),
            'status'   => Text::_('STATUS'),
            'paid'     => Text::_('PAID'),
            'attended' => Text::_('ATTENDED')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        Document::style('modal');
    }
}
