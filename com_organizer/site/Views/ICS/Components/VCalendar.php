<?php
/**
 * @package     Organizer\Views\ICS\Components
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\ICS\Components;


use Exception;
use Organizer\Helpers\Languages;
use SimpleXMLElement;

/**
 * Provide a grouping of component properties that describe a calendar.
 */
class VCalendar extends VComponent
{
	/**
	 * The instances data.
	 *
	 * @var array
	 */
	private $instances;

	/**
	 * The component version.
	 *
	 * @var string
	 */
	private $version;

	public function __construct(array $instances)
	{
		$this->instances = $instances;
		$this->version   = $this->getVersion();
	}

	/**
	 * @inheritDoc
	 */
	public function fill(array &$ics)
	{
		$tag = strtoupper(Languages::getTag());

		$ics[] = "BEGIN:VCALENDAR";
		$ics[] = "PRODID:-//TH Mittelhessen//NONSGML Organizer $this->version//$tag";
		$ics[] = 'VERSION:' . $this->version;
		$ics[] = "METHOD:PUBLISH";

		foreach ($this->instances as $instance)
		{
			$event = new VEvent($instance);
			$event->fill($ics);
		}
		echo "<pre>" . print_r($ics, true) . "</pre><br>";
		echo "<pre>in display</pre><br>";
	}

	/**
	 * Sets the version to that of the component.
	 *
	 * @return string
	 */
	private function getVersion(): string
	{
		$manifest = JPATH_ADMINISTRATOR . '/components/com_organizer/com_organizer.xml';

		try
		{
			$manifest = new SimpleXMLElement(file_get_contents($manifest));
			$version  = (string) $manifest->version;
		}
		catch (Exception $exception)
		{
			$version = "X.X.X";
		}

		return $version;
	}
}