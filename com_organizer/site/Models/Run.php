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
use THM\Organizer\Helpers\Terms;

/** @inheritDoc */
class Run extends EditModel
{
    protected string $tableClass = 'Runs';

    /** @inheritDoc */
    public function getForm($data = [], $loadData = true): ?FormAlias
    {
        if ($form = parent::getForm($data, $loadData)) {
            $defaultID = Input::getFilterID('termID', Terms::getCurrentID());
            $form->setValue('termID', null, $form->getValue('termID', null, $defaultID));
        }

        return $form;
    }
}
