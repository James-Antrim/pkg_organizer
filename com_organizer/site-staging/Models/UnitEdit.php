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

use THM\Organizer\Adapters\{Application, Database, Input};
use THM\Organizer\Helpers;
use THM\Organizer\Tables;
use THM\Organizer\Tables\Units as Table;
use stdClass;

/**
 * Class loads a form for editing instance data.
 */
class UnitEdit extends EditModelOld
{
    public $instances = [];

    public $items = [];

    public $my = false;

    /**
     * Checks access to edit the resource.
     * @return void
     */
    protected function authorize()
    {
        if (!Helpers\Users::getID()) {
            Application::error(401);
        }

        if (!Helpers\Can::manage('unit', Input::getInt('id'))) {
            Application::error(403);
        }
    }

    /**
     * Convert from a table object to a basic object to reduce overhead.
     *
     * @param   stdClass  $item   the item modelling the data for the view
     * @param   Table     $table  the table modelling the data in the database
     *
     * @return void
     */
    private function fillItem(stdClass $item, Table $table)
    {
        $item->id             = $table->id;
        $item->code           = $table->code;
        $item->organizationID = $table->organizationID;
        $item->termID         = $table->termID;
        $item->comment        = $table->comment;
        $item->courseID       = $table->courseID;
        $item->delta          = $table->delta;
        $item->endDate        = $table->endDate;
        $item->gridID         = $table->gridID;
        $item->runID          = $table->runID;
        $item->startDate      = $table->startDate;
    }

    /**
     * @inheritDoc
     */
    public function getForm($data = [], $loadData = true)
    {
        $form = parent::getForm($data, $loadData);

        if ($this->my) {
            $form->setValue('my', null, 1);
        }
        elseif (count($this->items) === 1) {
            $form->setValue('id', null, $this->items[0]->id);
        }

        /*$item = $this->item;

        $previous = Input::getFormItems();
        $session  = Factory::getSession();
        $instance = $session->get('organizer.instance', []);

        // Immutable once set
        if (!empty($item->organizationID))
        {
            $form->removeField('organizationID');
            $organizationID = $item->organizationID;
        }
        else
        {
            if ($organizationID = $previous->get('organizationID'))
            {
                $instance['organizationID'] = $organizationID;
            }
            else
            {
                // The authorize function would have blocked the object from getting this far if there was no value here.
                $organizations  = Helpers\Organizations::getResources('teach');
                $organizationID = reset($organizations)['id'];
            }

            $form->setValue('organizationID', null, $organizationID);
        }

        $item->organizationID = $organizationID;

        $this->setDate($item);
        $this->setGridID($item);
        $this->setBlockID($item);

        $instance['blockID'] = $item->blockID;
        $instance['date']    = $item->date;
        $instance['gridID']  = $item->gridID;

        $form->setValue('blockID', null, $item->blockID);
        $form->setValue('date', null, $item->date);
        $form->setValue('gridID', null, $item->gridID);

        $session->set('organizer.instance', $instance);*/

        return $form;
    }

    /**
     * Method to get a single record.
     *
     * @param   int  $pk  The id of the primary key
     *
     * @return object|false Object on success, false on failure
     */
    public function getItem($pk = 0)
    {
        $this->authorize();

        echo "<pre>" . print_r(Input::getInput(), true) . "</pre><br>";
        die;

        if ($this->my = Input::getBool('my')) {
            $code = Helpers\Users::getID() . '-1';
            $keys = ['code' => $code];

            // Get an organization associated with the user as a teacher
            $organizations  = Helpers\Organizations::getResources('teach');
            $organizationID = reset($organizations)['id'];
            $gridID         = Helpers\Organizations::getDefaultGrid($organizationID);

            foreach (Helpers\Terms::getResources(true) as $term) {
                $item  = new stdClass();
                $table = new Table();

                $keys['termID'] = $term['id'];

                if (!$table->load($keys)) {
                    $data = [
                        'code'      => $code,
                        'endDate'   => $term['endDate'],
                        'gridID'    => $gridID,
                        'startDate' => $term['startDate'],
                        'termID'    => $term['id'],
                    ];

                    $table->save($data);
                    // fill blank item
                }

                $this->fillItem($item, $table);
                $this->items[] = $item;
            }
        }
        else {
            // No unit creation outside of the my context right now.
            if (empty($pk)) {
                Application::error(501);
            }

            $item  = new stdClass();
            $table = new Tables\Units();

            $table->load($pk);
            $this->fillItem($item, $table);
            $items[] = $item;
        }

        /*

        if ($item->id)
        {
            $block = new Tables\Blocks();
            $block->load($item->blockID);
            $item->date = $block->date;

            $unit = new Tables\Units();
            $unit->load($item->unitID);

            // Null default on grid deletion
            $item->gridID = (int) $unit->gridID;

            $item->organizationID = $unit->organizationID;
            $this->setInstances($item);
            //$instance = ['instanceID' => $item->id, 'instanceStatus' => $item->delta];

            //Helpers\Instances::setPersons($instance, ['delta' => '']);

            //$item->resources = $instance['resources'];
        }
        else
        {
            // if the date has been selected get the user's default unit if existent
            //$item->resources = [];
        }*/

        return new stdClass();
    }

    /**
     * Method to get a table object, load it if necessary.
     *
     * @param   string  $name     The table name. Optional.
     * @param   string  $prefix   The class prefix. Optional.
     * @param   array   $options  Configuration array for model. Optional.
     *
     * @return Tables\Units A Table object
     * @SuppressWarnings(PHPMD.UnusedFormalParameter)
     */
    public function getTable($name = '', $prefix = '', $options = []): Tables\Units
    {
        return new Tables\Units();
    }

    /**
     * Attempts to determine the block to be used for planning the current instance.
     *
     * @param   object  $item
     *
     * @return void sets the item's block id
     */
    private function setBlockID(object $item)
    {
        $block = new Tables\Blocks();

        // Selected > unit > organization default > 0
        if ($blockID = Input::getFormItems()->get('blockID') and $block->load($blockID)) {
            $item->blockID = $blockID;
        }
    }

    /**
     * Attempts to determine the date to be used for planning the current instance.
     *
     * @param   object  $item
     *
     * @return void
     */
    private function setDate(object $item)
    {
        if ($date = Input::getFormItems()->get('date') and preg_match('/\d{4}-\d{2}-\d{2}/', $date)) {
            $item->date = $date;
        }
        elseif (empty($item->date)) {
            $item->date = date('Y-m-d');
        }
    }

    /**
     * Attempts to determine the grid to be used for planning the current instance.
     *
     * @param   object  $item
     *
     * @return void sets the item's grid id
     */
    private function setGridID(object $item)
    {
        $default = Helpers\Organizations::getDefaultGrid($item->organizationID);
        $grid    = new Tables\Grids();

        // Selected > unit > organization default > 0
        if ($gridID = Input::getFormItems()->get('gridID') and $grid->load($gridID)) {
            $item->gridID = $gridID;
        }
        elseif (empty($item->gridID)) {
            $item->gridID = $default;
        }
    }

    private function setInstances(object $item)
    {
        $query = Database::getQuery();
        $query->select('id, eventID')
            ->from('#__organizer_instances')
            ->where("blockID = $item->blockID")
            ->where("unitID = $item->unitID");
        Database::setQuery($query);
        $instances = Database::loadAssocList();
        echo "<pre>" . print_r($instances, true) . "</pre><br>";
    }
}