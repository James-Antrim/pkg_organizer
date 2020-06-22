<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

use JDatabaseDriver;

/**
 * Models the organizer_subjects table.
 */
class Subjects extends BaseTable
{
	use Aliased;

	/**
	 * The resource's German abbreviation.
	 * VARCHAR(25) NOT NULL DEFAULT ''
	 *
	 * @var string
	 */
	public $abbreviation_de;

	/**
	 * The resource's English abbreviation.
	 * VARCHAR(25) NOT NULL DEFAULT ''
	 *
	 * @var string
	 */
	public $abbreviation_en;

	/**
	 * The subject's supplementary materials in German.
	 * TEXT
	 *
	 * @var string
	 */
	public $aids_de;

	/**
	 * The subject's supplementary materials in English.
	 * TEXT
	 *
	 * @var string
	 */
	public $aids_en;

	/**
	 * A description of ways in which to achieve extra credit for this subject in German.
	 * TEXT
	 *
	 * @var string
	 */
	public $bonusPoints_de;

	/**
	 * A description of ways in which to achieve extra credit for this subject in English.
	 * TEXT
	 *
	 * @var string
	 */
	public $bonusPoints_en;

	/**
	 * An abbreviated nomenclature for the resource. Currently corresponding to the identifier in Untis scheduling
	 * software.
	 * VARCHAR(60) NOT NULL DEFAULT ''
	 *
	 * @var string
	 */
	public $code;

	/**
	 * The subject's contents in German.
	 * TEXT
	 *
	 * @var string
	 */
	public $content_de;

	/**
	 * The subject's contents in English.
	 * TEXT
	 *
	 * @var string
	 */
	public $content_en;

	/**
	 * The number of credit points (ECTS) rewarded for successful completion of this subject.
	 * DOUBLE(4, 1) UNSIGNED NOT NULL DEFAULT 0
	 *
	 * @var float
	 */
	public $creditpoints;

	/**
	 * The resource's German description.
	 * TEXT
	 *
	 * @var string
	 */
	public $description_de;

	/**
	 * The resource's English description.
	 * TEXT
	 *
	 * @var string
	 */
	public $description_en;

	/**
	 * The number of terms over which the subject is taught.
	 * INT(2) UNSIGNED DEFAULT 1
	 *
	 * @var int
	 */
	public $duration;

	/**
	 * The standard for evaluation in German.
	 * TEXT
	 *
	 * @var string
	 */
	public $evaluation_de;

	/**
	 * The standard for evaluation in English.
	 * TEXT
	 *
	 * @var string
	 */
	public $evaluation_en;

	/**
	 * The total number of scholastic hours (45 minutes) estimated to be necessary for this subject.
	 * INT(4) UNSIGNED NOT NULL DEFAULT
	 *
	 * @var int
	 */
	public $expenditure;

	/**
	 * The quantifier for the level of expertise of this subject. Values: NULL - unset, 0 - none ... 3 - much.
	 * TINYINT(1) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $expertise;

	/**
	 * The id of the field entry referenced.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $fieldID;

	/**
	 * The id of the frequency entry referenced.
	 * INT(1) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $frequencyID;

	/**
	 * The resource's German full name.
	 * VARCHAR(200) NOT NULL
	 *
	 * @var string
	 */
	public $fullName_de;

	/**
	 * The resource's English full name.
	 * VARCHAR(200) NOT NULL
	 *
	 * @var string
	 */
	public $fullName_en;

	/**
	 * The total number of scholastic hours (45 minutes) independent estimated to be necessary for this subject.
	 * INT(4) UNSIGNED NOT NULL DEFAULT
	 *
	 * @var int
	 */
	public $independent;

	/**
	 * The code for the language of instruction for this course.
	 * VARCHAR(2) NOT NULL DEFAULT 'D'
	 *
	 * @var string
	 */
	public $instructionLanguage;

	/**
	 * The recommended literature to accompany this subject.
	 * TEXT
	 *
	 * @var string
	 */
	public $literature;

	/**
	 * The id of the entry in the LSF software module.
	 * INT(11) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $lsfID;

	/**
	 * The German description for the way in which this subject is taught.
	 * TEXT
	 *
	 * @var string
	 */
	public $method_de;

