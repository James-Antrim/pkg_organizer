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
use THM\Organizer\Helpers\{Can, Dates};
use stdClass;

/**
 * Class loads persistent information a filtered set of schedules into the display context.
 */
class Schedules extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $toolbar = Toolbar::getInstance();

        $toolbar->addNew('schedules.add');

        if ($this->state->get('filter.organizationID') and $this->state->get('filter.termID') and Can::administrate()) {
            $toolbar->standardButton('rebuild', Text::_('REBUILD'), 'schedules.rebuild')->icon('fa fa-sync');
            $this->addDelete();
        }

        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $this->headers = [
            'check'            => ['type' => 'check'],
            'organizationName' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ORGANIZATION'),
                'type'       => 'text'
            ],
            'termName'         => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('TERM'),
                'type'       => 'text'
            ],
            'userName'         => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('USERNAME'),
                'type'       => 'text'
            ],
            'created'          => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('CREATION_DATE'),
                'type'       => 'text'
            ]
        ];
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $creationDate  = Dates::formatDate($item->creationDate);
        $creationTime  = Dates::formatTime($item->creationTime);
        $item->created = "$creationDate / $creationTime";
    }
}
