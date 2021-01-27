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

$directory       = Uri::base(true) . '/components/com_organizer/Layouts/HTML/Help/bookings-';
$imageAttribs    = [
	'height' => "height=\"500px\"",
	'style'  => "style=\"display: block; margin-left: auto; margin-right: auto;\"",
	'width'  => "width=\"700px\""
];
$jcePopUpAttribs = [
	'class'    => 'class="jcepopup"',
	'group'    => 'data-mediabox-group="Bookings"',
	'mediaBox' => 'data-mediabox="1"'
];

$bookingImageAttribs = [
	'alt' => "alt=\"Screenshot: Booking\"",
	'src' => "src=\"{$directory}booking.png\""
];
$bookingImageAttribs = array_merge($imageAttribs, $bookingImageAttribs);
$bookingPopUpAttribs = [
	'href' => "href=\"{$directory}booking.png\""
];
$bookingPopUpAttribs = array_merge($jcePopUpAttribs, $bookingPopUpAttribs);

$myApptsCaption = '(1) Klicken Sie hier um eine Buchung zu öffnen (2) Online Veranstaltungen brauchen keine Besucherregistrierung';

$myApptsImageAttribs = [
	'alt' => "alt=\"Screenshot: My Appointments\"",
	'src' => "src=\"{$directory}my-appointments.png\""
];
$myApptsImageAttribs = array_merge($imageAttribs, $myApptsImageAttribs);
$myApptsPopUpAttribs = [
	'caption' => "data-mediabox-caption=\"$myApptsCaption\"",
	'href'    => "href=\"{$directory}my-appointments.png\""
];
$myApptsPopUpAttribs = array_merge($jcePopUpAttribs, $myApptsPopUpAttribs);

$ongoingImageAttribs = [
	'alt' => "alt=\"Screenshot: Ongoing Booking\"",
	'src' => "src=\"{$directory}ongoing.png\""
];
$ongoingImageAttribs = array_merge($imageAttribs, $ongoingImageAttribs);
$ongoingPopUpAttribs = [
	'href' => "href=\"{$directory}ongoing.png\""
];
$ongoingPopUpAttribs = array_merge($jcePopUpAttribs, $ongoingPopUpAttribs);

$qrcCaption = 'Der QR-Code führt direkt auf die Checkin-Seite und fügt automatisch den Veranstaltungscode ein.';

$qrcImageAttribs = [
	'alt'   => "alt=\"Example: Booking QR Code\"",
	'src'   => "src=\"{$directory}qrcode.png\"",
	'style' => "style=\"margin-left: 2rem; margin-bottom: 2rem; margin-right: 1rem; float: right;\"",
	'width' => "width=\"200px\""
];
$qrcImageAttribs = array_merge($imageAttribs, $qrcImageAttribs);
$qrcPopUpAttribs = [
	'caption' => "data-mediabox-caption=\"$qrcCaption\"",
	'href'    => "href=\"{$directory}qrcode.png\""
];
$qrcPopUpAttribs = array_merge($jcePopUpAttribs, $qrcPopUpAttribs);

$registerCaption = 'Um einen neuen Account anzulegen, klicken Sie bitte auf den Registrierungslink ganz unten.';

$registerImageAttribs = [
	'alt'   => "alt=\"Screenshot: Registration Link on the Checkin Page\"",
	'src'   => "src=\"{$directory}register.png\"",
	'style' => "style=\"margin-left: 2rem; margin-bottom: 2rem; margin-right: 1rem; float: right;\"",
	'width' => "width=\"200px\""
];
$registerImageAttribs = array_merge($imageAttribs, $registerImageAttribs);
$registerPopUpAttribs = [
	'caption' => "data-mediabox-caption=\"$registerCaption\"",
	'href'    => "href=\"{$directory}register.png\""
];
$registerPopUpAttribs = array_merge($jcePopUpAttribs, $registerPopUpAttribs);

?>
<div style="float:right;">
	<?php //require_once 'toc.php'; ?>
</div>
<br>
<h3>Veranstaltung Eröffnen</h3>
<hr>
<p>
    Zur Registrierung der Studierenden in Ihrer Präsenzveranstaltung loggen Sie sich bitte in den Organizer in den
    Bereich
    <strong>
        <a href="index.php?option=com_organizer&amp;view=instances&amp;Itemid=4901">Service -&gt; Meine Termine</a>
    </strong>
    mit Ihrer THM Nutzerkennung ein. Nach erfolgreichem Login werden Ihnen Ihre nächsten Veranstaltungen angezeigt.
</p>
<p>
    Veranstaltungen, die in den nächsten 2 Tagen und nicht online (2) stattfinden, haben in der ersten Spalte einen
    <strong>[Buchung eröffnen]</strong> (1) Button. So können Sie bereits bei der Vorbereitung Ihrer Veranstaltung
    die Buchung eröffnen und die Planungsdaten abrufen.
</p>
<p>
    <a <?php echo implode(' ', $myApptsPopUpAttribs); ?>>
        <img <?php echo implode(' ', $myApptsImageAttribs); ?>/>
    </a>
</p>
<br>
<h3>Übersicht Ihrer Veranstaltung</h3>
<hr>
<p>Nachdem Sie auf <strong>[Buchung eröffnen]</strong> geklickt haben, wird Ihnen die Buchungsansicht angezeigt:
</p>
<p>
    <a <?php echo implode(' ', $bookingPopUpAttribs); ?>>
        <img <?php echo implode(' ', $bookingImageAttribs); ?>/>
    </a>
