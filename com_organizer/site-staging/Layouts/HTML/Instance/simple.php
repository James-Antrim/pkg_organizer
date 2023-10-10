<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use THM\Organizer\Adapters\{Input, Text};

echo $this->form->renderField('date');
echo $this->form->renderField('gridID');
echo $this->form->renderField('blockID');
echo $this->form->renderField('startTime');
echo $this->form->renderField('endTime');

if (Input::getBool('advanced')) {
    echo $this->form->renderFieldset('advanced');
} else {
    echo $this->form->renderField('eventIDs');
    echo $this->form->renderField('title');
    echo $this->form->renderField('methodID');
    echo $this->form->renderField('personID');
    echo $this->form->renderField('roleID');
    echo "// Set organizations based on the person's associations, assignments and existing assignments for the unit.<br>";
    echo '<div class="control-label"></div>';
    echo '<div class="controls">';
    echo '<span class="comment"> ' . Text::_('ORGANIZER_INSTANCES_GROUPS_DISPLAY_TEXT') . '</span>';
    echo '</div>';
    echo $this->form->renderField('groupIDs');
    echo $this->form->renderField('roomIDs');
}
