<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Helpers;

use Joomla\CMS\Factory;
use Joomla\CMS\Language\Language;
use Joomla\CMS\Language\Text;
use Organizer\Adapters;

/**
 * Provides general functions for language data retrieval and display.
 */
class Languages extends Text
{
	private static $baggage = [
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
	 * @inheritDoc
	 * @noinspection PhpMethodNamingConventionInspection
	 */
	public static function _($string, $jsSafe = false, $interpretBackSlashes = true, $script = false): string
	{
		if (is_array($jsSafe))
		{
			if (array_key_exists('interpretBackSlashes', $jsSafe))
			{
				$interpretBackSlashes = (bool) $jsSafe['interpretBackSlashes'];
			}

			if (array_key_exists('script', $jsSafe))
			{
				$script = (bool) $jsSafe['script'];
			}

			$jsSafe = !empty($jsSafe['jsSafe']);
		}

		$language = self::getLanguage();

		if ($script)
		{
			static::$strings[$string] = $language->_($string, $jsSafe, $interpretBackSlashes);

			return $string;
		}

		return $language->_($string, $jsSafe, $interpretBackSlashes);
	}

	/**
	 * Converts an array of values into a list string.
	 *
	 * @param   array  $array  the array to reformat
	 * @param   bool   $and    whether the last entry should be separated with ampersand
	 *
	 * @return string the reformatted
	 */
	public static function array2string(array $array, bool $and = true): string
	{
		asort($array);

		if ($and)
		{
			$last = array_pop($array);

			// Did the array originally have more than one item
			return $array ? implode(', ', $array) . ', & ' . $last : $last;
		}

		return implode(', ', $array);
	}

	/**
	 * Returns a language constant corresponding to the given class name.
	 *
	 * @param   string  $className  the name of the class
	 *
	 * @return string the constant containing the resolved text for the calling class
	 */
	public static function getConstant(string $className): string
	{
		$parts          = preg_split('/([A-Z][a-z]+)/', $className, 0, PREG_SPLIT_NO_EMPTY | PREG_SPLIT_DELIM_CAPTURE);
		$delimitedParts = implode('_', $parts);

		return 'ORGANIZER_' . strtoupper($delimitedParts);
	}

	/**
	 * Returns a language instance based on user input.
	 *
	 * @return Language
	 */
	public static function getLanguage(): Language
	{
		$language = Factory::getLanguage();
		$language->load('com_organizer', JPATH_ADMINISTRATOR . '/components/com_organizer');

		return $language;
	}

	/**
	 * Retrieves the two letter language identifier
	 *
	 * @return string
	 */
	public static function getTag(): string
	{
		return explode('-', Factory::getLanguage()->getTag())[0];
	}

	/**
	 * Translate a string into the current language and stores it in the JavaScript language store.
	 *
	 * @param   string  $string                The Text key.
	 * @param   bool    $jsSafe                Ensure the output is JavaScript safe.
	 * @param   bool    $interpretBackSlashes  Interpret \t and \n.
	 *
	 * @return  array
	 */
	public static function script($string = null, $jsSafe = false, $interpretBackSlashes = true): array
	{
		// Normalize the key and translate the string.
		static::$strings[strtoupper($string)] = self::_($string);

		// Load core.js dependency
		HTML::_('behavior.core');

		// Update Joomla.JText script options
		Adapters\Document::addScriptOptions('joomla.jtext', static::$strings, false);

		return static::getScriptStrings();
	}

	/**
	 * Sets the constant into the joomla translation scripts and resolves the constant for immediate use.
	 *
	 * @param   string  $constant  the constant to process
	 *
	 * @return string the resolved constant
	 */
	public static function setScript(string $constant): string
	{
		self::script($constant);

		return self::_($constant);
	}

	/**
	 * Converts a double colon separated string or 2 separate strings to a string ready for bootstrap tooltips
	 *
	 * @param   string  $title    The title of the tooltip (or combined '::' separated string).
	 * @param   string  $content  The content to tooltip.
	 * @param   bool    $escape   If true will pass texts through htmlspecialchars.
	 *
	 * @return  string  The tooltip string
	 */
	public static function tooltip(string $title = '', string $content = '', bool $escape = true): string
	{
		// Initialise return value.
		$result = '';

		// Don't process empty strings
		if ($content !== '' or $title !== '')
		{
			$title   = self::_($title);
			$content = self::_($content);

			if ($title === '')
			{
				$result = $content;
			}
			elseif ($title === $content)
			{
				$result = '<strong>' . $title . '</strong>';
			}
			elseif ($content !== '')
			{
				$result = '<strong>' . $title . '</strong><br />' . $content;
			}
			else
			{
				$result = $title;
			}

			// Escape everything, if required.
			if ($escape)
			{
				$result = htmlspecialchars($result);
			}
		}

		return $result;
	}

	/**
	 * @param   string  $text
	 *
	 * @return void
	 */
	public static function unpack(string &$text)
	{
		foreach (self::$baggage as $pattern => $replace)
		{
			$text = preg_replace($pattern, $replace, $text);
		}
	}
}
