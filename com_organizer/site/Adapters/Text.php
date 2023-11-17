<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Joomla\CMS\Language\Text as Base;

/**
 * Class handles localization resolution.
 */
class Text extends Base
{
    private static array $baggage = [
        // 's' so as not to replace &shy; with & shy;
        '/([^ ])&([^ s])/'                      => '$1 & $2',
        '/([^ ])& /'                            => '$1 & ',
        '/ &([^ s])/'                           => ' & $1',
        '/([a-zß-ÿ\.])\/([A-ZÀ-ÖØ-Þ])/'         => '$1 / $2',
        '/Audio([a-zß-ÿ])/'                     => 'Audio&shy;$1',
        '/Berufs([a-zß-ÿ])/'                    => 'Berufs&shy;$1',
        '/Betriebs([a-zß-ÿ])/'                  => 'Betriebs&shy;$1',
        '/([E|e])inführung(s?)([a-zß-ÿ])/'      => '$1inführung$2&shy;$3',
        '/Energie([a-zß-ÿ])/'                   => 'Energie&shy;$1',
        '/Event([a-zß-ÿ])/'                     => 'Event&shy;$1',
        '/Inter([a-zß-ÿ])/'                     => 'Inter&shy;$1',
        '/Kommunikations([a-zß-ÿ])/'            => 'Kommuni&shy;kations&shy;$1',
        '/Kommunikation/'                       => 'Kommuni&shy;kation',
        '/Landschafts([a-zß-ÿ])/'               => 'Land&shy;schafts&shy;$1',
        '/Multi([a-zß-ÿ])/'                     => 'Multi&shy;$1',
        '/Raum([a-zß-ÿ])/'                      => 'Raum&shy;$1',
        '/Reinigungs([a-zß-ÿ])/'                => 'Reinigungs&shy;$1',
        '/Studien([a-zß-ÿ])/'                   => 'Studien&shy;$1',
        '/Sicherheits([a-zß-ÿ])/'               => 'Sicherheits&shy;$1',
        '/Text([a-zß-ÿ])/'                      => 'Text&shy;$1',
        '/Unternehmens([a-zß-ÿ])/'              => 'Unter&shy;nehmens&shy;$1',
        '/Veranstaltungs([a-zß-ÿ])/'            => 'Veran&shy;staltungs&shy;$1',
        '/Wahl([a-zß-ÿ])/'                      => 'Wahl&shy;$1',
        '/([a-zß-ÿ])abschätzung($| )/'          => '$1&shy;abschätzung$2',
        '/([a-zß-ÿ])arbeit($| )/'               => '$1&shy;arbeit$2',
        '/([a-zß-ÿ])bearbeitung($| )/'          => '$1&shy;bearbeitung$2',
        '/([a-zß-ÿ])berechnung($| )/'           => '$1&shy;berechnung$2',
        '/([a-zß-ÿ])bewertung($| )/'            => '$1&shy;bewertung$2',
        '/([a-zß-ÿ])entwicklung(s?)($| )/'      => '$1&shy;entwicklung$2$3',
        '/([a-zß-ÿ])fachliche($| )/'            => '$1&shy;fachliche$2',
        '/([a-zß-ÿ])förderung($| )/'            => '$1&shy;förderung$2',
        '/([a-zß-ÿ])führung($| )/'              => '$1&shy;führung$2',
        '/([a-zß-ÿ])gestaltung($| )/'           => '$1&shy;gestaltung$2',
        '/([a-zß-ÿ])gruppen($| )/'              => '$1&shy;gruppen$2',
        '/([a-zß-ÿ])informatiker($| )/'         => '$1&shy;informatiker$2',
        '/([a-zß-ÿ])informations($| )/'         => '$1&shy;informations$2',
        '/([a-zß-ÿ])isierung($| )/'             => '$1&shy;isierung$2',
        '/([a-zß-ÿ])istische([mnrs]?)($| )/'    => '$1&shy;istische$2$3',
        '/([a-zß-ÿ])kalkulation($| )/'          => '$1&shy;kalkulation$2',
        '/([a-zß-ÿ])kommunikation($| )/'        => '$1&shy;kommunikation$2',
        '/([a-zß-ÿ])kompetenzen($| )/'          => '$1&shy;kompetenzen$2',
        '/([a-zß-ÿ])kunde($| )/'                => '$1&shy;kunde$2',
        '/([a-zß-ÿ])lehre($| )/'                => '$1&shy;lehre$2',
        '/([a-zß-ÿ])leitung($| )/'              => '$1&shy;leitung$2',
        '/([a-zß-ÿ])management([:s]?)($| )/'    => '$1&shy;management$2$3',
        '/([a-zß-ÿ])methodik($| )/'             => '$1&shy;methodik$2',
        '/([a-zß-ÿ])modelle($| )/'              => '$1&shy;modelle$2',
        '/([a-zß-ÿ])module($| )/'               => '$1&shy;module$2',
        '/([a-zß-ÿ])modul($| )/'                => '$1&shy;modul$2',
        '/([a-zß-ÿ])optimierte($| )/'           => '$1&shy;optimierte$2',
        '/([a-zß-ÿ])orientierte([mnrs]?)($| )/' => '$1&shy;orientierte$2$3',
        '/([a-zß-ÿ])planung($| )/'              => '$1&shy;planung$2',
        '/([a-zß-ÿ])produktion($| )/'           => '$1&shy;produktion$2',
        '/([a-zß-ÿ])programm($| )/'             => '$1&shy;programm$2',
        '/([a-zß-ÿ])projekt($| )/'              => '$1&shy;projekt$2',
        '/([a-zß-ÿ])rechnen($| )/'              => '$1&shy;rechnen$2',
        '/([a-zß-ÿ])rechnung($| )/'             => '$1&shy;rechnung$2',
        '/([a-zß-ÿ])rechtliche($| )/'           => '$1&shy;rechtliche$2',
        '/([a-zß-ÿ])recht($| )/'                => '$1&shy;recht$2',
        '/([a-zß-ÿ])schaftliche([mnrs]?)($| )/' => '$1&shy;schaftliche$2$3',
        '/([a-zß-ÿ])schaften($| )/'             => '$1&shy;schaften$2',
        '/([a-zß-ÿ])sicherheit($| )/'           => '$1&shy;sicherheit$2',
        '/([a-zß-ÿ])simulation($| )/'           => '$1&shy;simulation$2',
        '/([a-zß-ÿ])systematik($| |\))/'        => '$1&shy;systematik$2',
        '/([a-zß-ÿ])systeme($| |\))/'           => '$1&shy;systeme$2',
        '/([a-zß-ÿ])techniken($| )/'            => '$1&shy;techniken$2',
        '/([a-zß-ÿ])technik($| )/'              => '$1&shy;technik$2',
        '/([a-zß-ÿ])technologie($| )/'          => '$1&shy;technologie$2',
        '/([a-zß-ÿ])technology($| )/'           => '$1&shy;technology$2',
        '/([a-zß-ÿ])übung($| )/'                => '$1&shy;übung$2',
        '/([a-zß-ÿ])unterstützung($| )/'        => '$1&shy;unterstützung$2',
        '/([a-zß-ÿ])verarbeitung($| )/'         => '$1&shy;verarbeitung$2',
        '/([a-zß-ÿ])verfahren($| )/'            => '$1&shy;verfahren$2',
        '/([a-zß-ÿ])verwaltung($| )/'           => '$1&shy;verwaltung$2',
        '/([a-zß-ÿ])wirtschaft($| )/'           => '$1&shy;wirtschaft$2',
        '/([a-zß-ÿ])wesen(s?)($| )/'            => '$1&shy;wesen$2$3',
        '/([a-zß-ÿ])schaft($| )/'               => '$1&shy;schaft$2'
    ];

