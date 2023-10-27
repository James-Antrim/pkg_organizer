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
use THM\Organizer\Adapters\{Application, Document, HTML, Text, Toolbar};
use THM\Organizer\Helpers;

/**
 * Class loads subject information into the display context.
 */
class SubjectSelection extends ListView
{
    protected string $layout = 'list_modal';

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'new', Text::_('ORGANIZER_ADD'), 'x', true);
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
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/modal.css');
    }

    /**
     * @inheritdoc
     */
    protected function setHeaders(): void
    {
        $direction = $this->state->get('list.direction');
        $ordering  = $this->state->get('list.ordering');
        $headers   = [
            'checkbox' => HTML::checkAll(),
            'name'     => HTML::sort('NAME', 'name', $direction, $ordering),
            'program'  => Text::_('PROGRAMS')
        ];

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems(): void
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
            $structuredItems[$index]['checkbox'] = HTML::checkBox($index, $subject->id);
            $structuredItems[$index]['name']     = $name;
            $structuredItems[$index]['programs'] = Helpers\Subjects::getProgramName($subject->id);

            $index++;
        }

        $this->items = $structuredItems;
    }
}
