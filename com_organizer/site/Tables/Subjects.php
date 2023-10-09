<?php /** @noinspection PhpUnused */

/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Tables;

/**
 * Models the organizer_subjects table.
 */
class Subjects extends BaseTable
{
    use Aliased;
    use Coded;

    /**
     * The resource's German abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * Status: Unknown
     * @var string
     */
    public $abbreviation_de;

    /**
     * The resource's English abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * Status: Unknown
     * @var string
     */
    public $abbreviation_en;

    /**
     * A flag denoting whether or not it is possible to achieve extra credit.
     * TINYINT(1) UNSIGNED DEFAULT 0
     * @var bool
     */
    public $bonusPoints;

    /**
     * The subject's contents in German.
     * TEXT
     * @var string
     */
    public $content_de;

    /**
     * The subject's contents in English.
     * TEXT
     * @var string
     */
    public $content_en;

    /**
     * The number of credit points (ECTS) rewarded for successful completion of this subject.
     * INT(3) UNSIGNED  NOT NULL DEFAULT 0
     * @var float
     */
    public $creditPoints;

    /**
     * The resource's German description.
     * TEXT
     * Status: Changed -> maximum length of displayed characters is now 300?
     * @var string
     */
    public $description_de;

    /**
     * The resource's English description.
     * TEXT
     * Status: Changed -> maximum length of displayed characters is now 300?
     * @var string
     */
    public $description_en;

    /**
     * The number of terms over which the subject is taught.
     * TINYINT(1) UNSIGNED DEFAULT 1
     * @var int
     */
    public $duration;

    /**
     * The total number of scholastic hours (45 minutes) estimated to be necessary for this subject.
     * INT(4) UNSIGNED NOT NULL DEFAULT
     * Status: Unchanged
     * @var int
     */
    public $expenditure;

    /**
     * The quantifier for the level of expertise of this subject. Values: NULL - unset, 0 - none ... 3 - much.
     * TINYINT(1) UNSIGNED DEFAULT NULL
     * @deprecated replaced by localized full text fields
     * @var int
     */
    public $expertise;

    /**
     * The description for expertise learning objectives in German.
     * TEXT
     * @var int
     */
    public $expertise_de;

    /**
     * The description for expertise learning objectives in English.
     * TEXT
     * @var int
     */
    public $expertise_en;

    /**
     * The id of the field entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * Status: Unchanged (as yet internal)
     * @var int
     */
    public $fieldID;

    /**
     * The id of the frequency entry referenced.
     * INT(1) UNSIGNED DEFAULT NULL
     * Status: Changed -> tinyint(1) (keep semesterly, yearly and on demand)
     * @var int
     */
    public $frequencyID;

    /**
     * The resource's German full name.
     * VARCHAR(200) NOT NULL
     * Status: Unchanged
     * @var string
     */
    public $fullName_de;

    /**
     * The resource's English full name.
     * VARCHAR(200) NOT NULL
     * Status: Unchanged
     * @var string
     */
    public $fullName_en;

    /**
     * The total number of scholastic hours (45 minutes) independent estimated to be necessary for this subject.
     * INT(4) UNSIGNED NOT NULL DEFAULT
     * Status: Unchanged
     * @var int
     */
    public $independent;

    /**
     * The code for the language of instruction for this course.
     * VARCHAR(2) NOT NULL DEFAULT 'D'
     * Status: Unknown, are these still sent as codes? are there codes for any other potential languages?
     * @var string
     */
    public $language;

    /**
     * The recommended literature to accompany this subject.
     * TEXT
     * @var string
     */
    public $literature;

    /**
     * The id of the entry in the LSF software module.
     * INT(11) UNSIGNED DEFAULT NULL
     * Status: Unknown
     * @var int
     */
    public $lsfID;

    /**
     * The German description for the way in which this subject is taught.
     * TEXT
     * Status: A whole box of things...
     * @var string
     */
    public $method_de;

    /**
     * The English description for the way in which this subject is taught.
     * TEXT
     * Status: A whole box of things...
     * @var string
     */
    public $method_en;

    /**
     * The quantifier for the level of method competence of this subject. Values: NULL - unset, 0 - none ... 3 - much.
     * TINYINT(1) UNSIGNED DEFAULT NULL
     * @deprecated replaced by localized full text fields
     * @var int
     */
    public $methodCompetence;

