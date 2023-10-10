<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2021 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */


use Joomla\CMS\Factory;
use THM\Organizer\Adapters\Text;

$referrer = Factory::getSession()->get('organizer.checkin.referrer');
?>
<form id="adminForm" method="post" name="adminForm"
      class="form-vertical form-validate checkin" enctype="multipart/form-data" xmlns="http://www.w3.org/1999/html">
    <h3>1. Einleitung</h3>
    <p>
        Diese Datenschutzerklärung informiert Sie über die Art, den Umfang und Zweck der Verarbeitung von
        personenbezogenen
        Daten bei der Erfassung von Anwesenheitszeiten THM nach § 5a der Verordnung zur Beschränkung von sozialen
        Kontakten
        und des Betriebes von Einrichtungen und von Angeboten aufgrund der Corona-Pandemie (Corona-Kontakt- und
        Betriebsbeschränkungsverordnung).
    </p>
    <p>
        <b>
            Beachten Sie bitte, dass nach § 1 Abs. 2b Nr. 2 der Corona-Kontakt- und Betriebsbeschränkungsverordnung die
            Bestimmungen der Art. 13, 15, 18 und 20 der Datenschutz-Grundverordnung (DS-GVO) zur Informationspflicht und
            zum
            Recht auf Auskunft zu personenbezogenen Daten keine Anwendung finden.
        </b>
    </p>
    <h3>2. Zweck, Rechtsgrundlage dieser Erhebung</h3>
    <p>
        Der Zweck der Verarbeitung liegt in der Überprüfung und Erfassung der Anwesenheit von Personen und deren
        Kontaktdaten
        bei Hochschulveranstaltung zur Sicherstellung der effektiven Rückverfolgbarkeit von Infektionen.
    </p>
    <p>
        Die Verarbeitung erfolgt auf Grundlage von Art. 6 Abs. 1 Buchst. c DSGVO i.V. § 55 des Hessischen
        Hochschulgesetzes
        und
        § 2 der Hessischen Immatrikulationsverordnung.
    </p>
    <p>
        Darüber hinaus besteht eine rechtliche Verpflichtung zur Erhebung weiterer Kontakt-, Aufenthalts- und
        Bewegungsdaten
        nach Art. 6 Abs. 1 Buchst. c DSGVO i. V. m. § 5a Abs.2 sowie § 1 Abs. 2b Nr. 2 der Verordnung zur Beschränkung
        von
        sozialen Kontakten und des Betriebes von Einrichtungen und von Angeboten aufgrund der Corona-Pandemie
        (Corona-Kontakt-
        und Betriebsbeschränkungsverordnung) in der aktuellen Fassung. Daten die ausschließlich auf Grundlage der
        Corona-Kontakt- und Betriebsbeschränkungsverordnung werden durch die THM ausschließlich zur Ermöglichung der
        Nachverfolgung von Infektionen erfasst und für die vorgeschriebene Dauer der Aufbewahrung geschützt vor
        Einsichtnahme
        durch Dritte aufbewahrt.
    </p>
    <h3>3. Durchführung der Verarbeitung</h3>
    <p>
        Im Rahmen der Vorbereitung einer möglicherweise erforderlichen Rückverfolgbarkeit von Infektionen werden die
        nachfolgend unter den Ziffern 4 dargestellten Daten anlassbezogen im Zusammenhang mit dem Besuch von
        Veranstaltungen
        erfasst. Die Erfassung erfolgt digital mittels dem einscannen einen veranstaltungsbezogenen QR-Codes oder der
        manuellen Eingabe aber über die Website der THM unter
        <a href="https://go.thm.de/checkin">https://go.thm.de/checkin</a>.
    </p>
    <h3>4. Erfasste Daten</h3>
    <ul>
        <li>Benutzername (xyx123)</li>
        <li>Nachname</li>
        <li>Vorname</li>
        <li>Telefonnummer</li>
        <li>Straße</li>
        <li>Postleitzahl</li>
        <li>Wohnort</li>
        <li>Zeitstempel (Datum & Uhrzeit)</li>
        <li>Aufenthaltsort (Standort, Gebäude, Raum)</li>
        <li>Veranstaltungsname</li>
        <li>Status (anwesend)</li>
    </ul>
    <h3>5. Betroffener Personenkreis</h3>
    <ul>
        <li>Professorinnen / Professoren</li>
        <li>Studierende</li>
        <li>Wissenschaftliche Beschäftigte</li>
        <li>Beschäftige in Technik und Verwaltung</li>
        <li>Auszubildende</li>
        <li>Praktikanten</li>
        <li>Lehrbeauftragte</li>
        <li>Mitarbeiter von Fremdfirmen</li>
        <li>Gäste und Besucher</li>
    </ul>
    <h3>6. Dauer der Speicherung der personenbezogenen Daten</h3>
    <p>
        Die Aufenthalts-, Bewegungsdaten und Telefonnummer werden für einen Zeitraum von vier Wochen gespeichert und
        nach
        Ablauf automatisch gelöscht. Die Zuordnung der Aufenthaltsdaten zu den Kontaktdaten ist demzufolge für einen
        Zeitraum von 4 Wochen möglich.
    </p>
    <p>
        Im Übrigen erfolgt die Löschung unter Beachtung der gesetzlichen Regelungen für die Verarbeitung von Daten im
        Rahmen
        des Ausbildungs- oder Beschäftigungsverhältnisses.
    </p>
    <h3>7. Datenübermittlung</h3>
    <p>
        Die Kontaktdaten der Personen, die möglicherweise Kontakt zu einer infizierten Person hatten, werden auf
        Verlangen
        der zuständigen Behörde (z.B. Gesundheitsamt) auf Anforderung übermittelt, sofern dies zur Nachverfolgung von
        möglichen Infektionswegen erforderlich ist.
    </p>
    <h3>8. Rechte und Beschwerdemöglichkeiten</h3>
    <p>
        Vorbehaltlich der Regelung des § 1 Abs. 2b Nr. 2 der Verordnung zur Beschränkung von sozialen Kontakten und des
        Betriebes von Einrichtungen und von Angeboten aufgrund der Corona-Pandemie (Corona-Kontakt- und
        Betriebsbeschränkungsverordnung) zu Einschränkungen bei der Wahrnehmung von Informations- und Auskunftsrechten
        haben
        Sie ein Recht auf Auskunft über die betreffenden personenbezogenen Daten sowie auf Berichtigung oder Löschung
        oder
        auf Einschränkung der Verarbeitung oder auf Datenübertragbarkeit sowie ein Widerspruchsrecht gegen die
        Verarbeitung.
    </p>
    <p>
        Anfragen zu Ihren Rechten richten Sie bitte an die Datenschutzbeauftragte der THM unter der E-Mail:
        <b><a href="mailto:datenschutz@thm.de">datenschutz@thm.de</a></b>
    </p>
    <p>
        Unbeschadet eines anderweitigen verwaltungsrechtlichen oder gerichtlichen Rechtsbehelfs steht Ihnen das Recht
        auf
        Beschwerde bei einer Aufsichtsbehörde, insbesondere in dem Mitgliedstaat ihres Aufenthaltsorts, ihres
        Arbeitsplatzes
        oder des Orts des mutmaßlichen Verstoßes zu, wenn Sie der Ansicht sind, dass die Verarbeitung der Sie
        betreffenden
        personenbezogenen Daten gegen die EU- Datenschutzgrundverordnung verstößt.
        Die Aufsichtsbehörde, bei der die Beschwerde eingereicht wurde, unterrichtet den Beschwerdeführer über den Stand
        und
        die
        Ergebnisse der Beschwerde einschließlich der Möglichkeit eines gerichtlichen Rechtsbehelfs nach Art. 78 DSGVO.
    </p>
    <p>
        Sie haben das Recht sich bei datenschutzrechtlichen Problemen bei der zuständigen Aufsichtsbehörde des Landes
        Hessen
        zu
        beschweren.
    </p>
    <p>
        Kontaktadresse der Fachaufsichtsbehörde der Technischen Hochschule Mittelhessen:
    </p>
    <br>
    Der Hessische Datenschutzbeauftragte<br>
    Postfach 3163<br>
    65021 Wiesbaden<br>
    <br>
    E-Mail an HDSB über <a href="https://datenschutz.hessen.de/über-uns/kontakt">https://datenschutz.hessen.de/über-uns/kontakt</a>
    <br>
    <br>
    Telefon: +49 611 1408 – 0<br>
    Telefax: +49 611 1408 – 611<br>
    <br>
    <div class="control-group">
        <a class="btn" href="<?php echo $referrer; ?>">
            <?php echo Text::_('ORGANIZER_CLOSE'); ?>
        </a>
    </div>
</form>