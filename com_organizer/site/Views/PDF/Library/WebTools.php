<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 * @see         TCPDF (http://www.tcpdf.org) => Nicola Asuni
 * @see         FPDF (http://www.fpdf.org) => Olivier Plathey
 */

namespace THM\Organizer\Views\PDF\Library;

/** Trait extracting CSS, HTML, and JavaScript related functions and constants. */
trait WebTools
{
    private string $javascript = '';

    /** Javascript counter added to the document code. */
    private string $javascriptCounter = '';

    private array $javascriptObjects = [];

    /**
     * Adds a javascript form field.
     * @param string $type       field type
     * @param string $name       field name
     * @param int    $x
     * @param int    $y
     * @param int    $width      width
     * @param int    $height     height
     * @param array  $properties javascript field properties. Possible values are described on official Javascript for Acrobat API reference.
     */
    protected function addJavascriptField(string $type, string $name, int $x, int $y, int $width, int $height, array $properties): void
    {
        if ($this->rtl) {
            $x = $x - $width;
        }

        $pageNumber   = $this->PageNo() - 1;
        $scaledHeight = ($this->h - $y - $height) * $this->scaleFactor + 1;
        $scaledWidth  = ($x + $width) * $this->scaleFactor;
        $scaledX      = $x * $this->scaleFactor;
        $scaledY      = ($this->h - $y) * $this->scaleFactor + 1;

        // the following avoid fields duplication after saving the document
        $this->javascript .= "if (getField('OrganizerSaved').value != 'saved') {";
        $this->javascript .= "f" . $name . "=this.addField('$name','$type',$pageNumber,[$scaledX,$scaledY,$scaledWidth,$scaledHeight]);" . "\n";
        $this->javascript .= 'f' . $name . '.textSize=' . $this->FontSizePt . ";\n";

        foreach ($properties as $key => $value) {
            $value = strcmp(substr($key, -5), 'Color') == 0 ? Colors::jsColor($value) : "'" . $value . "'";

            $this->javascript .= 'f' . $name . '.' . $key . '=' . $value . ";\n";
        }

        $this->x = $this->rtl ? $this->x - $width : $this->x + $width;

        $this->javascript .= '}';
    }

    /**
     * Adds javascript to the corresponding property.
     * @param string $script
     */
    public function addJavascript(string $script): void
    {
        $this->javascript .= $script;
    }

    /**
     * Adds a javascript object and return its object ID.
     * @param string $script javascript code
     * @param bool   $onload if true executes this object when opening the document
     * @return int
     */
    public function addJavascriptObject(string $script, bool $onload = false): int
    {
        // javascript is not allowed in PDF/A mode
        if ($this->pdfaMode) {
            return false;
        }

        ++$this->objectNumber;
        $this->javascriptObjects[$this->objectNumber] = ['n' => $this->objectNumber, 'js' => $script, 'onload' => $onload];

        return $this->objectNumber;
    }

    /**
     * Create a javascript PDF string.
     */
    protected function appendJavascript(): void
    {
        if ($this->pdfaMode or (empty($this->javascript) and empty($this->javascriptObjects))) {
            return;
        }

        if (strpos($this->javascript, 'this.addField') > 0) {
            // the following two lines are used to avoid form fields duplication after saving
            // The addField method only works when releasing user rights (UR3)
            $jsa = "fOrganizerSaved=this.addField('OrganizerSaved','text',0,[0,1,0,1]);";
            $jsb = "getField('OrganizerSaved').value='saved';";

            $this->javascript = $jsa . "\n" . $this->javascript . "\n" . $jsb;
        }

        // name tree for javascript
        $this->javascriptCounter = '<< /Names [';

        if ($this->javascript) {
            $this->javascriptCounter .= ' (EmbeddedJS) ' . ($this->objectNumber + 1) . ' 0 R';
        }

        if ($this->javascriptObjects) {
            foreach ($this->javascriptObjects as $key => $value) {
                if ($value['onload']) {
                    $this->javascriptCounter .= ' (JS' . $key . ') ' . $key . ' 0 R';
                }
            }
        }

        $this->javascriptCounter .= ' ] >>';

        // default javascript object
        if ($this->javascript) {
            $objectID = $this->_newobj();
            $script   = $this->_textstring($this->javascript, $objectID);
            $this->append('<< /S /JavaScript  /JS ' . $script . ' >>' . "\n" . 'endobj');
        }

        // additional javascript objects
        if ($this->javascriptObjects) {
            foreach ($this->javascriptObjects as $key => $val) {
                $objectString = $this->_getobj($key);
                $script       = $this->_textstring($val['js'], $key);
                $this->append($objectString . "\n" . ' << /S /JavaScript /JS ' . $script . ' >>' . "\n" . 'endobj');
            }
        }
    }
}