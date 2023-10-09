<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use Organizer\Adapters;

trait Subordinate
{
    /**
     * @inheritDoc
     */
    protected function modifyDocument()
    {
        /** @noinspection PhpMultipleClassDeclarationsInspection */
        parent::modifyDocument();

        Adapters\Document::addScript(Uri::root() . 'components/com_organizer/js/curricula.js');
    }
}