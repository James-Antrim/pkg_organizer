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

use Exception;
use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\View\HtmlView;
use THM\Organizer\Adapters\{Application, Document, Input, Text, Toolbar};

/**
 * View class for setting general context variables.
 */
abstract class BaseView extends HtmlView
{
    use Configured;

    public $form;

    /**
     * The name of the layout to use during rendering.
     * @var string
     */
    protected string $layout = 'default';

    /**
     * Inheritance stems from BaseDatabaseModel, not BaseModel. BaseDatabaseModel is higher in the Joomla internal
     * hierarchy used for Joomla Admin, Form, List, ... models which in turn are the parents for the Organizer abstract
     * classes of similar names.
     * @var BaseDatabaseModel
     */
    protected BaseDatabaseModel $model;

    public int $refresh = 0;

    public string $submenu = '';

    public string $subtitle = '';

    public string $supplement = '';

    public string $title = '';

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);
        $this->configure();
    }

    /**
     * @inheritdoc
     * Does not dump the responsibility for exception handling onto inheriting classes.
     */
    public function display($tpl = null): void
    {
        try {
            parent::display($tpl);
        }
        catch (Exception $exception) {
            Application::handleException($exception);
        }
    }

    /**
     * @inheritDoc
     */
    public function getLayout(): string
    {
        return $this->layout ?: strtolower($this->_name);
    }

    /**
     * Modifies document and adds scripts and styles.
     * @return void
     */
    protected function modifyDocument(): void
    {
        //Document::setCharset();
        //Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/global.css');
        //Document::addStyleSheet(Uri::root() . 'media/jui/css/bootstrap-extended.css');

        //HTML::_('bootstrap.tooltip', '.hasTooltip', ['placement' => 'right']);
    }

    /**
     * @inheritDoc
     */
    public function setModel($model, $default = false): BaseDatabaseModel
    {
        $this->model = parent::setModel($model, $default);

        return $this->model;
    }

    /**
     * Prepares the title for standard HTML output.
     *
     * @param   string  $standard     the title to display
     * @param   string  $conditional  the conditional title to display
     *
     * @return void
     */
    protected function setTitle(string $standard, string $conditional = ''): void
    {
        $params = Input::getParams();

        if ($params->get('show_page_heading') and $params->get('page_title')) {
            $title = $params->get('page_title');
        }
        else {
            $title = empty($conditional) ? Text::_($standard) : $conditional;
        }

        // Backend => Joomla standard title/toolbar output property declared dynamically by Joomla
        Toolbar::setTitle($title);

        // Frontend => self developed title/toolbar output
        $this->title = $title;

        Document::setTitle(strip_tags($title) . ' - ' . Application::getApplication()->get('sitename'));
    }
}
