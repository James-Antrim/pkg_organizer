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

jimport('tcpdf.tcpdf');

use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\Document;
use THM\Organizer\Helpers;

/**
 * Class loads organization statistics into the display context.
 */
class OrganizationOccupancy extends SelectionView
{
    /**
     * Modifies document variables and adds links to external files
     * @return void
     */
    protected function modifyDocument()
    {
        parent::modifyDocument();

        Document::addScript(Uri::root() . 'components/com_organizer/js/organization_occupancy.js');
    }

    private function setBaseFields()
    {
        $attribs                    = [];
        $this->sets['baseSettings'] = [];

        $options    = $this->model->getYearOptions();
        $default    = date('Y');
        $termSelect = Helpers\HTML::selectBox($options, 'year', $attribs, $default);

        $this->sets['baseSettings']['termIDs'] = [
            'label' => Helpers\Languages::_('ORGANIZER_YEAR'),
            'description' => Helpers\Languages::_('ORGANIZER_YEAR_DESC'),
            'input' => $termSelect
        ];
    }

    /**
     * Function to define field sets and fill sets with fields
     * @return void sets the fields property
     */
    protected function setSets()
    {
        $this->sets['baseSettings'] = [];
        $this->setBaseFields();
        $this->sets['filterFields'] = ['label' => 'ORGANIZER_FILTERS'];
        $this->setFilterFields();
    }

    /**
     * Creates resource selection fields for the form
     * @return void sets indexes in $this->fields['resouceSettings'] with html content
     */
    private function setFilterFields()
    {
        $this->sets['filterFields'] = [];
        $attribs                    = ['multiple' => 'multiple'];

        $roomAttribs = $attribs;
        $roomOptions = $this->model->getRoomOptions();
        $roomSelect  = Helpers\HTML::selectBox($roomOptions, 'roomIDs', $roomAttribs);

        $this->sets['filterFields']['roomIDs'] = [
            'label' => Helpers\Languages::_('ORGANIZER_ROOMS'),
            'description' => Helpers\Languages::_('ORGANIZER_ROOMS_DESC'),
            'input' => $roomSelect
        ];

        $roomtypeAttribs             = $attribs;
        $roomtypeAttribs['onChange'] = 'repopulateRooms();';
        $typeOptions                 = $this->model->getRoomtypeOptions();
        $roomtypeSelect              = Helpers\HTML::selectBox($typeOptions, 'roomtypeIDs', $roomtypeAttribs);

        $this->sets['filterFields']['roomtypeIDs'] = [
            'label' => Helpers\Languages::_('ORGANIZER_ROOMTYPES'),
            'description' => Helpers\Languages::_('ORGANIZER_ROOMS_TYPES_DESC'),
            'input' => $roomtypeSelect
        ];
    }
}
