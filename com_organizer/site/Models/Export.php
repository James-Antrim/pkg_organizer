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
use THM\Organizer\Adapters\{Input, User};
use THM\Organizer\Helpers;

/**
 * Class retrieves information for a filtered set of instances.
 */
class Export extends OldFormModel
{

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        // Resolve potential inconsistencies cause by user choices before the form is initialized.
        if ($task = Input::getTask() and $task === 'export.reset') {
            $form = [];
        }
        else {
            $fields = ['categoryID' => 0, 'groupID' => 0, 'organizationID' => 0, 'personID' => 0, 'roomID' => 0];
            $form   = Input::getArray();

            if (!empty($form['my'])) {
                foreach (array_keys($fields) as $field) {
                    unset($form[$field]);
                }
            }
            else {
                $categoryID     = empty($form['categoryID']) ? 0 : $form['categoryID'];
                $organizationID = empty($form['organizationID']) ? 0 : $form['organizationID'];
                $groupID        = empty($form['groupID']) ? 0 : $form['groupID'];
                $personID       = empty($form['personID']) ? 0 : $form['personID'];
                if ($organizationID) {
                    if ($categoryID and !in_array($organizationID, Helpers\Categories::organizationIDs($categoryID))) {
                        $categoryID = 0;
                        unset($form['categoryID']);
                    }

                    if ($groupID and !in_array($organizationID, Helpers\Groups::organizationIDs($groupID))) {
                        $groupID = 0;
                        unset($form['groupID']);
                    }

                    if ($personID and !in_array($organizationID, Helpers\Persons::organizationIDs($personID))) {
                        unset($form['groupID']);
                    }
                }

                if ($categoryID and $groupID and $categoryID !== Helpers\Groups::category($groupID)->id) {
                    unset($form['groupID']);
                }
            }
        }

        // Post: where the data was actually transmitted
        Input::set('jform', $form, 'post');

        // Data: where Joomla preemptively aggregates request information
        Input::set('jform', $form);

        parent::__construct($config);
    }

    /**
     * Provides a strict access check which can be overwritten by extending classes.
     * @return void performs error management via redirects as appropriate
     */
    protected function authorize()
    {
        // Form has public access
    }

    /**
     * @inheritDoc
     */
    protected function filterForm(Form $form)
    {
        if (!User::id()) {
            $form->removeField('instances');
            $form->removeField('my');
            $form->removeField('personID');
        }

        $categoryID     = Input::getInt('categoryID');
        $groupID        = Input::getInt('groupID');
        $instances      = Input::getCMD('instances');
        $my             = Input::getBool('my');
        $organizationID = Input::getInt('organizationID');
        $personID       = Input::getInt('personID');
        $roomID         = Input::getInt('roomID');
        $atomic         = ($groupID or $personID or $roomID);

        if ($my) {
            $form->removeField('categoryID');
            $form->removeField('groupID');
            $form->removeField('instances');
            $form->removeField('organizationID');
            $form->removeField('personID');
            $form->removeField('roleID');
            $form->removeField('roomID');
            $form->removeField('separate');
        }
        elseif ($organizationID) {
            if (!Helpers\Can::view('organization', $organizationID) or $categoryID) {
                $form->removeField('instances');
            }
            elseif ($instances and $instances !== 'organization') {
                $form->removeField('categoryID');
                $form->removeField('groupID');
                $form->removeField('personID');
                $form->removeField('roomID');
            }
        }
        elseif ($categoryID) {
            $form->removeField('instances');
        }
        else {
            $form->removeField('groupID');
        }

        $noAggregate = (empty($organizationID) and empty($categoryID));
        $pdf         = (!$format = Input::getFormItems()->get('format') or strpos($format, 'pdf') === 0);

        if ($atomic or $noAggregate or !$pdf) {
            $form->removeField('separate');
        }
    }

    /**
     * @inheritDoc
     */
    public function getForm($data = [], $loadData = true)
    {
        return parent::getForm($data, $loadData);
    }

    /**
     * @inheritDoc
     */
    protected function loadFormData(): array
    {
        return Input::getArray();
    }
}