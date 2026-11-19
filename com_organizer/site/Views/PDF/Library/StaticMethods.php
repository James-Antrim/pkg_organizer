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

/** Static methods used by the PDF class. */
class StaticMethods
{

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Add "\" before "\", "(" and ")"
     * @param string $s string to escape.
     * @return string escaped string.
     */
    public static function _escape($s)
    {
        // the chr(13) substitution fixes the Bugs item #1421290.
        return strtr($s, [')' => '\\)', '(' => '\\(', '\\' => '\\\\', chr(13) => '\r']);
    }

    /**
     * Escape some special characters (&lt; &gt; &amp;) for XML output.
     * @param string $str Input string to convert.
     * @return string converted string
     */
    public static function _escapeXML($str)
    {
        $replaceTable = ["\0" => '', '&' => '&amp;', '<' => '&lt;', '>' => '&gt;'];
        $str          = strtr($str === null ? '' : $str, $replaceTable);
        return $str;
    }

    /**
     * Convert JavaScript form fields properties array to Annotation Properties array.
     * @param array $prop javascript field properties. Possible values are described on official Javascript for Acrobat API reference.
     * @return array of annotation properties
     * @author Nicola Asuni
     */
    public static function getAnnotOptFromJSProp($prop)
    {
        if (isset($prop['aopt']) and is_array($prop['aopt'])) {
            // the annotation options are already defined
            return $prop['aopt'];
        }
        $opt = []; // value to be returned
        // alignment: Controls how the text is laid out within the text field.
        if (isset($prop['alignment'])) {
            switch ($prop['alignment']) {
                case 'left':
                {
                    $opt['q'] = 0;
                    break;
                }
                case 'center':
                {
                    $opt['q'] = 1;
                    break;
                }
                case 'right':
                {
                    $opt['q'] = 2;
                    break;
                }
                default:
                {
                    $opt['q'] = 0;
                    break;
                }
            }
        }
        // lineWidth: Specifies the thickness of the border when stroking the perimeter of a field's rectangle.
        if (isset($prop['lineWidth'])) {
            $linewidth = intval($prop['lineWidth']);
        }
        else {
            $linewidth = 1;
        }
        // borderStyle: The border style for a field.
        if (isset($prop['borderStyle'])) {
            switch ($prop['borderStyle']) {
                case 'border.d':
                case 'dashed':
                {
                    $opt['border'] = [0, 0, $linewidth, [3, 2]];
                    $opt['bs']     = ['w' => $linewidth, 's' => 'D', 'd' => [3, 2]];
                    break;
                }
                case 'border.b':
                case 'beveled':
                {
                    $opt['border'] = [0, 0, $linewidth];
                    $opt['bs']     = ['w' => $linewidth, 's' => 'B'];
                    break;
                }
                case 'border.i':
                case 'inset':
                {
                    $opt['border'] = [0, 0, $linewidth];
                    $opt['bs']     = ['w' => $linewidth, 's' => 'I'];
                    break;
                }
                case 'border.u':
                case 'underline':
                {
                    $opt['border'] = [0, 0, $linewidth];
                    $opt['bs']     = ['w' => $linewidth, 's' => 'U'];
                    break;
                }
                case 'border.s':
                case 'solid':
                {
                    $opt['border'] = [0, 0, $linewidth];
                    $opt['bs']     = ['w' => $linewidth, 's' => 'S'];
                    break;
                }
                default:
                {
                    break;
                }
            }
        }
        if (isset($prop['border']) and is_array($prop['border'])) {
            $opt['border'] = $prop['border'];
        }
        if (!isset($opt['mk'])) {
            $opt['mk'] = [];
        }
        if (!isset($opt['mk']['if'])) {
            $opt['mk']['if'] = [];
        }
        $opt['mk']['if']['a'] = [0.5, 0.5];
        // buttonAlignX: Controls how space is distributed from the left of the button face with respect to the icon.
        if (isset($prop['buttonAlignX'])) {
            $opt['mk']['if']['a'][0] = $prop['buttonAlignX'];
        }
        // buttonAlignY: Controls how unused space is distributed from the bottom of the button face with respect to the icon.
        if (isset($prop['buttonAlignY'])) {
            $opt['mk']['if']['a'][1] = $prop['buttonAlignY'];
        }
        // buttonFitBounds: If true, the extent to which the icon may be scaled is set to the bounds of the button field.
        if (isset($prop['buttonFitBounds']) and ($prop['buttonFitBounds'] == 'true')) {
            $opt['mk']['if']['fb'] = true;
        }
        // buttonScaleHow: Controls how the icon is scaled (if necessary) to fit inside the button face.
        if (isset($prop['buttonScaleHow'])) {
            switch ($prop['buttonScaleHow']) {
                case 'scaleHow.proportional':
                {
                    $opt['mk']['if']['s'] = 'P';
                    break;
                }
                case 'scaleHow.anamorphic':
                {
                    $opt['mk']['if']['s'] = 'A';
                    break;
                }
            }
        }
        // buttonScaleWhen: Controls when an icon is scaled to fit inside the button face.
        if (isset($prop['buttonScaleWhen'])) {
            switch ($prop['buttonScaleWhen']) {
                case 'scaleWhen.always':
                {
                    $opt['mk']['if']['sw'] = 'A';
                    break;
                }
                case 'scaleWhen.never':
                {
                    $opt['mk']['if']['sw'] = 'N';
                    break;
                }
                case 'scaleWhen.tooBig':
                {
                    $opt['mk']['if']['sw'] = 'B';
                    break;
                }
                case 'scaleWhen.tooSmall':
                {
                    $opt['mk']['if']['sw'] = 'S';
                    break;
                }
            }
        }
        // buttonPosition: Controls how the text and the icon of the button are positioned with respect to each other within the button face.
        if (isset($prop['buttonPosition'])) {
            switch ($prop['buttonPosition']) {
                case 0:
                case 'position.textOnly':
                {
                    $opt['mk']['tp'] = 0;
                    break;
                }
                case 1:
                case 'position.iconOnly':
                {
                    $opt['mk']['tp'] = 1;
                    break;
                }
                case 2:
                case 'position.iconTextV':
                {
                    $opt['mk']['tp'] = 2;
                    break;
                }
                case 3:
                case 'position.textIconV':
                {
                    $opt['mk']['tp'] = 3;
                    break;
                }
                case 4:
                case 'position.iconTextH':
                {
                    $opt['mk']['tp'] = 4;
                    break;
                }
                case 5:
                case 'position.textIconH':
                {
                    $opt['mk']['tp'] = 5;
                    break;
                }
                case 6:
                case 'position.overlay':
                {
                    $opt['mk']['tp'] = 6;
                    break;
                }
            }
        }
        // fillColor: Specifies the background color for a field.
        if (isset($prop['fillColor'])) {
            if (is_array($prop['fillColor'])) {
                $opt['mk']['bg'] = $prop['fillColor'];
            }
            else {
                $opt['mk']['bg'] = Colors::htmlToColor($prop['fillColor']);
            }
        }
        // strokeColor: Specifies the stroke color for a field that is used to stroke the rectangle of the field with a line as large as the line width.
        if (isset($prop['strokeColor'])) {
            if (is_array($prop['strokeColor'])) {
                $opt['mk']['bc'] = $prop['strokeColor'];
            }
            else {
                $opt['mk']['bc'] = Colors::htmlToColor($prop['strokeColor']);
            }
        }
        // rotation: The rotation of a widget in counterclockwise increments.
        if (isset($prop['rotation'])) {
            $opt['mk']['r'] = $prop['rotation'];
        }
        // charLimit: Limits the number of characters that a user can type into a text field.
        if (isset($prop['charLimit'])) {
            $opt['maxlen'] = intval($prop['charLimit']);
        }
        $ff = 0;
        // readonly: The read-only characteristic of a field. If a field is read-only, the user can see the field but cannot change it.
        if (isset($prop['readonly']) and ($prop['readonly'] == 'true')) {
            $ff += 1 << 0;
        }
        // required: Specifies whether a field requires a value.
        if (isset($prop['required']) and ($prop['required'] == 'true')) {
            $ff += 1 << 1;
        }
        // multiline: Controls how text is wrapped within the field.
        if (isset($prop['multiline']) and ($prop['multiline'] == 'true')) {
            $ff += 1 << 12;
        }
        // password: Specifies whether the field should display asterisks when data is entered in the field.
        if (isset($prop['password']) and ($prop['password'] == 'true')) {
            $ff += 1 << 13;
        }
        // NoToggleToOff: If set, exactly one radio button shall be selected at all times; selecting the currently selected button has no effect.
        if (isset($prop['NoToggleToOff']) and ($prop['NoToggleToOff'] == 'true')) {
            $ff += 1 << 14;
        }
        // Radio: If set, the field is a set of radio buttons.
        if (isset($prop['Radio']) and ($prop['Radio'] == 'true')) {
            $ff += 1 << 15;
        }
        // Pushbutton: If set, the field is a pushbutton that does not retain a permanent value.
        if (isset($prop['Pushbutton']) and ($prop['Pushbutton'] == 'true')) {
            $ff += 1 << 16;
        }
        // Combo: If set, the field is a combo box; if clear, the field is a list box.
        if (isset($prop['Combo']) and ($prop['Combo'] == 'true')) {
            $ff += 1 << 17;
        }
        // editable: Controls whether a combo box is editable.
        if (isset($prop['editable']) and ($prop['editable'] == 'true')) {
            $ff += 1 << 18;
        }
        // Sort: If set, the field's option items shall be sorted alphabetically.
        if (isset($prop['Sort']) and ($prop['Sort'] == 'true')) {
            $ff += 1 << 19;
        }
        // fileSelect: If true, sets the file-select flag in the Options tab of the text field (Field is Used for File Selection).
        if (isset($prop['fileSelect']) and ($prop['fileSelect'] == 'true')) {
            $ff += 1 << 20;
        }
        // multipleSelection: If true, indicates that a list box allows a multiple selection of items.
        if (isset($prop['multipleSelection']) and ($prop['multipleSelection'] == 'true')) {
            $ff += 1 << 21;
        }
        // doNotSpellCheck: If true, spell checking is not performed on this editable text field.
        if (isset($prop['doNotSpellCheck']) and ($prop['doNotSpellCheck'] == 'true')) {
            $ff += 1 << 22;
        }
        // doNotScroll: If true, the text field does not scroll and the user, therefore, is limited by the rectangular region designed for the field.
        if (isset($prop['doNotScroll']) and ($prop['doNotScroll'] == 'true')) {
            $ff += 1 << 23;
        }
        // comb: If set to true, the field background is drawn as series of boxes (one for each character in the value of the field) and each character of the content is drawn within those boxes. The number of boxes drawn is determined from the charLimit property. It applies only to text fields. The setter will also raise if any of the following field properties are also set multiline, password, and fileSelect. A side-effect of setting this property is that the doNotScroll property is also set.
        if (isset($prop['comb']) and ($prop['comb'] == 'true')) {
            $ff += 1 << 24;
        }
        // radiosInUnison: If false, even if a group of radio buttons have the same name and export value, they behave in a mutually exclusive fashion, like HTML radio buttons.
        if (isset($prop['radiosInUnison']) and ($prop['radiosInUnison'] == 'true')) {
            $ff += 1 << 25;
        }
        // richText: If true, the field allows rich text formatting.
        if (isset($prop['richText']) and ($prop['richText'] == 'true')) {
            $ff += 1 << 25;
        }
        // commitOnSelChange: Controls whether a field value is committed after a selection change.
        if (isset($prop['commitOnSelChange']) and ($prop['commitOnSelChange'] == 'true')) {
            $ff += 1 << 26;
        }
        $opt['ff'] = $ff;
        // defaultValue: The default value of a field - that is, the value that the field is set to when the form is reset.
        if (isset($prop['defaultValue'])) {
            $opt['dv'] = $prop['defaultValue'];
        }
        $f = 4; // default value for annotation flags
        // readonly: The read-only characteristic of a field. If a field is read-only, the user can see the field but cannot change it.
        if (isset($prop['readonly']) and ($prop['readonly'] == 'true')) {
            $f += 1 << 6;
        }
        // display: Controls whether the field is hidden or visible on screen and in print.
        if (isset($prop['display'])) {
            if ($prop['display'] == 'display.visible') {
                //
            }
            elseif ($prop['display'] == 'display.hidden') {
                $f += 1 << 1;
            }
            elseif ($prop['display'] == 'display.noPrint') {
                $f -= 1 << 2;
            }
            elseif ($prop['display'] == 'display.noView') {
                $f += 1 << 5;
            }
        }
        $opt['f'] = $f;
        // currentValueIndices: Reads and writes single or multiple values of a list box or combo box.
        if (isset($prop['currentValueIndices']) and is_array($prop['currentValueIndices'])) {
            $opt['i'] = $prop['currentValueIndices'];
        }
        // value: The value of the field data that the user has entered.
        if (isset($prop['value'])) {
            if (is_array($prop['value'])) {
                $opt['opt'] = [];
                foreach ($prop['value'] as $key => $optval) {
                    // exportValues: An array of strings representing the export values for the field.
                    if (isset($prop['exportValues'][$key])) {
                        $opt['opt'][$key] = [$prop['exportValues'][$key], $prop['value'][$key]];
                    }
                    else {
                        $opt['opt'][$key] = $prop['value'][$key];
                    }
                }
            }
            else {
                $opt['v'] = $prop['value'];
            }
        }
        // richValue: This property specifies the text contents and formatting of a rich text field.
        if (isset($prop['richValue'])) {
            $opt['rv'] = $prop['richValue'];
        }
        // submitName: If nonempty, used during form submission instead of name. Only applicable if submitting in HTML format (that is, URL-encoded).
        if (isset($prop['submitName'])) {
            $opt['tm'] = $prop['submitName'];
        }
        // name: Fully qualified field name.
        if (isset($prop['name'])) {
            $opt['t'] = $prop['name'];
        }
        // userName: The user name (short description string) of the field.
        if (isset($prop['userName'])) {
            $opt['tu'] = $prop['userName'];
        }
        // highlight: Defines how a button reacts when a user clicks it.
        if (isset($prop['highlight'])) {
            switch ($prop['highlight']) {
                case 'none':
                case 'highlight.n':
                {
                    $opt['h'] = 'N';
                    break;
                }
                case 'invert':
                case 'highlight.i':
                {
                    $opt['h'] = 'i';
                    break;
                }
                case 'push':
                case 'highlight.p':
                {
                    $opt['h'] = 'P';
                    break;
                }
                case 'outline':
                case 'highlight.o':
                {
                    $opt['h'] = 'O';
                    break;
                }
            }
        }
        // Unsupported options:
        // - calcOrderIndex: Changes the calculation order of fields in the document.
        // - delay: Delays the redrawing of a field's appearance.
        // - defaultStyle: This property defines the default style attributes for the form field.
        // - style: Allows the user to set the glyph style of a check box or radio button.
        // - textColor, textFont, textSize
        return $opt;
    }

