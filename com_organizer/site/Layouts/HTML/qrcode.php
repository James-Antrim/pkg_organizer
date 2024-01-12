<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

require_once JPATH_ROOT . '/libraries/tcpdf/tcpdf_barcodes_2d.php';

use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{HTML, Text};
use THM\Organizer\Helpers\Bookings as Helper;
use TCPDF2DBarcode as QRCode;

$bookingID  = $this->booking->id;
$logoURL    = 'components/com_organizer/images/organizer.png';
$logo       = HTML::_('image', $logoURL, Text::_('ORGANIZER'), ['class' => 'organizer_main_image']);
$checkinURL = Uri::base() . "?option=com_organizer&view=checkin&code={$this->booking->code}";
$qrCode     = new QRCode($checkinURL, 'QRCODE,L');

?>
<div id="j-main-container" class="span10 qr-code">
    <h1><?php echo $this->booking->code; ?></h1>
    <?php echo $qrCode->getBarcodeHTML(6, 6); ?>
    <?php foreach (Helper::names($bookingID) as $name): ?>
        <?php echo "<p>$name</p>"; ?>
    <?php endforeach; ?>
    <?php echo '<p>' . implode(' ', Helper::rooms($bookingID)) . '</p>'; ?>
    <?php echo '<p class="date">' . Helper::dateTimeDisplay($bookingID) . '</p>'; ?>
    <div class="foot">go.thm.de/checkin</div>
</div>