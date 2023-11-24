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

use THM\Organizer\Adapters\{Application, Text, Toolbar};
use THM\Organizer\Helpers;

/**
 * Class loads the resource form into display context. Specific resource determined by extending class.
 */
abstract class MergeViewOld extends OldFormView
{
    /**
     * The list view to redirect to after completion of form view functions.
     * @var string
     */
    protected string $controller = '';

    /**
     * @inheritDoc
     */
    public function __construct($config = [])
    {
        if (empty($this->controller)) {
            Application::error(501);
        }

        parent::__construct($config);
    }

    /**
     * @inheritDoc
     */
    protected function addToolBar(): void
    {
        $this->setTitle(Text::_(strtoupper($this->_name)));

        $toolbar = Toolbar::getInstance();
        $toolbar->standardButton('merge', Text::_('MERGE'))
            ->icon('fa fa-code-branch')
            ->task($this->controller . '.merge');
        $toolbar->cancel($this->controller . '.cancel');
    }
}
