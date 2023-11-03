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
use THM\Organizer\Adapters\{HTML, Input, Text, Toolbar};

$query    = Uri::getInstance()->getQuery();
$script   = "document.getElementById('download-url').select();";
$script   .= "document.getElementById('download-url').setSelectionRange(0,99999);";
$script   .= "document.execCommand('copy');";
$interval = Input::getString('interval', 'week');

switch ($interval) {
    case 'month':
        $interval = Text::_('ORGANIZER_SELECTED_MONTH');
        break;
    case 'quarter':
        $interval = Text::_('ORGANIZER_QUARTER');
        break;
    case 'term':
        $interval = Text::_('ORGANIZER_SELECTED_TERM');
        break;
    case 'week':
    default:
        $interval = Text::_('ORGANIZER_SELECTED_WEEK');
        break;
}

echo $this->title;
?>
    <form action="<?php echo Uri::current(); ?>" id="adminForm" method="post" name="adminForm"
          class="form-horizontal form-validate" enctype="multipart/form-data">
        <div class="export-container">
            <?php
            foreach ($this->form->getFieldSets() as $set) {
                $label  = $set->label ?: false;
                $fields = $this->form->getFieldset($set->name);

                if ($label) {
                    $constant = 'ORGANIZER_' . strtoupper($label);
                    $label    = Text::_($constant);
                    echo "<fieldset class=\"organizer-$set->name\">";
                    echo "<legend>$label</legend>";
                }

                foreach ($fields as $field) {
                    if ($field->getAttribute('name') === 'format') {
                        $options = $field->__get('options');
                        foreach ($options as $option) {
                            $option->text = sprintf($option->text, $interval);
                        }
                        $field->__set('options', $options);
                    }

                    echo $field->renderField();
                }

                if ($label) {
                    echo '</fieldset>';
                }
            }
            ?>
        </div>
        <input type="hidden" name="Itemid" value="<?php echo Input::getInt('Itemid'); ?>"/>
        <input type="hidden" name="option" value="com_organizer"/>
        <input type="hidden" name="task" value=""/>
        <input type="hidden" name="view" value="<?php echo $this->get('name'); ?>"/>
        <?php echo HTML::token(); ?>
    </form>
<?php echo Toolbar::getInstance()->render(); ?>
<?php if ($this->url): ?>
    <div class="tbox-green">
        <h6><?php echo Text::_('ORGANIZER_DOWNLOAD_URL_DESC'); ?></h6>
        <input type="text" id="download-url" class="download-url" value="<?php echo $this->url; ?>"/>
        <button class="btn copy-button" onclick="<?php echo $script; ?>">
            <span class="icon-copy"></span><?php echo Text::_('ORGANIZER_COPY'); ?>
        </button>
    </div>
<?php endif; ?>