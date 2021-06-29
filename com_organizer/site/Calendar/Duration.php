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
 * This class specifies a positive duration of time.
 *
 * @url https://datatracker.ietf.org/doc/html/rfc5545#section-3.8.2.5
 */
class Duration extends VComponent
{
	public const DAY = 86400, HOUR = 3600, MINUTE = 60, WEEK = 604800;

	/**
	 * @var int
	 */
	private $days;

	/**
	 * @var int
	 */
	private $hours;

	/**
	 * @var int
	 */
	private $minutes;

	/**
	 * The raw number of seconds given to the constructor.
	 * @var int
	 */
	private $raw;

	/**
	 * @var int
	 */
	private $seconds;

	/**
	 * @var int
	 */
	private $weeks;

	/**
	 * Duration constructor.
	 *
	 * @param   int  $seconds
	 */
	public function __construct(int $seconds)
	{
		$this->raw = $seconds;

		if ($this->weeks = (int) floor($seconds / self::WEEK))
		{
			$seconds = $seconds % self::WEEK;
		}

		if ($this->days = (int) floor($seconds / self::DAY))
		{
			$seconds = $seconds % self::DAY;
		}

		if ($this->hours = (int) floor($seconds / self::HOUR))
		{
			$seconds = $seconds % self::HOUR;
		}

		if ($this->minutes = (int) floor($seconds / self::MINUTE))
		{
			$seconds = $seconds % self::HOUR;
		}

		$this->seconds = $seconds;
	}

	/**
	 * @inheritDoc
	 */
	public function getProps(array &$output)
	{
		if (empty($this->raw))
		{
			return;
		}

		$duration = 'P';

		if ($this->weeks)
		{
			$duration .= $this->weeks . 'W';
		}

		if ($this->days)
		{
			$duration .= $this->days . 'D';
		}

		if ($this->hours or $this->minutes or $this->seconds)
		{
			$duration .= 'T';

			if ($this->hours)
			{
				$duration .= $this->hours . 'H';
			}

			if ($this->minutes)
			{
				$duration .= $this->minutes . 'M';
			}

			if ($this->seconds)
			{
				$duration .= $this->seconds . 'S';
			}
		}

		$output[] = "DURATION:$duration";
	}
}