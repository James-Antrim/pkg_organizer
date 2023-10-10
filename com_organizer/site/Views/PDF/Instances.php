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

use THM\Organizer\Adapters\Input;
use THM\Organizer\Helpers;
use THM\Organizer\Models\Instances as Model;

/**
 * Class loads persistent information about a course into the display context.
 */
class Instances extends ListView
{
    /**
     * @var array
     */
    public $conditions;

    /**
     * @var Model
     */
    protected $model;

    /**
     * @var string
     */
    public $title;

    /**
     * @inheritDoc
     */
    public function __construct($orientation = self::PORTRAIT, $unit = 'mm', $format = 'A4')
    {
        parent::__construct($orientation, $unit, $format);
        $this->title      = $this->model->getTitle();
        $this->conditions = $this->model->conditions;
    }

    /**
     * @inheritdoc
     */
    protected function authorize()
    {
        // State has not been established => redundant checks :(
        $filters  = Input::getFilterItems();
        $params   = Input::getParams();
        $my       = Input::getInt('my', $params->get('my', 0));
        $personID = $filters->get('personID');

        if ($my or $personID) {
            if (!Helpers\Users::getID()) {
                Helpers\OrganizerHelper::error(401);
            }

            if ($personID and !in_array($personID, array_keys(Helpers\Persons::getResources()))) {
                Helpers\OrganizerHelper::error(403);
            }
        }
    }

    /**
     * @inheritDoc
     */
    public function display($destination = self::INLINE)
    {
        parent::display($destination);
    }

    /**
     * Set header items.
     * @return void
     */
    public function setOverhead()
    {
        // Header data is set per page.
        $this->setFooterData(self::BLACK, self::WHITE);

        parent::setHeader();
    }
}
