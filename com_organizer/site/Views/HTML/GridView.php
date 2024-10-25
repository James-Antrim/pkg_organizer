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

use Joomla\CMS\MVC\View\ListView as Base;
use Joomla\CMS\Uri\Uri;
use stdClass;
use THM\Organizer\Adapters\{Document, Text};
use THM\Organizer\Controllers\Controller;
use THM\Organizer\Helpers\Can;

/**
 * Class loads a grid into the display context.
 */
abstract class GridView extends Base
{
    use Configured;
    use Tasked;
    use Titled;

    /** @var string The default text for an empty result set. */
    public string $empty = '';
    /**
     * The header information to display indexed by the referenced attribute.
     * @var array
     */
    public array $headers = [];
    protected string $layout = 'grid';
    /** @var array the open items. */
    public array $toDo = [];

    /**
     * Constructor
     *
     * @param   array  $config  An optional associative array of configuration settings.
     */
    public function __construct(array $config)
    {
        $this->option = 'com_organizer';

        // If this is not explicitly set going in Joomla will default to default without looking at the object property value.
        $config['layout'] = $this->layout;

        parent::__construct($config);

        $this->baseURL = $this->baseURL ?: Uri::base() . 'index.php?option=com_organizer';
        $this->configure();
    }

    /**
     * @inheritDoc
     * ListView adds the title and configuration button if user has access. Inheriting classes are responsible for
     * their own buttons.
     */
    protected function addToolBar(): void
    {
        // MVC name identity is now the internal standard
        $controller = $this->getName();
        $this->setTitle(strtoupper($controller));
    }

    /**
     * Checks user authorization and initiates redirects accordingly. General access is now regulated through the
     * below-mentioned functions. Views with public access can be further restricted here as necessary.
     * @return void
     * @see Controller::display(), Can::view()
     */
    protected function authorize(): void
    {
        // See comment.
    }

    /**
     * Readies an item for output.
     *
     * @param   int       $index  the current iteration number
     * @param   stdClass  $item   the current item being iterated
     * @param   array     $options
     *
     * @return void
     */
    protected function completeItem(int $index, stdClass $item, array $options = []): void
    {
        // Overridable as needed.
    }

    /**
     * Processes items for output.
     *
     * @param   array  $options
     *
     * @return void
     */
    protected function completeItems(array $options = []): void
    {
        $index = 0;

        foreach ($this->items as $item) {
            $this->completeItem($index, $item, $options);
            $index++;
        }
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        $this->authorize();

        parent::display($tpl);
    }

    /**
     * Initializes the headers after the form and state properties have been initialized.
     * @return void
     */
    abstract protected function initializeColumns(): void;

    /**
     * @inheritDoc
     */
    protected function initializeView(): void
    {
        parent::initializeView();

        $this->empty = $this->empty ?: Text::_('EMPTY_RESULT_SET');

        $this->setSubTitle();
        $this->setSupplement();
        $this->grid = $this->get('Grid');
        $this->completeItems();
        $this->initializeColumns();
        $this->modifyDocument();
    }

    /**
     * Adds scripts and stylesheets to the document.
     */
    protected function modifyDocument(): void
    {
        Document::script('cacheMiss');
        Document::style('grid');
    }
}