    /**
     * Get the border mode accounting for multicell position (opens bottom side of multicell crossing pages)
     * @param string|array|int $brd      Indicates if borders must be drawn around the cell block. The value can be a number:<ul><li>0: no border (default)</li><li>1: frame</li></ul>or a string containing some or all of the following characters (in any order):<ul><li>L: left</li><li>T: top</li><li>R: right</li><li>B: bottom</li></ul> or an array of line styles for each border group: ['LTRB' => ['width' => 2, 'cap' => 'butt', 'join' => 'miter', 'dash' => 0, 'color' => self::BLACK]]
     * @param string           $position multicell position: 'start', 'middle', 'end'
     * @param boolean          $opencell True when the cell is left open at the page bottom, false otherwise.
     * @return array border mode array
     */
    public static function getBorderMode($brd, $position = 'start', $opencell = true)
    {
        if ((!$opencell) or empty($brd)) {
            return $brd;
        }
        if ($brd == 1) {
            $brd = 'LTRB';
        }
        if (is_string($brd)) {
            // convert string to array
            $slen   = strlen($brd);
            $newbrd = [];
            for ($i = 0; $i < $slen; ++$i) {
                $newbrd[$brd[$i]] = ['cap' => 'square', 'join' => 'miter'];
            }
            $brd = $newbrd;
        }
        foreach ($brd as $border => $style) {
            switch ($position) {
                case 'start':
                {
                    if (strpos($border, 'B') !== false) {
                        // remove bottom line
                        $newkey = str_replace('B', '', $border);
                        if (strlen($newkey) > 0) {
                            $brd[$newkey] = $style;
                        }
                        unset($brd[$border]);
                    }
                    break;
                }
                case 'middle':
                {
                    if (strpos($border, 'B') !== false) {
                        // remove bottom line
                        $newkey = str_replace('B', '', $border);
                        if (strlen($newkey) > 0) {
                            $brd[$newkey] = $style;
                        }
                        unset($brd[$border]);
                        $border = $newkey;
                    }
                    if (strpos($border, 'T') !== false) {
                        // remove bottom line
                        $newkey = str_replace('T', '', $border);
                        if (strlen($newkey) > 0) {
                            $brd[$newkey] = $style;
                        }
                        unset($brd[$border]);
                    }
                    break;
                }
                case 'end':
                {
                    if (strpos($border, 'T') !== false) {
                        // remove bottom line
                        $newkey = str_replace('T', '', $border);
                        if (strlen($newkey) > 0) {
                            $brd[$newkey] = $style;
                        }
                        unset($brd[$border]);
                    }
                    break;
                }
            }
        }
        return $brd;
    }

