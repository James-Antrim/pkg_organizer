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
use THM\Organizer\Helpers\{Dates, Programs as Helper};

/**
 * Class which manages stored (degree) program data.
 */
class Program extends EditModel
{
    /** @inheritDoc */
    public function getForm($data = [], $loadData = true): ?Form
    {
        $form = parent::getForm($data, $loadData);

        if (!$this->item->id or !Helper::hasSubordinates($this->item->id)) {
            $form->removeField('subordinates');
        }

        return $form;
    }

    /** @inheritDoc */
    public function getItem(): object
    {
        $item = parent::getItem();

        if (empty($item->id)) {
            $item->accredited = date('Y');
        }

        if ($item->expiration === Dates::NULL) {
            $item->expiration = date('Y-m-d', strtotime('+50 years'));
        }

        return $item;
    }
}
