<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use Organizer\Helpers\HTML;
use Organizer\Helpers\Languages;
use stdClass;

/**
 * Class loads the query's results into the display context.
 */
class Search extends ListView
{
    public $query;

    public $results;

    protected $rowStructure = ['result' => 'value', 'links' => 'value'];

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true)
    {
        $this->setTitle('ORGANIZER_SEARCH');
    }

    /**
     * Checks user authorization and initiates redirects accordingly.
     * @return void
     */
    protected function authorize()
    {
        // Public access.
    }

    /**
     * loads model data into view context
     *
     * @param string $tpl the name of the template to be used
     *
     * @return void
     */
    public function display($tpl = null)
    {
        $this->state = $this->get('State');

        $this->empty = $this->state->get('filter.search') ? '' : Languages::_('ORGANIZER_NO_SEARCH_QUERY');

        parent::display($tpl);
    }

    /**
     * @inheritDoc
     */
    protected function setHeaders()
    {
        $this->headers = ['result' => Languages::_('ORGANIZER_RESOURCE'), 'links' => Languages::_('ORGANIZER_LINKS')];
    }

    /**
     * Processes an individual list item resolving it to an array of table data values.
     *
     * @param int|string $index the row index, typically an int value, but can also be string
     * @param stdClass   $item  the item to be displayed in a table row
     * @param string     $link  the link to the individual resource
     *
     * @return array an array of property columns with their values
     */
    protected function structureItem($index, stdClass $item, string $link = ''): array
    {
        $processedItem = ['result' => '', 'links' => []];

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
                    $text = Languages::_('ORGANIZER_CURRICULUM');
                    break;
                case 'grid':
                    $icon = '<span class="icon-calendar"></span>';
                    $text = Languages::_('ORGANIZER_SCHEDULE');
                    break;
                case 'list':
                    $icon = '<span class="icon-list"></span>';
                    $text = Languages::_('ORGANIZER_INSTANCES');
                    break;
                case 'subjects':
                    $icon = '<span class="icon-list"></span>';
                    $text = Languages::_('ORGANIZER_SUBJECTS');
                    break;
                case 'subject_item':
                    $icon = '<span class="icon-book"></span>';
                    $text = Languages::_('ORGANIZER_SUBJECT');
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
    protected function structureItems()
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

            $structuredItems[$index] = $this->structureItem($index, $item);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