    /**
     * Returns the styles array that apply for the selected HTML tag.
     * @param array $dom array of HTML tags and properties
     * @param int   $key key of the current HTML tag
     * @param array $css array of CSS properties
     * @return array containing CSS properties
     */
    public static function getCSSdataArray($dom, $key, $css)
    {
        $cssarray = []; // style to be returned
        // get parent CSS selectors
        $selectors = [];
        if (isset($dom[($dom[$key]['parent'])]['csssel'])) {
            $selectors = $dom[($dom[$key]['parent'])]['csssel'];
        }
        // get all styles that apply
        foreach ($css as $selector => $style) {
            $pos = strpos($selector, ' ');
            // get specificity
            $specificity = substr($selector, 0, $pos);
            // remove specificity
            $selector = substr($selector, $pos);
            // check if this selector apply to current tag
            if (self::isValidCSSSelectorForTag($dom, $key, $selector)) {
                if (!in_array($selector, $selectors)) {
                    // add style if not already added on parent selector
                    $cssarray[]  = ['k' => $selector, 's' => $specificity, 'c' => $style];
                    $selectors[] = $selector;
                }
            }
        }
        if (isset($dom[$key]['attribute']['style'])) {
            // attach inline style (latest properties have high priority)
            $cssarray[] = ['k' => '', 's' => '1000', 'c' => $dom[$key]['attribute']['style']];
        }
        // order the css array to account for specificity
        $cssordered = [];
        foreach ($cssarray as $key => $val) {
            $skey                                = sprintf('%04d', $key);
            $cssordered[$val['s'] . '_' . $skey] = $val;
        }
        // sort selectors alphabetically to account for specificity
        ksort($cssordered, SORT_STRING);
        return [$selectors, $cssordered];
    }

