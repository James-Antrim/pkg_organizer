<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use Organizer\Adapters\Database;
use Organizer\Helpers;
use Organizer\Helpers\Languages;
use Organizer\Tables;

/**
 * Class which retrieves subject information for a detailed display of subject attributes.
 */
class SubjectItem extends ItemModel
{
	/**
	 * Loads subject information from the database
	 *
	 * @return array  subject data on success, otherwise empty
	 */
	public function getItem(): array
	{
		$subjectID = Helpers\Input::getID();
		if (empty($subjectID))
		{
			return [];
		}

		$query = Database::getQuery(true);
		$tag   = Languages::getTag();
		$query->select("f.name_$tag AS availability, bonusPoints, content_$tag AS content, creditPoints")
			->select("description_$tag AS description, duration, expenditure, expertise, expertise_$tag AS exText")
			->select("language, literature, independent, method_$tag AS method, methodCompetence")
			->select("methodCompetence_$tag AS meText, code, s.fullName_$tag AS name, objective_$tag AS objective")
			->select("preliminaryWork_$tag AS preliminaryWork, usedFor_$tag AS prerequisiteFor")
			->select("prerequisites_$tag AS prerequisites, proof_$tag AS proof, present")
			->select("recommendedPrerequisites_$tag as recommendedPrerequisites, selfCompetence")
			->select("selfCompetence_$tag AS seText, socialCompetence, socialCompetence_$tag AS soText, sws")
			->from('#__organizer_subjects AS s')
			->leftJoin('#__organizer_frequencies AS f ON f.id = s.frequencyID')
			->where("s.id = '$subjectID'");
		Database::setQuery($query);
		$result = Database::loadAssoc();

		if (empty($result['name']))
		{
			return [];
		}

		$code    = empty($result['code']) ? '' : "{$result['code']} ";
		$hours   = ' ' . Languages::_('ORGANIZER_HOURS_ABBR');
		$subject = $this->getStructure();

		foreach (array_keys($subject) as $property)
		{
			if (!array_key_exists($property, $result))
			{
				continue;
			}

			$value = $result[$property];

			switch ($property)
			{
				case 'bonusPoints':
					$value = '<p>';
					$value .= $value ? Languages::_('ORGANIZER_YES') : Languages::_('ORGANIZER_NO');
					$value .= '</p><p>' . Languages::_('ORGANIZER_BONUS_POINTS_TEXT') . '</p>';
					break;
				case 'creditPoints':
					$creditPoints = $value;
					$value        = [];
					if ($creditPoints)
					{
						$value[] = Languages::_('ORGANIZER_CREDIT_POINTS') . ' ' . $creditPoints;
					}

					if ($expenditue = $result['expenditure'])
					{
						$value[] = Languages::_('ORGANIZER_EXPENDITURE') . ' ' . $expenditue . $hours;
					}

					if ($present = $result['present'])
					{
						$value[] = Languages::_('ORGANIZER_PRESENT') . ' ' . $present . $hours;
					}

					if ($independent = $result['independent'])
					{
						$value[] = Languages::_('ORGANIZER_INDEPENDENT') . ' ' . $independent . $hours;
					}

					$value['evaluation'] = Languages::_('ORGANIZER_EVALUATION_TEXT');
					break;
				case 'duration':
					$constant = $value > 1 ? 'ORGANIZER_SEMESTERS' : 'ORGANIZER_SEMESTER';
					$value    .= ' ' . Languages::_($constant);
					break;
				case 'expertise':
					if (!empty($result['exText']))
					{
						$value                      = $result['exText'];
						$subject[$property]['type'] = 'text';
					}
					break;
				case 'method':
					$value = preg_split('/, ?/', $value);

					if ($result['sws'])
					{
						array_unshift($value, $result['sws'] . ' ' . Languages::_('ORGANIZER_SWS_ABBR'));
					}

					break;
				case 'methodCompetence':
					if (!empty($result['meText']))
					{
						$value                      = $result['meText'];
						$subject[$property]['type'] = 'text';
					}
					break;
				case 'name':
					$value = $code . $value;
					break;
				case 'selfCompetence':
					if (!empty($result['seText']))
					{
						$value                      = $result['seText'];
						$subject[$property]['type'] = 'text';
					}
					break;
				case 'socialCompetence':
					if (!empty($result['soText']))
					{
						$value                      = $result['soText'];
						$subject[$property]['type'] = 'text';
					}
					break;
			}

			$subject[$property]['value'] = $value;
		}

		$this->setDependencies($subject);
		$this->setLanguage($subject);
		$this->setPersons($subject);

		return $subject;
	}

