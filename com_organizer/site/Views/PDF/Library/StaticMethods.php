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
     * Determine whether a string is empty.
     * @param string $str string to be checked
     * @return bool true if string is empty
     */
    public static function empty_string($str)
    {
        return (is_null($str) or (is_string($str) and (strlen($str) == 0)));
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
     * Output data and compress it if possible.
     * @param string $output
     * @param int    $length (bytes)
     */
    public static function outputData(string $output, int $length): void
    {
        if (empty($_SERVER['HTTP_ACCEPT_ENCODING'])) {
            // the content length may vary if the server is using compression
            header('Content-Length: ' . $length);
        }
        echo $output;
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