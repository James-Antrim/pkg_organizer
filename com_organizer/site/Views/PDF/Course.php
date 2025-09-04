<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\PDF;

use THM\Organizer\Adapters\{Application, Input, User};
use THM\Organizer\Helpers;
use THM\Organizer\Models\EditModel;
use THM\Organizer\Tables;

/**
 * Class loads persistent information about a course into the display context.
 */
class Course extends BaseView
{
    use CourseRelated;

    public array $item;
    public int $participantID;

    /** @inheritDoc */
    public function __construct()
    {
        parent::__construct();

        $this->margins();
        $this->showHeaderFooter(false);

        /** @var EditModel $model */
        $model = $this->model;

        $item            = (array) $model->getItem();
        $this->campus    = Helpers\Campuses::name($item['campusID']);
        $this->course    = $item['name']['value'];
        $this->endDate   = Helpers\Dates::formatDate($item['endDate']);
        $this->fee       = $item['fee']['value'];
        $this->startDate = Helpers\Dates::formatDate($item['startDate']);
        $this->item      = $item;
    }

    /** @inheritDoc */
    protected function authorize(): void
    {
        // TODO revamp this to make a authorize according to the layout context course => true, badge = auth
        if (!$this->courseID = Input::id()) {
            Application::error(400);
        }

        if (!$this->participantID = User::id()) {
            Application::error(401);
        }

        $courseParticipant = new Tables\CourseParticipants();
        $cpKeys            = ['courseID' => $this->courseID, 'participantID' => $this->participantID];
        if (!$courseParticipant->load($cpKeys)) {
            Application::error(403);
        }
    }

    /** @inheritDoc */
    public function display($tpl = null): void
    {
        $this->layout->title();
        $this->layout->fill($this->item);

        parent::display($tpl);
    }
}
