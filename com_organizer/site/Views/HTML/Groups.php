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
use THM\Organizer\Adapters\{HTML, Text, Toolbar};
use THM\Organizer\Helpers\{Can, Grids, Groups as Helper, Terms};
use THM\Organizer\Layouts\HTML\ListItem;
use THM\Organizer\Tables\GroupPublishing;

/**
 * Class loads persistent information a filtered set of (scheduled subject) pools into the display context.
 */
class Groups extends ListView
{
    use Activated;
    use Merged;

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->toDo[] = 'Store the publishing value in the instance directly to make instance queries much better.';
        // Resource creation occurs in Untis.

        $toolbar = Toolbar::getInstance();

        /** @var DropdownButton $functions */
        $functions    = $toolbar->dropdownButton('functions-group', 'ORGANIZER_FUNCTIONS')
            ->toggleSplit(false)
            ->icon('icon-ellipsis-h')
            ->buttonClass('btn btn-action')
            ->listCheck(true);
        $functionsBar = $functions->getChildToolbar();
        $current      = Terms::name(Terms::getCurrentID());
        $functionsBar->standardButton('publish-current', Text::sprintf('PUBLISH_TERM', $current), 'groups.publishCurrent')
            ->icon('fa fa-eye');
        $functionsBar->standardButton('unpublish-current', Text::sprintf('UNPUBLISH_TERM', $current), 'groups.unpublishCurrent')
            ->icon('fa fa-eye-slash');
        $next = Terms::name(Terms::getNextID());
        $functionsBar->standardButton('publish-current', Text::sprintf('PUBLISH_TERM', $next), 'groups.publishNext')
            ->icon('fa fa-eye');
        $functionsBar->standardButton('unpublish-current', Text::sprintf('UNPUBLISH_TERM', $next), 'groups.unpublishNext')
            ->icon('fa fa-eye-slash');

        if (Can::administrate()) {
            $this->addMerge($functionsBar);
        }

        $this->addActa($functionsBar);

        // As it stands the controller restricts access to the view to planers, so further restriction would be redundant.
        $this->allowBatch = true;
        $functionsBar->popupButton('batch', Text::_('BATCH'))
            ->popupType('inline')
            ->textHeader(Text::_('BATCH_GROUPS'))
            ->url('#organizer-batch')
            ->modalWidth('800px')
            ->modalHeight('fit-content')
            ->listCheck(true);

        // This authorization level restriction isn't due to a security risk, as would otherwise be the case.
        if (Can::administrate()) {
            $toolbar->standardButton('publish-expired', Text::_('PUBLISH_EXPIRED_TERMS'), 'Groups.publishPast')
                ->icon('fa fa-reply-all');
        }


        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->active = HTML::toggle($index, Helper::ACTIVE_STATES[$item->active], 'Groups');
        $item->grid   = Grids::name($item->gridID);

        $publishing   = new GroupPublishing();
        $keys         = ['groupID' => $item->id, 'termID' => $options['currentID']];
        $currentValue = $publishing->load($keys) ? $publishing->published : 1;
        $item->this   = HTML::toggle($index, Helper::PUBLISHED_STATES[$currentValue], 'Groups', 'Current');

        $publishing     = new GroupPublishing();
        $keys['termID'] = $options['nextID'];
        $nextValue      = $publishing->load($keys) ? $publishing->published : 1;
        $item->next     = HTML::toggle($index, Helper::PUBLISHED_STATES[$nextValue], 'Groups', 'Next');
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(array $options = []): void
    {
        $options = ['currentID' => Terms::getCurrentID(), 'nextID' => Terms::getNextID()];
        parent::completeItems($options);
    }

    /**
     * @inheritDoc
     */
    protected function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'    => ['type' => 'check'],
            'fullName' => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('FULL_NAME', 'fullName', $direction, 'fullName'),
                'type'       => 'text'
            ],
            'this'     => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Terms::name(Terms::getCurrentID()),
                'type'       => 'value'
            ],
            'next'     => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Terms::name(Terms::getNextID()),
                'type'       => 'value'
            ],
            'name'     => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('SELECT_BOX_DISPLAY'),
                'type'       => 'text'
            ],
            'active'   => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ACTIVE'),
                'type'       => 'value'
            ],
            'grid'     => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('GRID'),
                'type'       => 'text'
            ],
            'code'     => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('UNTIS_ID'),
                'type'       => 'text'
            ],
        ];

        $this->headers = $headers;
    }
}
