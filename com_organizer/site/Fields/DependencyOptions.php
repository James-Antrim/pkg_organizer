<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Fields;

use Joomla\CMS\Form\FormField;
use Organizer\Helpers;

/**
 * Class creates a select box for superordinate pool resources.
 */
abstract class DependencyOptions extends OptionsField
{
    /**
     * Gets pool options for a select list. All parameters come from the
     *
     * @param   int    $subjectID  the selected subject
     * @param   array  $values     the subjects with which dependencies have already been mapped
     *
     * @return array  the options
     */
    protected function getDependencyOptions($subjectID, $values)
    {
        $programs = Helpers\Subjects::getPrograms($subjectID);
        $options  = [];

        foreach ($programs as $programRange) {
            if ($subjectRanges = Helpers\Programs::getSubjects($programRange['programID'])) {
                foreach ($subjectRanges as $subjectRange) {
                    $rSubjectID = (int)$subjectRange['subjectID'];

                    if ($rSubjectID === $subjectID or !$name = Helpers\Subjects::getFullName($rSubjectID)) {
                        continue;
                    }

                    $selected       = in_array($rSubjectID, $values) ? 'selected' : '';
                    $options[$name] = "<option value='$rSubjectID' $selected>$name</option>";
                }
            }
        }

        ksort($options);

        return $options;
    }
}
