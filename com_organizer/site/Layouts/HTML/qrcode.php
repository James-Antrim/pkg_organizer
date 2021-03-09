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
use Organizer\Helpers\HTML as HTML;
use Organizer\Helpers\Languages as Languages;
use Organizer\Helpers\Bookings as Helper;

$bookingID  = $this->booking->id;
$logoURL    = 'components/com_organizer/images/organizer.png';
$logo       = HTML::_('image', $logoURL, Languages::_('ORGANIZER'), ['class' => 'organizer_main_image']);
$checkinURL = Uri::base() . "?option=com_organizer&view=checkin&code={$this->booking->code}";
$checkinURL = urlencode(Uri::base() . "?option=com_organizer&view=checkin&code={$this->booking->code}");
$size       = '300x300';
$URL        = "https://chart.googleapis.com/chart?chs=$size&cht=qr&chl=$checkinURL";

?>
<div id="j-main-container" class="span10 qr-code">
    <h1><?php echo $this->booking->code; ?></h1>
    <img class="qrcode" src="<?php echo $URL; ?>" alt="QR code">
	<?php foreach (Helper::getNames($bookingID) as $name): ?>
		<?php echo "<p>$name</p>"; ?>
	<?php endforeach; ?>
	<?php echo '<p>' . implode(' ', Helper::getRooms($bookingID)) . '</p>'; ?>
	<?php echo '<p class="date">' . Helper::getDateTimeDisplay($bookingID) . '</p>'; ?>
    <div class="foot">go.thm.de/checkin</div>
</div>