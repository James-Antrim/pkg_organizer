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
use THM\Organizer\Adapters\{Text, Toolbar};
use THM\Organizer\Helpers\Can;

/**
 * Class modifies the document for the output of a menu like list of resource management views.
 */
class Organizer extends HtmlView
{
    use Configured, Tasked, ToCed;

    protected string $layout = 'organizer';

    /**
     * Constructor
     *
     * @param   array  $config  An optional associative array of configuration settings.
     */
    public function __construct(array $config)
    {
        $this->toDo[] = 'Add booking management to the planning menu.';
        $this->toDo[] = 'Add flooring management.';
        $this->toDo[] = 'Remove flooring insert values';
        $this->toDo[] = 'Dynamically add flooring types during room import process';
        $this->toDo[] = 'Add routing.';
        $this->toDo[] = 'Check for key resource names during menu aliasing and cause a validation error.';

        $this->option     = 'com_organizer';
        $config['layout'] = $this->layout;

        parent::__construct($config);

        $this->configure();
    }

    /**
     * Creates a toolbar
     * @return void
     */
    protected function addToolBar(): void
    {
        Toolbar::setTitle('MAIN');

        if (Can::administrate()) {
            $toolbar = Toolbar::getInstance();
            $toolbar->standardButton('brush', Text::_('CLEAN_DATABASE'), 'organizer.clean')->icon('fa fa-broom');
            $toolbar->standardButton('rekey', Text::_('REKEY_TABLES'), 'organizer.reKey')->icon('fa fa-key');
            $toolbar->preferences('com_organizer');
        }
    }

    /**
     * @inheritDoc
     */
    public function display($tpl = null): void
    {
        $this->addToC();
        $this->addToolBar();

        parent::display($tpl);
    }
}
