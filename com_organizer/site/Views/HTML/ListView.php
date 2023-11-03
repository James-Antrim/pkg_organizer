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

use Joomla\CMS\MVC\Model\BaseDatabaseModel;
use Joomla\CMS\MVC\View\ListView as Base;
use Joomla\CMS\Uri\Uri;
use stdClass;
use THM\Organizer\Adapters\{Document, HTML, Input, Text, Toolbar};
use THM\Organizer\Controllers\Controller;
use THM\Organizer\Helpers\Can;
use THM\Organizer\Models\ListModel;

/**
 * Class loads a filtered set of resources into the display context. Specific resource determined by extending class.
 */
abstract class ListView extends Base
{
    use Configured, ToCed;

    public bool $allowBatch = false;
    public string $empty = '';
    /**
     * The header information to display indexed by the referenced attribute.
     * @var array
     */
    public array $headers = [];
    protected string $layout = 'list';
    /** @var ListModel */
    protected BaseDatabaseModel $model;
    protected array $rowStructure = [];
    protected bool $sameTab = false;
    public string $supplement = '';
    protected bool $structureEmpty = false;
    /** @var array The default text for an empty result set. */
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

        $this->configure();
    }

    /**
     * Creates a subtitle element from the term name and the start and end dates of the course.
     * @return void modifies the course
     */
    protected function addSubtitle(): void
    {
        $this->subtitle = '';
    }

    /**
     * Adds supplemental information to the display output.
     * @return void modifies the object property supplement
     */
    protected function addSupplement(): void
    {
        $this->supplement = '';
    }

    /**
     * @inheritdoc
     * ListView adds the title and configuration button if user has access. Inheriting classes are responsible for
     * their own buttons.
     */
    protected function addToolBar(): void
    {
        // MVC name identity is now the internal standard
        $controller = $this->getName();
        $toolbar    = Toolbar::getInstance();
        $toolbar::setTitle(strtoupper($controller));

        if (Can::administrate()) {
            $toolbar->preferences('com_organizer');
        }
    }

    /**
     * Checks user authorization and initiates redirects accordingly. General access is now regulated through the below
     * mentioned functions. Views with public access can be further restricted here as necessary.
     * @return void
     * @see Controller::display(), Can::view()
     */
    protected function authorize(): void
    {
        // See comment.
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        $this->authorize();

        $this->initializeColumns();

        $this->items      = $this->get('Items');
        $this->pagination = $this->get('Pagination');

        if ($this->items or $this->structureEmpty) {
            $this->completeItems();
        }

        $this->empty = $this->empty ?: Text::_('ORGANIZER_EMPTY_RESULT_SET');

        $this->addToC();
        $this->addToolBar();
        $this->modifyDocument();
        $this->addSubtitle();
        $this->addSupplement();

        parent::display($tpl);
    }

    /**
     * Generates a toggle for an attribute of an association
     *
     * @param   string  $controller    the name of the controller which executes the task
     * @param   string  $columnOne     the name of the first identifying column
     * @param   int     $valueOne      the value of the first identifying column
     * @param   string  $columnTwo     the name of the second identifying column
     * @param   int     $valueTwo      the value of the second identifying column
     * @param   bool    $currentValue  the value currently set for the attribute (saves asking it later)
     * @param   string  $tip           the tooltip
     * @param   string  $attribute     the resource attribute to be changed (useful if multiple entries can be toggled)
     *
     * @return string  a HTML string
     * @noinspection PhpTooManyParametersInspection
     */
    protected function getAssocToggle(
        string $controller,
        string $columnOne,
        int $valueOne,
        string $columnTwo,
        int $valueTwo,
        bool $currentValue,
        string $tip,
        string $attribute = ''
    ): string
    {
        $url = Uri::base() . "?option=com_organizer&task=$controller.toggle";
        $url .= "&$columnOne=$valueOne&$columnTwo=$valueTwo";
        $url .= $attribute ? "&attribute=$attribute" : '';
        $url .= ($menuID = Input::getInt('Itemid')) ? "&Itemid=$menuID" : '';

        $iconClass = empty($currentValue) ? 'checkbox-unchecked' : 'checkbox-checked';
        $icon      = '<span class="icon-' . $iconClass . '"></span>';

        $attributes = ['title' => $tip, 'class' => 'hasTooltip'];

        return HTML::_('link', $url, $icon, $attributes);
    }

    /**
     * Generates a string containing attribute information for an HTML element to be output
     *
     * @param   mixed &$element  the element being processed
     *
     * @return string the HTML attribute output for the item
     */
    public function getAttributesOutput(array &$element): string
    {
        $output = '';

        $relevant = (!empty($element['attributes']) and is_array($element['attributes']));
        if ($relevant) {
            foreach ($element['attributes'] as $attribute => $attributeValue) {
                $output .= $attribute . '="' . $attributeValue . '" ';
            }
        }
        unset($element['attributes']);

        return $output;
    }

    /**
     * Generates a toggle for a binary resource attribute
     *
     * @param   string  $controller    the name of the data management controller
     * @param   int     $resourceID    the id of the resource
     * @param   bool    $currentValue  the value currently set for the attribute (saves asking it later)
     * @param   string  $tip           the tooltip
     * @param   string  $attribute     the resource attribute to be changed (useful if multiple entries can be toggled)
     *
     * @return string  a HTML string
     */
    protected function getToggle(
        string $controller,
        int $resourceID,
        bool $currentValue,
        string $tip,
        string $attribute = ''
    ): string
    {
        $url = Uri::base() . "?option=com_organizer&task=$controller.toggle&id=$resourceID";
        $url .= $attribute ? "&attribute=$attribute" : '';

        $iconClass = empty($currentValue) ? 'checkbox-unchecked' : 'checkbox-checked';
        $icon      = '<span class="icon-' . $iconClass . '"></span>';

        $attributes = ['title' => Text::_($tip), 'class' => 'hasTooltip'];

        return HTML::_('link', $url, $icon, $attributes);
    }

    /**
     * @inheritDoc
     */
    protected function initializeView(): void
    {
        // TODO: check submenu viability

        parent::initializeView();

        // All the tools are now there.
        $this->addSupplement();
        $this->initializeColumns();
        $this->completeItems();
    }

    /**
     * Adds scripts and stylesheets to the document.
     *     *
     * @deprecated  4.3 will be removed in 6.0
     *              Use WebAssetManager
     *              Example: $wa->registerAndUseStyle(...);
     */
    protected function modifyDocument(): void
    {
        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/list.css');
        Document::addScript(Uri::root() . 'components/com_organizer/js/list.js');
    }

    /**
     * Function to set the object's headers property which defines column output par
     * @return void sets the object headers property
     */
    abstract protected function initializeColumns(): void;

    /**
     * Readies an item for output.
     *
     * @param   int       $index  the current iteration number
     * @param   stdClass  $item   the current item being iterated
     * @param   array     $options
     *
     * @return void
     */
    abstract protected function completeItem(int $index, stdClass $item, array $options = []): void;

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
}
