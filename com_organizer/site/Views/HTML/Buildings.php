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

use THM\Organizer\Adapters\{Application, Text};
use THM\Organizer\Helpers;

/**
 * Class loads a filtered set of buildings into the display context.
 */
class Buildings extends ListView
{
    private const OWNED = 1, RENTED = 2, USED = 3;

    protected $rowStructure = [
        'checkbox' => '',
        'name' => 'link',
        'campusID' => 'link',
        'propertyType' => 'link',
        'address' => 'link'
    ];

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Can::manage('facilities')) {
            Application::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    public function setHeaders()
    {
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => '',
            'name' => Helpers\HTML::sort('NAME', 'name', $direction, 'name'),
            'campusID' => Text::_('ORGANIZER_CAMPUS'),
            'propertyType' => Text::_('ORGANIZER_PROPERTY_TYPE'),
            'address' => Text::_('ORGANIZER_STREET')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $link            = 'index.php?option=com_organizer&view=building_edit&id=';
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item) {
            $item->campusID = Helpers\Campuses::getName($item->campusID);

            switch ($item->propertyType) {
                case self::OWNED:
                    $item->propertyType = Text::_('ORGANIZER_OWNED');
                    break;

                case self::RENTED:
                    $item->propertyType = Text::_('ORGANIZER_RENTED');
                    break;

                case self::USED:
                    $item->propertyType = Text::_('ORGANIZER_USED');
                    break;

                default:
                    $item->propertyType = Text::_('ORGANIZER_UNKNOWN');
                    break;
            }

            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
