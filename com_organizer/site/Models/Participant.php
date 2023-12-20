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
use THM\Organizer\Adapters\User;
use THM\Organizer\Fields\{Programs, Text};

/**
 * @inheritDoc
 */
class Participant extends EditModel
{
    protected string $tableClass = 'Participants';

    /**
     * @inheritDoc
     */
    protected function preprocessForm(Form $form, $data, $group = 'content'): void
    {
        if ($data->get('id') !== User::id()) {
            /** @var Text $field */
            $field = $form->getField('address');
            $field->setAttribute('class', 'validate-address');
            $field->setAttribute('required', false);

            /** @var Text $field */
            $field = $form->getField('city');
            $field->setAttribute('class', 'validate-name');
            $field->setAttribute('required', false);

            /** @var Text $field */
            $field = $form->getField('zipCode');
            $field->setAttribute('class', 'validate-alphanumeric');
            $field->setAttribute('required', false);

            /** @var Programs $field */
            $field = $form->getField('programID');
            $field->setAttribute('required', false);
        }
    }
}
