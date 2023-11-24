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

use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Document, Text, Toolbar};
use THM\Organizer\Buttons\FormTarget;

/**
 * Class loads personnal workload statistics into the display context.
 */
class Workload extends OldFormView
{
    /**
     * Adds a toolbar and title to the view.
     * @return void  sets context variables
     */
    protected function addToolBar(): void
    {
        $this->setTitle('ORGANIZER_WORKLOAD');
        $toolbar = Toolbar::getInstance();

        if ($this->form->getValue('personID'))//Input::getInt('personID'))
        {
            $button = new FormTarget('export', Text::_('DOWNLOAD'));
            $button->icon('fa fa-file-excel')->task('Workloads.xls');
            $toolbar->appendButton($button);
        }
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        //Document::style('list');
    }
}
