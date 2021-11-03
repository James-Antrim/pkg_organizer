<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

$action = OrganizerHelper::getRedirectBase();
$menuID = OrganizerHelper::getInput()->getInt('Itemid');

$organizationID = $this->state->get('organizationID');
$periodID       = $this->state->get('periodID');
$categoryID     = $this->state->get('categoryID');
$showTable      = (!empty($this->columns) and !empty($this->rows));

?>
<div class="lesson-statistics-view">
    <h1 class="componentheading"><?php echo Languages::_('ORGANIZER_EVENT_STATISTICS'); ?></h1>
    <form enctype="multipart/form-data" method="post"
          id="form-lesson-statistics" class="form-horizontal">
        <input type="hidden" name="option" value="com_organizer">
        <input type="hidden" name="view" value="lesson_statistics">
        <input type='hidden' name='Itemid' value='<?php echo $menuID; ?>'>
		<?php echo $this->form->getField('termID')->input; ?>
		<?php echo $this->form->getField('organizationID')->input; ?>
		<?php echo $this->form->getField('programID')->input; ?>
    </form>
    <div class="table-container">
        <table>
            <tr>
				<?php if ($showTable) : ?>
                    <td>
                        <span class="name"><?php echo Languages::_('ORGANIZER_TOTAL'); ?></span>
                        <br>
						<?php echo $this->total; ?>
                    </td>
					<?php foreach ($this->columns as $column) : ?>
                        <td>
                            <span class="name"><?php echo $column['name']; ?></span>
                            <br>
                            <span class="total"><?php echo '(' . $column['total'] . ')'; ?></span>
                        </td>
					<?php endforeach; ?>
				<?php else : ?>
                    <td>
                        <span class="name"><?php echo Languages::_('ORGANIZER_NO_EVENTS_FOUND'); ?></span>
                    </td>
				<?php endif; ?>
            </tr>
			<?php foreach ($this->rows as $row) : ?>
                <tr>
                    <td>
                        <span class="name"><?php echo $row['name']; ?></span>
                        <br>
                        <span class="total"><?php echo '(' . $row['total'] . ')'; ?></span>
                    </td>
					<?php
					foreach (array_keys($this->columns) as $columnID)
					{
						$invalid = (empty($this->lessons[$row['id']]) or empty($this->lessons[$row['id']][$columnID]));
						$value   = $invalid ? 0 : $this->lessons[$row['id']][$columnID];
						echo "<td>$value</td>";
					}
					?>
                </tr>
			<?php endforeach; ?>
        </table>
    </div>
</div>