    /**
     * Returns a formatted date-time.
     * @param int $time Time in seconds.
     * @return string escaped date string.
     */
    public static function getFormattedDate($time)
    {
        return substr_replace(date('YmdHisO', intval($time)), '\'', (0 - 2), 0) . '\'';
    }

    /**
     * Compact CSS data array into single string.
     * @param array $css array of CSS properties
     * @return string containing merged CSS properties
     */
    public static function getTagStyleFromCSSarray($css)
    {
        $tagstyle = ''; // value to be returned
        foreach ($css as $style) {
            // split single css commands
            $csscmds = explode(';', $style['c']);
            foreach ($csscmds as $cmd) {
                if (!empty($cmd)) {
                    $pos = strpos($cmd, ':');
                    if ($pos !== false) {
                        $cmd = substr($cmd, 0, ($pos + 1));
                        if (strpos($tagstyle, $cmd) !== false) {
                            // remove duplicate commands (last commands have high priority)
                            $tagstyle = preg_replace('/' . $cmd . '[^;]+/i', '', $tagstyle);
                        }
                    }
                }
            }
            $tagstyle .= ';' . $style['c'];
        }
        // remove multiple semicolons
        $tagstyle = preg_replace('/[;]+/', ';', $tagstyle);
        return $tagstyle;
    }

    /**
     * Returns timestamp in seconds from formatted date-time.
     * @param string $date Formatted date-time.
     * @return int seconds.
     */
    public static function getTimestamp($date)
    {
        if (($date[0] == 'D') and ($date[1] == ':')) {
            // remove date prefix if present
            $date = substr($date, 2);
        }
        return strtotime($date);
    }

