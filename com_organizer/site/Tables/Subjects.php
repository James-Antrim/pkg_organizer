<?php /** @noinspection PhpUnused */

/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Tables;

use Joomla\Database\{DatabaseDriver, DatabaseInterface};
use THM\Organizer\Adapters\Application;

/**
 * @inheritDoc
 */
class Subjects extends Table
{
    use Aliased;
    use Coded;
    use LSFImported;

    /**
     * The resource's German abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * Status: Unknown
     * @var string
     */
    public string $abbreviation_de = '';

    /**
     * The resource's English abbreviation.
     * VARCHAR(25) NOT NULL DEFAULT ''
     * Status: Unknown
     * @var string
     */
    public string $abbreviation_en = '';

    /**
     * A flag denoting whether it is possible to achieve extra credit.
     * TINYINT(1) UNSIGNED DEFAULT 0
     * @var bool
     */
    public bool $bonusPoints = false;

    /**
     * The subject's contents in German.
     * TEXT
     * @var string
     */
    public string $content_de = '';

    /**
     * The subject's contents in English.
     * TEXT
     * @var string
     */
    public string $content_en = '';

    /**
     * The number of credit points (ECTS) rewarded for successful completion of this subject.
     * INT(3) UNSIGNED  NOT NULL DEFAULT 0
     * @var int
     */
    public int $creditPoints = 0;

    /**
     * The resource's German description.
     * TEXT
     * @var string
     */
    public string $description_de = '';

    /**
     * The resource's English description.
     * TEXT
     * Status: Changed -> maximum length of displayed characters is now 300?
     * @var string
     */
    public string $description_en = '';

    /**
     * The number of terms over which the subject is taught.
     * TINYINT(1) UNSIGNED DEFAULT 1
     * @var int
     */
    public int $duration = 1;

    /**
     * The total number of scholastic hours (45 minutes) estimated to be necessary for this subject.
     * INT(4) UNSIGNED NOT NULL DEFAULT
     * @var int
     */
    public int $expenditure = 0;

    /**
     * The quantifier for the level of expertise of this subject. Values: NULL - unset, 0 - none ... 3 - much.
     * TINYINT(1) UNSIGNED DEFAULT NULL
     * @deprecated   replaced by localized full text fields
     * @var int
     */
    public int $expertise;

    /**
     * The description for expertise learning objectives in German.
     * TEXT
     * @var string
     */
    public string $expertise_de = '';

    /**
     * The description for expertise learning objectives in English.
     * TEXT
     * @var string
     */
    public string $expertise_en = '';

    /**
     * The id of the field entry referenced.
     * INT(11) UNSIGNED DEFAULT NULL
     * @var int|null
     */
    public int|null $fieldID;

    /**
     * The id of the frequency entry referenced.
     * INT(1) UNSIGNED DEFAULT NULL
     * Status: Changed -> tinyint(1) (keep semesterly, yearly and on demand)
     * @var int|null
     */
    public int|null $frequencyID;

    /**
     * The resource's German full name.
     * VARCHAR(200) NOT NULL
     * @var string
     */
    public string $fullName_de;

    /**
     * The resource's English full name.
     * VARCHAR(200) NOT NULL
     * @var string
     */
    public string $fullName_en;

    /**
     * The total number of scholastic hours (45 minutes) independent estimated to be necessary for this subject.
     * INT(4) UNSIGNED NOT NULL DEFAULT
     * @var int
     */
    public int $independent = 0;

    /**
     * The code for the language of instruction for this course.
     * VARCHAR(2) NOT NULL DEFAULT 'D'
     * Status: Unknown, are these still sent as codes? are there codes for any other potential languages?
     * @var string
     */
    public string $language = 'D';

    /**
     * The recommended literature to accompany this subject.
     * TEXT
     * @var string
     */
    public string $literature = '';

    /**
     * The German description for the way in which this subject is taught.
     * TEXT
     * Status: A whole box of things...
     * @var string
     */
    public string $method_de = '';

    /**
     * The English description for the way in which this subject is taught.
     * TEXT
     * Status: A whole box of things...
     * @var string
     */
    public string $method_en = '';

    /**
     * The quantifier for the level of method competence of this subject. Values: NULL - unset, 0 - none ... 3 - much.
     * TINYINT(1) UNSIGNED DEFAULT NULL
     * @deprecated replaced by localized full text fields
     * @var int|null
     */
    public int|null $methodCompetence;

    /**
     * The description for procedural learning objectives in German.
     * TEXT
     * @var string
     */
    public string $methodCompetence_de = '';

    /**
     * The description for procedural learning objectives in English.
     * TEXT
     * @var string
     */
    public string $methodCompetence_en = '';

