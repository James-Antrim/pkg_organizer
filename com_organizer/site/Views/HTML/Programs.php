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
use THM\Organizer\Helpers\Can;

/**
 * Class loads persistent information a filtered set of degree programs into the display context.
 */
class Programs extends ListView
{
    use Activated;

    private bool $documentAccess = false;

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        if ($this->documentAccess) {
            $toolbar = Toolbar::getInstance();

            $toolbar->addNew('Program.add');
            $toolbar->standardButton('upload', Text::_('IMPORT_LSF'), 'Programs.import')->listCheck(true)->icon('fa fa-upload');
            $toolbar->standardButton('update', Text::_('UPDATE_SUBJECTS'),
                'Programs.update')->listCheck(true)->icon('fa fa-sync');

            if (Can::administrate()) {
                $toolbar->delete('Programs.delete')->message(Text::_('DELETE_CONFIRM'));
            }

            // No implicit basis in scheduling to deactivate programs.
            $this->addActa(true);
        }

        parent::addToolBar();
    }

    /**
     * @inheritdoc
     */
    protected function completeItems(): void
    {
        $editLink = 'index.php?option=com_organizer&view=program_edit&id=';
        $itemLink = 'index.php?option=com_organizer&view=program_item&id=';
        $links    = '';

        if (!Application::backend()) {
            $template = "<a class=\"hasTooltip\" href=\"URL\" target=\"_blank\" title=\"TIP\">ICON</a>";

            $icon  = "<span class=\"icon-grid-2\"></span>";
            $tip   = Text::_('ORGANIZER_CURRICULUM');
            $url   = 'index.php?option=com_organizer&view=curriculum&programID=XXXX';
            $links .= str_replace('URL', $url, str_replace('TIP', $tip, str_replace('ICON', $icon, $template)));

            $icon  = "<span class=\"icon-list\"></span>";
            $tip   = Text::_('ORGANIZER_SUBJECTS');
            $url   = 'index.php?option=com_organizer&view=subjects&programID=XXXX';
            $links .= str_replace('URL', $url, str_replace('TIP', $tip, str_replace('ICON', $icon, $template)));
        }

        $index           = 0;
        $structuredItems = [];
        foreach ($this->items as $program) {
            // The backend entries have been prefiltered for access
            if (Application::backend()) {
                $checkbox = HTML::checkBox($index, $program->id);
                $thisLink = $editLink . $program->id;
            }
            else {
                $access   = Can::document('program', (int) $program->id);
                $checkbox = $access ? HTML::checkBox($index, $program->id) : '';
                $thisLink = $itemLink . $program->id;
            }


            $structuredItems[$index]             = [];
            $structuredItems[$index]['checkbox'] = $checkbox;
            $structuredItems[$index]['name']     = HTML::link($thisLink, $program->name);

            if (Application::backend()) {
                $tip    = $program->active ? 'ORGANIZER_CLICK_TO_DEACTIVATE' : 'ORGANIZER_CLICK_TO_ACTIVATE';
                $toggle = $this->getToggle('programs', $program->id, $program->active, $tip, 'active');

                $structuredItems[$index]['active'] = $toggle;
            }

            if ($links) {
                $structuredItems[$index]['links'] = str_replace('XXXX', $program->id, $links);
            }

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

        $headers = [
            'checkbox' => '',
            'name'     => HTML::sort('NAME', 'name', $direction, $ordering)
        ];

        if (!Application::backend()) {
            $headers['links'] = '';
        }
        else {
            $headers['active'] = Text::_('ACTIVE');
        }

        $this->headers = $headers;
    }
}
