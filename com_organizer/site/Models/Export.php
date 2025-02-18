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
class Export extends FormModel
{
//            $categoryID     = empty($form['categoryID']) ? 0 : $form['categoryID'];
//            $organizationID = empty($form['organizationID']) ? 0 : $form['organizationID'];
//            $groupID        = empty($form['groupID']) ? 0 : $form['groupID'];
//            $personID       = empty($form['personID']) ? 0 : $form['personID'];
//            if ($organizationID) {
//                if ($categoryID and !in_array($organizationID, Helpers\Categories::organizationIDs($categoryID))) {
//                    $categoryID = 0;
//                    unset($form['categoryID']);
//                }
//
//                if ($groupID and !in_array($organizationID, Helpers\Groups::organizationIDs($groupID))) {
//                    $groupID = 0;
//                    unset($form['groupID']);
//                }
//
//                if ($personID and !in_array($organizationID, Helpers\Persons::organizationIDs($personID))) {
//                    unset($form['groupID']);
//                }
//            }
//
//            if ($categoryID and $groupID and $categoryID !== Helpers\Groups::category($groupID)->id) {
//                unset($form['groupID']);
//            }

    /** @inheritDoc */
    protected function filterForm(Form $form): void
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
        $pdf         = (!$format = Input::getFormItems()->get('format') or str_starts_with($format, 'pdf'));

        if ($atomic or $noAggregate or !$pdf) {
            $form->removeField('separate');
        }
    }

    /** @inheritDoc */
    protected function loadFormData(): array
    {
        if ($task = Input::getTask() and $task === 'export.reset') {
            return [];
        }

        $return = Input::getFormItems();

        return $return;
    }

    /** @inheritDoc */
//    public function getForm($data = [], $loadData = true): ?Form
//    {
//
//    }
}