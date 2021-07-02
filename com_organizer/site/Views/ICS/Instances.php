<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


namespace Organizer\Views\ICS;

use Organizer\Helpers;
use Organizer\Models;
use Organizer\Views\ICS\Components\VCalendar;

/**
 * Base class for a Joomla View
 *
 * Class holding methods for displaying presentation data.
 */
class Instances
{
	/**
	 * The instances data.
	 *
	 * @var array
	 */
	private $instances;

	/**
	 * Performs initial construction of the TCPDF Object.
	 */
	public function __construct()
	{
		$model = new Models\Instances();

		if (!$this->instances = $model->getItems())
		{
			Helpers\OrganizerHelper::error(404);
		}

		/*$conditions = $model->conditions;

		if (empty($conditions['my']))
		{
			if ($conditions['groupIDs'])
			{
				unset($conditions['organizationIDs']);
			}


		}*/

		//groups rooms, persons categories campuses
		//echo "<pre>" . print_r($conditions, true) . "</pre><br>";
		/*
		 * my => title = ~Stundenplan <Full Name> ; prodid
		 */
	}

	/**
	 * Chunks the lines into segments no greater than 75 Bytes for standards conformity.
	 *
	 * @param   string  $output  the output string to be chunked
	 *
	 * @return string
	 */
	private function chunk(string $output): string
	{
		// chunk it
		// add "\r\n "
		// implode it

		return 'CHUNKED OUTPUT';
	}

	/**
	 * Method to generate output. Overwriting functions should place class specific code before the parent call.
	 *
	 * @return void
	 */
	public function display()
	{
		$calendar = new VCalendar($this->instances);
		$ics      = [];
		$calendar->fill($ics);

		die;
		//$this->Output($this->filename, $destination);
		//ob_flush();
	}
}
