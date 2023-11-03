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

use Joomla\CMS\Form\Form;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Application, Document, HTML};

/**
 * Class loads a non-item based resource form (merge) into the display context. Specific resource determined by
 * extending class.
 */
abstract class FormView extends BaseView
{
    /**
     * @var Form
     */
    public $form = null;

    /**
     * @inheritdoc
     */
    protected string $layout = 'form';

    /**
     * The form orientation.
     * @var string
     */
    protected string $orientation = 'horizontal';
    public array $toDo = [];

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        $this->form = $this->get('Form');

        // Allows for view specific toolbar handling
        $this->addToolBar();

        if (!Application::backend()) {
            if (method_exists($this, 'setSubtitle')) {
                $this->setSubtitle();
            }
            if (method_exists($this, 'addSupplement')) {
                $this->addSupplement();
            }
        }

        $this->modifyDocument();
        parent::display($tpl);
    }

    /**
     * Adds a toolbar and title to the view.
     * @return void  adds toolbar items to the view
     */
    abstract protected function addToolBar(): void;

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        HTML::_('behavior.formvalidator');

        Document::addScript(Uri::root() . 'components/com_organizer/js/multiple.js');
        Document::addScript(Uri::root() . 'components/com_organizer/js/submitButton.js');
        Document::addScript(Uri::root() . 'components/com_organizer/js/validators.js');
        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/form.css');
    }
}
