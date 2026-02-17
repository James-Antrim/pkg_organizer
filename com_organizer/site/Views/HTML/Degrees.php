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

use stdClass;
use THM\Organizer\Adapters\{HTML, Text};
use THM\Organizer\Helpers\Degrees as Helper;
use THM\Organizer\Layouts\HTML\Row;

/**
 * Class loads persistent information a filtered set of degrees into the display context.
 */
class Degrees extends ListView
{
    use Activated;

    /** @inheritDoc */
    protected function addToolBar(): void
    {
        $this->addAdd();
        $this->addActa();
        $this->addDelete();
        parent::addToolBar();
    }

    /** @inheritDoc */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->active = HTML::toggle($index, Helper::ACTIVE_STATES[$item->active], 'Degrees');
    }

    /** @inheritDoc */
    public function initializeColumns(): void
    {
        $this->headers = [
            'check'         => ['type' => 'check'],
            'name'          => [
                'link'       => Row::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('NAME'),
                'type'       => 'text'
            ],
            'abbreviation'  => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ABBREVIATION'),
                'type'       => 'text'
            ],
            'code'          => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('DEGREE_CODE'),
                'type'       => 'text'
            ],
            'statisticCode' => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('STATISTIC_CODE'),
                'type'       => 'text'
            ],
            'active'        => [
                'properties' => ['class' => 'w-5 d-none d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ACTIVE'),
                'type'       => 'value'
            ],
        ];
    }
}
