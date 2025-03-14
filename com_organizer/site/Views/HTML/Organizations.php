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

use THM\Organizer\Adapters\{HTML, Text};
use THM\Organizer\Layouts\HTML\Row;

/**
 * Class loads persistent information a filtered set of organizations into the display context.
 */
class Organizations extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->addBasicButtons();
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'     => ['type' => 'check'],
            'name'      => [
                'link'       => Row::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, 'name'),
                'type'       => 'text'
            ],
            'shortName' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('SHORT_NAME'),
                'type'       => 'text'
            ],
        ];

        $this->headers = $headers;
    }
}
