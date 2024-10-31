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
use THM\Organizer\Helpers\{Can, Persons as Helper};
use THM\Organizer\Layouts\HTML\Row;

/**
 * Class loads persistent information a filtered set of persons into the display context.
 */
class Persons extends ListView
{
    use Activated;
    use Merged;

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->addAdd();
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
        $item->forename = empty($item->forename) ? '' : $item->forename;
        $item->username = empty($item->username) ? '' : $item->username;
        $item->active   = HTML::toggle($index, Helper::ACTIVE_STATES[$item->active], 'Persons');

        if (!$organizations = Helper::getOrganizationNames($item->id)) {
            $item->organizationID = Text::_('NONE');
        }
        elseif (count($organizations) === 1) {
            $item->organizationID = $organizations[0];
        }
        else {
            $item->organizationID = Text::_('MULTIPLE_ORGANIZATIONS');
        }

        $item->code = empty($item->code) ? '' : $item->code;
    }

    /**
     * @inheritDoc
     */
    public function initializeColumns(): void
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');

        $this->headers = [
            'check'          => ['type' => 'check'],
            'surname'        => [
                'link'       => Row::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('SURNAME', 'surname, forename', $direction, $ordering),
                'type'       => 'text'
            ],
            'forename'       => [
                'link'       => Row::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('FORENAME', 'surname, forename', $direction, $ordering),
                'type'       => 'text'
            ],
            'username'       => [
                'link'       => Row::DIRECT,
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => HTML::sort('USERNAME', 'username', $direction, $ordering),
                'type'       => 'text'
            ],
            'active'         => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ACTIVE'),
                'type'       => 'value'
            ],
            'organizationID' => [
                'properties' => ['class' => 'w-5 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('ORGANIZATION'),
                'type'       => 'text'
            ],
            'code'           => [
                'properties' => ['class' => 'w-10 d-md-table-cell', 'scope' => 'col'],
                'title'      => Text::_('UNTIS_ID'),
                'type'       => 'text'
            ],
        ];
    }
}
