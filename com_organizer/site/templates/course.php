<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2023 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

defined('_JEXEC') or die;

use Joomla\CMS\Router\Route;
use THM\Organizer\Adapters\{HTML, Input, Text};
use THM\Organizer\Views\HTML\Course;

$formName  = strtoupper($this->getName());
$ariaLabel = Text::_("ORGANIZER_{$formName}_FORM");

$input          = Input::getInput();
$forcedLanguage = $input->get('forcedLanguage', '');
$return         = $input->getBase64('return');

/** @var Course $this */
$this->renderTasks();

$rawData = [
    'campusID',
    'campusName',
    'deadline',
    'endDate',
    'events',
    'id',
    'maxParticipants',
    'name',
    'participants',
    'preparatory',
    'startDate',
    'termID'
];

$courseID = Input::getID();
require_once 'header.php';
?>
<form action="<?php echo Route::_('index.php?option=com_organizer'); ?>"
      aria-label="<?php echo $ariaLabel; ?>"
      class="form-validate"
      enctype="multipart/form-data"
      id="adminForm"
      method="post"
      name="adminForm">
    <input type="hidden" name="id" value="<?php echo $courseID; ?>">
    <input type="hidden" name="task" value="">
    <input type="hidden" name="return" value="<?php echo $return; ?>">
    <input type="hidden" name="forcedLanguage" value="<?php echo $forcedLanguage; ?>">
    <?php echo HTML::token(); ?>
</form>
<div class="item course">
    <?php foreach ($this->item as $label => $value) : ?>
        <?php // Suppress HTML output of raw data. ?>
        <?php if (in_array($label, $rawData)) : continue; ?>
        <?php else: $this->renderAttribute($label, $value); endif; ?>
    <?php endforeach; ?>
</div>