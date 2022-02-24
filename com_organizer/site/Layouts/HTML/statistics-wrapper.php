<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\CMS\Uri\Uri;
use Organizer\Adapters\Toolbar;
use Organizer\Helpers;
use Organizer\Views\HTML\Statistics as View;

$conditions = $this->form->getFieldset('conditions');
$query      = Uri::getInstance()->getQuery();
$statistic  = $this->state->get('conditions.statistic');

require_once 'titles.php';

?>
<?php echo Toolbar::getInstance()->render(); ?>
<div id="j-main-container" class="span10">
    <form action="<?php echo Uri::base() . "?$query"; ?>" id="adminForm" method="post" name="adminForm"
          enctype="multipart/form-data">
        <div class="js-stools clearfix">
            <div class="js-stools-container-filters hidden-phone clearfix shown">
				<?php foreach ($conditions as $fieldName => $field) : ?>
                    <div class="js-stools-field-filter">
						<?php echo $field->input; ?>
                    </div>
				<?php endforeach; ?>
            </div>
        </div>
        <input type="hidden" name="Itemid" value="<?php echo Helpers\Input::getInt('Itemid'); ?>"/>
        <input type="hidden" name="option" value="com_organizer"/>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="view" value="Statistics"/>
		<?php echo Helpers\HTML::_('form.token'); ?>
    </form>
	<?php if (count($this->grid)): ?>
		<?php $count = count(array_keys($this->grid['headers'])); ?>
		<?php switch ($statistic)
		{
			case View::METHOD_USE:
				require_once 'Statistics/methoduse.php';
				break;
			case View::REGISTRATIONS:
				require_once 'Statistics/registrations.php';
				break;
			case View::PLANNED_PRESENCE_TYPE:
				require_once 'Statistics/presencetype.php';
				break;
			case View::PRESENCE_USE:
				require_once 'Statistics/presenceuse.php';
				break;
			default:
				break;
		}
		?>
	<?php elseif ($statistic): ?>
		<?php echo '<div>' . Helpers\Languages::_('ORGANIZER_EMPTY_RESULT_SET') . '</div>'; ?>
	<?php endif; ?>
</div>