    /**
     * Determine whether a string is empty.
     * @param string $str string to be checked
     * @return bool true if string is empty
     */
    public static function empty_string($str)
    {
        return (is_null($str) or (is_string($str) and (strlen($str) == 0)));
    }

    /**
     * Encode a name object.
     * @param string $name Name object to encode.
     * @return string Encoded name object.
     * @author Nicola Asuni
     */
    public static function encodeNameObject($name)
    {
        $escname = '';
        $length  = strlen($name);
        for ($i = 0; $i < $length; ++$i) {
            $chr = $name[$i];
            if (preg_match('/[0-9a-zA-Z#_=-]/', $chr) == 1) {
                $escname .= $chr;
            }
            else {
                $escname .= sprintf('#%02X', ord($chr));
            }
        }
        return $escname;
    }

    /**
     * Encode query params in URL
     *
     * @param string $url
     * @return string
     * @since  6.3.3 (2019-11-01)
     * @public static
     */
    public static function encodeUrlQuery($url)
    {
        $urlData = parse_url($url);
        if (isset($urlData['query']) && $urlData['query']) {
            $urlQueryData = [];
            parse_str(urldecode($urlData['query']), $urlQueryData);
            $port       = isset($urlData['port']) ? ':' . $urlData['port'] : '';
            $updatedUrl = $urlData['scheme'] . '://' . $urlData['host'] . $port . $urlData['path'] . '?' . http_build_query($urlQueryData);
        }
        else {
            $updatedUrl = $url;
        }
        return $updatedUrl;
    }

    /**
     * Extracts the CSS properties from a CSS string.
     * @param string $cssdata string containing CSS definitions.
     * @return array An array where the keys are the CSS selectors and the values are the CSS properties.
     * @author Nicola Asuni
     */
    public static function extractCSSproperties($cssdata)
    {
        if (empty($cssdata)) {
            return [];
        }
        // remove comments
        $cssdata = preg_replace('/\/\*[^\*]*\*\//', '', $cssdata);
        // remove newlines and multiple spaces
        $cssdata = preg_replace('/[\s]+/', ' ', $cssdata);
        // remove some spaces
        $cssdata = preg_replace('/[\s]*([;:\{\}]{1})[\s]*/', '\\1', $cssdata);
        // remove empty blocks
        $cssdata = preg_replace('/([^\}\{]+)\{\}/', '', $cssdata);
        // replace media type parenthesis
        $cssdata = preg_replace('/@media[\s]+([^\{]*)\{/i', '@media \\1§', $cssdata);
        $cssdata = preg_replace('/\}\}/si', '}§', $cssdata);
        // trim string
        $cssdata = trim($cssdata);
        // find media blocks (all, braille, embossed, handheld, print, projection, screen, speech, tty, tv)
        $cssblocks = [];
        $matches   = [];
        if (preg_match_all('/@media[\s]+([^\§]*)§([^§]*)§/i', $cssdata, $matches) > 0) {
            foreach ($matches[1] as $key => $type) {
                $cssblocks[$type] = $matches[2][$key];
            }
            // remove media blocks
            $cssdata = preg_replace('/@media[\s]+([^\§]*)§([^§]*)§/i', '', $cssdata);
        }
        // keep 'all' and 'print' media, other media types are discarded
        if (isset($cssblocks['all']) and !empty($cssblocks['all'])) {
            $cssdata .= $cssblocks['all'];
        }
        if (isset($cssblocks['print']) and !empty($cssblocks['print'])) {
            $cssdata .= $cssblocks['print'];
        }
        // reset css blocks array
        $cssblocks = [];
        $matches   = [];
        // explode css data string into array
        if (substr($cssdata, -1) == '}') {
            // remove last parethesis
            $cssdata = substr($cssdata, 0, -1);
        }
        $matches = explode('}', $cssdata);
        foreach ($matches as $key => $block) {
            // index 0 contains the CSS selector, index 1 contains CSS properties
            $cssblocks[$key] = explode('{', $block);
            if (!isset($cssblocks[$key][1])) {
                // remove empty definitions
                unset($cssblocks[$key]);
            }
        }
        // split groups of selectors (comma-separated list of selectors)
        foreach ($cssblocks as $key => $block) {
            if (strpos($block[0], ',') > 0) {
                $selectors = explode(',', $block[0]);
                foreach ($selectors as $sel) {
                    $cssblocks[] = array(0 => trim($sel), 1 => $block[1]);
                }
                unset($cssblocks[$key]);
            }
        }
        // covert array to selector => properties
        $cssdata = [];
        foreach ($cssblocks as $block) {
            $selector = $block[0];
            // calculate selector's specificity
            $matches     = [];
            $a           = 0; // the declaration is not from is a 'style' attribute
            $b           = intval(preg_match_all('/[\#]/', $selector, $matches)); // number of ID attributes
            $c           = intval(preg_match_all('/[\[\.]/', $selector, $matches)); // number of other attributes
            $c           += intval(preg_match_all('/[\:]link|visited|hover|active|focus|target|lang|enabled|disabled|checked|indeterminate|root|nth|first|last|only|empty|contains|not/i', $selector, $matches)); // number of pseudo-classes
            $d           = intval(preg_match_all('/[\>\+\~\s]{1}[a-zA-Z0-9]+/', ' ' . $selector, $matches)); // number of element names
            $d           += intval(preg_match_all('/[\:][\:]/', $selector, $matches)); // number of pseudo-elements
            $specificity = $a . $b . $c . $d;
            // add specificity to the beginning of the selector
            $cssdata[$specificity . ' ' . $selector] = $block[1];
        }
        // sort selectors alphabetically to account for specificity
        ksort($cssdata, SORT_STRING);
        // return array
        return $cssdata;
    }

