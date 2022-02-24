<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

if ($this->refresh)
{
	?>
    <script type="text/javascript">
        let timer = null;

        function auto_reload() {
            window.location = document.URL;
        }

        window.onload = function () {
            timer = setTimeout('auto_reload()', <?php echo $this->refresh; ?>000);
        }
    </script>
	<?php
}


