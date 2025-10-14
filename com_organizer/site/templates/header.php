<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2025 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

use Joomla\CMS\Uri\Uri;
use THM\Organizer\Adapters\{Application, HTML, Text, Toolbar};
use THM\Organizer\Views\HTML\Titled;

/** @var Titled $this */
if (!Application::backend()) {
    if (Application::dynamic()) {
        $URI = Uri::getInstance();

        // Remove from query
        $query = $URI->getQuery(true);
        unset($query['lang']);

        // Remove from path w/o query
        $URL = $URI->toString(['scheme', 'user', 'pass', 'host', 'port', 'path', 'fragment']);
        $URL = preg_replace('/\/[a-z]{2}\/?$/', '/', $URL);

        if (Application::tag() === 'en') {
            $label = Text::_('GERMAN');
            $tag   = 'de';
        }
        else {
            $label = Text::_('ENGLISH');
            $tag   = 'en';
        }

        if (Application::configuration()->get('sef', 0)) {
            $URL = preg_replace('/\/(index\.php|)$/', "/$tag", $URL);
        }
        else {
            $query['lang'] = $tag;
        }

        $URL .= '?' . http_build_query($query);

        $icon = HTML::icon('fa fa-earth');
        echo '<div class="right">' . HTML::link($URL, $icon . $label) . '</div>';
    }
    echo "<h1>$this->title</h1>";
    echo $this->subtitle ? "<h4>$this->subtitle</h4>" : '';
    echo $this->supplement;
    echo Toolbar::render();
}