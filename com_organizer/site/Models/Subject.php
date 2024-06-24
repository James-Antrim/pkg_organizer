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

use Joomla\Database\ParameterType;
use THM\Organizer\Helpers\{Frequencies, Persons, Pools, Programs, Subjects as Helper};
use THM\Organizer\Adapters\{Application, Database as DB, Input, Text};

/** @inheritDoc */
class Subject extends EditModel
{
    protected string $tableClass = 'Subjects';

    /**
     * Loads an array of names and links into the subject model for subjects for which this subject is a prerequisite.
     *
     * @param   object  $subject  the subject being displayed
     *
     * @return array
     */
    private function dependencies(object $subject): array
    {
        $id     = $subject->id;
        $return = ['postrequisiteModules' => [], 'prerequisiteModules' => [], 'applicability' => ''];

        // There cannot be any dependencies for subjects without a program context
        if (!$programs = Helper::programs($id)) {
            return $return;
        }

        $query = DB::getQuery();
        $tag   = Application::getTag();

        $aliased  = DB::qn(
            ['s1.id', "s1.fullName_$tag", 's1.code', 's2.id', "s2.fullName_$tag", 's2.code'],
            ['preID', 'preName', 'preModuleNumber', 'postID', 'postName', 'postModuleNumber']
        );
        $selected = ['DISTINCT ' . DB::qn('pr.id', 'id')];

        $query->select(array_merge($selected, $aliased))
            ->from(DB::qn('#__organizer_prerequisites', 'pr'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c1'), DB::qc('c1.id', 'pr.prerequisiteID'))
            ->innerJoin(DB::qn('#__organizer_subjects', 's1'), DB::qc('s1.id', 'c1.subjectID'))
            ->innerJoin(DB::qn('#__organizer_curricula', 'c2'), DB::qc('c2.id', 'pr.subjectID'))
            ->innerJoin(DB::qn('#__organizer_subjects', 's2'), DB::qc('s2.id', 'c2.subjectID'))
            ->where(DB::qcs([
                ['c1.lft', ':left1', '>'],
                ['c1.rgt', ':right1', '<'],
                ['c2.lft', ':left2', '>'],
                ['c2.rgt', ':right2', '<']
            ]))
            ->where('(' . DB::qcs([['s1.id', $id], ['s2.id', $id]], 'OR') . ')');

        $return['applicability'] = Text::_('APPLICABILITY_TEXT');
        $level                   = '';

        foreach ($programs as $program) {
            if (!$level) {
                $level = Programs::level($program['programID']);
            }

            $query->bind(':left1', $program['lft'], ParameterType::INTEGER)
                ->bind(':left2', $program['lft'], ParameterType::INTEGER)
                ->bind(':right1', $program['rgt'], ParameterType::INTEGER)
                ->bind(':right2', $program['rgt'], ParameterType::INTEGER);

            DB::setQuery($query);

            if (!$dependencies = DB::loadAssocList('id')) {
                continue;
            }

            $programName = Programs::name($program['programID']);

            foreach ($dependencies as $dependency) {
                if ($dependency['preID'] === $id) {

                    if (empty($return['postrequisiteModules'][$programName])) {
                        $return['postrequisiteModules'][$programName] = [];
                    }

                    $name = $dependency['postName'];
                    $name .= empty($dependency['postModuleNumber']) ? '' : " ({$dependency['postModuleNumber']})";

                    $return['postrequisiteModules'][$programName][$dependency['postID']] = $name;
                }
                else {

                    if (empty($return['prerequisiteModules'][$programName])) {
                        $return['prerequisiteModules'][$programName] = [];
                    }

                    $name = $dependency['preName'];
                    $name .= empty($dependency['preModuleNumber']) ? '' : " ({$dependency['preModuleNumber']})";

                    $return['prerequisiteModules'][$programName][$dependency['preID']] = $name;
                }
            }

            if (isset($return['preRequisiteModules'][$programName])) {
                asort($return['preRequisiteModules'][$programName]);
            }

            if (isset($return['postrequisiteModules'][$programName])) {
                asort($return['postrequisiteModules'][$programName]);
            }
        }

        $level    = $level ?: 'Bachelor';
        $programs = Text::_(strtoupper($level) . '_PROGRAMS');

        $return['applicability'] = sprintf($return['applicability'], $programs);

        return $return;
    }

    /** @inheritDoc */
    public function getItem(): object
    {
        if (!$item = $this->item) {
            $item = parent::getItem();

            // Supplement raw values from associated tables.
            $item->coordinators = [];
            foreach (Helper::persons($item->id, Persons::COORDINATES) as $coordinator) {
                $item->coordinators[$coordinator['id']] = $coordinator['id'];
            }

            $item->organizationsIDs = Helper::organizationIDs($item->id);
            $item->persons          = [];
            foreach (Helper::persons($item->id, Persons::TEACHES) as $teacher) {
                $item->persons[$teacher['id']] = $teacher['id'];
            }
            $item->prerequisites = Helper::prerequisites($item->id);

            $ranges               = Helper::rows($item->id);
            $item->programIDs     = empty($ranges) ? [] : Programs::extractIDs($ranges);
            $item->superordinates = Pools::superValues($item->id, 'subject');

            // Reformat values for labeled display of an existing subject
            if ($item->id and !Input::getCMD('layout')) {
                $dependencies = $this->dependencies($item);
                $model        = [];
                $tag          = Application::getTag();

                $model['code'] = $item->code ?: '';
                $model['id']   = $item->id;
                $model['name'] = $item->{"fullName_$tag"};

                if ($item->coordinators) {
                    $label = Text::_('SUBJECT_COORDINATOR');

                    if (count($item->coordinators) === 1) {
                        $model[$label] = Persons::defaultName(reset($item->coordinators));
                    }
                    else {
                        $coordinators = [];
                        foreach ($item->coordinators as $personID) {
                            $name                = Persons::defaultName($personID);
                            $coordinators[$name] = $name;
                        }
                        $model[$label] = array_keys($coordinators);
                    }
                }

                $label = Text::_('TEACHERS');
                if ($item->persons) {
                    if (count($item->persons) === 1) {
                        $model[$label] = Persons::defaultName(reset($item->persons));
                    }
                    else {
                        $persons = [];
                        foreach ($item->persons as $personID) {
                            $name           = Persons::defaultName($personID);
                            $persons[$name] = $name;
                        }
                        $model[$label] = array_keys($persons);
                    }
                }
                else {
                    $model[$label] = Text::_('VARIOUS_TEACHERS');
                }

                if ($item->{"prerequisites_$tag"}) {
                    $model[Text::_('PREREQUISITES_LONG')] = $item->{"prerequisites_$tag"};
                }

                if ($dependencies['prerequisiteModules']) {
                    $model['PREREQUISITE_MODULES'] = $dependencies['prerequisiteModules'];
                }

                if ($item->{"recommendedPrerequisites_$tag"}) {
                    $model[Text::_('RECOMMENDED_PREREQUISITES_LONG')] = $item->{"recommendedPrerequisites_$tag"};
                }

                if ($item->{"description_$tag"}) {
                    $model[Text::_('SHORT_DESCRIPTION')] = $item->{"description_$tag"};
                }

                if ($item->{"content_$tag"}) {
                    $model[Text::_('CONTENT')] = $item->{"content_$tag"};
                }

                if ($item->{"objective_$tag"}) {
                    $model[Text::_('OBJECTIVES')] = $item->{"objective_$tag"};
                }

                if ($item->{"expertise_$tag"}) {
                    $model[Text::_('EXPERTISE')] = $item->{"expertise_$tag"};
                }

                if ($item->{"methodCompetence_$tag"}) {
                    $model[Text::_('METHOD_COMPETENCE')] = $item->{"methodCompetence_$tag"};
                }

                if ($item->{"socialCompetence_$tag"}) {
                    $model[Text::_('SOCIAL_COMPETENCE')] = $item->{"socialCompetence_$tag"};
                }

                if ($item->{"selfCompetence_$tag"}) {
                    $model[Text::_('SELF_COMPETENCE')] = $item->{"selfCompetence_$tag"};
                }

                if ($item->creditPoints) {
                    $hours  = ' ' . Text::_('HOURS_ABBR');
                    $values = [$item->creditPoints . ' ' . Text::_('CREDIT_POINTS_ABBR')];

                    if ($item->expenditure) {
                        $values[] = Text::_('EXPENDITURE') . ' ' . $item->expenditure . $hours;
                    }

                    if ($item->present) {
                        $values[] = Text::_('PRESENT') . ' ' . $item->present . $hours;
                    }

                    if ($item->independent) {
                        $values[] = Text::_('INDEPENDENT') . ' ' . $item->independent . $hours;
                    }

                    $model[Text::_('CREDIT_POINTS')] = count($values) === 1 ? reset($values) : $values;
                }

                if ($item->{"method_$tag"}) {
                    $methods = preg_split('/, ?/', $item->{"method_$tag"});

                    if ($item->sws) {
                        array_unshift($methods, $item->sws . ' ' . Text::_('ORGANIZER_SWS_ABBR'));
                    }

                    $model[Text::_('METHOD')] = count($methods) === 1 ? reset($methods) : $methods;
                }

                if ($pools = $this->pools($item)) {
                    $model[Text::_('SUBJECT_ITEM_SEMESTER')] = $pools;
                }

                if ($item->duration) {
                    $constant                   = $item->duration > 1 ? 'SEMESTERS' : 'SEMESTER';
                    $model[Text::_('DURATION')] = $item->duration . ' ' . Text::_($constant);
                }

                if ($item->frequencyID) {
                    $model[Text::_('AVAILABILITY')] = Frequencies::name($item->frequencyID);
                }

                $model[Text::_('INSTRUCTION_LANGUAGE')] = match ($item->language) {
                    'A' => Text::_('ARABIAN'),
                    'C' => Text::_('CHINESE'),
                    'E' => Text::_('ENGLISH'),
                    'F' => Text::_('FRENCH'),
                    'G' => Text::_('GREEK'),
                    'I' => Text::_('ITALIAN'),
                    'J' => Text::_('JAPANESE'),
                    'K' => Text::_('KOREAN'),
                    'P' => Text::_('POLISH'),
                    'S' => Text::_('SPANISH'),
                    'T' => Text::_('TURKISH'),
                    default => Text::_('GERMAN')
                };

                if ($item->{"preliminaryWork_$tag"}) {
                    $model[Text::_('PRELIMINARY_WORK')] = $item->{"preliminaryWork_$tag"};
                }

                $bonusPoints                    = $item->bonusPoints ? Text::_('YES') : Text::_('NO');
                $model[Text::_('BONUS_POINTS')] = "<p>$bonusPoints</p><p>" . Text::_('BONUS_POINTS_TEXT') . '</p>';

                if ($item->{"proof_$tag"}) {
                    $model[Text::_('PROOF')] = $item->{"proof_$tag"};
                }

                $model[Text::_('EVALUATION')] = Text::_('EVALUATION_TEXT');

                if ($dependencies['applicability']) {
                    $model[Text::_('APPLICABILITY')] = $dependencies['applicability'];
                }

                if ($dependencies['postrequisiteModules']) {
                    $model['POSTREQUISITE_MODULES'] = $dependencies['postrequisiteModules'];
                }

                if ($item->literature) {
                    $model[Text::_('LITERATURE')] = $item->literature;
                }

                $item       = (object) $model;
                $this->item = $item;
            }
        }

        return $item;
    }

    /**
     * Aggregates pool assignments by program context as texts.
     *
     * @param   object  $subject  the subject item being displayed
     *
     * @return array
     */
    private function pools(object $subject): array
    {
        $programs   = [];
        $poolRanges = Helper::pools($subject->id);

        foreach (Helper::programs($subject->id) as $prRange) {
            $program         = Programs::name($prRange['programID']);
            $semesterNumbers = [];

            foreach ($poolRanges as $poRange) {
                if ($poRange['lft'] < $prRange['lft'] or $poRange['rgt'] > $prRange['rgt']) {
                    continue;
                }

                $pool = strtolower(Pools::getFullName($poRange['poolID']));

                if (!str_contains($pool, 'semester')) {
                    continue;
                }

                if (preg_match('/(\d+)[^\d]+(\d+)?/', $pool, $numbers)) {
                    $semesterNumbers[$numbers[1]] = $numbers[1];

                    if (!empty($numbers[2])) {
                        $semesterNumbers[$numbers[2]] = $numbers[2];
                    }
                }
            }

            $semester = '';

            if ($semesterNumbers) {
                $first = min($semesterNumbers);
                $last  = max($semesterNumbers);
                $tag   = Application::getTag();

                if ($first !== $last) {
                    $suffix   = Text::_('ORGANIZER_SEMESTERS');
                    $semester = $tag === 'en' ? "$first - $last $suffix" : "$first. - $last. $suffix";
                }
                else {
                    $suffix   = Text::_('ORGANIZER_SEMESTER');
                    $semester = $tag === 'en' ? "$first $suffix" : "$first. $suffix";
                }
            }

            $semester = $semester ? "$program - $semester" : "$program - " . Text::_('SEMESTER_NOT_SPECIFIED');

            $programs[$program] = $semester;
        }

        ksort($programs);

        return array_values($programs);
    }
}
