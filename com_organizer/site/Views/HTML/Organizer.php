<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use Joomla\CMS\MVC\View\HtmlView;
use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Document, Text, Toolbar};
use THM\Organizer\Helpers\Can;

/**
 * Class modifies the document for the output of a menu like list of resource management views.
 */
class Organizer extends HtmlView
{
    use Configured, ToCed;

    protected string $layout = 'organizer';

    /**
     * Constructor
     *
     * @param   array  $config  An optional associative array of configuration settings.
     */
    public function __construct(array $config)
    {
        $this->option = 'com_organizer';

        // If this is not explicitly set going in Joomla will default to default without looking at the object property value.
        $config['layout'] = $this->layout;

        parent::__construct($config);

        $this->configure();
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        $this->addToC();
        $this->addToolBar();
        //Document::style('organizer');

        parent::display($tpl);
    }

    /**
     * Creates a toolbar
     * @return void
     */
    protected function addToolBar(): void
    {
        Toolbar::setTitle('MAIN');

        if (Can::administrate()) {
            $uri    = (string) Uri::getInstance();
            $return = urlencode(base64_encode($uri));
            $link   = "index.php?option=com_config&view=component&component=com_organizer&return=$return";

            $toolbar = Toolbar::getInstance();
            $toolbar->standardButton('trash', 'Clean Bookings', 'Organizer.cleanBookings');
            $toolbar->standardButton('bars', 'Update Participation Numbers', 'Organizer.updateNumbers');
            $toolbar->standardButton('brush', 'Clean DB Entries', 'Organizer.cleanDB');
            $toolbar->standardButton('key', 'Re-Key Tables', 'Organizer.reKeyTables');
            $toolbar->linkButton('options', Text::_('SETTINGS'))->url($link);
        }
    }
}