    /**
     * Translates a string into the current language.
     *
     * @param   string  $string                The string to translate.
     * @param   mixed   $jsSafe                Boolean: Make the result javascript safe.
     * @param   bool    $interpretBackSlashes  To interpret backslashes (\\=\, \n=carriage return, \t=tabulation)
     * @param   bool    $script                To indicate that the string will be push in the javascript language store
     *
     * @return  string  The translated string or the key if $script is true
     * @noinspection PhpMethodNamingConventionInspection
     */
    public static function _($string, $jsSafe = false, $interpretBackSlashes = true, $script = false): string
    {
        $string = self::prefaceKey($string);

        if ($script) {
            return self::useLocalization($string);
        }

        return Application::getLanguage()->_($string, $jsSafe, $interpretBackSlashes);
    }

    /**
     * Translate a string into the current language and stores it in the JavaScript language store.
     *
     * @param   string  $key  the localization key
     *
     * @return  array the current localizations queued for use in script
     */
    public static function addLocalization(string $key): array
    {
        $key = self::prefaceKey($key);
        $key = strtoupper($key);

        // Normalize the key and translate the string.
        static::$strings[$key] = Application::getLanguage()->_($key);

        // Load core.js dependency
        HTML::_('behavior.core');

        // Update Joomla.Text script options
        Document::scriptLocalizations('joomla.jtext', static::$strings, false);

        return static::getScriptStrings();
    }

    /**
     * Supplements a non-prefaced key as necessary.
     *
     * @param   string  $key
     *
     * @return string the resolved localization
     */
    private static function prefaceKey(string $key): string
    {
        preg_match('/^([A-Z_]+|\d{3})$/', $key, $matches);
        $isKey = !empty($matches);

        // The key is in fact a localization key and the component preface is missing.
        if ($isKey and !str_starts_with($key, 'ORGANIZER_')) {
            $key = "ORGANIZER_$key";
        }

        return $key;
    }

    /**
     * @inheritDoc
     * Two Joomla\CMS\Language\Text exist the real one (Text.php) uses $string the dummy (finalisation.php) uses $text.
     * @noinspection PhpParameterNameChangedDuringInheritanceInspection
     */
    public static function sprintf($string): string
    {
        $lang    = Application::getLanguage();
        $args    = func_get_args();
        $args[0] = $lang->_(self::prefaceKey($string));

        // Replace custom placeholders
        $args[0] = preg_replace('/\[\[%([0-9]+):[^\]]*\]\]/', '%\1$s', $args[0]);

        return call_user_func_array('sprintf', $args);
    }

    /**
     * Removes excess space characters from a given string.
     *
     * @param   string  $text  the text to be trimmed
     *
     * @return string the trimmed text
     */
    public static function trim(string $text): string
    {
        return trim(preg_replace('/ +/u', ' ', $text));
    }

    /**
     * Breaks long German words with &shy; for smoother HTML output.
     *
     * @param   string  $text
     *
     * @return void
     */
    public static function unpack(string &$text): void
    {
        foreach (self::$baggage as $pattern => $replace) {
            $text = preg_replace($pattern, $replace, $text);
        }
    }

    /**
     * Adds the localization as necessary and resolves the value for immediate use.
     *
     * @param   string  $key  the localization key to resolve
     *
     * @return string the resolved constant
     */
    public static function useLocalization(string $key): string
    {
        $key = self::prefaceKey($key);
        self::addLocalization($key);

        return self::_($key);
    }
}