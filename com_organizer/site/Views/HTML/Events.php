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
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Can;

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

            if (Application::backend()) {
                $toolbar = Toolbar::getInstance();
                $toolbar->preferences('com_organizer');
            }
        }
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(): void
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=event_edit&id=';
        $structuredItems = [];

        foreach ($this->items as $item) {
            $structuredItems[$index] = $this->completeItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox'     => '',
            'code'         => HTML::sort('UNTIS_ID', 'code', $direction, $ordering),
            'name'         => HTML::sort('NAME', 'name', $direction, $ordering),
            'organization' => HTML::sort('ORGANIZATION', 'name', $direction, $ordering)
        ];

        $this->headers = $headers;
    }
}