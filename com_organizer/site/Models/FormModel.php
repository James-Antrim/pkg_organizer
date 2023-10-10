<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use Joomla\CMS\Form\Form;
use Joomla\CMS\MVC\Model\FormModel as ParentModel;
use THM\Organizer\Adapters\Application;
use THM\Organizer\Helpers;

/**
 * Class loads non-item-specific form data.
 */
class FormModel extends ParentModel
{
    use Named;

    public $mobile = false;

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $this->mobile = Helpers\OrganizerHelper::isSmartphone();
        $this->setContext();
    }

    /**
     * Provides a strict access check which can be overwritten by extending classes.
     * @return void performs error management via redirects as appropriate
     */
    protected function authorize()
    {
        if (!Helpers\Can::administrate()) {
            Application::error(403);
        }
    }

    /**
     * Filters out form inputs which should not be displayed due to previous selections.
     *
     * @param Form $form the form to be filtered
     *
     * @return void modifies $form
     */
    protected function filterForm(Form $form)
    {
        // Per default no fields are altered
    }

    /**
     * @inheritDoc
     */
    public function getForm($data = [], $loadData = false)
    {
        $this->authorize();

        $name = $this->get('name');
        $form = $this->loadForm($this->context, $name, ['control' => 'jform', 'load_data' => $loadData]);

        if (empty($form)) {
            return false;
        }

        return $form;
    }

    /**
     * @inheritDoc
     */
    protected function loadForm($name, $source = null, $options = [], $clear = false, $xpath = '')
    {
        Form::addFormPath(JPATH_COMPONENT_SITE . '/Forms');
        Form::addFieldPath(JPATH_COMPONENT_SITE . '/Fields');


        if ($form = parent::loadForm($name, $source, $options, $clear, $xpath)) {
            $this->filterForm($form);
        }

        return $form;
    }
}
