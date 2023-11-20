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

use THM\Organizer\Adapters\{Application, HTML, Text, Toolbar};
use THM\Organizer\Helpers\Can;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of events into the display context.
 */
class Events extends ListView
{
    use Merged;

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        // Divergent title
        $this->setTitle('ORGANIZER_EVENT_TEMPLATES');

        if (Can::administrate()) {
            $this->addMerge();

            $toolbar = Toolbar::getInstance();
            $toolbar->delete('Events.clean')->icon('fa fa-broom')->listCheck(false)->text(Text::_('CLEAN_ENTRIES'));

            if (Application::backend()) {
                $toolbar->preferences('com_organizer');
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'        => ['type' => 'check'],
            'name'         => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, 'name'),
                'type'       => 'text'
            ],
            'code'         => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('UNTIS_ID'),
                'type'       => 'text'
            ],
            'organization' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ORGANIZATION'),
                'type'       => 'text'
            ],
        ];

        $this->headers = $headers;
    }
}