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
use Organizer\Adapters;
use Organizer\Adapters\Toolbar;
use Organizer\Helpers;
use Organizer\Helpers\Languages;

/**
 * Class loads persistent information a filtered set of course participants into the display context.
 */
class Participants extends ListView
{
    protected $rowStructure = [
        'checkbox' => '',
        'fullName' => 'value',
        'email' => 'value',
        'program' => 'value',
        'registerDate' => 'value',
        'status' => 'value',
        'paid' => 'value',
        'attended' => 'value'
    ];

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true)
    {
        $this->setTitle('ORGANIZER_PARTICIPANTS');

        if (Helpers\Can::administrate()) {
            $toolbar = Toolbar::getInstance();
            $toolbar->appendButton(
                'Standard',
                'edit',
                Languages::_('ORGANIZER_EDIT'),
                'participants.edit',
                true
            );
            $toolbar->appendButton(
                'Standard',
                'contract',
                Languages::_('ORGANIZER_MERGE'),
                'participants.mergeView',
                true
            );
            $toolbar->appendButton(
                'Standard',
                'contract-2',
                Languages::_('ORGANIZER_AUTOMATIC_MERGE'),
                'participants.automaticMerge',
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

        Adapters\Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/modal.css');
    }

    /**
     * @inheritdoc
     */
    protected function setHeaders()
    {
        $ordering  = $this->state->get('list.ordering');
        $direction = $this->state->get('list.direction');
        $headers   = [
            'checkbox' => Helpers\HTML::_('grid.checkall'),
            'fullName' => Helpers\HTML::sort('NAME', 'fullName', $direction, $ordering),
            'email' => Helpers\HTML::sort('EMAIL', 'email', $direction, $ordering),
            'program' => Helpers\HTML::sort('PROGRAM', 'program', $direction, $ordering),
        ];

        if ($courseID = Helpers\Input::getFilterID('course') and $courseID !== -1) {
            $headers['status']   = Helpers\HTML::sort('STATUS', 'status', $direction, $ordering);
            $headers['paid']     = Helpers\HTML::sort('PAID', 'paid', $direction, $ordering);
            $headers['attended'] = Helpers\HTML::sort('ATTENDED', 'attended', $direction, $ordering);
        } else {
            $headers['registerDate'] = Helpers\HTML::sort('REGISTRATION_DATE', 'registerDate', $direction, $ordering);
        }

        $this->headers = $headers;
    }

    /**
     * @inheritdoc
     */
    protected function structureItems()
    {
        $index           = 0;
        $link            = 'index.php?option=com_organizer&view=participant_edit&id=';
        $structuredItems = [];

        foreach ($this->items as $item) {
            $item->fullName          = $item->forename ? $item->fullName : $item->surname;
            $structuredItems[$index] = $this->structureItem($index, $item, $link . $item->id);
            $index++;
        }

        $this->items = $structuredItems;
    }
}
