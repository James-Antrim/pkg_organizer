<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2020 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 */

namespace THM\Organizer\Fields;

use Joomla\Filesystem\{File, Folder};
use SimpleXMLElement;
use THM\Organizer\Adapters\HTML;
use THM\Organizer\Helpers;

/**
 * Supports an HTML select list of files.
 */
class FileOptions extends Options
{
    protected string $filter;
    protected string $exclude;
    protected bool $hideNone = false;
    protected bool $hideDefault = false;
    protected bool $stripExt = false;
    protected string $directory;

    /**
     * Method to get certain otherwise inaccessible properties from the form field object.
     *
     * @param   string  $name  The property name for which to get the value.
     *
     * @return  mixed  The property value or null.
     */
    public function __get($name)
    {
        return match ($name) {
            'directory', 'exclude', 'filter', 'hideDefault', 'hideNone', 'stripExt' => $this->$name,
            default => parent::__get($name)
        };
    }

    /**
     * Method to set certain otherwise inaccessible properties of the form field object.
     *
     * @param   string  $name   The property name for which to set the value.
     * @param   mixed   $value  The value of the property.
     *
     * @return  void
     */
    public function __set($name, $value)
    {
        switch ($name) {
            case 'filter':
            case 'directory':
            case 'exclude':
                $this->$name = (string) $value;
                break;

            case 'hideNone':
            case 'hideDefault':
            case 'stripExt':
                $value       = (string) $value;
                $this->$name = ($value === 'true' || $value === $name || $value === '1');
                break;

            default:
                parent::__set($name, $value);
        }
    }

    /**
     * Method to attach a JForm object to the field.
     *
     * @param   SimpleXMLElement  $element  The SimpleXMLElement object representing the field.
     * @param   mixed             $value    The form field value to validate.
     * @param   string            $group    The field name group control value. This acts as an array container for the
     *                                      field.
     *
     * @return  bool  True on success.
     * @see     JFormField::setup()
     */
    public function setup(SimpleXMLElement $element, $value, $group = null): bool
    {
        $return = parent::setup($element, $value, $group);

        if ($return) {
            $this->filter  = (string) $this->element['filter'];
            $this->exclude = (string) $this->element['exclude'];

            $hideNone       = (string) $this->element['hide_none'];
            $this->hideNone = ($hideNone == 'true' || $hideNone == 'hideNone' || $hideNone == '1');

            $hideDefault       = (string) $this->element['hide_default'];
            $this->hideDefault = ($hideDefault == 'true' || $hideDefault == 'hideDefault' || $hideDefault == '1');

            $stripExt       = (string) $this->element['stripext'];
            $this->stripExt = ($stripExt == 'true' || $stripExt == 'stripExt' || $stripExt == '1');

            // Get the path in which to search for file options.
            $this->directory = (string) $this->element['directory'];
        }

        return $return;
    }

    /**
     * Method to get the list of files for the field options.
     * Specify the target directory with a directory attribute
     * Attributes allow an exclude mask and stripping of extensions from file name.
     * Default attribute may optionally be set to null (no file) or -1 (use a default).
     * @return  array  The field option objects.
     */
    protected function getOptions(): array
    {
        $options = [];

        $path = $this->directory;

        if (!is_dir($path)) {
            $path = JPATH_ROOT . '/' . $path;
        }

        $cleanedName = preg_replace('/[^a-zA-Z0-9_\-]/', '_', $this->fieldname);

        // Prepend some default options based on field attributes.
        if (!$this->hideNone) {
            $options[] = HTML::option('-1', Helpers\Languages::alt('JOPTION_DO_NOT_USE', $cleanedName));
        }

        if (!$this->hideDefault) {
            $options[] = HTML::option('', Helpers\Languages::alt('JOPTION_USE_DEFAULT', $cleanedName));
        }

        // Get a list of files in the search path with the given filter.
        $files = Folder::files($path, $this->filter);

        // Build the options list from the list of files.
        if (is_array($files)) {
            foreach ($files as $file) {
                // Check to see if the file is in the exclude mask.
                if ($this->exclude) {
                    if (preg_match(chr(1) . $this->exclude . chr(1), $file)) {
                        continue;
                    }
                }

                // If the extension is to be stripped, do it.
                if ($this->stripExt) {
                    $file = File::stripExt($file);
                }

                $options[] = HTML::option($file, $file);
            }
        }

        // Merge any additional options in the XML definition.
        return array_merge(parent::getOptions(), $options);
    }
}
