<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\JSON;

use Joomla\CMS\Uri\Uri;
use Organizer\Views\Named;

/**
 * Base class for a Joomla View
 * Class holding methods for displaying presentation data.
 */
abstract class BaseView
{
    use Named;

    /**
     * The base path of the site itself
     * @var string
     */
    private $baseURL;

    /**
     * Constructor
     */
    public function __construct()
    {
        $this->getName();
        $this->baseURL = Uri::base(true);
    }

    /**
     * Display the view output.
     */
    abstract public function display();
}
