<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.2
 * @link        www.thm.de
 */

namespace THM\Organizer\Views\HTML;

use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters;
use THM\Organizer\Adapters\Document;

trait Subordinate
{
    /**
     * @inheritDoc
     */
    protected function modifyDocument(): void
    {
        /** @noinspection PhpMultipleClassDeclarationsInspection */
        parent::modifyDocument();

        Document::script('curricula');
    }
}