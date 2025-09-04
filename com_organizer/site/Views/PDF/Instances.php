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
use THM\Organizer\Models\Instances as Model;

/**
 * Class loads persistent information about a course into the display context.
 */
class Instances extends ListView
{
    public array $conditions;

    /**
     * @var string
     */
    public $title;

    /** @inheritDoc */
    public function __construct($orientation = self::PORTRAIT, $unit = 'mm', $format = 'A4')
    {
        parent::__construct($orientation, $unit, $format);

        /** @var Model $model */
        $model = $this->model;

        $this->conditions  = $model->conditions;
        $this->destination = self::INLINE;
        $this->title       = $model->title();
    }

    /** @inheritDoc */
    protected function authorize(): void
    {
        // State has not been established => redundant checks :(
        $filters  = Input::filters();
        $params   = Input::parameters();
        $my       = Input::integer('my', $params->get('my', 0));
        $personID = $filters->get('personID');

        if ($my or $personID) {
            if (!User::id()) {
                Application::error(401);
            }

            if ($personID and !in_array($personID, array_keys(Helpers\Persons::resources()))) {
                Application::error(403);
            }
        }
    }

    /** @inheritDoc */
    public function setOverhead(): void
    {
        // Header data is set per page.
        $this->setFooterData(self::BLACK, self::WHITE);

        parent::setHeader();
    }
}
