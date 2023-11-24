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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use THM\Organizer\Adapters\{Document, Form, Text, Toolbar};
use THM\Organizer\Helpers;

/**
 * Class loads statistical information about appointments into the display context.
 */
class Statistics extends OldFormView
{
    public const METHOD_USE = 1, PLANNED_PRESENCE_TYPE = 2, PRESENCE_USE = 3, REGISTRATIONS = 4;

    public Form $filterForm;

    public array $grid;

    protected string $layout = 'statistics-wrapper';

    public Registry $state;

    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $this->setTitle('ORGANIZER_STATISTICS');
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton(
            'Standard',
            'play',
            Text::_('ORGANIZER_GENERATE_STATISTIC'),
            'Statistics.display',
            false
        );
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        $this->state = $this->get('State');
        $this->form  = $this->get('Form');
        $this->grid  = $this->get('Grid');

        // Allows for view specific toolbar handling
        $this->addToolBar();
        $this->setSubtitle();

        $this->modifyDocument();
        HtmlView::display($tpl);
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        BaseView::modifyDocument();

        Document::script('statistics');
        //Document::style('statistics');
    }

    /**
     * Adds a text describing the selected layout as a subtitle.
     * @return void modifies the course
     */
    protected function setSubtitle(): void
    {
        $termID    = $this->state->get('conditions.termID');
        $endDate   = Helpers\Terms::getEndDate($termID);
        $endDate   = Helpers\Dates::formatDate($endDate);
        $startDate = Helpers\Terms::getStartDate($termID);
        $startDate = Helpers\Dates::formatDate($startDate);

        $text = match ($this->state->get('conditions.statistic')) {
            self::REGISTRATIONS => Text::sprintf('ORGANIZER_REGISTRATIONS_DESC', $startDate, $endDate),
            self::METHOD_USE => Text::sprintf('ORGANIZER_METHOD_USE_DESC', $startDate, $endDate),
            self::PLANNED_PRESENCE_TYPE => Text::sprintf('ORGANIZER_PLANNED_PRESENCE_TYPE_DESC', $startDate, $endDate),
            self::PRESENCE_USE => Text::sprintf('ORGANIZER_PRESENCE_USE_DESC', $startDate, $endDate),
            default => '',
        };

        $this->subtitle = $text ? "<h4>$text</h4>" : $text;
    }
}
