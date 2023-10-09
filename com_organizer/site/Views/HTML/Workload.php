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
use THM\Organizer\Adapters;
use THM\Organizer\Helpers;

/**
 * Class loads personnal workload statistics into the display context.
 */
class Workload extends FormView
{
    /**
     * Adds a toolbar and title to the view.
     * @return void  sets context variables
     */
    protected function addToolBar()
    {
        $this->setTitle('ORGANIZER_WORKLOAD');
        $toolbar = Adapters\Toolbar::getInstance();

        if ($this->form->getValue('personID'))//Helpers\Input::getInt('personID'))
        {
            $toolbar->appendButton(
                'NewTab',
                'file-xls',
                Helpers\Languages::_('ORGANIZER_DOWNLOAD'),
                'Workloads.xls',
                false
            );
        }
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument()
    {
        parent::modifyDocument();

        Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/list.css');
    }
}
