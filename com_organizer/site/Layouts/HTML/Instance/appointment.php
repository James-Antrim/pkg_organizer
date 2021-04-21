<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

echo $this->form->renderField('id');
echo $this->form->renderField('type');
echo $this->form->renderField('date');
echo $this->form->renderField('startTime');
echo $this->form->renderField('endTime');
echo $this->form->renderField('title');
echo $this->form->renderField('roomIDs');
