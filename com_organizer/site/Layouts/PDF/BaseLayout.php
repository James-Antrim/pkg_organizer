<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Layouts\PDF;

use THM\Organizer\Views\PDF\BaseView;

/**
 * Base class for a Joomla View
 * Class holding methods for displaying presentation data.
 */
abstract class BaseLayout
{
    protected $filename;

    /**
     * @var BaseView
     */
    protected $view;

    /**
     * Performs initial construction of the TCPDF Object.
     *
     * @param   BaseView  $view
     */
    public function __construct(BaseView $view)
    {
        $this->view = $view;
    }

    /**
     * Fills the document with formatted data.
     *
     * @param   array  $data  the document data
     *
     * @return void
     */
    abstract public function fill(array $data);

    /**
     * Generates the title and sets name related properties.
     */
    abstract public function setTitle();
}
