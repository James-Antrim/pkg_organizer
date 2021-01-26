<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\CMS\Uri\Uri;

?>
<div id="workload" class="workload">
	<?php


	if (!empty($showHeading))
	{
		?>
        <div class="componentheading">
			<?php echo $title; ?>
        </div>
		<?php
	}
	?>
    <form id='workload-form' name='workload-form' enctype='multipart/form-data' method='post'
          action='<?php echo Uri::current(); ?>'>
        <div class="filter-bar">
            <div class="filter-header">
                <div class="selection-settings">
                    <div class="control-group">
                        <div class="control-label">
                            <label for="schedules">
								<?php echo Languages::_('ORGANIZER_DATA_SOURCE') ?>
                            </label>
                        </div>
                        <div class="controls">
							<?php echo $this->scheduleSelectBox; ?>
                        </div>
                    </div>
					<?php
					if (!empty($this->model->schedule))
					{
						?>
                        <div class="control-group">
                            <div class="control-label">
                                <label for="persons">
									<?php echo Languages::_('ORGANIZER_TEACHERS') ?>
                                </label>
                            </div>
                            <div class="controls">
								<?php echo $this->persons; ?>
                            </div>
                        </div>
                        <div class="button-group">
                            <button type="submit">
								<?php echo Languages::_('ORGANIZER_SHOW') ?>
                                <span class="icon-play"></span>
                            </button>
                        </div>
						<?php
					}
					?>
                </div>
            </div>
        </div>
		<?php
		if (!empty($this->model->schedule))
		{
			echo $this->tables;
		}
		?>
    </form>
    <a href="https://www.thm.de/dev/organizer/service/werkzeug/workload-fb-bau.html?format=xls">form</a>
    <a id="dLink" style="display:none;"></a>
</div>
