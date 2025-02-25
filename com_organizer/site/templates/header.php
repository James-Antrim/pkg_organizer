<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2025 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use THM\Organizer\Adapters\{Application, Toolbar};
use THM\Organizer\Views\HTML\Titled;

/** @var Titled $this */
if (!Application::backend()) {
    echo "<h1>$this->title</h1>";
    echo $this->subtitle ? "<h4>$this->subtitle</h4>" : '';
    echo $this->supplement;
    echo Toolbar::render();
}