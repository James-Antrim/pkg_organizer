<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace Organizer\Adapters;

use Joomla\CMS\Factory;

/**
 * Adapts functions of the document class to avoid exceptions and deprecated warnings.
 */
class Document
{
    /**
     * Adds a linked script to the page
     *
     * @param string $url     URL to the linked script.
     * @param array  $options Array of options. Example: array('version' => 'auto', 'conditional' => 'lt IE 9')
     * @param array  $attribs Array of attributes. Example: array('id' => 'scriptID', 'async' => 'async', 'data-test'
     *                        => 1)
     *
     * @return  void
     */
    public static function addScript(string $url, array $options = [], array $attribs = [])
    {
        /** @noinspection PhpDeprecationInspection */
        Factory::getDocument()->addScript($url, $options, $attribs);
    }

    /**
     * Adds a script to the page
     *
     * @param string $content Script
     *
     * @return  void
     */
    public static function addScriptDeclaration(string $content)
    {
        Factory::getDocument()->addScriptDeclaration($content);
    }

    /**
     * Add option for script. Static wrapper for dynamic function.
     *
     * @param string $key     Name in Storage
     * @param mixed  $options Scrip options as array or string
     * @param bool   $merge   Whether merge with existing (true) or replace (false)
     *
     * @return  void
     */
    public static function addScriptOptions(string $key, $options, bool $merge = true)
    {
        Factory::getDocument()->addScriptOptions($key, $options, $merge);
    }

    /**
     * Adds a linked stylesheet to the page.
     *
     * @param string $url     URL to the linked style sheet
     * @param array  $options Array of options. Example: array('version' => 'auto', 'conditional' => 'lt IE 9')
     * @param array  $attribs Array of attributes. Example: array('id' => 'stylesheet', 'data-test' => 1)
     *
     * @return  void
     */
    public static function addStyleSheet(string $url, array $options = [], array $attribs = [])
    {
        /** @noinspection PhpDeprecationInspection */
        Factory::getDocument()->addStyleSheet($url, $options, $attribs);
    }

    /**
     * Sets the document charset to UTF-8.
     * @return void
     */
    public static function setCharset()
    {
        /** @noinspection PhpRedundantOptionalArgumentInspection */
        Factory::getDocument()->setCharset('utf-8');
    }

    /**
     * Sets the title of the document.
     *
     * @param string $title The title to be set
     *
     * @return  void
     */
    public static function setTitle(string $title)
    {
        Factory::getDocument()->setTitle($title);
    }
}
