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

/**
 * Class loads room statistic information into the display context.
 */
class RoomStatistics extends SelectionView
{
    /**
     * Modifies document variables and adds links to external files
     * @return void
     */
    protected function modifyDocument()
    {
        parent::modifyDocument();

        Adapters\Document::addScript(Uri::root() . 'components/com_organizer/js/room_statistics.js');
    }

    private function setBaseFields()
    {
        $this->sets['basic'] = ['label' => 'ORGANIZER_BASIC_SETTINGS'];

        $intervals = [
            'week' => 'ORGANIZER_WEEK',
            'month' => 'ORGANIZER_MONTH',
            'semester' => 'ORGANIZER_SEMESTER'
        ];
        $this->setListField('interval', 'basic', $intervals, ['onChange' => 'handleInterval();'], 'week');

        $date = '<input name="date" type="date" value="' . date('Y-m-d') . '">';
        $this->setField('date', 'basic', 'ORGANIZER_DATE', $date);
    }

    /**
     * Sets form fields used to filter the resources available for selection.
     * @return void modifies the sets property
     */
    private function setFilterFields()
    {
        $this->sets['filters'] = ['label' => 'ORGANIZER_FILTERS'];

        $orgAttribs = [
            'multiple' => 'multiple',
            'onChange' => 'repopulateTerms();repopulateCategories();repopulateRooms();'
        ];
        $this->setResourceField('organization', 'filters', $orgAttribs, true);

        $categoryAttribs = ['multiple' => 'multiple', 'onChange' => 'repopulateRooms();'];
        $this->setResourceField('category', 'filters', $categoryAttribs);

        $roomtypeAttribs = ['multiple' => 'multiple', 'onChange' => 'repopulateRooms();'];
        $this->setResourceField('roomtype', 'content', $roomtypeAttribs);

        $roomAttribs = ['multiple' => 'multiple'];
        $this->setResourceField('room', 'content', $roomAttribs);
    }

    /**
     * Function to define field sets and fill sets with fields
     * @return void sets the fields property
     */
    protected function setSets()
    {
        $this->setBaseFields();
        $this->setFilterFields();
    }
}