    /**
     * Cleanup HTML code (requires HTML Tidy library).
     * @param string     $html         htmlcode to fix
     * @param string     $default_css  CSS commands to add
     * @param array|null $tagvs        parameters for setHtmlVSpace method
     * @param array|null $tidy_options options for tidy_parse_string function
     * @param array      $tagvspaces   Array of vertical spaces for tags.
     * @return string XHTML code cleaned up
     * @author Nicola Asuni
     * @see    setHtmlVSpace()
     */
    public static function fixHTMLCode($html, $default_css, $tagvs, $tidy_options, &$tagvspaces)
    {
        // configure parameters for HTML Tidy
        if (empty($tidy_options)) {
            $tidy_options = [
                'clean'                       => 1,
                'drop-empty-paras'            => 0,
                'drop-proprietary-attributes' => 1,
                'fix-backslash'               => 1,
                'hide-comments'               => 1,
                'join-styles'                 => 1,
                'lower-literals'              => 1,
                'merge-divs'                  => 1,
                'merge-spans'                 => 1,
                'output-xhtml'                => 1,
                'word-2000'                   => 1,
                'wrap'                        => 0,
                'output-bom'                  => 0,
                //'char-encoding' => 'utf8',
                //'input-encoding' => 'utf8',
                //'output-encoding' => 'utf8'
            ];
        }
        // clean up the HTML code
        $tidy = tidy_parse_string($html, $tidy_options);
        // fix the HTML
        $tidy->cleanRepair();
        // get the CSS part
        $tidy_head = tidy_get_head($tidy);
        $css       = $tidy_head->value;
        $css       = preg_replace('/<style([^>]+)>/ims', '<style>', $css);
        $css       = preg_replace('/<\/style>(.*)<style>/ims', "\n", $css);
        $css       = str_replace('/*<![CDATA[*/', '', $css);
        $css       = str_replace('/*]]>*/', '', $css);
        preg_match('/<style>(.*)<\/style>/ims', $css, $matches);
        if (isset($matches[1])) {
            $css = strtolower($matches[1]);
        }
        else {
            $css = '';
        }
        // include default css
        $css = '<style>' . $default_css . $css . '</style>';
        // get the body part
        $tidy_body = tidy_get_body($tidy);
        $html      = $tidy_body->value;
        // fix some self-closing tags
        $html = str_replace('<br>', '<br />', $html);
        // remove some empty tag blocks
        $html = preg_replace('/<div([^\>]*)><\/div>/', '', $html);
        $html = preg_replace('/<p([^\>]*)><\/p>/', '', $html);
        if (!empty($tagvs)) {
            // set vertical space for some XHTML tags
            $tagvspaces = $tagvs;
        }
        // return the cleaned XHTML code + CSS
        return $css . $html;
    }

    /**
     * Format the page numbers.
     * This method can be overridden for custom formats.
     * @param int $num page number
     * @return string
     */
    public static function formatPageNumber($num)
    {
        return number_format((float) $num, 0, '', '.');
    }

    /**
     * Format the page numbers on the Table Of Content.
     * This method can be overridden for custom formats.
     * @param int $num page number
     * @return string
     * @see    addTOC(), addHTMLTOC()
     */
    public static function formatTOCPageNumber($num)
    {
        return number_format((float) $num, 0, '', '.');
    }

    /**
     * Returns an array of hyphenation patterns.
     * @param string $file TEX file containing hypenation patterns. TEX patterns can be downloaded from http://www.ctan.org/tex-archive/language/hyph-utf8/tex/generic/hyph-utf8/patterns/
     * @return array of hyphenation patterns
     * @author Nicola Asuni
     * @since  4.9.012 (2010-04-12)
     * @public static
     */
    public static function getHyphenPatternsFromTEX($file)
    {
        // TEX patterns are available at:
        // http://www.ctan.org/tex-archive/language/hyph-utf8/tex/generic/hyph-utf8/patterns/
        $data     = file_get_contents($file);
        $patterns = [];
        // remove comments
        $data = preg_replace('/\%[^\n]*/', '', $data);
        // extract the patterns part
        preg_match('/\\\\patterns\{([^\}]*)\}/i', $data, $matches);
        $data = trim(substr($matches[0], 10, -1));
        // extract each pattern
        $patterns_array = preg_split('/[\s]+/', $data);
        // create new language array of patterns
        $patterns = [];
        foreach ($patterns_array as $val) {
            if (!TCPDF_STATIC::empty_string($val)) {
                $val            = trim($val);
                $val            = str_replace('\'', '\\\'', $val);
                $key            = preg_replace('/[0-9]+/', '', $val);
                $patterns[$key] = $val;
            }
        }
        return $patterns;
    }