    /**
     * The subject's objectives in German.
     * TEXT
     * @deprecated   replaced by competences
     * @var string
     */
    public string $objective_de = '';

    /**
     * The subject's objectives in English.
     * TEXT
     * @deprecated   replaced by competences
     * @var string
     */
    public string $objective_en = '';

    /**
     * The subject's required preliminary work in German.
     * TEXT
     * Status: Unknown. Notwendige Voraussetzungen, Prüfungsvorleistung?
     * @var string
     */
    public string $preliminaryWork_de = '';

    /**
     * The subject's required preliminary work in English.
     * TEXT
     * Status: Unknown. Notwendige Voraussetzungen, Prüfungsvorleistung?
     * @var string
     */
    public string $preliminaryWork_en = '';

    /**
     * The textual description of the subject's prerequisites in German.
     * TEXT NOT NULL
     * Status: Unknown. Notwendige Voraussetzungen, Prüfungsvorleistung?
     * @var string
     */
    public string $prerequisites_de = '';

    /**
     * The textual description of the subject's prerequisites in English.
     * TEXT
     * Status: Unknown. Notwendige Voraussetzungen, Prüfungsvorleistung?
     * @var string
     */
    public string $prerequisites_en = '';

    /**
     * The total number of scholastic hours (45 minutes) present estimated to be necessary for this subject.
     * INT(4) UNSIGNED NOT NULL DEFAULT 0
     * @var int
     */
    public int $present = 0;

    /**
     * The description of how credit points are awarded for this subject in German.
     * TEXT
     * Status: Prüfungsleistung?
     * @var string
     */
    public string $proof_de = '';

    /**
     * The description of how credit points are awarded for this subject in English.
     * TEXT
     * Status: Prüfungsleistung?
     * @var string
     */
    public string $proof_en = '';

    /**
     * The textual description of the subject's recommended prerequisites in German.
     * TEXT
     * Status: Unknown. Empfohlene Voraussetzungen
     * @var string
     * @noinspection PhpPropertyNamingConventionInspection
     */
    public string $recommendedPrerequisites_de = '';

    /**
     * The textual description of the subject's recommended prerequisites in English.
     * TEXT
     * Status: Unknown. Empfohlene Voraussetzungen
     * @var string
     * @noinspection PhpPropertyNamingConventionInspection
     */
    public string $recommendedPrerequisites_en = '';

    /**
     * The quantifier for the level of self competence of this subject. Values: NULL - unset, 0 - none ... 3 - much.
     * TINYINT(1) UNSIGNED DEFAULT NULL
     * @deprecated replaced by localized full text fields
     * @var int|null
     */
    public int|null $selfCompetence;

    /**
     * The description for personal learning objectives in German.
     * TEXT
     * @var string
     */
    public string $selfCompetence_de = '';

    /**
     * The description for personal learning objectives in English.
     * TEXT
     * @var string
     */
    public string $selfCompetence_en = '';

    /**
     * The quantifier for the level of social competence of this subject. Values: NULL - unset, 0 - none ... 3 - much.
     * TINYINT(1) UNSIGNED DEFAULT NULL
     * @deprecated replaced by localized full text fields
     * @var int|null
     */
    public int|null $socialCompetence;

    /**
     * The description for social learning objectives in German.
     * TEXT
     * @var string
     */
    public string $socialCompetence_de = '';

    /**
     * The description for social learning objectives in English.
     * TEXT
     * @var string
     */
    public string $socialCompetence_en = '';

    /**
     * The number of scholastic hours (45 minutes) of this course held per week.
     * INT(2) UNSIGNED NOT NULL DEFAULT 0
     * Status: A whole box of things...
     * @var int
     */
    public int $sws = 0;

    /**
     * @inheritDoc
     */
    public function __construct(DatabaseInterface $dbo = null)
    {
        $dbo = $dbo ?? Application::getDB();

        /** @var DatabaseDriver $dbo */
        parent::__construct('#__organizer_subjects', 'id', $dbo);
    }

    /**
     * @inheritDoc
     */
    public function check(): bool
    {
        $nullColumns = ['alias', 'fieldID', 'frequencyID', 'lsfID'];

        foreach ($nullColumns as $nullColumn) {
            if (!$this->$nullColumn) {
                $this->$nullColumn = null;
            }
        }

        $replacedFields = ['expertise', 'selfCompetence', 'methodCompetence', 'socialCompetence'];

        foreach ($replacedFields as $replacedField) {
            // Truly empty
            if (!strlen($this->$replacedField)) {
                $this->$replacedField = null;
                continue;
            }

            $value = (int) $this->$replacedField;

            $this->$replacedField = ($value < 0 or $value > 3) ? null : $value;
        }

        return true;
    }
}
