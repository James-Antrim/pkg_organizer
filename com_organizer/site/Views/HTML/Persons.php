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
 * Class loads persistent information a filtered set of persons into the display context.
 */
class Persons extends ListView
{
    use Activated;

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('Person.add');
        $this->addActa();

        if (Helpers\Can::administrate()) {
            $toolbar->delete('Persons.delete')->message('DELETE_CONFIRM');
            $toolbar->standardButton('merge', Text::_('MERGE'), 'MergePersons.display')->icon('fa fa-compress');
        }

        parent::addToolBar();
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        $index           = 0;
        $structuredItems = [];
        $link            = "index.php?option=com_organizer&view=person_edit&id=";

        foreach ($this->items as $item) {
            $item->forename = empty($item->forename) ? '' : $item->forename;
            $item->username = empty($item->username) ? '' : $item->username;

            $tip          = $item->active ? 'ORGANIZER_CLICK_TO_DEACTIVATE' : 'ORGANIZER_CLICK_TO_ACTIVATE';
            $item->active = $this->getToggle('persons', $item->id, $item->active, $tip, 'active');

            if (!$organizations = Helpers\Persons::getOrganizationNames($item->id)) {
                $item->organizationID = Text::_('JNONE');
            }
            elseif (count($organizations) === 1) {
                $item->organizationID = $organizations[0];
            }
            else {
                $item->organizationID = Text::_('ORGANIZER_MULTIPLE_ORGANIZATIONS');
            }

            $item->code = empty($item->code) ? '' : $item->code;

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
        $headers = [
            'checkbox'       => '',
            'surname'        => Text::_('ORGANIZER_SURNAME'),
            'forename'       => Text::_('ORGANIZER_FORENAME'),
            'username'       => Text::_('ORGANIZER_USERNAME'),
            'active'         => Helpers\Text::_('ORGANIZER_ACTIVE'),
            'organizationID' => Text::_('ORGANIZER_ORGANIZATION'),
            't.code'         => Text::_('ORGANIZER_UNTIS_ID')
        ];

        $this->headers = $headers;
    }
}