	/**
	 * Creates a framework for labeled subject attributes
	 *
	 * @return array the subject template
	 */
	private function getStructure(): array
	{
		$option = 'ORGANIZER_';
		$url    = '?option=com_organizer&view=subject_item&languageTag=' . Languages::getTag() . '&id=';

		return [
			'subjectID'                => Helpers\Input::getID(),
			'name'                     => ['label' => Languages::_($option . 'NAME'), 'type' => 'text'],

			// Persons
			'coordinators'             => ['label' => Languages::_($option . 'SUBJECT_COORDINATOR'), 'type' => 'list'],
			'persons'                  => ['label' => Languages::_($option . 'TEACHERS'), 'type' => 'list'],

			// Prerequisites
			'prerequisites'            => ['label' => Languages::_($option . 'PREREQUISITES_LONG'), 'type' => 'text'],
			'preRequisiteModules'      => [
				'label' => Languages::_($option . 'PREREQUISITE_MODULES'),
				'type'  => 'list',
				'url'   => $url
			],
			'recommendedPrerequisites' => [
				'label' => Languages::_($option . 'RECOMMENDED_PREREQUISITES_LONG'),
				'type'  => 'text'
			],

			// Descriptive texts
			'description'              => ['label' => Languages::_($option . 'SHORT_DESCRIPTION'), 'type' => 'text'],
			'content'                  => ['label' => Languages::_($option . 'CONTENT'), 'type' => 'text'],
			'objective'                => ['label' => Languages::_($option . 'OBJECTIVES'), 'type' => 'text'],
			'expertise'                => ['label' => Languages::_($option . 'EXPERTISE'), 'type' => 'star'],
			'methodCompetence'         => ['label' => Languages::_($option . 'METHOD_COMPETENCE'), 'type' => 'star'],
			'socialCompetence'         => ['label' => Languages::_($option . 'SOCIAL_COMPETENCE'), 'type' => 'star'],
			'selfCompetence'           => ['label' => Languages::_($option . 'SELF_COMPETENCE'), 'type' => 'star'],

			// Hard attributes
			'creditPoints'             => ['label' => Languages::_($option . 'EVALUATION'), 'type' => 'list'],
			'method'                   => ['label' => Languages::_($option . 'METHOD'), 'type' => 'list'],
			'duration'                 => ['label' => Languages::_($option . 'DURATION'), 'type' => 'text'],
			'availability'             => ['label' => Languages::_($option . 'AVAILABILITY'), 'type' => 'text'],
			'language'                 => ['label' => Languages::_($option . 'INSTRUCTION_LANGUAGE'), 'type' => 'text'],

			// Testing
			'preliminaryWork'          => ['label' => Languages::_($option . 'PRELIMINARY_WORK'), 'type' => 'text'],
			'proof'                    => ['label' => Languages::_($option . 'PROOF'), 'type' => 'text'],
			'bonusPoints'              => ['label' => Languages::_($option . 'BONUS_POINTS'), 'type' => 'text'],

			// Prerequisite for
			'prerequisiteFor'          => ['label' => Languages::_($option . 'PREREQUISITE_FOR'), 'type' => 'text'],
			'postRequisiteModules'     => [
				'label' => Languages::_($option . 'POSTREQUISITE_MODULES'),
				'type'  => 'list',
				'url'   => $url
			],

			// Other
			'literature'               => ['label' => Languages::_($option . 'LITERATURE'), 'type' => 'text']
		];
	}

	/**
	 * @inheritDoc
	 */
	public function getTable($name = '', $prefix = '', $options = [])
	{
		return new Tables\Subjects();
	}

