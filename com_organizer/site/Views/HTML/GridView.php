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

    protected string $baseURL = '';

    /** @var string The default text for an empty result set. */
    public string $empty = '';
    /**
     * A multidimensional array structuring the retrieved data into a grid for display.
     * @var array
     */
    public array $grid = [];
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
        $this->title(strtoupper($controller));
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
     * Fills a grid structure as appropriate in the inheriting view.
     * @return void
     */
    abstract protected function fill(): void;

    /**
     * Creates a grid structure as appropriate in the inheriting view.
     * @return void
     */
    abstract protected function grid(): void;

    /**
     * @inheritDoc
     */
    protected function initializeView(): void
    {
        parent::initializeView();

        $this->empty = $this->empty ?: Text::_('EMPTY_RESULT_SET');

        $this->subTitle();
        $this->supplement();
        $this->grid();
        $this->fill();
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
