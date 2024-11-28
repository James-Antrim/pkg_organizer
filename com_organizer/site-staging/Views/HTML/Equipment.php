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

use THM\Organizer\Adapters\{HTML};

/**
 * Class loads persistent information a filtered set of room types into the display context.
 */
class Equipment extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = false): void
    {
        $this->addBasicButtons();
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => '',
            'code'     => HTML::sort('UNTIS_ID', 'code', $direction, $ordering),
            'name'     => HTML::sort('NAME', 'name', $direction, $ordering)
        ];

        $this->headers = $headers;
    }
}
