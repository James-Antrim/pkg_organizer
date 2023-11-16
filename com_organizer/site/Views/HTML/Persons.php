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

use THM\Organizer\Adapters\{Text, Toolbar};
use stdClass;
use THM\Organizer\Helpers;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of persons into the display context.
 */
class Persons extends ListView
{
    use Activated;

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('Person.add');
        $this->addActa();

        if (Helpers\Can::administrate()) {
            $toolbar->delete('Persons.delete')->message('DELETE_CONFIRM');
            $toolbar->standardButton('merge', Text::_('MERGE'), 'MergePersons.display')->icon('fa fa-compress');
        }

        parent::addToolBar();
    }

    /**
     * @inheritdoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->forename = empty($item->forename) ? '' : $item->forename;
        $item->username = empty($item->username) ? '' : $item->username;

        $tip          = $item->active ? 'CLICK_TO_DEACTIVATE' : 'CLICK_TO_ACTIVATE';
        $item->active = $this->getToggle('persons', $item->id, $item->active, $tip, 'active');

        if (!$organizations = Helpers\Persons::getOrganizationNames($item->id)) {
            $item->organizationID = Text::_('NONE');
        }
        elseif (count($organizations) === 1) {
            $item->organizationID = $organizations[0];
        }
        else {
            $item->organizationID = Text::_('MULTIPLE_ORGANIZATIONS');
        }

        $item->code = empty($item->code) ? '' : $item->code;
    }

    /**
     * @inheritdoc
     */
    public function initializeColumns(): void
    {
        $this->headers = [
            'check'          => ['type' => 'check'],
            'surname'        => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('SURNAME'),
                'type'       => 'text'
            ],
            'forename'       => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('FORENAME'),
                'type'       => 'text'
            ],
            'username'       => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('USERNAME'),
                'type'       => 'text'
            ],
            'active'         => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ACTIVE'),
                'type'       => 'value'
            ],
            'organizationID' => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ORGANIZATION'),
                'type'       => 'text'
            ],
            'code'           => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('UNTIS_ID'),
                'type'       => 'text'
            ],
        ];
    }
}
