<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Models;

use JDatabaseQuery;
use Organizer\Helpers;
use Organizer\Helpers\Input;

/**
 * Class retrieves information for an instance and related instances.
 */
class InstanceItem extends ListModel
{
	/**
	 * The conditions used to determine instance relevance.
	 *
	 * @var array
	 */
	public $conditions = [];

	protected $defaultLimit = 0;

	public $instance;

	/**
	 * @inheritDoc
	 */
	public function __construct($config = [])
	{
		parent::__construct($config);

		$instanceID = Input::getID();
		$instance   = Helpers\Instances::getInstance($instanceID);

		$endDate    = Helpers\Terms::getEndDate($instance['termID']);
		$tStartDate = Helpers\Terms::getStartDate($instance['termID']);
		$today      = date('Y-m-d');
		$startDate  = $tStartDate > $today ? $tStartDate : $today;

		$this->conditions = [
			'delta'           => date('Y-m-d 00:00:00', strtotime('-14 days')),
			'endDate'         => $endDate,
			'eventIDs'        => [$instance['eventID']],
			'showUnpublished' => Helpers\Can::manage('instance', $instanceID),
			'startDate'       => $startDate,
			'status'          => self::CURRENT
		];

		Helpers\Instances::fill($instance, $this->conditions);
		$this->instance = (object) $instance;
	}

	/**
	 * @inheritDoc.
	 */
	public function getItems(): array
	{
		$items = parent::getItems();

		foreach ($items as $key => $instance)
		{
			$instance = Helpers\Instances::getInstance($instance->id);
			Helpers\Instances::fill($instance, $this->conditions);
			$items[$key] = (object) $instance;
		}

		return $items;
	}

	/**
	 * @inheritDoc
	 */
	protected function getListQuery(): JDatabaseQuery
	{
		$endDate   = $this->conditions['endDate'];
		$endTime   = date('H:i:s');
		$query     = Helpers\Instances::getInstanceQuery($this->conditions);
		$startDate = $this->conditions['startDate'];

		$query->select("DISTINCT i.id")
			->where("(b.date > '$startDate' OR (b.date = '$startDate' AND b.endTime >= '$endTime'))")
			->where("b.date <= '$endDate'")
			->order('b.date, b.startTime, b.endTime');

		return $query;
	}
}