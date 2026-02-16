<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2022 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Joomla\CMS\Form\FormFactoryInterface;
use Joomla\Database\DatabaseAwareTrait;

/**
 * A factory which can create Form objects.
 */
class FormFactory implements FormFactoryInterface
{
    use DatabaseAwareTrait;

    /** @inheritDoc */
    public function createForm(string $name, array $options = []): Form
    {
        $form = new Form($name, $options);

        $form->setDatabase($this->getDatabase());

        return $form;
    }
}
