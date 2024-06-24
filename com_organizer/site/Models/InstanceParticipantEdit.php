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
class InstanceParticipantEdit extends EditModelOld
{
    /** @inheritDoc */
    public function __construct($config = [])
    {
        parent::__construct($config);

        $session = Factory::getSession();

        if (!$session->get('organizer.participation.referrer')) {
            $referrer = Input::getInput()->server->getString('HTTP_REFERER');
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

        if (!$participationID = Input::getID() or !$bookingID = Helper::bookingID($participationID)) {
            Application::error(400);
        }

        if (!Helpers\Can::manage('booking', $bookingID)) {
            Application::error(403);
        }
    }

    /**
     * Method to get a single record.
     *
     * @param   int  $pk  The id of the primary key.
     *
     * @return mixed    Object on success, false on failure.
     */
    public function getItem($pk = 0)
    {
        $this->item           = parent::getItem($pk);
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
