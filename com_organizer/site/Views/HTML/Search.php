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

use Joomla\CMS\Uri\Uri;
use stdClass;
use THM\Organizer\Adapters\{HTML, Text};

/**
 * Class loads the query's results into the display context.
 */
class Search extends ListView
{
    public $query;

    public $results;

    /**
     * Processes an individual list item resolving it to an array of table data values.
     *
     * @param   int|string  $index  the row index, typically an int value, but can also be string
     * @param   stdClass    $item   the item to be displayed in a table row
     * @param   string      $link   the link to the individual resource
     *
     * @return array an array of property columns with their values
     */
    protected function completeItem(int|string $index, stdClass $item, string $link = ''): array
    {
        $processedItem = [];

        $attribs = ['target' => '_blank'];
        $result  = '<span class="resource-item">' . $item->text . '</span>';

        if ($item->description) {
            $result .= '<br><span class="comment">';
            $result .= is_array($item->description) ? implode(', ', $item->description) : $item->description;
            $result .= '</span>';
        }

        $links                   = [];
        $processedItem['result'] = $result;

        foreach ($item->links as $type => $link) {
            $icon = '';
            $text = '';

            switch ($type) {
                case 'curriculum':
                    $icon = '<span class="icon-grid-2"></span>';
                    $text = Text::_('CURRICULUM');
                    break;
                case 'grid':
                    $icon = '<span class="icon-calendar"></span>';
                    $text = Text::_('SCHEDULE');
                    break;
                case 'list':
                    $icon = '<span class="icon-list"></span>';
                    $text = Text::_('INSTANCES');
                    break;
                case 'subjects':
                    $icon = '<span class="icon-list"></span>';
                    $text = Text::_('SUBJECTS');
                    break;
                case 'subject_item':
                    $icon = '<span class="icon-book"></span>';
                    $text = Text::_('SUBJECT');
                    break;
            }

            $links[] = HTML::link(Uri::base() . $link, $icon . $text, $attribs);
        }

        $processedItem['links'] = implode(' ', $links);

        return $processedItem;
    }

    /**
     * Processes the items in a manner specific to the view, so that a generalized  output in the layout can occur.
     * @return void processes the class items property
     */
    protected function completeItems(): void
    {
        $index = 0;
        $start = (int) $this->state->get('list.start');
        $end   = $start + (int) $this->state->get('list.limit');

        $structuredItems = [];
        foreach ($this->items as $key => $item) {
            // Emulate pagination.
            if ($end and $key >= $end) {
                break;
            }

            if ($start and $key < $start) {
                continue;
            }

            $structuredItems[$index] = $this->completeItem($index, $item);
            $index++;
        }

        $this->items = $structuredItems;
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        $this->state = $this->get('State');

        $this->empty = $this->state->get('filter.search') ? '' : Text::_('NO_SEARCH_QUERY');

        parent::display($tpl);
    }

    /**
     * @inheritDoc
     */
    protected function initializeColumns(): void
    {
        $this->headers = ['result' => Text::_('RESOURCE'), 'links' => Text::_('LINKS')];
    }
}