    /**
     * The description for procedural learning objectives in German.
     * TEXT
     * @var int
     */
    public $methodCompetence_de;

    /**
     * The description for procedural learning objectives in English.
     * TEXT
     * @var int
     */
    public $methodCompetence_en;

    /**
     * The subject's objectives in German.
     * TEXT
     * @deprecated replaced by competences
     * @var string
     */
    public $objective_de;

    /**
     * The subject's objectives in English.
     * TEXT
     * @deprecated replaced by competences
     * @var string
     */
    public $objective_en;

    /**
     * The subject's required preliminary work in German.
     * TEXT
     * Status: Unknown. Notwendige Voraussetzungen, Prüfungsvorleistung?
     * @var string
     */
    public $preliminaryWork_de;

    /**
     * The subject's required preliminary work in English.
     * TEXT
     * Status: Unknown. Notwendige Voraussetzungen, Prüfungsvorleistung?
     * @var string
     */
    public $preliminaryWork_en;

    /**
     * The textual description of the subject's prerequisites in German.
     * TEXT NOT NULL
     * Status: Unknown. Notwendige Voraussetzungen, Prüfungsvorleistung?
     * @var string
     */
    public $prerequisites_de;

    /**
     * The textual description of the subject's prerequisites in English.
     * TEXT
     * Status: Unknown. Notwendige Voraussetzungen, Prüfungsvorleistung?
     * @var string
     */
    public $prerequisites_en;

    /**
     * The total number of scholastic hours (45 minutes) present estimated to be necessary for this subject.
     * INT(4) UNSIGNED NOT NULL DEFAULT
     * Status: Unchanged
     * @var int
     */
    public $present;

    /**
     * The description of how credit points are awarded for this subject in German.
     * TEXT
     * Status: Prüfungsleistung?
     * @var string
     */
    public $proof_de;

    /**
     * The description of how credit points are awarded for this subject in English.
     * TEXT
     * Status: Prüfungsleistung?
     * @var string
     */
    public $proof_en;

    /**
     * The textual description of the subject's recommended prerequisites in German.
     * TEXT
     * Status: Unknown. Empfohlene Voraussetzungen
     * @var string
     */
    public $recommendedPrerequisites_de;

    /**
     * The textual description of the subject's recommended prerequisites in English.
     * TEXT
     * Status: Unknown. Empfohlene Voraussetzungen
     * @var string
     */
    public $recommendedPrerequisites_en;

    /**
     * The quantifier for the level of self competence of this subject. Values: NULL - unset, 0 - none ... 3 - much.
     * TINYINT(1) UNSIGNED DEFAULT NULL
     * @deprecated replaced by localized full text fields
     * @var int
     */
    public $selfCompetence;

    /**
     * The description for personal learning objectives in German.
     * TEXT
     * @var int
     */
    public $selfCompetence_de;

    /**
     * The description for personal learning objectives in English.
     * TEXT
     * @var int
     */
    public $selfCompetence_en;

    /**
     * The quantifier for the level of social competence of this subject. Values: NULL - unset, 0 - none ... 3 - much.
     * TINYINT(1) UNSIGNED DEFAULT NULL
     * @deprecated replaced by localized full text fields
     * @var int
     */
    public $socialCompetence;

    /**
     * The description for social learning objectives in German.
     * TEXT
     * @var int
     */
    public $socialCompetence_de;

    /**
     * The description for social learning objectives in English.
     * TEXT
     * @var int
     */
    public $socialCompetence_en;

    /**
     * The number of scholastic hours (45 minutes) of this course held per week.
     * INT(2) UNSIGNED NOT NULL DEFAULT 0
     * Status: A whole box of things...
     * @var int
     */
    public $sws;

    /**
     * Declares the associated table.
     */
    public function __construct()
    {
        parent::__construct('#__organizer_subjects');
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        $nullColumns = ['alias', 'fieldID', 'frequencyID', 'language', 'lsfID'];

        foreach ($nullColumns as $nullColumn) {
            if (!$this->$nullColumn) {
                $this->$nullColumn = null;
            }
        }

        $competences = ['expertise', 'selfCompetence', 'methodCompetence', 'socialCompetence'];

        foreach ($competences as $competence) {
            // Truly empty
            if (!strlen($this->$competence)) {
                $this->$competence = null;
                continue;
            }

            $value = (int) $this->$competence;

            $this->$competence = ($value < 0 or $value > 3) ? null : $value;
        }

        return true;
    }
}
