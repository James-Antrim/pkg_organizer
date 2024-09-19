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
 * @see Application::getDocument()
 */
class Document
{
    /**
     * Gets the path for the given file and type.
     *
     * @param   string  $file  the name of the file
     * @param   string  $type  the file extension type
     *
     * @return string
     */
    private static function getPath(string $file, string $type): string
    {
        $path = "components/com_organizer/$type/$file.$type";

        return file_exists(JPATH_ROOT . "/$path") ? $path : '';
    }

    /**
     * Wraps the new standard access method to retrieve a toolbar.
     *
     * @param   string  $name
     *
     * @return TB
     */
    public static function getToolbar(string $name = 'toolbar'): TB
    {
        /** @var HtmlDocument $document */
        $document = Application::getDocument();

        return $document->getToolbar($name);
    }

    /**
     * Adds a script to a page.
     *
     * @param   string  $file  the file name
     *
     * @return void
     */
    public static function script(string $file = ''): void
    {
        if ($path = self::getPath($file, 'js')) {
            self::webAssetManager()->registerAndUseScript("oz.$file", $path);
        }
    }

    /**
     * Add script variables for localizations.
     *
     * @param   string        $key            key for addressing the localizations in script files
     * @param   array|string  $localizations  localization(s)
     * @param   bool          $merge          true if the localizations should be merged with existing
     *
     * @return  HtmlDocument instance of $this to allow chaining
     */
    public static function scriptLocalizations(string $key, array|string $localizations, bool $merge = true): HtmlDocument
    {
        /** @var HtmlDocument $document */
        $document = Application::getDocument();

        return $document->addScriptOptions($key, $localizations, $merge);
    }

    /**
     * Explicitly sets the document's charset.
     *
     * @param   string  $type  Charset encoding string
     *
     * @return  HtmlDocument instance of $this to allow chaining
     */
    public static function setCharset(string $type = 'utf-8'): HtmlDocument
    {
        /** @var HtmlDocument $document */
        $document = Application::getDocument();

        return $document->setCharset($type);
    }

    /**
     * Sets the title of the document
     *
     * @param   string  $title  The title to be set
     *
     * @return  HtmlDocument instance of $this to allow chaining
     * @since   1.7.0
     */
    public static function setTitle(string $title): HtmlDocument
    {
        /** @var HtmlDocument $document */
        $document = Application::getDocument();

        return $document->setTitle($title);
    }

    /**
     * Adds a style to a page.
     *
     * @param   string  $file  the file name
     *
     * @return void
     */
    public static function style(string $file = ''): void
    {
        if ($path = self::getPath($file, 'css')) {
            self::webAssetManager()->registerAndUseStyle("oz.$file", $path);
        }
    }

    /**
     * Wrapper for document type property accessors.
     *
     * @param   string  $type  the optional type to set the document to.
     *
     * @return string
     */
    public static function type(string $type = ''): string
    {
        $document = Application::getDocument();

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
        $document = Application::getDocument();

        return $document->getWebAssetManager();
    }
}
