<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Helpers;

use THM\Organizer\Tables\Subjects as Table;
use SimpleXMLElement as Element;

/**
 * Class provides general functions for retrieving building data.
 */
class SubjectsLSF
{
    /**
     * Checks whether the text is without content other than subject module numbers and subject name attributes
     *
     * @param string $text          the text to be checked
     * @param array  $attributes    the attributes whose values are to be removed during the search
     * @param array  $codeGroupings array code (module number) => [curriculumID => subject information]
     *
     * @return bool
     */
    public static function checkContents(string $text, array $attributes, array $codeGroupings): bool
    {
        foreach ($attributes as $checkedAttribute) {
            foreach ($codeGroupings as $codeGroup) {
                foreach ($codeGroup as $curriculumSubject) {
                    if ($checkedAttribute == 'code') {
                        $text = str_replace(strtolower($curriculumSubject[$checkedAttribute]), '', $text);
                        $text = str_replace(strtoupper($curriculumSubject[$checkedAttribute]), '', $text);
                    } elseif (!empty($curriculumSubject[$checkedAttribute])) {
                        $text = str_replace($curriculumSubject[$checkedAttribute], '', $text);
                    }
                }
            }
        }

        $text = self::sanitizeText($text);
        $text = trim($text);

        return empty($text);
    }

    /**
     * Checks whether proof and method values are valid and set, and filling them with values
     * from other languages if possible
     *
     * @param Table $table the subject object
     *
     * @return void
     */
    public static function checkProofAndMethod(Table $table)
    {
        $unusableProofValue = (empty($table->proof_en) or strlen($table->proof_en) < 4);

        if ($unusableProofValue and !empty($table->proof_de)) {
            $table->proof_en = $table->proof_de;
        }

        $unusableMethodValue = (empty($table->method_en) or strlen($table->method_en) < 4);

        if ($unusableMethodValue and !empty($table->method_de)) {
            $table->method_en = $table->method_de;
        }
    }

    /**
     * Removes the formatted text tag on a text node
     *
     * @param string $text the xml node as a string
     *
     * @return string  the node without its formatted text shell
     */
    private static function cleanText(string $text): string
    {
        // Gets rid of bullshit encoding from copy and paste from word
        $text = str_replace(chr(160), ' ', $text);
        $text = str_replace(chr(194) . chr(167), '&sect;', $text);
        $text = str_replace(chr(194) . chr(171), '&laquo;', $text);
        $text = str_replace(chr(194) . chr(187), '&raquo;', $text);
        $text = str_replace(chr(194), ' ', $text);
        $text = str_replace(chr(195) . chr(159), '&szlig;', $text);
        $text = str_replace(chr(226) . chr(128) . chr(162), '&bull;', $text);

        // Remove the formatted text tag
        $text = preg_replace('/<[\/]?[f|F]ormatted[t|T]ext>/', '', $text);

        // Remove non-self-closing tags with no content and unwanted self-closing tags
        $text = preg_replace('/<((?!br|col|link).)[a-z]*[\s]*\/>/', '', $text);

        // Replace non-blank spaces
        $text = preg_replace('/&nbsp;/', ' ', $text);

        // Replace windows return entity with <br>
        $text = preg_replace('/&#13;/', '<br>', $text);

        // Run iterative parsing for nested bullshit.
        do {
            $startText = $text;

            // Replace multiple whitespace characters with a single space
            $text = preg_replace('/\s+/', ' ', $text);

            // Replace non-blank spaces
            $text = ltrim($text);

            // Remove leading white space
            $text = ltrim($text);

            // Remove trailing white space
            $text = rtrim($text);

            // Replace remaining white space with an actual space to prevent errors from weird coding
            $text = preg_replace("/\s$/", ' ', $text);

            // Remove white space between closing and opening tags
            $text = preg_replace('/(<\/[^>]+>)\s*(<[^>]*>)/', "$1$2", $text);

            // Remove non-self closing tags containing only white space
            $text = preg_replace('/<[^\/>][^>]*>\s*<\/[^>]+>/', '', $text);
        } while ($text != $startText);

        return $text;
    }

    /**
     * Parses the object and sets subject attributes
     *
     * @param Table   $table   the subject table object
     * @param Element $subject an object representing the data from the LSF response
     *
     * @return void modifies the Table object
     */
    public static function processAttributes(Table $table, Element $subject)
    {
        $table->setColumn('code', (string) $subject->modulecode, '');
        $table->setColumn('language', (string) $subject->sprache, '');
        $table->setColumn('frequencyID', (string) $subject->turnus, '');

        $durationExists = preg_match('/\d+/', (string) $subject->dauer, $duration);
        $durationValue  = empty($durationExists) ? 1 : (int) $duration[0];
        $table->setColumn('duration', $durationValue, 1);

        // Ensure reset before iterative processing
        $table->setColumn('creditPoints', 0, 0);

        // Attributes that can be set by text or individual fields
        self::processSpecialFields($table, $subject);

        $blobs = $subject->xpath('//blobs/blob');

        foreach ($blobs as $objectNode) {
            self::processObject($table, $objectNode);
        }

        self::checkProofAndMethod($table);
    }

