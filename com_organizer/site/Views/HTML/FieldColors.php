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
 * Class loads persistent information a filtered set of fields (of expertise) into the display context.
 */
class FieldColors extends ListView
{
    protected array $rowStructure = ['checkbox' => '', 'field' => 'link', 'organization' => 'link', 'color' => 'value'];

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->setTitle('ORGANIZER_FIELD_COLORS');
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'link', Text::_('ORGANIZER_ADD'), "field_colors.add", false);
        $toolbar->appendButton('Standard', 'edit', Text::_('ORGANIZER_EDIT'), "field_colors.edit", true);

        $toolbar->appendButton(
            'Confirm',
            Text::_('ORGANIZER_DELETE_CONFIRM'),
            'delete',
            Text::_('ORGANIZER_DELETE'),
            "field_colors.delete",
            true
        );

        $toolbar->appendButton('Standard', 'lamp', Text::_('ORGANIZER_FIELD_NEW'), 'fields.add', false);
        $toolbar->appendButton('Standard', 'palette', Text::_('ORGANIZER_COLOR_NEW'), 'colors.add', false);
    }

    /**
     * @inheritdoc
     */
    protected function authorize(): void
    {
        if (!Helpers\Can::documentTheseOrganizations()) {
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
            'checkbox'     => '',
            'field'        => HTML::sort('FIELD', 'field', $direction, $ordering),
            'organization' => HTML::sort('ORGANIZATION', 'organization', $direction, $ordering),
            'color'        => Text::_('COLOR')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems(): void
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=field_color_edit&id=';
        $structuredItems = [];

        foreach ($this->items as $item) {
            $item->color = Helpers\Colors::getListDisplay($item->color, $item->colorID);

            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
