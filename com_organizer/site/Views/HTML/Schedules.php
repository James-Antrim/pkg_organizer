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

use THM\Organizer\Adapters\{Application, Text, Toolbar};
use THM\Organizer\Helpers;

/**
 * Class loads persistent information a filtered set of schedules into the display context.
 */
class Schedules extends ListView
{
    protected $rowStructure = [
        'checkbox' => '',
        'organizationName' => 'value',
        'termName' => 'value',
        'userName' => 'value',
        'created' => 'value'
    ];

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true)
    {
        $this->setTitle('ORGANIZER_SCHEDULES');
        $admin   = Helpers\Can::administrate();
        $toolbar = Toolbar::getInstance();

        $toolbar->appendButton('Standard', 'upload', Text::_('ORGANIZER_UPLOAD'), 'schedules.edit', false);

        if ($this->state->get('filter.organizationID') and $this->state->get('filter.termID')) {
            /*$toolbar->appendButton( 'Standard', 'envelope', Text::_('ORGANIZER_NOTIFY_CHANGES'), 'schedules.notify', true);*/

            $toolbar->appendButton(
                'Confirm',
                Text::_('ORGANIZER_REFERENCE_CONFIRM'),
                'share-alt',
                Text::_('ORGANIZER_REFERENCE'),
                'schedules.reference',
                true
            );

            if ($admin) {
                $toolbar->appendButton('Standard', 'loop', Text::_('ORGANIZER_REBUILD'), 'schedules.rebuild', false);
                $toolbar->appendButton(
                    'Confirm',
                    Text::_('ORGANIZER_DELETE_CONFIRM'),
                    'delete',
                    Text::_('ORGANIZER_DELETE'),
                    'schedules.delete',
                    true
                );
            }
        }

        if ($admin) {
            $toolbar->appendButton(
                'Standard',
                'filter',
                'Filter Relevance',
                'schedules.filterRelevance',
                false
            );
        }
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Can::scheduleTheseOrganizations()) {
            Application::error(403);
        }
    }

    /**
     * @inheritdoc
     */
    public function setHeaders()
    {
        $headers = [
            'checkbox' => Helpers\HTML::_('grid.checkall'),
            'organizationName' => Text::_('ORGANIZER_ORGANIZATION'),
            'termName' => Text::_('ORGANIZER_TERM'),
            'userName' => Text::_('ORGANIZER_USERNAME'),
            'created' => Text::_('ORGANIZER_CREATION_DATE')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $item) {
            $creationDate  = Helpers\Dates::formatDate($item->creationDate);
            $creationTime  = Helpers\Dates::formatTime($item->creationTime);
            $item->created = "$creationDate / $creationTime";

            $structuredItems[$index] = $this->structureItem($index, $item);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
