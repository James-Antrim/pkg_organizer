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
		$query->select("f.name_$tag AS availability, bonusPoints_$tag as bonus, content_$tag AS content, creditpoints")
			->select("description_$tag AS description, duration, expenditure, expertise, instructionLanguage")
			->select("method_$tag AS method, methodCompetence, code AS moduleCode, s.fullName_$tag AS name")
			->select("objective_$tag AS objective, preliminaryWork_$tag AS preliminaryWork")
			->select("usedFor_$tag AS prerequisiteFor, prerequisites_$tag AS prerequisites, proof_$tag AS proof")
			->select("recommendedPrerequisites_$tag as recommendedPrerequisites, selfCompetence")
			->select("socialCompetence, sws, present")
			->from('#__organizer_subjects AS s')
			->leftJoin('#__organizer_frequencies AS f ON f.id = s.frequencyID')
			->where("s.id = '$subjectID'");
		Database::setQuery($query);
		$result = Database::loadAssoc();

		if (empty($result['name']))
		{
			return [];
		}

		$subject = $this->getStructure();
		foreach ($result as $property => $value)
		{
			$subject[$property]['value'] = $value;
		}

		$this->setCampus($subject);
		$this->setDependencies($subject);
		$this->setExpenditureText($subject);
		$this->setInstructionLanguage($subject);
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
			'campus'                   => ['label' => Languages::_($option . 'CAMPUS'), 'type' => 'location'],
			'moduleCode'               => ['label' => Languages::_($option . 'MODULE_CODE'), 'type' => 'text'],
			'coordinators'             => ['label' => Languages::_($option . 'SUBJECT_COORDINATOR'), 'type' => 'list'],
			'persons'                  => ['label' => Languages::_($option . 'TEACHERS'), 'type' => 'list'],
			'description'              => ['label' => Languages::_($option . 'SHORT_DESCRIPTION'), 'type' => 'text'],
			'objective'                => ['label' => Languages::_($option . 'OBJECTIVES'), 'type' => 'text'],
			'content'                  => ['label' => Languages::_($option . 'CONTENT'), 'type' => 'text'],
			'expertise'                => ['label' => Languages::_($option . 'EXPERTISE'), 'type' => 'star'],
			'methodCompetence'         => ['label' => Languages::_($option . 'METHOD_COMPETENCE'), 'type' => 'star'],
			'socialCompetence'         => ['label' => Languages::_($option . 'SOCIAL_COMPETENCE'), 'type' => 'star'],
			'selfCompetence'           => ['label' => Languages::_($option . 'SELF_COMPETENCE'), 'type' => 'star'],
			'duration'                 => ['label' => Languages::_($option . 'DURATION'), 'type' => 'text'],
			'instructionLanguage'      => ['label' => Languages::_($option . 'INSTRUCTION_LANGUAGE'), 'type' => 'text'],
			'expenditure'              => ['label' => Languages::_($option . 'EXPENDITURE'), 'type' => 'text'],
			'sws'                      => ['label' => Languages::_($option . 'SWS'), 'type' => 'text'],
			'method'                   => ['label' => Languages::_($option . 'METHOD'), 'type' => 'text'],
			'preliminaryWork'          => ['label' => Languages::_($option . 'PRELIMINARY_WORK'), 'type' => 'text'],
			'proof'                    => ['label' => Languages::_($option . 'PROOF'), 'type' => 'text'],
			'evaluation'               => [
				'label' => Languages::_($option . 'EVALUATION'),
				'type'  => 'text',
				'value' => Languages::_($option . 'EVALUATION_TEXT')
			],
			'bonus'                    => ['label' => Languages::_($option . 'BONUS_POINTS'), 'type' => 'text'],
			'availability'             => ['label' => Languages::_($option . 'AVAILABILITY'), 'type' => 'text'],
			'prerequisites'            => ['label' => Languages::_($option . 'PREREQUISITES'), 'type' => 'text'],
			'preRequisiteModules'      => [
				'label' => Languages::_($option . 'PREREQUISITE_MODULES'),
				'type'  => 'list',
				'url'   => $url
			],
			'recommendedPrerequisites' => [
				'label' => Languages::_($option . 'RECOMMENDED_PREREQUISITES'),
				'type'  => 'text'
			],
			'prerequisiteFor'          => ['label' => Languages::_($option . 'PREREQUISITE_FOR'), 'type' => 'text'],
			'postRequisiteModules'     => [
				'label' => Languages::_($option . 'POSTREQUISITE_MODULES'),
				'type'  => 'list',
				'url'   => $url
			]
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
	 * Sets campus information in a form that can be processed by external systems.
	 *
	 * @param   array  $subject  the subject being processed.
	 *
	 * @return void modifies the subject array
	 */
	private function setCampus(array &$subject)
	{
		if (!empty($subject['campus']['value']))
		{
			$campusID                      = $subject['campus']['value'];
			$subject['campus']['value']    = Helpers\Campuses::getName($campusID);
			$subject['campus']['location'] = Helpers\Campuses::getLocation($campusID);
		}
		else
		{
			unset($subject['campus']);
		}
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
	 * Creates a textual output for the various expenditure values.
	 *
	 * @param   array &$subject  the object containing subject data
	 *
	 * @return void  sets values in the references object
	 */
	private function setExpenditureText(array &$subject)
	{
		// If there are no credit points set, this text is meaningless.
		if (!empty($subject['creditpoints']['value']))
		{
			if (empty($subject['expenditure']['value']))
			{
				$subject['expenditure']['value'] = sprintf(
					Languages::_('ORGANIZER_EXPENDITURE_SHORT'),
					$subject['creditpoints']['value']
				);
			}
			elseif (empty($subject['present']['value']))
			{
				$subject['expenditure']['value'] = sprintf(
					Languages::_('ORGANIZER_EXPENDITURE_MEDIUM'),
					$subject['creditpoints']['value'],
					$subject['expenditure']['value']
				);
			}
			else
			{
				$subject['expenditure']['value'] = sprintf(
					Languages::_('ORGANIZER_EXPENDITURE_FULL'),
					$subject['creditpoints']['value'],
					$subject['expenditure']['value'],
					$subject['present']['value']
				);
			}
		}

		unset($subject['creditpoints'], $subject['present']);
	}

	/**
	 * Creates a textual output for the language of instruction.
	 *
	 * @param   array &$subject  the object containing subject data
	 *
	 * @return void  sets values in the references object
	 */
	private function setInstructionLanguage(array &$subject)
	{
		switch (strtoupper((string) $subject['instructionLanguage']['value']))
		{
			case 'E':
				$subject['instructionLanguage']['value'] = Languages::_('ORGANIZER_ENGLISH');
				break;
			case 'D':
			default:
				$subject['instructionLanguage']['value'] = Languages::_('ORGANIZER_GERMAN');
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
