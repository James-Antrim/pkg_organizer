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
use THM\Organizer\Adapters\{Application, Document, Input, Text, Toolbar, User};
use THM\Organizer\Helpers\{Courses as Helper, CourseParticipants as CP, Dates, Participants};
use THM\Organizer\Buttons\FormTarget;

/** @inheritDoc */
class Course extends FormView
{
    public bool $coordinates = false;

    protected string $layout = 'course';

    /** @inheritDoc */
    protected function addToolBar(array $buttons = [], string $constant = ''): void
    {
        $this->toDo[] = 'Course registration functionality.';
        $this->toDo[] = 'Course badge functionality.';

        $item     = $this->item;
        $courseID = empty($item->id) ? 0 : $item->id;
        $toolbar  = Toolbar::getInstance();

        if ($this->layout === 'edit') {
            $this->setTitle(empty($courseID) ? Text::_('ADD_COURSE') : Text::_('EDIT_COURSE'));
            $saveGroup = $toolbar->dropdownButton('save-group');
            $saveBar   = $saveGroup->getChildToolbar();
            $saveBar->apply('course.apply');
            $saveBar->save('course.save');
            $toolbar->cancel("course.cancel");
            return;
        }

        $this->setTitle(Helper::name($courseID));

        if ($this->coordinates) {
            $toolbar->edit('course.edit');
            return;
        }

        if ($userID = User::id()) {
            $pText = Participants::exists($userID) ? 'EDIT_PROFILE' : 'ADD_PROFILE';
            $toolbar->linkButton('profile', Text::_($pText))->url("$this->baseURL&view=profile")->icon('fa fa-address-card');

            $startDate = $item->startDate;
            $deadline  = $item->deadline ? date('Y-m-d', strtotime("-$item->deadline Days", strtotime($startDate))) : $startDate;
            $today     = date('Y-m-d');

            if ($deadline < $today) {
                return;
            }

            $full  = $item->participants >= $item->maxParticipants;
            $state = CP::state($item->id, $userID);
            $valid = CP::validProfile($item->id, $userID);

            if ($state === CP::UNREGISTERED) {
                if (!$full and $valid) {
                    $toolbar->standardButton('register', Text::_('REGISTER'), "course.register")->icon('fa fa-sign-in-alt');
                }
                return;
            }

            $toolbar->standardButton('deregister', Text::_('DEREGISTER'), "course.deregister")->icon('fa fa-sign-out-alt');

            if ($state === CP::ACCEPTED and CP::paid($item->id, $userID)) {
                $button = new FormTarget('badge', Text::_('DOWNLOAD_BADGE'));
                $button->icon('fa fa-tag')->task('course.badge');
                $toolbar->appendButton($button);
            }
        }
    }

    /** @inheritDoc */
    protected function authorize(): void
    {
        $courseID = Input::getID();

        // Access >= coordinates works for both layouts.
        if ($this->coordinates = Helper::coordinatable($courseID)) {
            return;
        }

        // Non-authorized edit attempt.
        if ($this->layout === 'edit' or $courseID === 0) {
            Application::error(403);
        }
    }

    /** @inheritDoc */
    protected function modifyDocument(): void
    {
        if ($this->layout === 'course') {
            Document::style('item');
        }
    }

    /** @inheritDoc */
    protected function setSubtitle(): void
    {
        $this->subtitle = '<h6 class="sub-title">';

        if ($this->item->campusID and $campusName = $this->item->campusName) {
            $this->subtitle .= Text::_('CAMPUS') . " $campusName: ";
        }

        $this->subtitle .= Helper::displayDate($this->item->id) . '</h6>';
    }

    /** @inheritDoc */
    protected function setSupplement(): void
    {
        $item = $this->item;

        $startDate = $item->startDate;
        $deadline  = $item->deadline ? date('Y-m-d', strtotime("-$item->deadline Days", strtotime($startDate))) : $startDate;

        $today     = Dates::standardize();
        $textClass = '';
        $texts     = [];

        if ($item->endDate < $today) {
            $textClass = 'bg-secondary';
            $texts[]   = Text::_('COURSE_EXPIRED');
        }
        else {

            $full    = $item->participants >= $item->maxParticipants;
            $ninety  = (!$full and ($item->participants / (int) $item->maxParticipants) >= .9);
            $ongoing = ($startDate <= $today);

            $closed   = (!$ongoing and $deadline <= $today);
            $deadline = Dates::formatDate($deadline);

            if ($ongoing or $full) {
                $textClass = 'bg-danger';

                if ($ongoing) {
                    $texts['cStatus'] = Text::_('COURSE_ONGOING');
                }
                else {
                    $texts['rStatus'] = Text::_('COURSE_FULL');
                }
            }
            elseif ($closed or $ninety) {
                $textClass        = 'bg-warning';
                $texts['rStatus'] = $closed ? Text::_('DEADLINE_EXPIRED') : Text::_('COURSE_LIMITED');
            }

            $deadlineText = Text::sprintf('DEADLINE_TEXT', $deadline);

            // Personal registration texts only if registration is (still) possible.
            if (!$this->coordinates and !$closed and !$ongoing) {

                if ($userID = User::id()) {
                    if (!Participants::exists() or !CP::validProfile($item->id, $userID)) {
                        $textClass         = 'bg-warning';
                        $texts['myStatus'] = Text::_('COURSE_PROFILE_REQUIRED');
                    }
                    elseif ($item->registrationStatus === CP::UNREGISTERED) {
                        $textClass         = 'bg-info';
                        $texts['myStatus'] = Text::_('COURSE_UNREGISTERED');
                    }
                    else {
                        // Irrelevant if already registered
                        unset($texts['cStatus'], $texts['rStatus']);

                        if ($item->registrationStatus) {
                            $textClass         = 'bg-success';
                            $texts['myStatus'] = Text::_('COURSE_ACCEPTED');
                        }
                        else {
                            $textClass         = 'bg-info';
                            $texts['myStatus'] = Text::_('COURSE_WAITLIST');
                        }
                    }
                }
                else {
                    $currentURL     = Uri::getInstance()->toString() . '#login-anchor';
                    $textClass      = 'bg-warning';
                    $texts['login'] = Text::sprintf('COURSE_LOGIN_WARNING', $currentURL, $currentURL);
                }

                $texts['deadline'] = $deadlineText;
            }
        }

        if (true) {//!$this->coordinates) {
            $this->supplement = '<div class="p-2 ' . $textClass . '">' . implode('<br>', $texts) . '</div>';
        }
    }
}