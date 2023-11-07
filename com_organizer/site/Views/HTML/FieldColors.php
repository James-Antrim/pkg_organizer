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
        // Divergent naming scheme
        Toolbar::setTitle('FIELD_COLORS');

        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('FieldColor.add')->icon('fa fa-link');
        $toolbar->delete('FieldColors.delete')->message(Text::_('DELETE_CONFIRM'));

        $toolbar->standardButton('newField', Text::_('FIELD_NEW'), 'Field.add')->icon('fa fa-lightbulb');
        $toolbar->standardButton('newColor', Text::_('COLOR_NEW'), 'Color.add')->icon('fa fa-palette');


        if (Can::administrate()) {
            $toolbar = Toolbar::getInstance();
            $toolbar->preferences('com_organizer');
        }
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
    protected function completeItems(): void
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=field_color_edit&id=';
        $structuredItems = [];

        foreach ($this->items as $item) {
            $item->color = Helpers\Colors::getListDisplay($item->color, $item->colorID);

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
            'field'        => HTML::sort('FIELD', 'field', $direction, $ordering),
            'organization' => HTML::sort('ORGANIZATION', 'organization', $direction, $ordering),
            'color'        => Text::_('COLOR')
        ];

        $this->headers = $headers;
    }
}
