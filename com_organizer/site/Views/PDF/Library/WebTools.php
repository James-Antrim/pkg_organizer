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
    protected string $javascriptCounter = '';
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
    private function addJavascriptField(string $type, string $name, int $x, int $y, int $width, int $height, array $properties): void
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
            $value = strcmp(substr($key, -5), 'Color') == 0 ? $this->colorToJavascript($value) : "'" . $value . "'";

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
    private function appendJavascript(): void
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

    /**
     * Convert JavaScript form fields properties array to Annotation Properties array.
     * @param array $properties javascript field properties. Possible values are described on official Javascript for Acrobat API reference.
     * @return array of annotation properties
     */
    private function jsPropertiesToAnnotations(array $properties): array
    {
        // the annotation options are already defined
        if (isset($properties['aopt']) and is_array($properties['aopt'])) {
            return $properties['aopt'];
        }

        $annotations = [];

        // alignment: Controls how the text is laid out within the text field.
        if (isset($properties['alignment'])) {
            $annotations['q'] = match ($properties['alignment']) {
                'center' => 1,
                'right' => 2,
                default => 0
            };
        }

        // lineWidth: Specifies the thickness of the border when stroking the perimeter of a field's rectangle.
        $lineWidth = isset($properties['lineWidth']) ? (int) $properties['lineWidth'] : 1;

        // borderStyle: The border style for a field.
        if (isset($properties['borderStyle'])) {
            switch ($properties['borderStyle']) {
                case 'border.d':
                case 'dashed':
                    $annotations['border'] = [0, 0, $lineWidth, [3, 2]];
                    $annotations['bs']     = ['w' => $lineWidth, 's' => 'D', 'd' => [3, 2]];
                    break;
                case 'border.b':
                case 'beveled':
                    $annotations['border'] = [0, 0, $lineWidth];
                    $annotations['bs']     = ['w' => $lineWidth, 's' => 'B'];
                    break;
                case 'border.i':
                case 'inset':
                    $annotations['border'] = [0, 0, $lineWidth];
                    $annotations['bs']     = ['w' => $lineWidth, 's' => 'I'];
                    break;
                case 'border.u':
                case 'underline':
                    $annotations['border'] = [0, 0, $lineWidth];
                    $annotations['bs']     = ['w' => $lineWidth, 's' => 'U'];
                    break;
                case 'border.s':
                case 'solid':
                    $annotations['border'] = [0, 0, $lineWidth];
                    $annotations['bs']     = ['w' => $lineWidth, 's' => 'S'];
                    break;
            }
        }

        if (isset($properties['border']) and is_array($properties['border'])) {
            $annotations['border'] = $properties['border'];
        }

        if (!isset($annotations['mk'])) {
            $annotations['mk'] = [];
        }

        if (!isset($annotations['mk']['if'])) {
            $annotations['mk']['if'] = [];
        }

        $annotations['mk']['if']['a'] = [0.5, 0.5];

        // buttonAlignX: Controls how space is distributed from the left of the button face with respect to the icon.
        if (isset($properties['buttonAlignX'])) {
            $annotations['mk']['if']['a'][0] = $properties['buttonAlignX'];
        }

        // buttonAlignY: Controls how unused space is distributed from the bottom of the button face with respect to the icon.
        if (isset($properties['buttonAlignY'])) {
            $annotations['mk']['if']['a'][1] = $properties['buttonAlignY'];
        }

        // buttonFitBounds: If true, the extent to which the icon may be scaled is set to the bounds of the button field.
        if (isset($properties['buttonFitBounds']) and ($properties['buttonFitBounds'] == 'true')) {
            $annotations['mk']['if']['fb'] = true;
        }

        // buttonScaleHow: Controls how the icon is scaled (if necessary) to fit inside the button face.
        if (isset($properties['buttonScaleHow'])) {
            switch ($properties['buttonScaleHow']) {
                case 'scaleHow.proportional':
                    $annotations['mk']['if']['s'] = 'P';
                    break;
                case 'scaleHow.anamorphic':
                    $annotations['mk']['if']['s'] = 'A';
                    break;
            }
        }

        // buttonScaleWhen: Controls when an icon is scaled to fit inside the button face.
        if (isset($properties['buttonScaleWhen'])) {
            switch ($properties['buttonScaleWhen']) {
                case 'scaleWhen.always':
                    $annotations['mk']['if']['sw'] = 'A';
                    break;
                case 'scaleWhen.never':
                    $annotations['mk']['if']['sw'] = 'N';
                    break;
                case 'scaleWhen.tooBig':
                    $annotations['mk']['if']['sw'] = 'B';
                    break;
                case 'scaleWhen.tooSmall':
                    $annotations['mk']['if']['sw'] = 'S';
                    break;
            }
        }

        // buttonPosition: Controls how the text and the icon of the button are positioned with respect to each other within the button face.
        if (isset($properties['buttonPosition'])) {
            switch ($properties['buttonPosition']) {
                case 0:
                case 'position.textOnly':
                    $annotations['mk']['tp'] = 0;
                    break;
                case 1:
                case 'position.iconOnly':
                    $annotations['mk']['tp'] = 1;
                    break;
                case 2:
                case 'position.iconTextV':
                    $annotations['mk']['tp'] = 2;
                    break;
                case 3:
                case 'position.textIconV':
                    $annotations['mk']['tp'] = 3;
                    break;
                case 4:
                case 'position.iconTextH':
                    $annotations['mk']['tp'] = 4;
                    break;
                case 5:
                case 'position.textIconH':
                    $annotations['mk']['tp'] = 5;
                    break;
                case 6:
                case 'position.overlay':
                    $annotations['mk']['tp'] = 6;
                    break;
            }
        }

        // fillColor: Specifies the background color for a field.
        if (isset($properties['fillColor'])) {
            $annotations['mk']['bg'] = is_array($properties['fillColor']) ?
                $properties['fillColor'] : $this->colorFromHTML($properties['fillColor']);
        }

        // strokeColor: Specifies the stroke color for a field that is used to stroke the rectangle of the field with a line as large as the line width.
        if (isset($properties['strokeColor'])) {
            $annotations['mk']['bc'] = is_array($properties['strokeColor']) ?
                $properties['strokeColor'] : $this->colorFromHTML($properties['strokeColor']);
        }

        // rotation: The rotation of a widget in counterclockwise increments.
        if (isset($properties['rotation'])) {
            $annotations['mk']['r'] = $properties['rotation'];
        }

        // charLimit: Limits the number of characters that a user can type into a text field.
        if (isset($properties['charLimit'])) {
            $annotations['maxlen'] = intval($properties['charLimit']);
        }

        $ff = 0;

        // readonly: The read-only characteristic of a field. If a field is read-only, the user can see the field but cannot change it.
        if (isset($properties['readonly']) and ($properties['readonly'] == 'true')) {
            $ff += 1 << 0;
        }

        // required: Specifies whether a field requires a value.
        if (isset($properties['required']) and ($properties['required'] == 'true')) {
            $ff += 1 << 1;
        }

        // multiline: Controls how text is wrapped within the field.
        if (isset($properties['multiline']) and ($properties['multiline'] == 'true')) {
            $ff += 1 << 12;
        }

        // password: Specifies whether the field should display asterisks when data is entered in the field.
        if (isset($properties['password']) and ($properties['password'] == 'true')) {
            $ff += 1 << 13;
        }

        // NoToggleToOff: If set, exactly one radio button shall be selected at all times; selecting the currently selected button has no effect.
        if (isset($properties['NoToggleToOff']) and ($properties['NoToggleToOff'] == 'true')) {
            $ff += 1 << 14;
        }

        // Radio: If set, the field is a set of radio buttons.
        if (isset($properties['Radio']) and ($properties['Radio'] == 'true')) {
            $ff += 1 << 15;
        }

        // Pushbutton: If set, the field is a push button that does not retain a permanent value.
        if (isset($properties['Pushbutton']) and ($properties['Pushbutton'] == 'true')) {
            $ff += 1 << 16;
        }

        // Combo: If set, the field is a combo box; if clear, the field is a list box.
        if (isset($properties['Combo']) and ($properties['Combo'] == 'true')) {
            $ff += 1 << 17;
        }

        // editable: Controls whether a combo box is editable.
        if (isset($properties['editable']) and ($properties['editable'] == 'true')) {
            $ff += 1 << 18;
        }

        // Sort: If set, the field's option items shall be sorted alphabetically.
        if (isset($properties['Sort']) and ($properties['Sort'] == 'true')) {
            $ff += 1 << 19;
        }

        // fileSelect: If true, sets the file-select flag in the Options tab of the text field (Field is Used for File Selection).
        if (isset($properties['fileSelect']) and ($properties['fileSelect'] == 'true')) {
            $ff += 1 << 20;
        }

        // multipleSelection: If true, indicates that a list box allows a multiple selection of items.
        if (isset($properties['multipleSelection']) and ($properties['multipleSelection'] == 'true')) {
            $ff += 1 << 21;
        }

        // doNotSpellCheck: If true, spell checking is not performed on this editable text field.
        if (isset($properties['doNotSpellCheck']) and ($properties['doNotSpellCheck'] == 'true')) {
            $ff += 1 << 22;
        }

        // doNotScroll: If true, the text field does not scroll and the user, therefore, is limited by the rectangular region designed for the field.
        if (isset($properties['doNotScroll']) and ($properties['doNotScroll'] == 'true')) {
            $ff += 1 << 23;
        }

        // comb: If set to true, the field background is drawn as series of boxes (one for each character in the value of the field) and each character of the content is drawn within those boxes. The number of boxes drawn is determined from the charLimit property. It applies only to text fields. The setter will also raise if any of the following field properties are also set multiline, password, and fileSelect. A side-effect of setting this property is that the doNotScroll property is also set.
        if (isset($properties['comb']) and ($properties['comb'] == 'true')) {
            $ff += 1 << 24;
        }

        // radiosInUnison: If false, even if a group of radio buttons have the same name and export value, they behave in a mutually exclusive fashion, like HTML radio buttons.
        if (isset($properties['radiosInUnison']) and ($properties['radiosInUnison'] == 'true')) {
            $ff += 1 << 25;
        }

        // richText: If true, the field allows rich text formatting.
        if (isset($properties['richText']) and ($properties['richText'] == 'true')) {
            $ff += 1 << 25;
        }

        // commitOnSelChange: Controls whether a field value is committed after a selection change.
        if (isset($properties['commitOnSelChange']) and ($properties['commitOnSelChange'] == 'true')) {
            $ff += 1 << 26;
        }

        $annotations['ff'] = $ff;

        // defaultValue: The default value of a field - that is, the value that the field is set to when the form is reset.
        if (isset($properties['defaultValue'])) {
            $annotations['dv'] = $properties['defaultValue'];
        }

        $f = 4; // default value for annotation flags
        // readonly: The read-only characteristic of a field. If a field is read-only, the user can see the field but cannot change it.
        if (isset($properties['readonly']) and ($properties['readonly'] == 'true')) {
            $f += 1 << 6;
        }

        // display: Controls whether the field is hidden or visible on screen and in print.
        if (isset($properties['display']) and $properties['display'] !== 'display.visible') {
            if ($properties['display'] == 'display.hidden') {
                $f += 1 << 1;
            }
            elseif ($properties['display'] == 'display.noPrint') {
                $f -= 1 << 2;
            }
            elseif ($properties['display'] == 'display.noView') {
                $f += 1 << 5;
            }
        }

        $annotations['f'] = $f;

        // currentValueIndices: Reads and writes single or multiple values of a list box or combo box.
        if (isset($properties['currentValueIndices']) and is_array($properties['currentValueIndices'])) {
            $annotations['i'] = $properties['currentValueIndices'];
        }

        // value: The value of the field data that the user has entered.
        if (isset($properties['value'])) {
            if (is_array($properties['value'])) {
                $annotations['opt'] = [];
                foreach ($properties['value'] as $property => $value) {
                    // exportValues: An array of strings representing the export values for the field.
                    $annotations['opt'][$property] = isset($properties['exportValues'][$property]) ?
                        [$properties['exportValues'][$property], $properties['value'][$property]] : $properties['value'][$property];
                }
            }
            else {
                $annotations['v'] = $properties['value'];
            }
        }

        // richValue: This property specifies the text contents and formatting of a rich text field.
        if (isset($properties['richValue'])) {
            $annotations['rv'] = $properties['richValue'];
        }

        // submitName: If nonempty, used during form submission instead of name. Only applicable if submitting in HTML format (that is, URL-encoded).
        if (isset($properties['submitName'])) {
            $annotations['tm'] = $properties['submitName'];
        }

        // name: Fully qualified field name.
        if (isset($properties['name'])) {
            $annotations['t'] = $properties['name'];
        }

        // userName: The username (short description string) of the field.
        if (isset($properties['userName'])) {
            $annotations['tu'] = $properties['userName'];
        }

        // highlight: Defines how a button reacts when a user clicks it.
        if (isset($properties['highlight'])) {
            switch ($properties['highlight']) {
                case 'none':
                case 'highlight.n':
                    $annotations['h'] = 'N';
                    break;
                case 'invert':
                case 'highlight.i':
                    $annotations['h'] = 'i';
                    break;
                case 'push':
                case 'highlight.p':
                    $annotations['h'] = 'P';
                    break;
                case 'outline':
                case 'highlight.o':
                    $annotations['h'] = 'O';
                    break;
            }
        }

        return $annotations;
    }
}