	/**
	 * The English description for the way in which this subject is taught.
	 * TEXT
	 *
	 * @var string
	 */
	public $method_en;

	/**
	 * The quantifier for the level of method competence of this subject. Values: NULL - unset, 0 - none ... 3 - much.
	 * TINYINT(1) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $methodCompetence;

	/**
	 * The subject's objectives in German.
	 * TEXT
	 *
	 * @var string
	 */
	public $objective_de;

	/**
	 * The subject's objectives in English.
	 * TEXT
	 *
	 * @var string
	 */
	public $objective_en;

	/**
	 * The subject's required preliminary work in German.
	 * TEXT
	 *
	 * @var string
	 */
	public $preliminaryWork_de;

	/**
	 * The subject's required preliminary work in English.
	 * TEXT
	 *
	 * @var string
	 */
	public $preliminaryWork_en;

	/**
	 * The textual description of the subject's prerequisites in German.
	 * TEXT NOT NULL
	 *
	 * @var string
	 */
	public $prerequisites_de;

	/**
	 * The textual description of the subject's prerequisites in English.
	 * TEXT
	 *
	 * @var string
	 */
	public $prerequisites_en;

	/**
	 * The total number of scholastic hours (45 minutes) present estimated to be necessary for this subject.
	 * INT(4) UNSIGNED NOT NULL DEFAULT
	 *
	 * @var int
	 */
	public $present;

	/**
	 * The description of how credit points are awarded for this subject in German.
	 * TEXT
	 *
	 * @var string
	 */
	public $proof_de;

	/**
	 * The description of how credit points are awarded for this subject in English.
	 * TEXT
	 *
	 * @var string
	 */
	public $proof_en;

	/**
	 * The textual description of the subject's recommended prerequisites in German.
	 * TEXT
	 *
	 * @var string
	 */
	public $recommendedPrerequisites_de;

	/**
	 * The textual description of the subject's recommended prerequisites in English.
	 * TEXT
	 *
	 * @var string
	 */
	public $recommendedPrerequisites_en;

	/**
	 * The quantifier for the level of self competence of this subject. Values: NULL - unset, 0 - none ... 3 - much.
	 * TINYINT(1) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $selfCompetence;

	/**
	 * The resource's shortened German name.
	 * VARCHAR(50) NOT NULL DEFAULT ''
	 *
	 * @var string
	 */
	public $shortName_de;

	/**
	 * The resource's shortened English name.
	 * VARCHAR(50) NOT NULL DEFAULT ''
	 *
	 * @var string
	 */
	public $shortName_en;

	/**
	 * The quantifier for the level of social competence of this subject. Values: NULL - unset, 0 - none ... 3 - much.
	 * TINYINT(1) UNSIGNED DEFAULT NULL
	 *
	 * @var int
	 */
	public $socialCompetence;

	/**
	 * The number of scholastic hours (45 minutes) of this course held per week.
	 * INT(2) UNSIGNED NOT NULL DEFAULT 0
	 *
	 * @var int
	 */
	public $sws;

	/**
	 * Resources requiring this subject in German.
	 * TEXT
	 *
	 * @var string
	 */
	public $usedFor_de;

	/**
	 * Resources requiring this subject in English.
	 * TEXT
	 *
	 * @var string
	 */
	public $usedFor_en;

	/**
	 * Declares the associated table
	 *
	 * @param   JDatabaseDriver  $dbo  A database connector object
	 */
	public function __construct($dbo = null)
	{
		parent::__construct('#__organizer_subjects', 'id', $dbo);
	}

	/**
	 * Set the table column names which are allowed to be null
	 *
	 * @return boolean  true
	 */
	public function check()
	{
		$nullColumns = ['alias', 'fieldID', 'frequencyID', 'instructionLanguage'];

		foreach ($nullColumns as $nullColumn)
		{
			if (!$this->$nullColumn)
			{
				$this->$nullColumn = null;
			}
		}

		$competences = ['expertise', 'selfCompetence', 'methodCompetence', 'socialCompetence'];

		foreach ($competences as $competence)
		{
			// Truly empty
			if (!strlen($this->$competence))
			{
				$this->$competence = null;
				continue;
			}

			$value = (int) $this->$competence;

			$this->$competence = ($value < 0 or $value > 3) ? null : $value;
		}

		return true;
	}
}
