<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Views\XML;

use Joomla\CMS\Uri\Uri;
use Organizer\Helpers\OrganizerHelper;
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
    public function display(string $response = '')
    {
        $app = OrganizerHelper::getApplication();

        // Send xml mime type.
        $app->setHeader('Content-Type', 'text/xml' . '; charset=' . $app->charSet);
        $app->sendHeaders();

        echo $response;

        $app->close();
    }

    /**
     * Replaces ampersand to avoid any potential "unterminated entity reference" errors.
     *
     * @param string $string
     *
     * @return void
     */
    protected function amp(string $string): string
    {
        return str_replace('&', '&amp;', $string);
    }
}
