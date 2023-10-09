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

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Uri\Uri;
use Joomla\Registry\Registry;
use Organizer\Adapters;
use Organizer\Helpers;

/**
 * Class loads statistical information about appointments into the display context.
 */
class Statistics extends FormView
{
    public const METHOD_USE = 1, PLANNED_PRESENCE_TYPE = 2, PRESENCE_USE = 3, REGISTRATIONS = 4;

    public $filterForm;

    public $grid;

    protected $layout = 'statistics-wrapper';

    /**
     * @var Registry
     */
    public $state;

    public $statistic;

    /**
     * @inheritDoc
     */
    protected function addToolBar()
    {
        $this->setTitle('ORGANIZER_STATISTICS');
        $toolbar = Adapters\Toolbar::getInstance();
        $toolbar->appendButton(
            'Standard',
            'play',
            Helpers\Languages::_('ORGANIZER_GENERATE_STATISTIC'),
            'Statistics.display',
            false
        );
    }

    /**
     * Execute and display a template script.
     *
     * @param string $tpl The name of the template file to parse; automatically searches through the template paths.
     *
     * @return void sets context variables and uses the parent's display method
     */
    public function display($tpl = null)
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
    protected function modifyDocument()
    {
        BaseView::modifyDocument();

        Adapters\Document::addScript(Uri::root() . 'components/com_organizer/js/statistics.js');
        Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/statistics.css');
    }

    /**
     * Adds a text describing the selected layout as a subtitle.
     * @return void modifies the course
     */
    protected function setSubtitle()
    {
        $termID    = $this->state->get('conditions.termID');
        $endDate   = Helpers\Terms::getEndDate($termID);
        $endDate   = Helpers\Dates::formatDate($endDate);
        $startDate = Helpers\Terms::getStartDate($termID);
        $startDate = Helpers\Dates::formatDate($startDate);

        switch ($this->state->get('conditions.statistic')) {
            case self::REGISTRATIONS:
                $text = sprintf(Helpers\Languages::_('ORGANIZER_REGISTRATIONS_DESC'), $startDate, $endDate);
                break;
            case self::METHOD_USE:
                $text = sprintf(Helpers\Languages::_('ORGANIZER_METHOD_USE_DESC'), $startDate, $endDate);
                break;
            case self::PLANNED_PRESENCE_TYPE:
                $text = sprintf(Helpers\Languages::_('ORGANIZER_PLANNED_PRESENCE_TYPE_DESC'), $startDate, $endDate);
                break;
            case self::PRESENCE_USE:
                $text = sprintf(Helpers\Languages::_('ORGANIZER_PRESENCE_USE_DESC'), $startDate, $endDate);
                break;
            default:
                $text = '';
                break;
        }

        $this->subtitle = $text ? "<h4>$text</h4>" : $text;
    }
}
