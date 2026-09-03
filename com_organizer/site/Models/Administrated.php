<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Models;

use Joomla\CMS\Form\Form as FormAlias;
use THM\Organizer\Helpers\Can;

trait Administrated
{
    /** @inheritDoc */
    public function getForm($data = [], $loadData = true): ?FormAlias
    {
        $form = parent::getForm($data, $loadData);

        if (Can::administrate()) {
            $form->setFieldAttribute('code', 'readonly', false);
        }

        return $form;
    }
}