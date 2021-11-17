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
		'/Audio([a-zß-ÿ])/'             => 'Audio&shy;$1',
		'/Berufs([a-zß-ÿ])/'            => 'Berufs&shy;$1',
		'/Betriebs([a-zß-ÿ])/'          => 'Betriebs&shy;$1',
		'/Energie([a-zß-ÿ])/'           => 'Energie&shy;$1',
		'/Event([a-zß-ÿ])/'             => 'Event&shy;$1',
		'/Inter([a-zß-ÿ])/'             => 'Inter&shy;$1',
		'/Kommunikations([a-zß-ÿ])/'    => 'Kommuni&shy;kations&shy;$1',
		'/Kommunikation/'               => 'Kommuni&shy;kation',
		'/Landschafts([a-zß-ÿ])/'       => 'Land&shy;schafts&shy;$1',
		'/Multi([a-zß-ÿ])/'             => 'Multi&shy;$1',
		'/Sicherheits([a-zß-ÿ])/'       => 'Sicherheits&shy;$1',
		'/Text([a-zß-ÿ])/'              => 'Text&shy;$1',
		'/Unternehmens([a-zß-ÿ])/'      => 'Unter&shy;nehmens&shy;$1',
		'/Veranstaltungs([a-zß-ÿ])/'    => 'Veran&shy;staltungs&shy;$1',
		'/Wahl([a-zß-ÿ])/'              => 'Wahl&shy;$1',
		'/([a-zß-ÿ])fachliche($| )/'    => '$1&shy;fachliche$2',
		'/([a-zß-ÿ])führung($| )/'      => '$1&shy;führung$2',
		'/([a-zß-ÿ])gestaltung($| )/'   => '$1&shy;gestaltung$2',
		'/([a-zß-ÿ])isierung($| )/'     => '$1&shy;isierung$2',
		'/([a-zß-ÿ])kunde($| )/'        => '$1&shy;kunde$2',
		'/([a-zß-ÿ])lehre($| )/'        => '$1&shy;lehre$2',
		'/([a-zß-ÿ])leitung($| )/'      => '$1&shy;leitung$2',
		'/([a-zß-ÿ])management($| )/'   => '$1&shy;management$2',
		'/([a-zß-ÿ])modelle($| )/'      => '$1&shy;modelle$2',
		'/([a-zß-ÿ])module($| )/'       => '$1&shy;module$2',
		'/([a-zß-ÿ])modul($| )/'        => '$1&shy;modul$2',
		'/([a-zß-ÿ])planung($| )/'      => '$1&shy;planung$2',
		'/([a-zß-ÿ])produktion($| )/'   => '$1&shy;produktion$2',
		'/([a-zß-ÿ])rechnen($| )/'      => '$1&shy;rechnen$2',
		'/([a-zß-ÿ])recht($| )/'        => '$1&shy;recht$2',
		'/([a-zß-ÿ])schaftliches($| )/' => '$1&shy;schaftliches$2',
		'/([a-zß-ÿ])schaften($| )/'     => '$1&shy;schaften$2',
		'/([a-zß-ÿ])schaft($| )/'       => '$1&shy;schaft$2',
		'/([a-zß-ÿ])technik($| )/'      => '$1&shy;technik$2',
		'/([a-zß-ÿ])technologie($| )/'  => '$1&shy;technologie$2',
		'/([a-zß-ÿ])technology($| )/'   => '$1&shy;technology$2',
		'/([a-zß-ÿ])wesen($| )/'        => '$1&shy;wesen$2',
		'/([a-zß-ÿ])wesens($| )/'       => '$1&shy;wesens$2',
	];

	/**
	 * @inheritDoc
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
