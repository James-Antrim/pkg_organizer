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

use Joomla\CMS\Toolbar\Button\DropdownButton;
use stdClass;
use THM\Organizer\Adapters\{Application, Document, HTML, Input, Text, Toolbar, User};
use THM\Organizer\Buttons\FormTarget;
use THM\Organizer\Helpers\{Campuses, Courses as cHelper, CourseParticipants as Helper};
use THM\Organizer\Layouts\HTML\ListItem;

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
        $subTitle[] = cHelper::name($courseID);

        if ($campusID = cHelper::campusID($courseID)) {
            $subTitle[] = Campuses::name($campusID);
        }

        $subTitle[] = cHelper::displayDate($courseID);

        $this->subtitle = '<h6 class="sub-title">' . implode('<br>', $subTitle) . '</h6>';
    }

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->toDo[] = 'Paid/Unpaid/Attended/Unattended buttons';
        $this->toDo[] = 'The borders/fill in attendance by FB/DP needs adjusting.';
        $this->toDo[] = 'Cancel (bar?) functionality (item/list/menuitem)';

        $this->setTitle('PARTICIPANTS');

        $toolbar = Toolbar::getInstance();

        /** @var DropdownButton $functions */
        $functions    = $toolbar->dropdownButton('functions-group', 'ORGANIZER_FUNCTIONS')
            ->toggleSplit(false)
            ->icon('fa fa-users-cog')
            ->buttonClass('btn btn-action')
            ->listCheck(true);
        $functionsBar = $functions->getChildToolbar();
        $functionsBar->standardButton('checkin', Text::_('ACCEPT'), 'courseparticipants.accept')->icon('fa fa-check-square');
        $functionsBar->standardButton('wait', Text::_('WAITLIST'), 'courseparticipants.waitlist')->icon('fa fa-square');
        $functionsBar->delete('courseparticipants.delete')->message(Text::_('DELETE_CONFIRM'))->icon('fa fa-times');

        /** @var DropdownButton $documents */
        $documents    = $toolbar->dropdownButton('documents-group', 'ORGANIZER_FILES')
            ->toggleSplit(false)
            ->icon('fa fa-copy')
            ->buttonClass('btn btn-action');
        $documentsBar = $documents->getChildToolbar();

        $button       = new FormTarget('badges', Text::_('BADGES'), ['icon' => 'fa fa-tags']);
        $button->task = 'courseparticipants.badges';
        $documentsBar->appendButton($button);

        $button = new FormTarget('attendance', Text::_('ATTENDANCE'));
        $button->icon('fa fa-list');
        $button->task = 'courseparticipants.attendance';
        $documentsBar->appendButton($button);

        $button = new FormTarget('participation', Text::_('GROUPED_PARTICIPATION'));
        $button->icon('fa fa-list-ul');
        $button->task = 'courseparticipants.participation';
        $documentsBar->appendButton($button);

        $this->allowBatch = true;
        $toolbar->popupButton('batch', Text::_('NOTIFY'))
            ->popupType('inline')
            ->textHeader(Text::_('NOTIFY_HEADER'))
            ->url('#organizer-batch')
            ->modalWidth('800px')
            ->modalHeight('fit-content');

        $batchBar = Toolbar::getInstance('batch');
        $batchBar->confirmButton('batch', Text::_('SEND'), 'courseparticipants.notify')->message(Text::_('NOTIFY_CONFIRM'));
    }

    /** @inheritDoc */
    protected function authorize(): void
    {
        if (!User::id()) {
            Application::error(401);
        }

        if (!$courseID = $this->state->get('hidden.id')) {
            Application::message(400, Application::ERROR);
            Application::redirect($this->baseurl, 400);
        }

        if (!cHelper::coordinatable($courseID)) {
            Application::error(403);
        }
    }

    /** @inheritDoc */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        extract($options);
        $aContext = "attended-$index";
        $pContext = "paid-$index";
        $rContext = "registration-$index";

        if ($expired) {
            $item->attended = $item->attended ?
                HTML::tip($checked, $aContext, 'ATTENDED') : HTML::tip($unchecked, $aContext, 'UNATTENDED');
            $item->paid     = $item->paid ?
                HTML::tip($checked, $pContext, 'PAID') : HTML::tip($unchecked, $pContext, 'UNPAID');
            $item->status   = $item->status ?
                HTML::tip($checked, $rContext, 'REGISTERED') : HTML::tip($unchecked, $rContext, 'UNREGISTERED');
        }
        else {
            $item->attended = HTML::toggle($index, Helper::ATTENDANCE_STATES[$item->attended], 'courseparticipants');
            $item->paid     = HTML::toggle($index, Helper::PAYMENT_STATES[$item->paid], 'courseparticipants');
            $item->status   = HTML::toggle($index, Helper::REGISTRATION_STATES[$item->status], 'courseparticipants');
        }
    }

    /** @inheritDoc */
    protected function completeItems(array $options = []): void
    {
        $options = [
            'checked'   => HTML::icon('fa fa-check'),
            'expired'   => cHelper::expired(Input::getID()),
            'unchecked' => HTML::icon('fa fa-times')
        ];

        parent::completeItems($options);
    }

    /** @inheritDoc */
    public function display($tpl = null): void
    {
        // Set batch template path
        $this->batch = ['batch_participant_notify'];

        // Sets the state before authorization checks because otherwise refresh may cause a cache miss.
        $this->state = $this->get('State');

        parent::display($tpl);
    }

    /** @inheritDoc */
    protected function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');

        $headers = [
            'check'    => ['type' => 'check'],
            'fullName' => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'fullName', $direction, 'fullName'),
                'type'       => 'text'
            ],
            'email'    => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('EMAIL'),
                'type'       => 'text'
            ],
            'program'  => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('PROGRAM'),
                'type'       => 'text'
            ],
            'status'   => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('STATUS'),
                'type'       => 'value'
            ],
            'paid'     => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('PAID'),
                'type'       => 'value'
            ],
            'attended' => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ATTENDED'),
                'type'       => 'value'
            ]
        ];

        $this->headers = $headers;
    }

    /** @inheritDoc */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        Document::style('modal');
    }
}
