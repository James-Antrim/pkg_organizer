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
use Organizer\Helpers;

$logoURL    = 'components/com_organizer/images/organizer.png';
$logo       = Helpers\HTML::_('image', $logoURL, Helpers\Languages::_('ORGANIZER'), ['class' => 'organizer_main_image']);
$query      = Uri::getInstance()->getQuery();
$checkinURL = Uri::base() . "?option=com_organizer&view=checkin&code={$this->booking->code}";
echo "<pre>" . print_r($checkinURL, true) . "</pre>";
$checkinURL = urlencode(Uri::base() . "?option=com_organizer&view=checkin&code={$this->booking->code}");
$size       = '300x300';
//&amp;choe=UTF-8
$URL = "https://chart.googleapis.com/chart?chs=$size&cht=qr&chl=$checkinURL";
?>
<div id="j-main-container" class="span10 qr-code">
    <h1><?php echo $this->booking->code; ?></h1>
    <img class="qrcode" src="<?php echo $URL; ?>" alt="QR code">
</div>