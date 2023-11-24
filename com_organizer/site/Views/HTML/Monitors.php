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
use THM\Organizer\Adapters\{HTML, Input, Text, Toolbar};
use THM\Organizer\Helpers\Monitors as Helper;
use THM\Organizer\Layouts\HTML\ListItem;

/**
 * Class loads persistent information a filtered set of monitors into the display context.
 */
class Monitors extends ListView
{
    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('Monitors.add');
        $toolbar->delete('Monitors.delete')->message(Text::_('DELETE_CONFIRM'));

        parent::addToolBar();
    }

    /**
     * @inheritDoc
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        if ($item->useDefaults) {
            $item->display = $options['templates'][$options['template']];
            $item->content = $options['content'];
        }
        else {
            $item->display = $options['templates'][$item->display];
        }

        $item->useDefaults = $this->getToggle('monitor', $item->id, $item->useDefaults, 'TOGGLE_DEFAULT');
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(array $options = []): void
    {
        $params   = Input::getParams();
        $template = $params->get('display');
        $content  = $params->get('content');

        $options = [
            'content'   => $content,
            'template'  => $template,
            'templates' => [
                Helper::UPCOMING => Text::_('UPCOMING_INSTANCES'),
                Helper::CURRENT  => Text::_('CURRENT_INSTANCES'),
                Helper::MIXED    => Text::_('MIXED_PLAN'),
                Helper::CONTENT  => Text::_('CONTENT_DISPLAY'),
            ]
        ];
        parent::completeItems($options);
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $this->headers = [
            'check'       => ['type' => 'check'],
            'name'        => [
                'link'       => ListItem::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('ROOM', 'r.name', $direction, $ordering),
                'type'       => 'value'
            ],
            'ip'          => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('IP', 'm.ip', $direction, $ordering),
                'type'       => 'text'
            ],
            'useDefaults' => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('DEFAULT_SETTINGS'),
                'type'       => 'value'
            ],
            'display'     => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('DISPLAY_BEHAVIOUR'),
                'type'       => 'text'
            ],
            'content'     => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('DISPLAY_CONTENT', 'm.content', $direction, $ordering),
                'type'       => 'text'
            ],
        ];
    }
}
