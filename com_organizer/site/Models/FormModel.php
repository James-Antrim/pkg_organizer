<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use Exception;
use Joomla\CMS\Form\Form as FormAlias;
use Joomla\CMS\MVC\Model\FormModel as Base;
use THM\Organizer\Adapters\{Application, Form, FormFactory, MVCFactory};

/**
 * Model for data to be used with a form.
 */
abstract class FormModel extends Base
{
    use Named;

    /**
     * Constructor
     *
     * @param   array        $config            An array of configuration options (name, state, dbo, table_path,
     *                                          ignore_request).
     * @param   MVCFactory   $factory           The factory.
     * @param   FormFactory  $formFactory       The form factory.
     *
     * @throws Exception
     */
    public function __construct($config, MVCFactory $factory, FormFactory $formFactory)
    {
        parent::__construct($config, $factory, $formFactory);

        $this->setContext();
    }

    /**
     * Filters out form inputs which should not be displayed due to previous selections.
     *
     * @param   Form  $form  the form to be filtered
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
    public function getForm($data = [], $loadData = true): ?FormAlias
    {
        $options = ['control' => '', 'load_data' => $loadData];

        try {
            return $this->loadForm($this->context, strtolower($this->name), $options);
        }
        catch (Exception $exception) {
            Application::handleException($exception);
        }

        return null;
    }
}