    /**
     * Sets attributes dealing with required student expenditure
     *
     * @param Table  $table the subject data
     * @param string $text  the expenditure text
     *
     * @return void
     */
    private static function processBonus(Table $table, string $text)
    {
        // Remove tags and indescriminate left spacing then standardize as lower for comparisions.
        $text = strtolower(trim(strip_tags($text)));

        $hardNo = (
            empty($text)
            or strpos($text, 'nein') !== false
            or strpos($text, 'kein') !== false
            or $text === '0'
            or $text === '-'
        );

        if ($hardNo) {
            $table->bonusPoints = false;

            return;
        }

        // Hard yes
        if (strpos($text, 'ja') !== false or $text === '1') {
            $table->bonusPoints = true;

            return;
        }

        /**
         * Only explanatory text => implied no
         * Explanatory text for exam prerequisites => implied error => no
         */
        if (strpos($text, 'bonus') === 0 or strpos($text, 'prüfungsvorleistung') === 0) {
            $table->bonusPoints = false;

            return;
        }

        $table->bonusPoints = true;
    }

    /**
     * Sets attributes dealing with required student expenditure
     *
     * @param Table  $table the subject data
     * @param string $text  the expenditure text
     *
     * @return void
     */
    private static function processExpenditures(Table $table, string $text)
    {
        $crpMatch = [];
        preg_match('/(\d) CrP/', $text, $crpMatch);
        if (!empty($crpMatch[1])) {
            $table->setColumn('creditPoints', $crpMatch[1], 0);
        }

        $hoursMatches = [];
        preg_match_all('/(\d+)+ Stunden/', $text, $hoursMatches);
        if (!empty($hoursMatches[1])) {
            $table->setColumn('expenditure', $hoursMatches[1][0], 0);
            if (!empty($hoursMatches[1][1])) {
                $table->setColumn('present', $hoursMatches[1][1], 0);
            }

            if (!empty($hoursMatches[1][2])) {
                $table->setColumn('independent', $hoursMatches[1][2], 0);
            }
        }
    }

    /**
     * Sets subject properties according to those of the dynamic lsf properties
     *
     * @param Table   $table    the subject table object
     * @param Element $property the object containing a text blob
     *
     * @return void
     */
    private static function processObject(Table $table, Element $property)
    {
        $category = (string) $property->kategorie;

        /**
         * SimpleXML is terrible with mixed content. Since there is no guarantee what a node's format is,
         * this needs to be processed manually.
         */

        // German entries are the standard.
        if (empty($property->de->txt)) {
            $germanText  = '';
            $englishText = '';
        } else {
            $rawGermanText = (string) $property->de->txt->FormattedText->asXML();
            $germanText    = self::cleanText($rawGermanText);

            if (empty($property->en->txt)) {
                $englishText = '';
            } else {
                $rawEnglishText = (string) $property->en->txt->FormattedText->asXML();
                $englishText    = self::cleanText($rawEnglishText);
            }
        }

        switch ($category) {
            case 'Aufteilung des Arbeitsaufwands':
                // There are int fields handled elsewhere for this, hopefully.
                if (!$table->creditPoints) {
                    self::processExpenditures($table, $germanText);
                }
                break;

            case 'Bonuspunkte':
                self::processBonus($table, $germanText);
                break;

            case 'Empfohlene Voraussetzungen':
                $table->setColumn('recommendedPrerequisites_de', $germanText, '');
                $table->setColumn('recommendedPrerequisites_en', $englishText, '');
                break;

            case 'Inhalt':
                $table->setColumn('content_de', $germanText, '');
                $table->setColumn('content_en', $englishText, '');
                break;

            case 'Kurzbeschreibung':
                $table->setColumn('description_de', $germanText, '');
                $table->setColumn('description_en', $englishText, '');
                break;

            case 'Lehrformen':
                $table->setColumn('method_de', strip_tags($germanText), '');
                $table->setColumn('method_en', strip_tags($englishText), '');
                break;

            case 'Literatur':
                // This should never have been implemented with multiple languages
                $litText = $germanText ?: $englishText;
                $table->setColumn('literature', $litText, '');
                break;

            case 'Prüfungsvorleistungen':
                $table->setColumn('preliminaryWork_de', $germanText, '');
                $table->setColumn('preliminaryWork_en', $englishText, '');
                break;

            case 'Qualifikations und Lernziele':
                $table->setColumn('objective_de', $germanText, '');
                $table->setColumn('objective_en', $englishText, '');
                break;

            case 'Voraussetzungen':
                $table->setColumn('prerequisites_de', $germanText, '');
                $table->setColumn('prerequisites_en', $englishText, '');
                break;

            case 'Voraussetzungen für die Vergabe von Creditpoints':
                $table->setColumn('proof_de', $germanText, '');
                $table->setColumn('proof_en', $englishText, '');
                break;

            case 'Fachkompetenz':
            case 'Methodenkompetenz':
            case 'Sozialkompetenz':
            case 'Selbstkompetenz':
                self::processStarAttribute($table, $category, $germanText, $englishText);
                break;
        }
    }

