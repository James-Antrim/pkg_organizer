<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

?>
<script type="text/javascript">
    let timer = null;

    function auto_reload()
    {
        window.location = document.URL;
    }

    window.onload = function () {
        timer = setTimeout('auto_reload()', 60000);
    }
</script>
<img class="room-display-content" src="images/organizer/<?php echo $this->model->image; ?>">
