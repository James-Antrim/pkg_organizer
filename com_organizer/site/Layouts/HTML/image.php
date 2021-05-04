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

$style = [
    "background-image: url('" . Uri::base(true) . '/images/organizer/' . $this->model->image . "')",
    "background-position: center center",
    "background-repeat: no-repeat",
    "background-size: contain",
    "height: 100%"
]

?>
<script type="text/javascript">
    let timer = null;

    function auto_reload() {
        window.location = document.URL;
    }

    window.onload = function () {
        document.body.style.backgroundImage = '';
        timer = setTimeout('auto_reload()', 60000);
    }
</script>
<div class='screen' style="<?php echo implode(';', $style) ?>"></div>
