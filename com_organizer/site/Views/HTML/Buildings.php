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
use THM\Organizer\Adapters\{Application, HTML, Text};
use THM\Organizer\Helpers\{Buildings as Helper, Campuses, Can};
use THM\Organizer\Layouts\HTML\Row;

/**
 * Class loads a filtered set of buildings into the display context.
 */
class Buildings extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $this->addBasicButtons();
        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function authorize(): void
    {
        if (!Can::fm()) {
            Application::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        $item->campusID     = Campuses::name($item->campusID);
        $item->propertyType = match ($item->propertyType) {
            Helper::OWNED => Text::_('OWNED'),
            Helper::RENTED => Text::_('RENTED'),
            Helper::USED => Text::_('USED'),
            default => Text::_('UNKNOWN'),
        };
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $direction     = $this->state->get('list.direction');
        $this->headers = [
            'check'        => ['type' => 'check'],
            'name'         => [
                'link'       => Row::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('NAME', 'name', $direction, 'name'),
                'type'       => 'value'
            ],
            'campusID'     => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('CAMPUS'),
                'type'       => 'text'
            ],
            'propertyType' => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('PROPERTY_TYPE'),
                'type'       => 'text'
            ],
            'address'      => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('STREET'),
                'type'       => 'text'
            ],
        ];
    }
}
