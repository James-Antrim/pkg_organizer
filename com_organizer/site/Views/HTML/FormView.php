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
use THM\Organizer\Adapters;
use THM\Organizer\Helpers;

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
    protected $layout = 'form';

    /**
     * The form orientation.
     * @var string
     */
    protected $orientation = 'horizontal';

    /**
     * @inheritDoc
     */
    public function display($tpl = null)
    {
        $this->form = $this->get('Form');

        // Allows for view specific toolbar handling
        $this->addToolBar();

        if (empty($this->adminContext)) {
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
    abstract protected function addToolBar();

    /**
     * @inheritDoc
     */
    protected function modifyDocument()
    {
        parent::modifyDocument();

        Helpers\HTML::_('behavior.formvalidator');

        Adapters\Document::addScript(Uri::root() . 'components/com_organizer/js/multiple.js');
        Adapters\Document::addScript(Uri::root() . 'components/com_organizer/js/submitButton.js');
        Adapters\Document::addScript(Uri::root() . 'components/com_organizer/js/validators.js');
        Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/form.css');
    }
}
