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
use THM\Organizer\Helpers\{Categories, Dates, Groups, Organizations, Roles};

/**
 * Class retrieves information for a filtered set of instances.
 */
class Export extends FormModel
{
    public const EXPORT_FORMATS = ['pdf.grid.A3', 'default' => 'pdf.grid.A4', 'xls.list'];
    private const INTERVALS = [Conditions::MONTH, Conditions::QUARTER, Conditions::TERM, 'default' => Conditions::WEEK];

//            $personID       = empty($form['personID']) ? 0 : $form['personID'];


    /** @inheritDoc */
    protected function loadFormData(): array
    {
        $return = [
            'categoryIDs'     => [],
            'date'            => date('Y-m-d'),
            'groupIDs'        => [],
            'exportFormat'    => self::EXPORT_FORMATS['default'],
            'instances'       => Conditions::INSTANCES['default'],
            'interval'        => self::INTERVALS['default'],
            'methodIDs'       => [],
            'my'              => Input::NO,
            'organizationIDs' => [],
            'personIDs'       => [],
            'roleID'          => '',
            'roomIDs'         => [],
            'separate'        => Input::NO,
        ];

        if ($task = Input::getTask() and $task === 'export.reset') {
            return $return;
        }

        if (User::id() and $my = Input::getInt('my') and $my === Input::YES) {
            $return['my'] = $my;
        }
        else {
            $organizationIDs = Input::getIntArray('organizationIDs');
            if ($organizationIDs and $organizationIDs = array_intersect($organizationIDs, Organizations::getIDs())) {
                $return['organizationIDs'] = $organizationIDs;

                if ($categoryIDs = Input::getIntArray('categoryIDs')) {
                    echo "<pre>" . print_r($categoryIDs, true) . "</pre>";
                    $return['categoryIDs'] = [];
                    foreach ($categoryIDs as $categoryID) {
                        echo "<pre>" . print_r(Categories::organizationIDs($categoryID), true) . "</pre>";
                        if (array_intersect($organizationIDs, Categories::organizationIDs($categoryID))) {
                            $return['categoryIDs'][$categoryID] = $categoryID;
                        }
                    }

                    if ($groupIDs = Input::getIntArray('groupIDs')) {
                        $return['groupIDs'] = [];
                        foreach ($groupIDs as $groupID) {
                            if (array_intersect($return['categoryIDs'], Groups::categoryID($groupID))) {
                                $return['groupIDs'][$groupID] = $groupID;
                            }
                        }
                    }
                }
            }

            // the other resources
            $return['instances'] = Input::validCMD('instances', Conditions::INSTANCES);
            $return['methodIDs'] = Input::getIntArray('methodIDs');
            $return['roleID']    = Input::validInt('roleID', array_keys(Roles::resources()));
            $return['separate']  = (int) ($separate = Input::getInt('separate') and $separate === Input::YES);
            $return['roomIDs']   = Input::getIntArray('roomIDs');
//            'personIDs'       => [],
        }

        $return['date']         = Dates::standardize(Input::getCMD('date', $return['date']));
        $return['exportFormat'] = Input::validCMD('exportFormat', self::EXPORT_FORMATS);
        $return['interval']     = Input::validCMD('interval', self::INTERVALS);

        return $return;
    }

    /** @inheritDoc */
    protected function preprocessForm(Form $form, $data, $group = 'content'): void
    {
        if (!User::id()) {
            $form->removeField('instances');
            $form->removeField('my');
            $form->removeField('personIDs');
        }

        if ($data['my'] or empty($data['organizationIDs'])) {
            $form->removeField('categoryIDs');
            $form->removeField('groupIDs');
            $form->removeField('instances');
            $form->removeField('roleID');
            $form->removeField('methodIDs');
            $form->removeField('separate');
        }
        elseif (empty($data['categoryIDs'])) {
            $form->removeField('groupIDs');
            $form->removeField('separate');

            if (array_diff($data['organizationIDs'], Organizations::viewableIDs())) {
                $form->removeField('instances');
            }
            elseif ($data['instances'] === Conditions::PERSON) {
                $form->removeField('categoryIDs');
                $form->removeField('groupIDs');
                $form->removeField('personIDs');
                $form->removeField('roomIDs');
            }
        }
        else {
            $form->removeField('instances');
        }

        if ($data['my']) {
            $form->removeField('organizationIDs');
            $form->removeField('personIDs');
            $form->removeField('roomIDs');
        }

        if ($data['groupIDs'] or $data['personIDs'] or !str_starts_with($data['exportFormat'], 'pdf')) {
            $form->removeField('separate');
        }
    }
}