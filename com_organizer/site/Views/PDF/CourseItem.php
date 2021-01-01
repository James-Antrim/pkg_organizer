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
use Organizer\Tables;

/**
 * Class loads persistent information about a course into the display context.
 */
class CourseItem extends BaseView
{
	/**
	 * The campus where the course takes place
	 * @var string
	 */
	public $campus;

	/**
	 * The name of the course
	 * @var string
	 */
	public $course;

	/**
	 * The id of the associated course.
	 * @var int
	 */
	public $courseID;

	/**
	 * The dates as displayed in the generated document.
	 * @var string
	 */
	public $dates;

	/**
	 * The course end date
	 * @var string
	 */
	public $endDate;

	/**
	 * The fee required for participation in the course
	 * @var int
	 */
	public $fee;

	public $item;

	/**
	 * The course start date
	 * @var string
	 */
	public $startDate;

	public $participantID;

	/**
	 * Constructor
	 */
	public function __construct()
	{
		parent::__construct();

		$this->margins();
		$this->showPrintOverhead(false);

		$item            = $this->get('item');
		$this->campus    = Helpers\Campuses::getName($item['campusID']);
		$this->course    = $item['name']['value'];
		$this->endDate   = Helpers\Dates::formatDate($item['endDate']);
		$this->fee       = $item['fee']['value'];
		$this->startDate = Helpers\Dates::formatDate($item['startDate']);
		$this->item      = $item;
	}

	/**
	 * @inheritDoc
	 */
	protected function authorize()
	{
		// TODO revamp this to make a authorize according to the layout context course => true, badge = auth
		if (!$this->courseID = Helpers\Input::getID())
		{
			Helpers\OrganizerHelper::error(400);
		}

		if (!$this->participantID = Helpers\Users::getID())
		{
			Helpers\OrganizerHelper::error(401);
		}

		$courseParticipant = new Tables\CourseParticipants();
		$cpKeys            = ['courseID' => $this->courseID, 'participantID' => $this->participantID];
		if (!$courseParticipant->load($cpKeys))
		{
			Helpers\OrganizerHelper::error(403);
		}
	}

	/**
	 * @inheritdoc
	 */
	public function display()
	{
		$this->layout->setTitle();
		$this->layout->fill($this->item);

		parent::display();
	}
}
