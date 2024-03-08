<?php
/**
 * @package     Organizer
 * @extension   pkg_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace THM\Organizer\Views\HTML;

use Joomla\CMS\MVC\View\FormView as Base;
use THM\Organizer\Adapters\Document;
use THM\Organizer\Adapters\Input;
use THM\Organizer\Views\Named;

/**
 * Class loads form data into the HTML view context.
 */
class FormView extends Base
{
    use Attributed;
    use Configured;
    use Named;
    use Tasked;
    use Titled;

    /**
     * The name of the layout to use during rendering.
     * @var string
     */
    protected string $layout = 'edit';

    /**
     * Constructor
     *
     * @param   array  $config  An optional associative array of configuration settings.
     */
    public function __construct(array $config)
    {
        // Joomla ignores the property value and overwrites it.
        if ($config['layout'] === 'default') {
            $config['layout'] = $this->layout;
        }
        else {
            $this->layout = $config['layout'];
        }

        parent::__construct($config);

        $this->configure();
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
     * Adds resource related title, cancel/close and eventually help buttons.
     *
     * @param   string[]  $buttons  the names of the available button functions
     *
     * @return  void adds buttons to the global toolbar object
     */
    protected function addToolbar(array $buttons = [], string $constant = ''): void
    {
        Input::set('hidemainmenu', true);
        $buttons    = $buttons ?: ['apply', 'save'];
        $controller = $this->getName();
        $constant   = $constant ?: strtoupper($controller);

        $new = empty($this->item->id);

        $title = $new ? "ORGANIZER_ADD_$constant" : "ORGANIZER_EDIT_$constant";
        $this->setTitle($title);

        $toolbar = Document::getToolbar();

        if (count($buttons) > 1) {
            $saveGroup = $toolbar->dropdownButton('save-group');
            $saveBar   = $saveGroup->getChildToolbar();

            foreach ($buttons as $button) {
                switch ($button) {
                    case 'apply':
                        $saveBar->apply("$controller.apply");
                        break;
                    case 'save':
                        $saveBar->save("$controller.save");
                        break;
                    case 'save2copy':
                        if (!$new) {
                            $saveBar->save2copy("$controller.save2copy");
                        }
                        break;
                    case 'save2new':
                        $saveBar->save2new("$controller.save2new");
                        break;
                }
            }
        }
        else {
            $toolbar->save("$controller.save");
        }

        $toolbar->cancel("$controller.cancel");

        //TODO help!
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
     * @inheritDoc
     */
    protected function initializeView(): void
    {
        parent::initializeView();

        $this->modifyDocument();
    }

    /**
     * Adds scripts and stylesheets to the document.
     */
    protected function modifyDocument(): void
    {
        // Added on demand by implementing classes.
    }
}