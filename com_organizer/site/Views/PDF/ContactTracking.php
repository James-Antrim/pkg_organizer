<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\PDF;

use Organizer\Helpers;
use Organizer\Tables\Persons;

/**
 * Class loads persistent information about a course into the display context.
 */
class ContactTracking extends ListView
{
	public $participantName;

	/**
	 * Performs initial construction of the TCPDF Object.
	 *
	 * @param   string  $orientation  page orientation
	 * @param   string  $unit         unit of measure
	 * @param   mixed   $format       page format; possible values: string - common format name, array - parameters
	 *
	 * @see \TCPDF_STATIC::getPageSizeFromFormat(), setPageFormat()
	 */
	public function __construct($orientation = self::PORTRAIT, $unit = 'mm', $format = 'A4')
	{
		parent::__construct($orientation, $unit, $format);

		$name  = '';
		$state = $this->formState;

		if ($participantID = $state->get('participantID'))
		{
			$user = Helpers\Users::getUser($participantID);
			$name = "$user->name ($user->username)";
		}
		elseif ($personID = $state->get('personID'))
		{
			$person = new Persons();
			if ($person->load($personID))
			{
				if ($person->forename)
				{
					$name .= "$person->forename ";
				}

				$name .= "$person->surname ";

				if ($person->username)
				{
					$name .= " ($person->username)";
				}
			}
		}

		if (!$name)
		{
			Helpers\OrganizerHelper::error(400);
		}

		$this->participantName = $name;
	}

	/**
	 * @inheritdoc
	 */
	protected function authorize()
	{
		if (!Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}

		if (!Helpers\Can::traceContacts())
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * Set header items.
	 *
	 * @return void
	 */
	public function setOverhead()
	{
		$then  = Helpers\Dates::formatDate(date('Y-m-d', strtotime("-28 days")));
		$today = Helpers\Dates::formatDate(date('Y-m-d'));

		$title    = Helpers\Languages::_('ORGANIZER_CONTACTS') . ': ' . $this->participantName;
		$subTitle = "$then - $today";

		$this->setHeaderData('pdf_logo.png', '55', $title, $subTitle, self::BLACK, self::WHITE);
		$this->setFooterData(self::BLACK, self::WHITE);

		parent::setHeader();
	}
}
