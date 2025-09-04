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

use Joomla\CMS\Factory;
use THM\Organizer\Adapters\{Application, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Helpers\Participation as Helper;
use THM\Organizer\Tables\InstanceParticipants as Table;

/**
 * Class loads a form for editing campus data.
 */
class InstanceParticipantEdit extends EditModel
{
    /** @inheritDoc */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $session = Factory::getSession();

        if (!$session->get('organizer.participation.referrer')) {
            $referrer = Input::instance()->server->getString('HTTP_REFERER');
            $session->set('organizer.participation.referrer', $referrer);
        }
    }

    /**
     * Checks access to edit the resource.
     * @return void
     */
    protected function authorize()
    {
        $bookingID = 0;

        if (!$participationID = Input::id() or !$bookingID = Helper::bookingID($participationID)) {
            Application::error(400);
        }

        if (!Helpers\Can::manage('booking', $bookingID)) {
            Application::error(403);
        }
    }

    /** @inheritDoc */
    public function getItem(): object
    {
        $this->item           = parent::getItem();
        $this->item->referrer = Factory::getSession()->get('organizer.participation.referrer');

        return $this->item;
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return Table  A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Table
    {
        return new Table();
    }
}
