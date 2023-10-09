<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use JFormFieldSubform;
use Joomla\CMS\Form\FormHelper;

FormHelper::loadFieldClass('subform');

/**
 * Class loads multiple/repeatable period blocks from database and make it possible to advance them.
 * This needs an own form field to load the values, maybe because the dates are saved as json string.
 */
class RunsField extends JFormFieldSubform
{
    /**
     * Type
     * @var    String
     */
    protected $type = 'Runs';

    /**
     * Method to get the multiple field input of the loaded Runs Section
     * @return string  The field input markup.
     */
    protected function getInput(): string
    {
        $this->value = $this->value['runs'] ?? [];

        return parent::getInput();
    }
}
