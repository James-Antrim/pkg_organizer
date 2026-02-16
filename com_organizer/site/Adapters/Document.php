<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Adapters;

use Joomla\CMS\Document\HtmlDocument;
use Joomla\CMS\Toolbar\Toolbar as TB;
use Joomla\CMS\WebAsset\WebAssetManager;

/**
 * Adapts functions of the document class to avoid exceptions, deprecated warnings, and overlong access chains.
 *
 * @see Application::document()
 */
class Document
{
    /**
     * Wrapper for document charset functions.
     *
     * @param string $type Charset encoding string
     *
     * @return HtmlDocument|string
     */
    public static function charset(string $type = ''): HtmlDocument|string
    {
        /** @var HtmlDocument $document */
        $document = Application::document();

        return $type ? $document->setCharset($type) : $document->getCharset();
    }

    /**
     * Sets the document MIME encoding that is sent to the browser.
     *
     * @param string  $type The document type to be sent
     * @param boolean $sync Should the type be synced with HTML?
     *
     * @return  void
     */
    public static function mime(string $type = '', bool $sync = true): void
    {
        Application::document()->setMimeEncoding($type, $sync);
    }

    /**
     * Gets the path for the given file and type.
     *
     * @param string $file the name of the file
     * @param string $type the file extension type
     *
     * @return string
     */
    private static function path(string $file, string $type): string
    {
        $path = "components/com_organizer/$type/$file.$type";

        return file_exists(JPATH_ROOT . "/$path") ? $path : '';
    }

    /**
     * Adds a script to a page.
     *
     * @param string $file the file name
     *
     * @return void
     */
    public static function script(string $file = ''): void
    {
        if ($path = self::path($file, 'js')) {
            self::webAssetManager()->registerAndUseScript("oz.$file", $path);
        }
    }

    /**
     * Add script variables for localizations.
     *
     * @param string       $key           key for addressing the localizations in script files
     * @param array|string $localizations localization(s)
     * @param bool         $merge         true if the localizations should be merged with existing
     *
     * @return  HtmlDocument instance of $this to allow chaining
     */
    public static function scriptLocalizations(string $key, array|string $localizations, bool $merge = true): HtmlDocument
    {
        /** @var HtmlDocument $document */
        $document = Application::document();

        return $document->addScriptOptions($key, $localizations, $merge);
    }

    /**
     * Adds a style to a page.
     *
     * @param string $file the file name
     *
     * @return void
     */
    public static function style(string $file = ''): void
    {
        if ($path = self::path($file, 'css')) {
            self::webAssetManager()->registerAndUseStyle("oz.$file", $path);
        }
    }

    /**
     * Wrapper for document title functions.
     *
     * @param string $title The title to be set
     *
     * @return HtmlDocument|string
     */
    public static function title(string $title = ''): HtmlDocument|string
    {
        /** @var HtmlDocument $document */
        $document = Application::document();

        return $title ? $document->setTitle($title) : $document->getTitle();
    }

    /**
     * Wraps the new standard access method to retrieve a toolbar.
     *
     * @param string $name
     *
     * @return TB
     */
    public static function toolbar(string $name = 'toolbar'): TB
    {
        /** @var HtmlDocument $document */
        $document = Application::document();

        return $document->getToolbar($name);
    }

    /**
     * Wrapper for document type property accessors.
     *
     * @param string $type the optional type to set the document to.
     *
     * @return string
     */
    public static function type(string $type = ''): string
    {
        $document = Application::document();

        /**
         * The Joomla\CMS\Document\Document _type property is public. Function use is to future-proof it should they
         * decide to remove the underscore prefix.
         */

        if ($type) {
            $document->setType($type);
        }

        return $document->getType();
    }

    /**
     * Return WebAsset manager
     *
     * @return  WebAssetManager
     * @see HtmlDocument::getWebAssetManager()
     */
    public static function webAssetManager(): WebAssetManager
    {
        /** @var HtmlDocument $document */
        $document = Application::document();

        return $document->getWebAssetManager();
    }
}
