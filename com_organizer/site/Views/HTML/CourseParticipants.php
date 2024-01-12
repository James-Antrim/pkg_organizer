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

use THM\Organizer\Adapters\{Application, Document, HTML, Input, Text, Toolbar, User};
use THM\Organizer\Buttons\FormTarget;
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class CourseParticipants extends Participants
{
    /**
     * @inheritDoc
     */
    protected function setSubTitle(): void
    {
        $courseID = Input::getID();

        $subTitle   = [];
        $subTitle[] = Helpers\Courses::name($courseID);

        if ($campusID = Helpers\Courses::campusID($courseID)) {
            $subTitle[] = Helpers\Campuses::name($campusID);
        }

        $subTitle[] = Helpers\Courses::displayDate($courseID);

        $this->subtitle = '<h6 class="sub-title">' . implode('<br>', $subTitle) . '</h6>';
    }

    /**
     * @inheritDoc
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

        $toolbar->delete('CourseParticipants.remove')
            ->message(Text::_('DELETE_CONFIRM'))
            ->icon('fa fa-user-minus')
            ->listCheck(true);

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

        $title       = Text::_('NOTIFY');
        $batchButton .= '<span class="icon-envelope" title="' . $title . '"></span>' . " $title";

        $batchButton .= '</button>';

        $toolbar->appendButton('Custom', $batchButton, 'batch');
    }

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (!User::id()) {
            Application::error(401);
        }

        if (!$courseID = Input::getID()) {
            Application::error(400);
        }

        if (!Helpers\Can::coordinate('course', $courseID)) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(): void
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=participant_edit&id=';
        $structuredItems = [];

        $admin     = Helpers\Can::administrate();
        $checked   = '<span class="icon-checkbox-checked"></span>';
        $courseID  = Input::getID();
        $expired   = Helpers\Courses::expired($courseID);
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
                    Text::_('TOGGLE_ACCEPTED'),
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
                    Text::_('TOGGLE_ATTENDED'),
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
                    Text::_('TOGGLE_PAID'),
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
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        // Set batch template path
        $this->batch = ['batch_participant_notify'];

        parent::display($tpl);
    }

    /**
     * @inheritDoc
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
