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

use Joomla\CMS\Form\Form as FormAlias;
use THM\Organizer\Adapters\Input;

/** @inheritDoc */
class FieldColor extends EditModel
{
    protected string $tableClass = 'FieldColors';

    /** @inheritDoc */
    public function getForm($data = [], $loadData = true): ?FormAlias
    {
        if (!$form = parent::getForm($data, $loadData)) {
            return null;
        }

        if (Input::id()) {
            $form->setFieldAttribute('fieldID', 'disabled', true);
            $form->setFieldAttribute('organizationID', 'disabled', true);
        }

        return $form;
    }
}
