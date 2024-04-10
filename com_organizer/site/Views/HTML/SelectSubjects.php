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

use THM\Organizer\Adapters\{HTML, Text, Toolbar};
use THM\Organizer\Helpers;

/**
 * Class loads subject information into the display context.
 */
class SelectSubjects extends Subjects
{
    protected string $layout = 'modallist';

    /**
     * @inheritDoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->addNew('x', Text::_('ADD'));
    }

    /**
     * @inheritDoc
     */
    protected function completeItems(): void
    {
        $index           = 0;
        $structuredItems = [];

        foreach ($this->items as $subject) {
            if (!Helpers\Subjects::documentable((int) $subject->id)) {
                continue;
            }

            $name = $subject->name;
            $name .= empty($subject->code) ? '' : " - $subject->code";

            $structuredItems[$index]             = [];
            $structuredItems[$index]['checkbox'] = HTML::checkBox($index, $subject->id);
            $structuredItems[$index]['name']     = $name;
            $structuredItems[$index]['programs'] = Helpers\Subjects::programName($subject->id);

            $index++;
        }

        $this->items = $structuredItems;
    }

    /**
     * @inheritDoc
     */
    protected function initializeColumns(): void
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
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        //Document::style('modal');
    }
}