    /**
     * Returns true if the CSS selector is valid for the selected HTML tag
     * @param array  $dom      array of HTML tags and properties
     * @param int    $key      key of the current HTML tag
     * @param string $selector CSS selector string
     * @return true if the selector is valid, false otherwise
     */
    public static function isValidCSSSelectorForTag($dom, $key, $selector)
    {
        $valid = false; // value to be returned
        $tag   = $dom[$key]['value'];
        $class = [];
        if (isset($dom[$key]['attribute']['class']) and !empty($dom[$key]['attribute']['class'])) {
            $class = explode(' ', strtolower($dom[$key]['attribute']['class']));
        }
        $id = '';
        if (isset($dom[$key]['attribute']['id']) and !empty($dom[$key]['attribute']['id'])) {
            $id = strtolower($dom[$key]['attribute']['id']);
        }
        $selector = preg_replace('/([\>\+\~\s]{1})([\.]{1})([^\>\+\~\s]*)/si', '\\1*.\\3', $selector);
        $matches  = [];
        if (preg_match_all('/([\>\+\~\s]{1})([a-zA-Z0-9\*]+)([^\>\+\~\s]*)/si', $selector, $matches, PREG_PATTERN_ORDER | PREG_OFFSET_CAPTURE) > 0) {
            $parentop = array_pop($matches[1]);
            $operator = $parentop[0];
            $offset   = $parentop[1];
            $lasttag  = array_pop($matches[2]);
            $lasttag  = strtolower(trim($lasttag[0]));
            if (($lasttag == '*') or ($lasttag == $tag)) {
                // the last element on selector is our tag or 'any tag'
                $attrib = array_pop($matches[3]);
                $attrib = strtolower(trim($attrib[0]));
                if (!empty($attrib)) {
                    // check if matches class, id, attribute, pseudo-class or pseudo-element
                    switch ($attrib[0]) {
                        case '.':
                        { // class
                            if (in_array(substr($attrib, 1), $class)) {
                                $valid = true;
                            }
                            break;
                        }
                        case '#':
                        { // ID
                            if (substr($attrib, 1) == $id) {
                                $valid = true;
                            }
                            break;
                        }
                        case '[':
                        { // attribute
                            $attrmatch = [];
                            if (preg_match('/\[([a-zA-Z0-9]*)[\s]*([\~\^\$\*\|\=]*)[\s]*["]?([^"\]]*)["]?\]/i', $attrib, $attrmatch) > 0) {
                                $att = strtolower($attrmatch[1]);
                                $val = $attrmatch[3];
                                if (isset($dom[$key]['attribute'][$att])) {
                                    switch ($attrmatch[2]) {
                                        case '=':
                                        {
                                            if ($dom[$key]['attribute'][$att] == $val) {
                                                $valid = true;
                                            }
                                            break;
                                        }
                                        case '~=':
                                        {
                                            if (in_array($val, explode(' ', $dom[$key]['attribute'][$att]))) {
                                                $valid = true;
                                            }
                                            break;
                                        }
                                        case '^=':
                                        {
                                            if ($val == substr($dom[$key]['attribute'][$att], 0, strlen($val))) {
                                                $valid = true;
                                            }
                                            break;
                                        }
                                        case '$=':
                                        {
                                            if ($val == substr($dom[$key]['attribute'][$att], -strlen($val))) {
                                                $valid = true;
                                            }
                                            break;
                                        }
                                        case '*=':
                                        {
                                            if (strpos($dom[$key]['attribute'][$att], $val) !== false) {
                                                $valid = true;
                                            }
                                            break;
                                        }
                                        case '|=':
                                        {
                                            if ($dom[$key]['attribute'][$att] == $val) {
                                                $valid = true;
                                            }
                                            elseif (preg_match('/' . $val . '[\-]{1}/i', $dom[$key]['attribute'][$att]) > 0) {
                                                $valid = true;
                                            }
                                            break;
                                        }
                                        default:
                                        {
                                            $valid = true;
                                        }
                                    }
                                }
                            }
                            break;
                        }
                        case ':':
                        { // pseudo-class or pseudo-element
                            if ($attrib[1] == ':') { // pseudo-element
                                // pseudo-elements are not supported!
                                // (::first-line, ::first-letter, ::before, ::after)
                            }
                            else { // pseudo-class
                                // pseudo-classes are not supported!
                                // (:root, :nth-child(n), :nth-last-child(n), :nth-of-type(n), :nth-last-of-type(n), :first-child, :last-child, :first-of-type, :last-of-type, :only-child, :only-of-type, :empty, :link, :visited, :active, :hover, :focus, :target, :lang(fr), :enabled, :disabled, :checked)
                            }
                            break;
                        }
                    } // end of switch
                }
                else {
                    $valid = true;
                }
                if ($valid and ($offset > 0)) {
                    $valid = false;
                    // check remaining selector part
                    $selector = substr($selector, 0, $offset);
                    switch ($operator) {
                        case ' ':
                        { // descendant of an element
                            while ($dom[$key]['parent'] > 0) {
                                if (self::isValidCSSSelectorForTag($dom, $dom[$key]['parent'], $selector)) {
                                    $valid = true;
                                    break;
                                }
                                else {
                                    $key = $dom[$key]['parent'];
                                }
                            }
                            break;
                        }
                        case '>':
                        { // child of an element
                            $valid = self::isValidCSSSelectorForTag($dom, $dom[$key]['parent'], $selector);
                            break;
                        }
                        case '+':
                        { // immediately preceded by an element
                            for ($i = ($key - 1); $i > $dom[$key]['parent']; --$i) {
                                if ($dom[$i]['tag'] and $dom[$i]['opening']) {
                                    $valid = self::isValidCSSSelectorForTag($dom, $i, $selector);
                                    break;
                                }
                            }
                            break;
                        }
                        case '~':
                        { // preceded by an element
                            for ($i = ($key - 1); $i > $dom[$key]['parent']; --$i) {
                                if ($dom[$i]['tag'] and $dom[$i]['opening']) {
                                    if (self::isValidCSSSelectorForTag($dom, $i, $selector)) {
                                        break;
                                    }
                                }
                            }
                            break;
                        }
                    }
                }
            }
        }
        return $valid;
    }

