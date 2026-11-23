<?php

namespace THM\Organizer\Views\PDF\Library\Print;

trait Bookmarks
{
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////DONE

#endregion
#region //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED

#endregion
#region ////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Adds a bookmark - alias for Bookmark().
     * @param string     $txt   Bookmark description.
     * @param int        $level Bookmark level (minimum value is 0).
     * @param float      $y     Y position in user units of the bookmark on the selected page (default = -1 = current position; 0 = page start;).
     * @param int|string $page  Target page number (leave empty for current page). If you prefix a page number with the * character, then this page will not be changed when adding/deleting/moving pages.
     * @param string     $style Font style: B = Bold, I = Italic, BI = Bold + Italic.
     * @param array      $color RGB color array (values from 0 to 255).
     * @param float      $x     X position in user units of the bookmark on the selected page (default = -1 = current position;).
     * @param mixed      $link  URL, or numerical link ID, or named destination (# character followed by the destination name), or embedded file (* character followed by the file name).
     * @public
     */
    public function setBookmark($txt, $level = 0, $y = -1, $page = '', $style = '', $color = array(0, 0, 0), $x = -1, $link = '')
    {
        $this->Bookmark($txt, $level, $y, $page, $style, $color, $x, $link);
    }

    /**
     * Adds a bookmark.
     * @param string     $txt   Bookmark description.
     * @param int        $level Bookmark level (minimum value is 0).
     * @param float      $y     Y position in user units of the bookmark on the selected page (default = -1 = current position; 0 = page start;).
     * @param int|string $page  Target page number (leave empty for current page). If you prefix a page number with the * character, then this page will not be changed when adding/deleting/moving pages.
     * @param string     $style Font style: B = Bold, I = Italic, BI = Bold + Italic.
     * @param array      $color RGB color array (values from 0 to 255).
     * @param float      $x     X position in user units of the bookmark on the selected page (default = -1 = current position;).
     * @param mixed      $link  URL, or numerical link ID, or named destination (# character followed by the destination name), or embedded file (* character followed by the file name).
     */
    public function Bookmark($txt, $level = 0, $y = -1, $page = '', $style = '', $color = self::BLACK, $x = -1, $link = '')
    {
        if ($level < 0) {
            $level = 0;
        }
        if (isset($this->outlines[0])) {
            $lastoutline = end($this->outlines);
            $maxlevel    = $lastoutline['l'] + 1;
        }
        else {
            $maxlevel = 0;
        }
        if ($level > $maxlevel) {
            $level = $maxlevel;
        }
        if ($y == -1) {
            $y = $this->y();
        }
        elseif ($y < 0) {
            $y = 0;
        }
        elseif ($y > $this->height) {
            $y = $this->height;
        }
        if ($x == -1) {
            $x = $this->x();
        }
        elseif ($x < 0) {
            $x = 0;
        }
        elseif ($x > $this->width) {
            $x = $this->width;
        }
        $fixed        = false;
        $pageAsString = (string) $page;
        if ($pageAsString && $pageAsString[0] == '*') {
            $page = intval(substr($page, 1));
            // this page number will not be changed when moving/add/deleting pages
            $fixed = true;
        }
        if (empty($page)) {
            $page = $this->page;
            if (empty($page)) {
                return;
            }
        }
        $this->outlines[] = array('t' => $txt, 'l' => $level, 'x' => $x, 'y' => $y, 'p' => $page, 'f' => $fixed, 's' => strtoupper($style), 'c' => $color, 'u' => $link);
    }

    /**
     * Sort bookmarks for page and key.
     */
    protected function sortBookmarks()
    {
        // get sorting columns
        $outline_p = [];
        $outline_y = [];
        foreach ($this->outlines as $key => $row) {
            $outline_p[$key] = $row['p'];
            $outline_k[$key] = $key;
        }
        // sort outlines by page and original position
        array_multisort($outline_p, SORT_NUMERIC, SORT_ASC, $outline_k, SORT_NUMERIC, SORT_ASC, $this->outlines);
    }

    /**
     * Create a bookmark PDF string.
     * @author Olivier Plathey, Nicola Asuni
     */
    protected function _putbookmarks()
    {
        $nb = count($this->outlines);
        if ($nb == 0) {
            return;
        }
        // sort bookmarks
        $this->sortBookmarks();
        $lru   = [];
        $level = 0;
        foreach ($this->outlines as $i => $o) {
            if ($o['l'] > 0) {
                $parent = $lru[($o['l'] - 1)];
                //Set parent and last pointers
                $this->outlines[$i]['parent']    = $parent;
                $this->outlines[$parent]['last'] = $i;
                if ($o['l'] > $level) {
                    //Level increasing: set first pointer
                    $this->outlines[$parent]['first'] = $i;
                }
            }
            else {
                $this->outlines[$i]['parent'] = $nb;
            }
            if (($o['l'] <= $level) and ($i > 0)) {
                //Set prev and next pointers
                $prev                          = $lru[$o['l']];
                $this->outlines[$prev]['next'] = $i;
                $this->outlines[$i]['prev']    = $prev;
            }
            $lru[$o['l']] = $i;
            $level        = $o['l'];
        }
        //Outline items
        $n      = $this->objectNumber + 1;
        $nltags = '/<br[\s]?\/>|<\/(blockquote|dd|dl|div|dt|h1|h2|h3|h4|h5|h6|hr|li|ol|p|pre|ul|tcpdf|table|tr|td)>/si';
        foreach ($this->outlines as $o) {
            // covert HTML title to string
            $title = preg_replace($nltags, "\n", $o['t']);
            $title = preg_replace("/[\r]+/si", '', $title);
            $title = preg_replace("/[\n]+/si", "\n", $title);
            $title = strip_tags($title);
            $title = $this->trim($title);
            $out   = '<</Title ' . $this->metadataTextString($title);
            $out   .= ' /Parent ' . ($n + $o['parent']) . ' 0 R';
            if (isset($o['prev'])) {
                $out .= ' /Prev ' . ($n + $o['prev']) . ' 0 R';
            }
            if (isset($o['next'])) {
                $out .= ' /Next ' . ($n + $o['next']) . ' 0 R';
            }
            if (isset($o['first'])) {
                $out .= ' /First ' . ($n + $o['first']) . ' 0 R';
            }
            if (isset($o['last'])) {
                $out .= ' /Last ' . ($n + $o['last']) . ' 0 R';
            }
            if (isset($o['u']) and !empty($o['u'])) {
                // link
                if (is_string($o['u'])) {
                    if ($o['u'][0] === '#') {
                        // internal destination
                        $out .= ' /Dest /' . StaticMethods::encodeNameObject(substr($o['u'], 1));
                    }
                    elseif ($o['u'][0] === '%') {
                        // embedded PDF file
                        $filename = basename(substr($o['u'], 1));
                        $out      .= ' /A <</S /GoToE /D [0 /Fit] /NewWindow true /T << /R /C /P ' . ($o['p'] - 1) . ' /A ' . $this->embeddedfiles[$filename]['a'] . ' >> >>';
                    }
                    elseif ($o['u'][0] == '*') {
                        // embedded generic file
                        $filename = basename(substr($o['u'], 1));
                        $jsa      = 'var D=event.target.doc;var MyData=D.dataObjects;for (var i in MyData) if (MyData[i].path=="' . $filename . '") D.exportDataObject( { cName : MyData[i].name, nLaunch : 2});';
                        $out      .= ' /A <</S /JavaScript /JS ' . $this->metadataTextString($jsa) . '>>';
                    }
                    else {
                        // external URI link
                        $out .= ' /A <</S /URI /URI ' . $this->metadataDataString($this->unhtmlentities($o['u'])) . '>>';
                    }
                }
                elseif (isset($this->internalLinks[$o['u']])) {
                    // internal link ID
                    $l = $this->internalLinks[$o['u']];
                    if (isset($this->pageObjectIDs[($l['p'])])) {
                        $out .= sprintf(' /Dest [%u 0 R /XYZ 0 %F null]', $this->pageObjectIDs[($l['p'])], ($this->pageDimensions[$l['p']]['h'] - ($l['y'] * $this->factor)));
                    }
                }
            }
            elseif (isset($this->pageObjectIDs[($o['p'])])) {
                // link to a page
                $out .= ' ' . sprintf('/Dest [%u 0 R /XYZ %F %F null]', $this->pageObjectIDs[($o['p'])], ($o['x'] * $this->factor), ($this->pageDimensions[$o['p']]['h'] - ($o['y'] * $this->factor)));
            }
            // set font style
            $style = 0;
            if (!empty($o['s'])) {
                // bold
                if (strpos($o['s'], 'B') !== false) {
                    $style |= 2;
                }
                // oblique
                if (strpos($o['s'], 'I') !== false) {
                    $style |= 1;
                }
            }
            $out .= sprintf(' /F %d', $style);
            // set bookmark color
            if (isset($o['c']) and is_array($o['c']) and (count($o['c']) == 3)) {
                $color = array_values($o['c']);
                $out   .= sprintf(' /C [%F %F %F]', ($color[0] / 255), ($color[1] / 255), ($color[2] / 255));
            }
            else {
                // black
                $out .= ' /C [0.0 0.0 0.0]';
            }
            $out .= ' /Count 0'; // normally closed item
            $out .= ' >>';
            $out .= "\n" . 'endobj';
            $this->append($out);
        }
        //Outline root
        $this->OutlineRoot = $this->appendObject();
        $this->append('<< /Type /Outlines /First ' . $n . ' 0 R /Last ' . ($n + $lru[0]) . ' 0 R >>' . "\n" . 'endobj');
    }

#endregion
}