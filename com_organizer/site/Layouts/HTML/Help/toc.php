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
use THM\Organizer\Adapters\{Application, Input, Text};

if (Input::getCMD('tmpl') === 'component') {
    return;
}

$contents = [];
$dynamic  = Application::dynamic();
$folder   = dirname(__FILE__);
$iterator = new DirectoryIterator($folder);
$layout   = Input::getCMD('topic');
$link     = $dynamic ? Uri::current() . '?' . Uri::getInstance()->getQuery() : Uri::current();

foreach ($iterator as $node) {
    if ($node->isFile()) {
        $file = $node->getFilename();

        if (!str_contains($file, '.php') or $file === 'toc.php') {
            continue;
        }

        $topic           = str_replace('.php', '', $file);
        $constant        = 'ORGANIZER_TOPIC_' . strtoupper($topic);
        $text            = Text::_($constant);
        $thisLink        = $dynamic ? $link . "&topic=$topic" : $link . "?topic=$topic";
        $contents[$text] = $thisLink;
    }
}
//echo "<pre>" . print_r($contents, true) . "</pre><br>";
/*

function fillArrayWithFileNodes( DirectoryIterator $dir )
{
	$data = array();
	foreach ( $dir as $node )
	{
		if ( $node->isDir() && !$node->isDot() )
		{
			$data[$node->getFilename()] = fillArrayWithFileNodes( new DirectoryIterator( $node->getPathname() ) );
		}
		else
	}
	return $data;
}*/