    /**
     * Check if the URL exist.
     * @param string $url URL to check.
     * @return boolean true if the URl exist, false otherwise.
     */
    public static function isValidURL($url)
    {
        $headers = @get_headers($url);
        if ($headers === false) {
            return false;
        }
        return (strpos($headers[0], '200') !== false);
    }

    /**
     * Creates a copy of a class object
     * @param object $object class object to be cloned
     * @return object cloned object
     */
    public static function objclone($object)
    {
        if (($object instanceof Imagick) and (version_compare(phpversion('imagick'), '3.0.1') !== 1)) {
            // on the versions after 3.0.1 the clone() method was deprecated in favour of clone keyword
            return @$object->clone();
        }
        return @clone($object);
    }

    /**
     * Removes SHY characters from text.
     * Unicode Data:<ul>
     * <li>Name : SOFT HYPHEN, commonly abbreviated as SHY</li>
     * <li>HTML Entity (decimal): "&amp;#173;"</li>
     * <li>HTML Entity (hex): "&amp;#xad;"</li>
     * <li>HTML Entity (named): "&amp;shy;"</li>
     * <li>How to type in Microsoft Windows: [Alt +00AD] or [Alt 0173]</li>
     * <li>UTF-8 (hex): 0xC2 0xAD (c2ad)</li>
     * <li>UTF-8 character: chr(194).chr(173)</li>
     * </ul>
     * @param string  $txt     input string
     * @param boolean $unicode True if we are in unicode mode, false otherwise.
     * @return string without SHY characters.
     */
    public static function removeSHY($txt = '', $unicode = true)
    {
        $txt = preg_replace('/([\\xc2]{1}[\\xad]{1})/', '', $txt);
        if (!$unicode) {
            $txt = preg_replace('/([\\xad]{1})/', '', $txt);
        }
        return $txt;
    }

    /**
     * Replace page number aliases with number.
     * @param string $page    Page content.
     * @param array  $replace Array of replacements (array keys are replacement strings, values are alias arrays).
     * @param int    $diff    If passed, this will be set to the total char number difference between alias and replacements.
     * @return array replaced page content and updated $diff parameter as array.
     */
    public static function replacePageNumAliases($page, $replace, $diff = 0)
    {
        foreach ($replace as $rep) {
            foreach ($rep[3] as $a) {
                if (strpos($page, $a) !== false) {
                    $page = str_replace($a, $rep[0], $page);
                    $diff += ($rep[2] - $rep[1]);
                }
            }
        }
        return [$page, $diff];
    }

    /**
     * Output input data and compress it if possible.
     * @param string $data   Data to output.
     * @param int    $length Data length in bytes.
     */
    public static function sendOutputData($data, $length)
    {
        if (!isset($_SERVER['HTTP_ACCEPT_ENCODING']) or empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            // the content length may vary if the server is using compression
            header('Content-Length: ' . $length);
        }
        echo $data;
    }

    /**
     * Check if the URL exist.
     * @param string $url URL to check.
     * @return bool Returns TRUE if the URL exists; FALSE otherwise.
     * @public static
     * @since  6.2.25
     */
    public static function url_exists($url)
    {
        $crs = curl_init();
        // encode query params in URL to get right response form the server
        $url = self::encodeUrlQuery($url);
        curl_setopt($crs, CURLOPT_URL, $url);
        curl_setopt($crs, CURLOPT_NOBODY, true);
        curl_setopt($crs, CURLOPT_FAILONERROR, true);
        if ((ini_get('open_basedir') == '') && (!ini_get('safe_mode'))) {
            curl_setopt($crs, CURLOPT_FOLLOWLOCATION, true);
        }
        curl_setopt($crs, CURLOPT_CONNECTTIMEOUT, 5);
        curl_setopt($crs, CURLOPT_TIMEOUT, 30);
        curl_setopt($crs, CURLOPT_SSL_VERIFYPEER, false);
        curl_setopt($crs, CURLOPT_SSL_VERIFYHOST, false);
        curl_setopt($crs, CURLOPT_USERAGENT, 'tc-lib-file');
        curl_setopt($crs, CURLOPT_MAXREDIRS, 5);
        if (defined('CURLOPT_PROTOCOLS')) {
            curl_setopt($crs, CURLOPT_PROTOCOLS, CURLPROTO_HTTPS | CURLPROTO_HTTP | CURLPROTO_FTP | CURLPROTO_FTPS);
        }
        curl_exec($crs);
        $code = curl_getinfo($crs, CURLINFO_HTTP_CODE);
        curl_close($crs);
        return ($code == 200);
    }
}