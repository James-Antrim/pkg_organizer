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

use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Document, Text, Toolbar};

/**
 * Class loads pool information into the display context.
 */
class PoolSelection extends PoolsView
{
    protected string $layout = 'list_modal';

    protected array $rowStructure = ['checkbox' => '', 'name' => 'value', 'programID' => 'value'];

    /**
     * @inheritdoc
     */
    protected function addToolBar(bool $delete = true): void
    {
        $toolbar = Toolbar::getInstance();
        $toolbar->appendButton('Standard', 'new', Text::_('ORGANIZER_ADD'), 'x', true);
    }

    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        parent::modifyDocument();

        Document::addStyleSheet(Uri::root() . 'components/com_organizer/css/modal.css');
    }
}
