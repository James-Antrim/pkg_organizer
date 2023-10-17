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
 * Class loads persistent information a filtered set of degree programs into the display context.
 */
class Programs extends ListView
{
    private bool $documentAccess = false;

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $this->setTitle('ORGANIZER_PROGRAMS');

        if ($this->documentAccess) {
            $toolbar = Toolbar::getInstance();

            $toolbar->appendButton('Standard', 'new', Text::_('ORGANIZER_ADD'), 'programs.add', false);
            $toolbar->appendButton('Standard', 'edit', Text::_('ORGANIZER_EDIT'), 'programs.edit', true);
            $toolbar->appendButton('Standard', 'upload', Text::_('ORGANIZER_IMPORT_LSF'), 'programs.import', true);
            $toolbar->appendButton('Standard', 'loop', Text::_('ORGANIZER_UPDATE_SUBJECTS'), 'programs.update', true);

            if (Helpers\Can::administrate()) {
                $toolbar->appendButton(
                    'Confirm',
                    Text::_('ORGANIZER_DELETE_CONFIRM'),
                    'delete',
                    Text::_('ORGANIZER_DELETE'),
                    'programs.delete',
                    true
                );
            }

            $toolbar->appendButton('Standard', 'eye-open', Text::_('ORGANIZER_ACTIVATE'), 'programs.activate', true);
            $toolbar->appendButton('Standard', 'eye-close', Text::_('ORGANIZER_DEACTIVATE'), 'programs.deactivate', true);
        }
    }

    /**
     * @inheritdoc
     */
    protected function authorize(): void
    {
        if (!Application::backend()) {
            return;
        }

        if (!$this->documentAccess = Helpers\Can::documentTheseOrganizations()) {
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

        $headers = [
            'checkbox' => '',
            'name' => Helpers\HTML::sort('NAME', 'name', $direction, $ordering)
        ];

        if (!Application::backend()) {
            $headers['links'] = '';
        } else {
            $headers['active'] = Text::_('ORGANIZER_ACTIVE');
        }

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems(): void
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
                $checkbox = Helpers\HTML::_('grid.id', $index, $program->id);
                $thisLink = $editLink . $program->id;
            } else {
                $access   = Helpers\Can::document('program', (int) $program->id);
                $checkbox = $access ? Helpers\HTML::_('grid.id', $index, $program->id) : '';
                $thisLink = $itemLink . $program->id;
            }


            $structuredItems[$index]             = [];
            $structuredItems[$index]['checkbox'] = $checkbox;
            $structuredItems[$index]['name']     = Helpers\HTML::_('link', $thisLink, $program->name);

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
}
