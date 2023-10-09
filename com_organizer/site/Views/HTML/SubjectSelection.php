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
use Organizer\Adapters;
use Organizer\Adapters\Toolbar;
use Organizer\Helpers;

/**
 * Class loads subject information into the display context.
 */
class SubjectSelection extends ListView
{
    protected $layout = 'list_modal';

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true)
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'new', Helpers\Languages::_('ORGANIZER_ADD'), 'x', true);
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        if (!Helpers\Can::documentTheseOrganizations()) {
            Helpers\OrganizerHelper::error(403);
        }
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument()
    {
        parent::modifyDocument();

        Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/modal.css');
    }

    /**
     * @inheritdoc
     */
    protected function setHeaders()
    {
        $direction = $this->state->get('list.direction');
        $ordering  = $this->state->get('list.ordering');
        $headers   = [
            'checkbox' => Helpers\HTML::_('grid.checkall'),
            'name' => Helpers\HTML::sort('NAME', 'name', $direction, $ordering),
            'program' => Helpers\Languages::_('ORGANIZER_PROGRAMS')
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

        foreach ($this->items as $subject) {
            if (!Helpers\Can::document('subject', (int) $subject->id)) {
                continue;
            }

            $name = $subject->name;
            $name .= empty($subject->code) ? '' : " - $subject->code";

            $structuredItems[$index]             = [];
            $structuredItems[$index]['checkbox'] = Helpers\HTML::_('grid.id', $index, $subject->id);
            $structuredItems[$index]['name']     = $name;
            $structuredItems[$index]['programs'] = Helpers\Subjects::getProgramName($subject->id);

            $index++;
        }

        $this->items = $structuredItems;
    }
}
