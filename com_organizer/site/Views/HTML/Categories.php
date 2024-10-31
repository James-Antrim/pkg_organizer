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
use stdClass;
use THM\Organizer\Helpers\{Can, Categories as Helper};
use THM\Organizer\Layouts\HTML\Row;

/**
 * Class loads persistent information a filtered set of event categories into the display context.
 */
class Categories extends ListView
{
    use Activated;
    use Merged;

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        // Resource creation occurs in Untis.

        $this->addActa();
        $this->addMerge();

        if (Can::administrate()) {
            $this->addDelete();
        }

        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->active  = HTML::toggle($index, Helper::ACTIVE_STATES[$item->active], 'Categories');
        $item->program = Helper::name($item->id);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'check'   => ['type' => 'check'],
            'name'    => [
                'link'       => Row::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, 'name'),
                'type'       => 'text'
            ],
            'active'  => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ACTIVE'),
                'type'       => 'value'
            ],
            'program' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('PROGRAM'),
                'type'       => 'text'
            ],
            'code'    => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('UNTIS_ID'),
                'type'       => 'text'
            ],
        ];

        $this->headers = $headers;
    }
}
