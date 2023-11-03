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

/**
 * Class loads persistent information a filtered set of events into the display context.
 */
class Events extends ListView
{
    protected array $rowStructure = [
        'checkbox'     => '',
        'code'         => 'link',
        'name'         => 'link',
        'organization' => 'link'
    ];

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->setTitle('ORGANIZER_EVENT_TEMPLATES');
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'edit', Text::_('ORGANIZER_EDIT'), 'events.edit', true);

        if (Helpers\Can::administrate()) {
            $toolbar->appendButton(
                'Standard',
                'contract',
                Text::_('ORGANIZER_MERGE'),
                'events.mergeView',
                true
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function authorize(): void
    {
        if (!Helpers\Can::edit('events')) {
            Application::error(403);
        }
    }

    /**
     * @inheritdoc
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
     * @inheritdoc
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