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
use THM\Organizer\Adapters\{Application, Document, HTML, Input, Text, Toolbar};
use THM\Organizer\Controllers\Controller;
use THM\Organizer\Helpers\Can;
use THM\Organizer\Models\ListModel;

/**
 * Class loads a filtered set of resources into the display context. Specific resource determined by extending class.
 */
abstract class ListView extends Base
{
    use Configured;
    use Tasked;
    use Titled;
    use ToCed;

    /** @var bool the value of the relevant authorizations in context. */
    public bool $allowBatch = false;
    /** @var string The default text for an empty result set. */
    public string $empty = '';
    /**
     * The header information to display indexed by the referenced attribute.
     * @var array
     */
    public array $headers = [];
    protected string $layout = 'list';
    /** @var ListModel */
    protected BaseDatabaseModel $model;
    protected bool $sameTab = false;
    protected bool $structureEmpty = false;
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

        if (Application::backend() and Can::administrate()) {
            $toolbar = Toolbar::getInstance();
            $toolbar->preferences('com_organizer');
        }
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
     * Initializes the headers after the form and state properties have been initialized.
     * @return void
     */
    abstract protected function initializeColumns(): void;

    /**
     * @inheritDoc
     */
    protected function initializeView(): void
    {
        // TODO: check submenu viability

        parent::initializeView();

        $this->empty = $this->empty ?: Text::_('EMPTY_RESULT_SET');

        $this->setSubTitle();
        $this->setSupplement();
        $this->addToC();
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
        Document::style('list');
    }
}
