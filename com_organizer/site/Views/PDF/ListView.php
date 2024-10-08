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

use Joomla\Registry\Registry;
use THM\Organizer\Models\ListModel;

/**
 * Base class for a Joomla View
 * Class holding methods for displaying presentation data.
 */
abstract class ListView extends BaseView
{
    /**
     * TCPDF has its own 'state' property. This is the state from the submitted form.
     * @var Registry
     */
    public Registry $formState;

    /** @inheritDoc */
    public function __construct($orientation = self::PORTRAIT, $unit = 'mm', $format = 'A4')
    {
        parent::__construct($orientation, $unit, $format);
        $this->formState = $this->model->getState();
    }

    /** @inheritDoc */
    public function display($tpl = null): void
    {
        /** @var ListModel $model */
        $model = $this->model;

        $this->setOverhead();
        $this->layout->title();
        $this->layout->fill($model->getItems());

        parent::display($tpl);
    }

    /**
     * Set header items and footer colors.
     *
     * @return void
     */
    abstract public function setOverhead(): void;
}
