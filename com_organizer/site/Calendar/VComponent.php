<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Calendar;

/**
 * Class provides methods useful to all calendar components.
 */
abstract class VComponent
{
	protected const NEWLINE = "\r\n";

	/**
	 * Stores IANA properties.
	 *
	 * Description:
	 *
	 * This specification allows other properties registered with IANA to be specified in any calendar components.
	 * Compliant applications are expected to be able to parse these other IANA-registered properties but can ignore
	 * them.
	 *
	 * Format Definition:
	 *
	 * iana-prop = iana-token *(";" icalparameter) ":" value CRLF
	 *
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.8.1
	 * @var array
	 */
	protected $ianaProps = [];

	/**
	 * Purpose:
	 *
	 * To explicitly specify the value type format for a property value.
	 *
	 * Description:
	 *
	 * This parameter specifies the value type and format of the property value.  The property values MUST be of a
	 * single value type.  For example, a "RDATE" property cannot have a combination of DATE-TIME and TIME value types.
	 *
	 * If the property's value is the default value type, then this parameter need not be specified.  However, if the
	 * property's default value type is overridden by some other allowable value type, then this parameter MUST be
	 * specified.
	 *
	 * Applications MUST preserve the value data for x-name and iana-token values that they don't recognize without
	 * attempting to interpret or parse the value data.
	 *
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.2.20
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.3
	 * @var string[]
	 */
	protected $valueTypes = [
		'BINARY',
		'BOOLEAN',
		'CAL-ADDRESS',
		'DATE',
		'DATE-TIME',
		'DURATION',
		'FLOAT',
		'INTEGER',
		'PERIOD',
		'RECUR',
		'TEXT',
		'TIME',
		'URI',
		'UTC-OFFSET',
	];

	/**
	 * Stores experimental properties.
	 *
	 * Description:
	 *
	 * The MIME Calendaring and Scheduling Content Type provides a "standard mechanism for doing non-standard things".
	 * This extension support is provided for implementers to "push the envelope" on the existing version of the memo.
	 * Extension properties are specified by property and/or property parameter names that have the prefix text of "X-"
	 * (the two-character sequence: LATIN CAPITAL LETTER X character followed by the HYPHEN-MINUS character). It is
	 * recommended that vendors concatenate onto this sentinel another short prefix text to identify the vendor. This
	 * will facilitate readability of the extensions and minimize possible collision of names between different vendors.
	 *  User agents that support this content type are expected to be able to parse the extension properties and
	 * property parameters but can ignore them.
	 *
	 * At present, there is no registration authority for names of extension properties and property parameters. The
	 * value type for this property is TEXT.  Optionally, the value type can be any of the other valid value types.
	 *
	 * Format Definition:
	 *
	 * x-prop = x-name *(";" icalparameter) ":" value CRLF
	 *
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.8.2
	 * @var array
	 */
	protected $xProps = [];

	/**
	 * Adds the component's IANA properties to the output array.
	 *
	 * @param   array  $output
	 *
	 * @return void
	 */
	protected function getIANAProps(array &$output)
	{
		foreach ($this->ianaProps as $token => $data)
		{
			$type     = $data['TYPE'] === 'TEXT' ? '' : ";VALUE={$data['type']}";
			$output[] = "$token$type:{$data['VALUE']}";
		}
	}

	/**
	 *
	 * @param $output
	 *
	 * @return mixed
	 */
	abstract protected function getProps(&$output);

	/**
	 * Adds the component's experimental properties to the output array.
	 *
	 * @param   array  $output
	 *
	 * @return void
	 */
	protected function getXProps(array &$output)
	{
		foreach ($this->xProps as $token => $data)
		{
			$property = $token;
			$property .= $data['TYPE'] === 'TEXT' ? '' : ";VALUE={$data['TYPE']}";
			$value    = ":{$data['VALUE']}";
			unset($data['TYPE'], $data['VALUE']);

			foreach ($data as $key => $value)
			{
				$property .= ";$key=$value";
			}

			$output[] = $property . $value;
		}
	}

	/**
	 * Sets an IANA registered property value. Optionally with a specific type.
	 *
	 * @param   string  $token  the iana token
	 * @param   mixed   $value  literally anything
	 * @param   string  $type   a $valueType, an-iana type or an x-type
	 *
	 * @return void
	 */
	protected function setIANAProp(string $token, $value, string $type = 'TEXT')
	{
		if ($value === null)
		{
			return;
		}

		$this->ianaProps[strtoupper($token)] = ['TYPE' => strtoupper($type), 'VALUE' => $value];
	}

	/**
	 * Sets an IANA registered property value. Optionally with a specific type.
	 *
	 * @param   string  $token          the iana token
	 * @param   array   $configuration  an array of items to store as key value pairs. if the value for the key value is
	 *                                  not set the item is not stored
	 * @param   string  $type           a $valueType, an-iana type or an x-type
	 *
	 * @return void sets entries in the xProps property
	 */
	protected function setXProp(string $token, array $configuration, string $type = 'TEXT')
	{
		$properties = [];

		foreach ($configuration as $key => $value)
		{
			$properties[strtoupper($key)] = $value;
		}

		if (!isset($properties['VALUE']) or $properties['VALUE'] === null)
		{
			return;
		}

		$token = strtoupper($token);

		if (strpos($token, 'X-') !== 0)
		{
			$token = "X-$token";
		}

		$properties['TYPE'] = empty($properties['TYPE']) ? 'TEXT' : strtoupper($properties['TYPE']);

		$this->xProps[$token] = $properties;
	}

	/**
	 * Converts individual lines of output into chunks <= 75 octets.
	 *
	 * Lines of text SHOULD NOT be longer than 75 octets, excluding the line break.  Long content lines SHOULD be split
	 * into a multiple line representations using a line "folding" technique.  That is, a long line can be split between
	 * any two characters by inserting a CRLF immediately followed by a single linear white-space character (i.e., SPACE
	 * or HTAB).  Any sequence of CRLF followed immediately by a single linear white-space character is ignored (i.e.,
	 * removed) when processing the content type.
	 *
	 * @param   array  $output
	 *
	 * @return void
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.1
	 */
	protected function chunkOutput(array &$output)
	{
		foreach ($output as $index => $line)
		{
			$pos    = 0;
			$string = '';


		}
	}
}