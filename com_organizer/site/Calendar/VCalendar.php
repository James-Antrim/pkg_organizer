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

use Exception;
use Organizer\Helpers\Languages;
use SimpleXMLElement;

/**
 * Provide a grouping of components and properties that describe a calendar.
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.4
 */
class VCalendar extends VComponent
{
	/**
	 * @var VComponent[]
	 */
	private $components;

	/**
	 * This property defines the iCalendar object method associated with the calendar object.
	 *
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.7.2
	 * @var string
	 */
	private $method = 'PUBLISH';

	/**
	 * This property specifies the identifier for the product that created the calendar. This string, however, only
	 * includes the schedule specific name, as the company and product names are static.
	 *
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.7.3
	 * @var string
	 */
	private $productID;

	/**
	 * This property specifies the version number of the component at the time at which the calendar was generated.
	 *
	 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.7.4
	 * @var string
	 */
	private $version;

	/**
	 * VCalendar constructor.
	 *
	 * @param   string  $productID  the name of the calendar
	 */
	public function __construct(string $productID)
	{
		$this->productID = $productID;
		$this->setVersion();
	}

	/**
	 * Adds components to the calendar.
	 *
	 * @param   VComponent  $component
	 *
	 * @return void
	 */
	public function addComponent(VComponent $component)
	{
		$this->components[] = $component;
	}

	/**
	 * @inheritDoc
	 */
	public function getProps(&$output)
	{
		$tag = strtoupper(Languages::getTag());

		$output[] = "BEGIN:VCALENDAR";
		$output[] = "PRODID:-//Technische Hochschule Mittelhessen//Organizer Component//$this->productID//$tag";
		$output[] = "VERSION:$this->version";
		$output[] = "METHOD:$this->method";

		$this->getIANAProps($output);
		$this->getXProps($output);

		foreach ($this->components as $component)
		{
			$component->getProps($output);
		}

		$output[] = "END:VCALENDAR";
	}

	/**
	 * Sets the version to that of the component.
	 *
	 * @return void
	 */
	private function setVersion()
	{
		$manifest = JPATH_ADMINISTRATOR . '/components/com_organizer/com_organizer.xml';

		try
		{
			$manifest      = new SimpleXMLElement(file_get_contents($manifest));
			$this->version = (string) $manifest->version;
		}
		catch (Exception $exception)
		{
			$this->version = "X.X.X";
		}
	}
}