	/**
	 * Loads an array of names and links into the subject model for subjects for which this subject is a prerequisite.
	 *
	 * @param   array  &$subject  the object containing subject data
	 *
	 * @return void
	 */
	private function setDependencies(array &$subject)
	{
		$subjectID = $subject['subjectID'];
		$programs  = Helpers\Subjects::getPrograms($subjectID);
		$query     = Database::getQuery();
		$tag       = Languages::getTag();
		$query->select('DISTINCT pr.id AS id')
			->select("s1.id AS preID, s1.fullName_$tag AS preName, s1.code AS preModuleNumber")
			->select("s2.id AS postID, s2.fullName_$tag AS postName, s2.code AS postModuleNumber")
			->from('#__organizer_prerequisites AS pr')
			->innerJoin('#__organizer_curricula AS c1 ON c1.id = pr.prerequisiteID')
			->innerJoin('#__organizer_subjects AS s1 ON s1.id = c1.subjectID')
			->innerJoin('#__organizer_curricula AS c2 ON c2.id = pr.subjectID')
			->innerJoin('#__organizer_subjects AS s2 ON s2.id = c2.subjectID');

		foreach ($programs as $program)
		{
			$query->clear('where');
			$query->where("c1.lft > {$program['lft']} AND c1.rgt < {$program['rgt']}")
				->where("c2.lft > {$program['lft']} AND c2.rgt < {$program['rgt']}")
				->where("(s1.id = $subjectID OR s2.id = $subjectID)");
			Database::setQuery($query);

			if (!$dependencies = Database::loadAssocList('id'))
			{
				continue;
			}

			$programName = Helpers\Programs::getName($program['programID']);

			foreach ($dependencies as $dependency)
			{
				if ($dependency['preID'] == $subjectID)
				{
					if (empty($subject['postRequisiteModules']['value']))
					{
						$subject['postRequisiteModules']['value'] = [];
					}

					if (empty($subject['postRequisiteModules']['value'][$programName]))
					{
						$subject['postRequisiteModules']['value'][$programName] = [];
					}

					$name = $dependency['postName'];
					$name .= empty($dependency['postModuleNumber']) ? '' : " ({$dependency['postModuleNumber']})";

					$subject['postRequisiteModules']['value'][$programName][$dependency['postID']] = $name;
				}
				else
				{
					if (empty($subject['preRequisiteModules']['value']))
					{
						$subject['preRequisiteModules']['value'] = [];
					}

					if (empty($subject['preRequisiteModules']['value'][$programName]))
					{
						$subject['preRequisiteModules']['value'][$programName] = [];
					}

					$name = $dependency['preName'];
					$name .= empty($dependency['preModuleNumber']) ? '' : " ({$dependency['preModuleNumber']})";

					$subject['preRequisiteModules']['value'][$programName][$dependency['preID']] = $name;
				}
			}

			if (isset($subject['preRequisiteModules']['value'][$programName]))
			{
				asort($subject['preRequisiteModules']['value'][$programName]);
			}

			if (isset($subject['postRequisiteModules']['value'][$programName]))
			{
				asort($subject['postRequisiteModules']['value'][$programName]);
			}
		}
	}

	/**
	 * Creates a textual output for the language of instruction.
	 *
	 * @param   array &$subject  the object containing subject data
	 *
	 * @return void  sets values in the references object
	 */
	private function setLanguage(array &$subject)
	{
		switch (strtoupper((string) $subject['language']['value']))
		{
			case 'E':
				$subject['language']['value'] = Languages::_('ORGANIZER_ENGLISH');
				break;
			case 'D':
			default:
				$subject['language']['value'] = Languages::_('ORGANIZER_GERMAN');
		}
	}

	/**
	 * Loads an array of names and links into the subject model for subjects for which this subject is a prerequisite.
	 *
	 * @param   array &$subject  the object containing subject data
	 *
	 * @return void
	 */
	private function setPersons(array &$subject)
	{
		$personData = Helpers\Persons::getDataBySubject($subject['subjectID'], 0, true, false);

		if (empty($personData))
		{
			return;
		}

		$coordinators = [];
		$persons      = [];

		foreach ($personData as $person)
		{
			$title    = empty($person['title']) ? '' : "{$person['title']} ";
			$forename = empty($person['forename']) ? '' : "{$person['forename']} ";
			$surname  = $person['surname'];
			$name     = $title . $forename . $surname;

			if ($person['role'] == '1')
			{
				$coordinators[$person['id']] = $name;
			}
			else
			{
				$persons[$person['id']] = $name;
			}
		}

		if (count($coordinators))
		{
			$subject['coordinators']['value'] = $coordinators;
		}

		if (count($persons))
		{
			$subject['persons']['value'] = $persons;
		}
	}
}
