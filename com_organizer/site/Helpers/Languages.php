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

			$jsSafe = array_key_exists('jsSafe', $jsSafe) ? (bool) $jsSafe['jsSafe'] : false;
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
		$tag = self::getTag();
		switch ($tag)
		{
			case 'en':
				$language = Language::getInstance('en-GB');
				break;
			case 'de':
			default:
				$language = Language::getInstance('de-DE');
				break;
		}

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
		$requestedTag = Input::getCMD('languageTag');

		if (!empty($requestedTag))
		{
			return $requestedTag;
		}

		$list = Input::getListItems();
		if ($listTag = $list->get('languageTag'))
		{
			return $listTag;
		}

		$default = explode('-', Factory::getLanguage()->getTag())[0];

		return Input::getParams()->get('initialLanguage', $default);
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
	public static function tooltip($title = '', $content = '', $escape = true): string
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
}
