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
 * Class loads persistent information a filtered set of event categories into the display context.
 */
class Categories extends ListView
{
    protected array $rowStructure = [
        'checkbox' => '',
        'name'     => 'link',
        'active'   => 'value',
        'program'  => 'link',
        'code'     => 'link'
    ];

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->setTitle('ORGANIZER_CATEGORIES');
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'edit', Text::_('ORGANIZER_EDIT'), 'categories.edit', true);
        $toolbar->appendButton(
            'Standard',
            'eye-open',
            Text::_('ORGANIZER_ACTIVATE'),
            'Categories.activate',
            false
        );
        $toolbar->appendButton(
            'Standard',
            'eye-close',
            Text::_('ORGANIZER_DEACTIVATE'),
            'Categories.deactivate',
            false
        );

        if (Helpers\Can::administrate()) {
            $toolbar->appendButton(
                'Standard',
                'delete',
                Text::_('ORGANIZER_DELETE'),
                'Categories.delete',
                true
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function authorize(): void
    {
        if (!Helpers\Can::scheduleTheseOrganizations()) {
            Application::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    public function setHeaders(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => '',
            'name'     => HTML::sort('DISPLAY_NAME', 'name', $direction, $ordering),
            'active'   => Text::_('ACTIVE'),
            'program'  => Text::_('PROGRAM'),
            'code'     => HTML::sort('UNTIS_ID', 'code', $direction, $ordering)
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems(): void
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=CategoryEdit&id=';
        $structuredItems = [];

        foreach ($this->items as $item) {
            $tip          = $item->active ? 'ORGANIZER_CLICK_TO_DEACTIVATE' : 'ORGANIZER_CLICK_TO_ACTIVATE';
            $item->active = $this->getToggle('categories', $item->id, $item->active, $tip, 'active');

            $item->program           = Helpers\Categories::getName($item->id);
            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