</p>
<br>
<dl>
    <dt>Veranstaltungscode (1)</dt>
    <dd>
        Der Veranstaltungscode hat das Format <strong>XXXX-XXXX</strong>. Geben Sie diesen bitte an der Tafel, über den
        Beamer oder per Ausdruck an die Studierenden weiter. Sie benötigen diesen, um sich für diese Veranstaltung zu
        registrieren.
    </dd>
    <dt>[Meine Termine] (2)</dt>
    <dd>Dieser Button führt wieder zurück zu Ihrer Terminübersicht.</dd>
    <dt>
        [QR-Code] (3)
    </dt>
    <dd>
        <a <?php echo implode(' ', $qrcPopUpAttribs); ?>>
            <img <?php echo implode(' ', $qrcImageAttribs); ?>/>
        </a>
        Durch Drücken dieses Buttons wird ein neues Fenster geöffnet, in dem der Veranstaltungscode mit einem passenden
        QR-Code zum Ausdrucken oder Beamen generiert wird. Das Einscannen dieses QR-Codes führt direkt auf die
        Checkin-Seite
        und fügt automatisch den Veranstaltungscode ein.
    </dd>
    <dt>[Notizen] (4)</dt>
    <dd>Hier können Sie Notizen zur Veranstaltung hinzufügen.</dd>
    <dt>[Teilnehmer entfernen] (5)</dt>
    <dd>Markierte Teilnehmer aus der Liste werden entfernt.</dd>
    <dt>[Teilnehmer hinzufügen] (6)</dt>
    <dd>
        Durch Eingabe einer THM-Nutzerkennung lassen sich Teilnehmer (die sich z.B. nicht selbst anmelden können) zur
        Veranstaltung hinzufügen.
    </dd>
</dl>
<br>
<h3>Durchführung der Veranstaltung</h3>
<hr>
<p>
    Geben Sie vor oder zu Anfang Ihrer Veranstaltung den Veranstaltungscode an die Studierenenden weiter (Tafel, QR-Code
    Ausdruck, Beamer). Die Studierenden können sich dann über die Adresse:
    <strong><a href="https://go.thm.de/checkin">https://go.thm.de/checkin</a></strong>
    durch Login mit ihrer THM-Nutzerkennung und Angabe des Veranstaltungscodes (oder durch das Einscannen des QR-Codes)
    für
    die Veranstaltung einbuchen.
</p>
<p>Wenn Sie sich nun über&nbsp;<strong><a href="index.php?option=com_organizer&amp;view=instances&amp;Itemid=4901">Service
            -&gt; Meine Termine</a></strong>&nbsp;in Ihre Terminübersicht einwählen und auf den Buchungsbutton der
    aktuellen Veranstaltung klicken, sehen Sie die Buchungsübersicht mit den aktuellen Anmeldungen:</p>
<p>
    <a <?php echo implode(' ', $ongoingPopUpAttribs); ?>>
        <img <?php echo implode(' ', $ongoingImageAttribs); ?>/>
    </a>
</p>
<dl>
    <dt>Statusbalken (1)</dt>
    <dd>Im Statusbalken sehen Sie den aktuellen Status der Veranstaltung ( noch nicht begonnen | laufend | beendet),
        sowie die Anzahl der eingecheckten Studierenden.
    <dt>[ausserplanmässiges Start/Stopp] (2)</dt>
    <dd>
        <p>
            Grundsätzlich wird die Buchung zur im Organizer geplanten Uhrzeit automatisch gestartet und beendet. Je nach
            Zeitpunkt zeigt dieser Button die folgenden Beschriftungen an:
        </p>
        <ul>
            <li><strong>[Vorzeitig starten]</strong> - ab 60 min vor dem normalen Terminstart</li>
            <li><strong>[Vorzeitig beenden]</strong> - während der normalen Terminzeit</li>
            <li><strong>[Neue Endzeit setzen]</strong> - am gleichen Tag nach dem normalen Terminende</li>
        </ul>
        <p>
            Damit haben Sie die Möglichkeit eine Veranstaltung eher zu starten, oder auch eher oder später zu beenden.
            Im Normalfall läuft aber der komplette Buchungsvorgang automatisch ab und nur bei größeren Zeitabweichungen
            sollten die Sonderbuchungen aktiviert werden.
        </p>
    </dd>
    <dt>[Teilnehmer hinzufügen] (3)</dt>
    <dd>
        Sie können jederzeit Studierende (z.B. wenn sie kein Handy/Laptop mit sich führen) per Hand einbuchen. Fragen
        Sie dazu einfach nach der THM-Nutzerkennung, geben Sie diese in das Eingabefeld ein und bestätigen Sie mit der
        Eingabe-Taste. Bei gültiger Nutzerkennung erscheint die Person als Eintrag in der Liste.
    </dd>
    <dt>Profil vollständig (4)</dt>
    <dd>
        In dieser Spalte sehen Sie welche Studierende auch ihr Profil komplett ausgefüllt haben, welches die Privatdaten
        enthält die für eine eventuelle Kontaktnachverfolgung benötigt werden.
    </dd>
</dl>
<br>
<h3>Registrierung externer Personen zu einer Veranstaltung (z.B. Gastredner)</h3>
<hr>
<p>
    <a <?php echo implode(' ', $registerPopUpAttribs); ?>>
        <img <?php echo implode(' ', $registerImageAttribs); ?>/>
    </a>
    Personen die an Ihren Veranstaltungen teilnehmen und <strong>keine</strong> THM Nutzerkennung haben, gehen bitte auf
    die
    Checkin-Seite: <strong><a href="https://go.thm.de/checkin">https://go.thm.de/checkin</a></strong>
</p>
<p>
    Dort finden sie unterhalb der Eingabefelder (1) einen Link um sich zu registrieren. Nach Eingabe der notwendigen
    Informationen und Auswahl eines Passwortes können sie sich normal mit dem gültigen Veranstaltungscode an der
    Veranstaltung anmelden.
</p>