    /**
     * Checks for the existence and viability of seldom used fields
     *
     * @param Table   $table   the data object
     * @param Element $subject the subject object
     *
     * @return void
     */
    private static function processSpecialFields(Table $table, Element $subject)
    {
        if (!empty($subject->sws)) {
            $table->setColumn('sws', (int) $subject->sws, 0);
        }

        if (empty($subject->lp)) {
            $table->setColumn('creditPoints', 0, 0);
            $table->setColumn('expenditure', 0, 0);
            $table->setColumn('present', 0, 0);
            $table->setColumn('independent', 0, 0);

            return;
        }

        $crp = (int) $subject->lp;

        $table->setColumn('creditPoints', $crp, 0);

        $expenditure = empty($subject->aufwand) ? $crp * 30 : (int) $subject->aufwand;
        $table->setColumn('expenditure', $expenditure, 0);

        $validSum = false;
        if ($subject->praesenzzeit and $subject->selbstzeit) {
            $validSum = ((int) $subject->praesenzzeit + (int) $subject->selbstzeit) == $expenditure;
        }

        if ($validSum) {
            $table->setColumn('present', (int) $subject->praesenzzeit, 0);
            $table->setColumn('independent', (int) $subject->selbstzeit, 0);

            return;
        }

        $independent = 0;
        $presence    = 0;

        // I let required presence time take priority
        if ($subject->praesenzzeit) {
            $presence    = (int) $subject->praesenzzeit;
            $independent = $expenditure - $presence;
        } elseif ($subject->selbstzeit) {
            $independent = (int) $subject->selbstzeit;
            $presence    = $expenditure - $independent;
        }

        $table->setColumn('present', $presence, 0);
        $table->setColumn('independent', $independent, 0);
    }

    /**
     * Sets business administration organization start attributes
     *
     * @param Table  $table     the subject table object
     * @param string $attribute the attribute's name in the xml response
     * @param string $deValue   the attribute's German value
     * @param string $enValue   the attribute's English value
     *
     * @return void
     */
    private static function processStarAttribute(Table $table, string $attribute, string $deValue, string $enValue)
    {
        switch ($attribute) {
            case 'Fachkompetenz':
                $deName     = 'expertise_de';
                $enName     = 'expertise_en';
                $scalarName = 'expertise';
                break;
            case 'Methodenkompetenz':
                $deName     = 'methodCompetence_de';
                $enName     = 'methodCompetence_en';
                $scalarName = 'methodCompetence';
                break;
            case 'Sozialkompetenz':
                $deName     = 'socialCompetence_de';
                $enName     = 'socialCompetence_en';
                $scalarName = 'socialCompetence';
                break;
            case 'Selbstkompetenz':
                $deName     = 'selfCompetence_de';
                $enName     = 'selfCompetence_en';
                $scalarName = 'selfCompetence';
                break;
            default:
                return;
        }

        if ($deValue === '') {
            $table->$deName     = '';
            $table->$enName     = '';
            $table->$scalarName = null;

            return;
        }

        $scalarValue = null;

        // Old scalar valuation - numbers or asterix
        if (is_numeric($deValue)) {
            $scalarValue = (int) $deValue;
            $scalarValue = $scalarValue < 4 ? $scalarValue : 3;
            $scalarValue = max($scalarValue, 0);
        } elseif (preg_match('/^(\*)+$/', $deValue, $occurences)) {
            $scalarValue = count($occurences);
            $scalarValue = $scalarValue < 4 ? $scalarValue : 3;
        }

        $table->$scalarName = $scalarValue;

        if ($scalarValue !== null) {
            $table->$deName = '';
            $table->$enName = '';

            return;
        }

        if (preg_match('/^\d/', $deValue)) {
            $deValue = trim(substr($deValue, 1));

            if (strpos($deValue, '<br>') === 0) {
                $deValue = trim(substr($deValue, 4));
            }
        }

        if (preg_match('/^\d/', $enValue)) {
            $enValue = trim(substr($enValue, 1));

            if (strpos($enValue, '<br>') === 0) {
                $enValue = trim(substr($enValue, 4));
            }
        }

        $table->$deName = $deValue;
        $table->$enName = $enValue;
    }

    /**
     * Sanitizes text for more consistent processing
     *
     * @param string $text the text to be processed
     *
     * @return string
     */
    public static function sanitizeText(string $text): string
    {
        // Get rid of HTML tags & entities
        $text = preg_replace('/<[^>]+>/', ' ', $text);
        $text = html_entity_decode($text);

        // Remove any non alphanum characters
        $text = preg_replace("/[^a-zA-Z\d]/", ' ', $text);

        // Remove excess white space
        $text = trim($text);

        return preg_replace('/\s+/', ' ', $text);
    }
}
