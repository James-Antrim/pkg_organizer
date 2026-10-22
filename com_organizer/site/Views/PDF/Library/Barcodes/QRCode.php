<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 *
 * @see         TCPDF (http://www.tcpdf.org) => Nicola Asuni
 * @see         FPDF (http://www.fpdf.org) => Olivier Plathey
 */

namespace THM\Organizer\Views\PDF\Library\Barcodes;

/**
 * Creates QR-code arrays.
 * @see "PHP QR Code encoder" (http://phpqrcode.sourceforge.net/) => Dominik Dzienia
 * @see "libqrencode C library 3.1.1." (http://megaui.net/fukuchi/works/qrencode/index.en.html) => Kentaro Fukuchi
 */
class QRCode
{
    use Barcoded;

    private const ALPHANUMERIC = 1, EIGHT_BIT = 2, KANJI = 3, NORMAL = -1, NUMERIC = 0, STRUCTURED = 4;

    private const HIGH = 3, LOW = 0, MEDIUM = 1, QUARTER = 2;

    /**
     *  Mask patterns:
     *      MP1 => 1 : concatenation of same color in a line or a column
     *      MP2 => 2 : module block of same color
     *      MP3 => 3 : 1:1:3:1:1(dark:bright:dark:bright:dark)pattern in a line or a column
     *      MP4 => 4 : ration of dark modules in whole
     */
    private const MP1 = 3, MP2 = 3, MP3 = 40, MP4 = 10;

    /** Version 1 is 21*21 matrix => Version 40 is 177*177 matrix. */
    private const MAX_SYMBOLS = 16, MAX_VERSION = 40, MAX_WIDTH = 177;

    /** Matrix indexes */
    private const COUNT = 1, LEVEL = 3, REMAINDER = 2, WIDTH = 0;

    private const BEST_MASK = true, DEFAULT_MASK = 2, HEADER_BITS = 20, RANDOM_MASK = 2;

    /**
     * Array positions of alignment patterns. This array includes only the second and the third position of the alignment patterns. Rest of them can
     * be calculated from the distance between them. See Table 1 in Appendix E (pp.71) of JIS X0510:2004.
     */
    protected array $alignmentPattern = [
        [0, 0], [0, 0], [18, 0], [22, 0], [26, 0], [30, 0], [34, 0], [22, 38], [24, 42], [26, 46], [28, 50], [30, 54], [32, 58], [34, 62], [26, 46],
        [26, 48], [26, 50], [30, 54], [30, 56], [30, 58], [34, 62], [28, 50], [26, 50], [30, 54], [28, 54], [32, 58], [30, 58], [34, 62], [26, 50],
        [30, 54], [26, 52], [30, 56], [34, 60], [30, 58], [34, 62], [30, 54], [24, 50], [28, 54], [32, 58], [26, 54], [30, 58]
    ];

    private array $anTable = [
        -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, 36, -1, -1,
        -1, 37, 38, -1, -1, -1, -1, 39, 40, -1, 41, 42, 43, 0, 1, 2, 3, 4, 5, 6, 7, 8, 9, 44, -1, -1, -1, -1, -1, -1, 10, 11, 12, 13, 14, 15, 16, 17,
        18, 19, 20, 21, 22, 23, 24, 25, 26, 27, 28, 29, 30, 31, 32, 33, 34, 35, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1,
        -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1, -1
    ];

    private int $bit;

    private int $block1;

    private int $blocks;

    /** Array Table of the capacity of symbols. See Table 1 (pp.13) and Table 12-16 (pp.30-36), JIS X0510:2004. */
    protected array $capacity = [
        [0, 0, 0, [0, 0, 0, 0]], [21, 26, 0, [7, 10, 13, 17]], [25, 44, 7, [10, 16, 22, 28]], [29, 70, 7, [15, 26, 36, 44]],
        [33, 100, 7, [20, 36, 52, 64]], [37, 134, 7, [26, 48, 72, 88]], [41, 172, 7, [36, 64, 96, 112]], [45, 196, 0, [40, 72, 108, 130]],
        [49, 242, 0, [48, 88, 132, 156]], [53, 292, 0, [60, 110, 160, 192]], [57, 346, 0, [72, 130, 192, 224]], [61, 404, 0, [80, 150, 224, 264]],
        [65, 466, 0, [96, 176, 260, 308]], [69, 532, 0, [104, 198, 288, 352]], [73, 581, 3, [120, 216, 320, 384]], [77, 655, 3, [132, 240, 360, 432]],
        [81, 733, 3, [144, 280, 408, 480]], [85, 815, 3, [168, 308, 448, 532]], [89, 901, 3, [180, 338, 504, 588]],
        [93, 991, 3, [196, 364, 546, 650]], [97, 1085, 3, [224, 416, 600, 700]], [101, 1156, 4, [224, 442, 644, 750]],
        [105, 1258, 4, [252, 476, 690, 816]], [109, 1364, 4, [270, 504, 750, 900]], [113, 1474, 4, [300, 560, 810, 960]],
        [117, 1588, 4, [312, 588, 870, 1050]], [121, 1706, 4, [336, 644, 952, 1110]], [125, 1828, 4, [360, 700, 1020, 1200]],
        [129, 1921, 3, [390, 728, 1050, 1260]], [133, 2051, 3, [420, 784, 1140, 1350]], [137, 2185, 3, [450, 812, 1200, 1440]],
        [141, 2323, 3, [480, 868, 1290, 1530]], [145, 2465, 3, [510, 924, 1350, 1620]], [149, 2611, 3, [540, 980, 1440, 1710]],
        [153, 2761, 3, [570, 1036, 1530, 1800]], [157, 2876, 0, [570, 1064, 1590, 1890]], [161, 3034, 0, [600, 1120, 1680, 1980]],
        [165, 3196, 0, [630, 1204, 1770, 2100]], [169, 3362, 0, [660, 1260, 1860, 2220]], [173, 3532, 0, [720, 1316, 1950, 2310]],
        [177, 3706, 0, [750, 1372, 2040, 2430]]
    ];

    private bool $caseSensitive = true;

    private int $counter;

    private array|null $data;

    private array $dataCode = [];

    protected int $dataLength;

    protected string $dataString = '';

    private int $direction;

    private array $ecCode = [];

    /** Table of error correction code (Reed-Solomon block).See Table 12-16 (pp.30-36), JIS X0510:2004. */
    private array $ecCodes = [
        [[0, 0], [0, 0], [0, 0], [0, 0]], [[1, 0], [1, 0], [1, 0], [1, 0]], [[1, 0], [1, 0], [1, 0], [1, 0]], [[1, 0], [1, 0], [2, 0], [2, 0]],
        [[1, 0], [2, 0], [2, 0], [4, 0]], [[1, 0], [2, 0], [2, 2], [2, 2]], [[2, 0], [4, 0], [4, 0], [4, 0]], [[2, 0], [4, 0], [2, 4], [4, 1]],
        [[2, 0], [2, 2], [4, 2], [4, 2]], [[2, 0], [3, 2], [4, 4], [4, 4]], [[2, 2], [4, 1], [6, 2], [6, 2]], [[4, 0], [1, 4], [4, 4], [3, 8]],
        [[2, 2], [6, 2], [4, 6], [7, 4]], [[4, 0], [8, 1], [8, 4], [12, 4]], [[3, 1], [4, 5], [11, 5], [11, 5]], [[5, 1], [5, 5], [5, 7], [11, 7]],
        [[5, 1], [7, 3], [15, 2], [3, 13]], [[1, 5], [10, 1], [1, 15], [2, 17]], [[5, 1], [9, 4], [17, 1], [2, 19]],
        [[3, 4], [3, 11], [17, 4], [9, 16]], [[3, 5], [3, 13], [15, 5], [15, 10]], [[4, 4], [17, 0], [17, 6], [19, 6]],
        [[2, 7], [17, 0], [7, 16], [34, 0]], [[4, 5], [4, 14], [11, 14], [16, 14]], [[6, 4], [6, 14], [11, 16], [30, 2]],
        [[8, 4], [8, 13], [7, 22], [22, 13]], [[10, 2], [19, 4], [28, 6], [33, 4]], [[8, 4], [22, 3], [8, 26], [12, 28]],
        [[3, 10], [3, 23], [4, 31], [11, 31]], [[7, 7], [21, 7], [1, 37], [19, 26]], [[5, 10], [19, 10], [15, 25], [23, 25]],
        [[13, 3], [2, 29], [42, 1], [23, 28]], [[17, 0], [10, 23], [10, 35], [19, 35]], [[17, 1], [14, 21], [29, 19], [11, 46]],
        [[13, 6], [14, 23], [44, 7], [59, 1]], [[12, 7], [12, 26], [39, 14], [22, 41]], [[6, 14], [6, 34], [46, 10], [2, 64]],
        [[17, 4], [29, 14], [49, 10], [24, 46]], [[4, 18], [13, 32], [48, 14], [42, 32]], [[20, 4], [40, 7], [43, 22], [10, 67]],
        [[19, 6], [18, 31], [34, 34], [20, 61]]
    ];

    protected float|int $ecLength;

    private int $ecLevel = self::LOW;

    /**
     * Error correction levels:
     *     LOW     => 0 : < 7%
     *     MEDIUM  => 1 : < 15%
     *     QUARTER => 2 : < 25%
     *     HIGH    => 3 : < 30%
     */
    private array $ecLevels = [self::LOW, self::MEDIUM, self::HIGH];

    private array $formatInfo = [
        [0x77c4, 0x72f3, 0x7daa, 0x789d, 0x662f, 0x6318, 0x6c41, 0x6976],
        [0x5412, 0x5125, 0x5e7c, 0x5b4b, 0x45f9, 0x40ce, 0x4f97, 0x4aa0],
        [0x355f, 0x3068, 0x3f31, 0x3a06, 0x24b4, 0x2183, 0x2eda, 0x2bed],
        [0x1689, 0x13be, 0x1ce7, 0x19d0, 0x0762, 0x0255, 0x0d0c, 0x083b],
    ];

    private array|null $frame;

    private array $frames = [];

    protected array $items = [];

    /** Array Length indicator. */
    protected array $lengthTableBits = [[10, 12, 14], [9, 11, 13], [8, 16, 16], [8, 10, 12]];

    private int $mode = self::EIGHT_BIT;

    /**
     * Encoding modes:
     *     NORMAL       => -1 : It's a mystery!
     *     NUMERIC      => 0 : (0-9). 3 characters are encoded to 10bit length. ~7089 characters.
     *     ALPHANUMERIC => 1 : (0-9A-Z $%*+-./:) 45characters. 2 characters are encoded to 11bit length. ~4296 characters.
     *     EIGHT_BIT    => 2 : 8bit byte data. ~2953 characters.
     *     KANJI        => 3 : (multibyte character) is encoded to 13bit length. ~1817 characters.
     */
    private array $modes = [self::ALPHANUMERIC, self::EIGHT_BIT, self::KANJI, self::NORMAL, self::NUMERIC, self::STRUCTURED];

    /** Reed-Solomon */
    private array $rsBlocks = [];
    protected array $rsItems = [];

    private array $runLength = [];

    private int $version = 0;

    /** Array Version information pattern (BCH coded). See Table 1 in Appendix D (pp.68) of JIS X0510:2004. size: [self::MAX_VERSION - 6] */
    private array $versionPattern = [
        0x07c94, 0x085bc, 0x09a99, 0x0a4d3, 0x0bbf6, 0x0c762, 0x0d847, 0x0e60d, //
        0x0f928, 0x10b78, 0x1145d, 0x12a17, 0x13532, 0x149a6, 0x15683, 0x168c9, //
        0x177ec, 0x18ec4, 0x191e1, 0x1afab, 0x1b08e, 0x1cc1a, 0x1d33f, 0x1ed75, //
        0x1f250, 0x209d5, 0x216f0, 0x228ba, 0x2379f, 0x24b0b, 0x2542e, 0x26a64, //
        0x27541, 0x28c69
    ];

    private int $width;

    private int $x;

    private int $y;


    /**
     * Creates a QRCode object.
     * @param string $code    code to represent using QR code
     * @param string $ecLevel error correction level
     */
    public function __construct(string $code, string $ecLevel = 'L')
    {
        $barcode = [];
        if ((is_null($code)) or ($code == '\0') or ($code == '')) {
            return false;
        }
        // set error correction level
        $this->ecLevel = array_search($ecLevel, ['L', 'M', 'Q', 'H']);
        if ($this->ecLevel === false) {
            $this->ecLevel = self::LOW;
        }
        if (($this->mode != self::EIGHT_BIT) and ($this->mode != self::KANJI)) {
            return false;
        }
        if (($this->version < 0) or ($this->version > self::MAX_VERSION)) {
            return false;
        }
        $this->items = [];
        $this->encodeString($code);
        if (is_null($this->data)) {
            return false;
        }
        $qrTab                  = $this->binarize($this->data);
        $size                   = count($qrTab);
        $barcode['rowCount']    = $size;
        $barcode['columnCount'] = $size;
        $barcode['barcode']     = [];
        foreach ($qrTab as $line) {
            $arrAdd = [];
            foreach (str_split($line) as $char) {
                $arrAdd[] = ($char == '1') ? 1 : 0;
            }
            $barcode['barcode'][] = $arrAdd;
        }
        $this->barcode = $barcode;
    }

    /**
     * Convert the frame into binary form.
     * @param array $frame array to binarize
     * @return array
     */
    protected function binarize(array $frame): array
    {
        $length = count($frame);
        // the frame is square (width = height)
        foreach ($frame as &$frameLine) {
            for ($i = 0; $i < $length; $i++) {
                $frameLine[$i] = (ord($frameLine[$i]) & 1) ? '1' : '0';
            }
        }
        return $frame;
    }

    /**
     * Encode the input string to QR code
     * @param string $string input string to encode
     */
    protected function encodeString($string)
    {
        $this->dataString = $string;
        if (!$this->caseSensitive) {
            $this->toUpper();
        }
        $ret = $this->splitString();
        if ($ret < 0) {
            return null;
        }
        $this->encodeMask(-1);
    }

    /**
     * Encode mask
     * @param int $mask masking mode
     */
    protected function encodeMask($mask)
    {
        $bitStream      = $this->mergeBitStream($this->items);
        $bitStream      = $this->appendPaddingBit($bitStream);
        $this->dataCode = $this->bitstreamToByte($bitStream);

        if (is_null($this->dataCode)) {
            return null;
        }
        $spec             = [0, 0, 0, 0, 0];
        $spec             = $this->getEccSpec($this->version, $this->ecLevel, $spec);
        $this->block1     = $this->rsBlockNum1($spec);
        $this->dataLength = $this->rsDataLength($spec);
        $this->ecLength   = $this->rsEccLength($spec);
        $this->ecCode     = array_fill(0, $this->ecLength, 0);
        $this->blocks     = $this->rsBlockNum($spec);
        $ret              = $this->init($spec);
        if ($ret < 0) {
            return null;
        }
        $this->counter = 0;
        $this->width   = $this->capacity[$this->version][self::WIDTH];;
        $this->frame     = $this->newFrame($this->version);
        $this->x         = $this->width - 1;
        $this->y         = $this->width - 1;
        $this->direction = -1;
        $this->bit       = -1;
        // inteleaved data and ecc codes
        for ($i = 0; $i < ($this->dataLength + $this->ecLength); $i++) {
            $code = $this->getCode();
            $bit  = 0x80;
            for ($j = 0; $j < 8; $j++) {
                $addr = $this->getNextPosition();
                $this->setFrameAt($addr, 0x02 | (($bit & $code) != 0));
                $bit = $bit >> 1;
            }
        }
        // remainder bits
        $j = $this->capacity[$this->version][self::REMAINDER];
        for ($i = 0; $i < $j; $i++) {
            $addr = $this->getNextPosition();
            $this->setFrameAt($addr, 0x02);
        }
        // masking
        $this->runLength = array_fill(0, self::MAX_WIDTH + 1, 0);
        if ($mask < 0) {
            if (self::BEST_MASK) {
                $masked = $this->mask($this->width, $this->frame, $this->ecLevel);
            }
            else {
                $masked = $this->makeMask($this->width, $this->frame, (intval(self::DEFAULT_MASK) % 8), $this->ecLevel);
            }
        }
        else {
            $masked = $this->makeMask($this->width, $this->frame, $mask, $this->ecLevel);
        }
        if ($masked == null) {
            return null;
        }
        $this->data = $masked;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - -

    // FrameFiller

    /**
     * Set frame value at specified position
     * @param array $at  x,y position
     * @param int   $val value of the character to set
     */
    protected function setFrameAt($at, $val)
    {
        $this->frame[$at['y']][$at['x']] = chr($val);
    }

    /**
     * Get frame value (byte/int) at specified position
     * @param array $at x,y position
     * @return int
     */
    protected function getFrameAt($at)
    {
        return ord($this->frame[$at['y']][$at['x']]);
    }

    /**
     * Return the next frame position
     * @return array of x,y coordinates
     */
    protected function getNextPosition()
    {
        do {
            if ($this->bit == -1) {
                $this->bit = 0;
                return ['x' => $this->x, 'y' => $this->y];
            }
            $x = $this->x;
            $y = $this->y;
            $w = $this->width;
            if ($this->bit == 0) {
                $x--;
                $this->bit++;
            }
            else {
                $x++;
                $y += $this->direction;
                $this->bit--;
            }
            if ($this->direction < 0) {
                if ($y < 0) {
                    $y               = 0;
                    $x               -= 2;
                    $this->direction = 1;
                    if ($x == 6) {
                        $x--;
                        $y = 9;
                    }
                }
            }
            else {
                if ($y == $w) {
                    $y               = $w - 1;
                    $x               -= 2;
                    $this->direction = -1;
                    if ($x == 6) {
                        $x--;
                        $y -= 8;
                    }
                }
            }
            if (($x < 0) or ($y < 0)) {
                return null;
            }
            $this->x = $x;
            $this->y = $y;
        } while (ord($this->frame[$y][$x]) & 0x80);
        return ['x' => $x, 'y' => $y];
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - -

    // QRrawcode

    /**
     * Initialize code.
     * @param array $spec array of ECC specification
     * @return int 0 in case of success, -1 in case of error
     */
    protected function init($spec)
    {
        $dl      = $this->rsDataCodes1($spec);
        $el      = $this->rsEccCodes1($spec);
        $rs      = $this->init_rs(8, 0x11d, 0, 1, $el, 255 - $dl - $el);
        $blockNo = 0;
        $dataPos = 0;
        $eccPos  = 0;
        $endfor  = $this->rsBlockNum1($spec);
        for ($i = 0; $i < $endfor; ++$i) {
            $ecc                                    = array_slice($this->ecCode, $eccPos);
            $this->rsBlocks[$blockNo]               = [];
            $this->rsBlocks[$blockNo]['dataLength'] = $dl;
            $this->rsBlocks[$blockNo]['data']       = array_slice($this->dataCode, $dataPos);
            $this->rsBlocks[$blockNo]['eccLength']  = $el;
            $ecc                                    = $this->encode_rs_char($rs, $this->rsBlocks[$blockNo]['data'], $ecc);
            $this->rsBlocks[$blockNo]['ecc']        = $ecc;
            $this->ecCode                           = array_merge(array_slice($this->ecCode, 0, $eccPos), $ecc);
            $dataPos                                += $dl;
            $eccPos                                 += $el;
            $blockNo++;
        }
        if ($this->rsBlockNum2($spec) == 0) {
            return 0;
        }
        $dl = $this->rsDataCodes2($spec);
        $el = $this->rsEccCodes2($spec);
        $rs = $this->init_rs(8, 0x11d, 0, 1, $el, 255 - $dl - $el);
        if ($rs == null) {
            return -1;
        }
        $endfor = $this->rsBlockNum2($spec);
        for ($i = 0; $i < $endfor; ++$i) {
            $ecc                                    = array_slice($this->ecCode, $eccPos);
            $this->rsBlocks[$blockNo]               = [];
            $this->rsBlocks[$blockNo]['dataLength'] = $dl;
            $this->rsBlocks[$blockNo]['data']       = array_slice($this->dataCode, $dataPos);
            $this->rsBlocks[$blockNo]['eccLength']  = $el;
            $ecc                                    = $this->encode_rs_char($rs, $this->rsBlocks[$blockNo]['data'], $ecc);
            $this->rsBlocks[$blockNo]['ecc']        = $ecc;
            $this->ecCode                           = array_merge(array_slice($this->ecCode, 0, $eccPos), $ecc);
            $dataPos                                += $dl;
            $eccPos                                 += $el;
            $blockNo++;
        }
        return 0;
    }

    /**
     * Return Reed-Solomon block code.
     * @return array rsblocks
     */
    protected function getCode()
    {
        if ($this->counter < $this->dataLength) {
            $row = $this->counter % $this->blocks;
            $col = (int) ($this->counter / $this->blocks);
            if ($col >= $this->rsBlocks[0]['dataLength']) {
                $row += $this->block1;
            }
            $row = (int) $row;
            $ret = $this->rsBlocks[$row]['data'][$col];
        }
        elseif ($this->counter < $this->dataLength + $this->ecLength) {
            $row = ($this->counter - $this->dataLength) % $this->blocks;
            $col = (int) (($this->counter - $this->dataLength) / $this->blocks);
            $ret = $this->rsBlocks[$row]['ecc'][$col];
        }
        else {
            return 0;
        }
        $this->counter++;
        return $ret;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - -

    // QRmask

    /**
     * Write Format Information on frame and returns the number of black bits
     * @param int   $width frame width
     * @param array $frame frame
     * @param array $mask  masking mode
     * @param int   $level error correction level
     * @return int blacks
     */
    protected function writeFormatInformation($width, &$frame, $mask, $level)
    {
        $blacks = 0;
        $format = $this->formatInfo($level, $mask);
        for ($i = 0; $i < 8; ++$i) {
            if ($format & 1) {
                $blacks += 2;
                $v      = 0x85;
            }
            else {
                $v = 0x84;
            }
            $frame[8][$width - 1 - $i] = chr($v);
            if ($i < 6) {
                $frame[$i][8] = chr($v);
            }
            else {
                $frame[$i + 1][8] = chr($v);
            }
            $format = $format >> 1;
        }
        for ($i = 0; $i < 7; ++$i) {
            if ($format & 1) {
                $blacks += 2;
                $v      = 0x85;
            }
            else {
                $v = 0x84;
            }
            $frame[$width - 7 + $i][8] = chr($v);
            if ($i == 0) {
                $frame[8][7] = chr($v);
            }
            else {
                $frame[8][6 - $i] = chr($v);
            }
            $format = $format >> 1;
        }
        return $blacks;
    }

    /**
     * mask0
     * @param int $x X position
     * @param int $y Y position
     * @return int mask
     */
    protected function mask0($x, $y)
    {
        return ($x + $y) & 1;
    }

    /**
     * mask1
     * @param int $x X position
     * @param int $y Y position
     * @return int mask
     */
    protected function mask1($x, $y)
    {
        return ($y & 1);
    }

    /**
     * mask2
     * @param int $x X position
     * @param int $y Y position
     * @return int mask
     */
    protected function mask2($x, $y)
    {
        return ($x % 3);
    }

    /**
     * mask3
     * @param int $x X position
     * @param int $y Y position
     * @return int mask
     */
    protected function mask3($x, $y)
    {
        return ($x + $y) % 3;
    }

    /**
     * mask4
     * @param int $x X position
     * @param int $y Y position
     * @return int mask
     */
    protected function mask4($x, $y)
    {
        return (((int) ($y / 2)) + ((int) ($x / 3))) & 1;
    }

    /**
     * mask5
     * @param int $x X position
     * @param int $y Y position
     * @return int mask
     */
    protected function mask5($x, $y)
    {
        return (($x * $y) & 1) + ($x * $y) % 3;
    }

    /**
     * mask6
     * @param int $x X position
     * @param int $y Y position
     * @return int mask
     */
    protected function mask6($x, $y)
    {
        return ((($x * $y) & 1) + ($x * $y) % 3) & 1;
    }

    /**
     * mask7
     * @param int $x X position
     * @param int $y Y position
     * @return int mask
     */
    protected function mask7($x, $y)
    {
        return ((($x * $y) % 3) + (($x + $y) & 1)) & 1;
    }

    /**
     * Return bitmask
     * @param int   $maskNo mask number
     * @param int   $width  width
     * @param array $frame  frame
     * @return array bitmask
     */
    protected function generateMaskNo($maskNo, $width, $frame)
    {
        $bitMask = array_fill(0, $width, array_fill(0, $width, 0));
        for ($y = 0; $y < $width; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                if (ord($frame[$y][$x]) & 0x80) {
                    $bitMask[$y][$x] = 0;
                }
                else {
                    $maskFunc        = call_user_func([$this, 'mask' . $maskNo], $x, $y);
                    $bitMask[$y][$x] = ($maskFunc == 0) ? 1 : 0;
                }
            }
        }
        return $bitMask;
    }

    /**
     * makeMaskNo
     * @param int     $maskNo
     * @param int     $width
     * @param int     $s
     * @param int     $d
     * @param boolean $maskGenOnly
     * @return int b
     */
    protected function makeMaskNo($maskNo, $width, $s, &$d, $maskGenOnly = false)
    {
        $b       = 0;
        $bitMask = [];
        $bitMask = $this->generateMaskNo($maskNo, $width, $s);
        if ($maskGenOnly) {
            return;
        }
        $d = $s;
        for ($y = 0; $y < $width; ++$y) {
            for ($x = 0; $x < $width; ++$x) {
                if ($bitMask[$y][$x] == 1) {
                    $d[$y][$x] = chr(ord($s[$y][$x]) ^ ((int) ($bitMask[$y][$x])));
                }
                $b += ord($d[$y][$x]) & 1;
            }
        }
        return $b;
    }

    /**
     * makeMask
     * @param int   $width
     * @param array $frame
     * @param int   $maskNo
     * @param int   $level
     * @return array mask
     */
    private function makeMask($width, $frame, $maskNo, $level)
    {
        $masked = array_fill(0, $width, str_repeat("\0", $width));
        $this->makeMaskNo($maskNo, $width, $frame, $masked);
        $this->writeFormatInformation($width, $masked, $maskNo, $level);
        return $masked;
    }

    /**
     * calcN1N3
     * @param int $length
     * @return int demerit
     */
    private function calcN1N3(int $length): int
    {
        $demerit = 0;
        for ($i = 0; $i < $length; ++$i) {
            if ($this->runLength[$i] >= 5) {
                $demerit += (self::MP1 + ($this->runLength[$i] - 5));
            }
            if ($i & 1) {
                if (($i >= 3) and ($i < ($length - 2)) and ($this->runLength[$i] % 3 == 0)) {
                    $fact = (int) ($this->runLength[$i] / 3);
                    if (($this->runLength[$i - 2] == $fact)
                        and ($this->runLength[$i - 1] == $fact)
                        and ($this->runLength[$i + 1] == $fact)
                        and ($this->runLength[$i + 2] == $fact)) {
                        if (($this->runLength[$i - 3] < 0) or ($this->runLength[$i - 3] >= (4 * $fact))) {
                            $demerit += self::MP3;
                        }
                        elseif ((($i + 3) >= $length) or ($this->runLength[$i + 3] >= (4 * $fact))) {
                            $demerit += self::MP3;
                        }
                    }
                }
            }
        }

        return $demerit;
    }

    /**
     * evaluateSymbol
     * @param int   $width
     * @param array $frame
     * @return int demerit
     */
    protected function evaluateSymbol($width, $frame)
    {
        $head    = 0;
        $demerit = 0;
        for ($y = 0; $y < $width; ++$y) {
            $head               = 0;
            $this->runLength[0] = 1;
            $frameY             = $frame[$y];
            if ($y > 0) {
                $frameYM = $frame[$y - 1];
            }
            for ($x = 0; $x < $width; ++$x) {
                if (($x > 0) and ($y > 0)) {
                    $b22 = ord($frameY[$x]) & ord($frameY[$x - 1]) & ord($frameYM[$x]) & ord($frameYM[$x - 1]);
                    $w22 = ord($frameY[$x]) | ord($frameY[$x - 1]) | ord($frameYM[$x]) | ord($frameYM[$x - 1]);
                    if (($b22 | ($w22 ^ 1)) & 1) {
                        $demerit += self::MP2;
                    }
                }
                if (($x == 0) and (ord($frameY[$x]) & 1)) {
                    $this->runLength[0]     = -1;
                    $head                   = 1;
                    $this->runLength[$head] = 1;
                }
                elseif ($x > 0) {
                    if ((ord($frameY[$x]) ^ ord($frameY[$x - 1])) & 1) {
                        $head++;
                        $this->runLength[$head] = 1;
                    }
                    else {
                        $this->runLength[$head]++;
                    }
                }
            }
            $demerit += $this->calcN1N3($head + 1);
        }
        for ($x = 0; $x < $width; ++$x) {
            $head               = 0;
            $this->runLength[0] = 1;
            for ($y = 0; $y < $width; ++$y) {
                if (($y == 0) and (ord($frame[$y][$x]) & 1)) {
                    $this->runLength[0]     = -1;
                    $head                   = 1;
                    $this->runLength[$head] = 1;
                }
                elseif ($y > 0) {
                    if ((ord($frame[$y][$x]) ^ ord($frame[$y - 1][$x])) & 1) {
                        $head++;
                        $this->runLength[$head] = 1;
                    }
                    else {
                        $this->runLength[$head]++;
                    }
                }
            }
            $demerit += $this->calcN1N3($head + 1);
        }
        return $demerit;
    }

    /**
     * mask
     * @param int   $width
     * @param array $frame
     * @param int   $level
     * @return array best mask
     */
    protected function mask($width, $frame, $level)
    {
        $minDemerit    = PHP_INT_MAX;
        $bestMaskNum   = 0;
        $bestMask      = [];
        $checked_masks = [0, 1, 2, 3, 4, 5, 6, 7];
        if (self::RANDOM_MASK !== false) {
            $howManuOut = 8 - (self::RANDOM_MASK % 9);
            for ($i = 0; $i < $howManuOut; ++$i) {
                $remPos = rand(0, count($checked_masks) - 1);
                unset($checked_masks[$remPos]);
                $checked_masks = array_values($checked_masks);
            }
        }
        $bestMask = $frame;
        foreach ($checked_masks as $i) {
            $mask    = array_fill(0, $width, str_repeat("\0", $width));
            $demerit = 0;
            $blacks  = 0;
            $blacks  = $this->makeMaskNo($i, $width, $frame, $mask);
            $blacks  += $this->writeFormatInformation($width, $mask, $i, $level);
            $blacks  = (int) (100 * $blacks / ($width * $width));
            $demerit = (int) ((int) (abs($blacks - 50) / 5) * self::MP4);
            $demerit += $this->evaluateSymbol($width, $mask);
            if ($demerit < $minDemerit) {
                $minDemerit  = $demerit;
                $bestMask    = $mask;
                $bestMaskNum = $i;
            }
        }
        return $bestMask;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - -

    // QRsplit

    /**
     * Return true if the character at specified position is a number
     * @param string $str string
     * @param int    $pos characted position
     * @return boolean true of false
     */
    protected function isdigitat($str, $pos)
    {
        if ($pos >= strlen($str)) {
            return false;
        }
        return ((ord($str[$pos]) >= ord('0')) && (ord($str[$pos]) <= ord('9')));
    }

    /**
     * Return true if the character at specified position is an alphanumeric character
     * @param string $str string
     * @param int    $pos characted position
     * @return boolean true of false
     */
    protected function isalnumat($str, $pos)
    {
        if ($pos >= strlen($str)) {
            return false;
        }
        return ($this->lookAnTable(ord($str[$pos])) >= 0);
    }

    /**
     * identifyMode
     * @param int $pos
     * @return int mode
     */
    protected function identifyMode($pos)
    {
        if ($pos >= strlen($this->dataString)) {
            return self::NORMAL;
        }
        $c = $this->dataString[$pos];
        if ($this->isdigitat($this->dataString, $pos)) {
            return self::NUMERIC;
        }
        elseif ($this->isalnumat($this->dataString, $pos)) {
            return self::ALPHANUMERIC;
        }
        elseif ($this->mode == self::KANJI) {
            if ($pos + 1 < strlen($this->dataString)) {
                $d    = $this->dataString[$pos + 1];
                $word = (ord($c) << 8) | ord($d);
                if (($word >= 0x8140 && $word <= 0x9ffc) or ($word >= 0xe040 && $word <= 0xebbf)) {
                    return self::KANJI;
                }
            }
        }
        return self::EIGHT_BIT;
    }

    /**
     * eatNum
     * @return int run
     */
    protected function eatNum()
    {
        $ln = $this->lengthIndicator(self::NUMERIC, $this->version);
        $p  = 0;
        while ($this->isdigitat($this->dataString, $p)) {
            $p++;
        }
        $run  = $p;
        $mode = $this->identifyMode($p);
        if ($mode == self::EIGHT_BIT) {
            $dif = $this->estimateBitsModeNum($run) + 4 + $ln
                + $this->estimateBitsMode8(1)         // + 4 + l8
                - $this->estimateBitsMode8($run + 1); // - 4 - l8
            if ($dif > 0) {
                return $this->eat8();
            }
        }
        if ($mode == self::ALPHANUMERIC) {
            $dif = $this->estimateBitsModeNum($run) + 4 + $ln
                + $this->estimateBitsModeAn(1)        // + 4 + la
                - $this->estimateBitsModeAn($run + 1);// - 4 - la
            if ($dif > 0) {
                return $this->eatAn();
            }
        }
        $this->items = $this->appendNewInputItem($this->items, self::NUMERIC, $run, str_split($this->dataString));
        return $run;
    }

    /**
     * eatAn
     * @return int run
     */
    protected function eatAn()
    {
        $la = $this->lengthIndicator(self::ALPHANUMERIC, $this->version);
        $ln = $this->lengthIndicator(self::NUMERIC, $this->version);
        $p  = 1;
        while ($this->isalnumat($this->dataString, $p)) {
            if ($this->isdigitat($this->dataString, $p)) {
                $q = $p;
                while ($this->isdigitat($this->dataString, $q)) {
                    $q++;
                }
                $dif = $this->estimateBitsModeAn($p) // + 4 + la
                    + $this->estimateBitsModeNum($q - $p) + 4 + $ln
                    - $this->estimateBitsModeAn($q); // - 4 - la
                if ($dif < 0) {
                    break;
                }
                else {
                    $p = $q;
                }
            }
            else {
                $p++;
            }
        }
        $run = $p;
        if (!$this->isalnumat($this->dataString, $p)) {
            $dif = $this->estimateBitsModeAn($run) + 4 + $la
                + $this->estimateBitsMode8(1) // + 4 + l8
                - $this->estimateBitsMode8($run + 1); // - 4 - l8
            if ($dif > 0) {
                return $this->eat8();
            }
        }
        $this->items = $this->appendNewInputItem($this->items, self::ALPHANUMERIC, $run, str_split($this->dataString));
        return $run;
    }

    /**
     * eatKanji
     * @return int run
     */
    protected function eatKanji()
    {
        $p = 0;
        while ($this->identifyMode($p) == self::KANJI) {
            $p += 2;
        }
        $this->items = $this->appendNewInputItem($this->items, self::KANJI, $p, str_split($this->dataString));
        $run         = $p;
        return $run;
    }

    /**
     * eat8
     * @return int run
     */
    protected function eat8()
    {
        $la         = $this->lengthIndicator(self::ALPHANUMERIC, $this->version);
        $ln         = $this->lengthIndicator(self::NUMERIC, $this->version);
        $p          = 1;
        $dataStrLen = strlen($this->dataString);
        while ($p < $dataStrLen) {
            $mode = $this->identifyMode($p);
            if ($mode == self::KANJI) {
                break;
            }
            if ($mode == self::NUMERIC) {
                $q = $p;
                while ($this->isdigitat($this->dataString, $q)) {
                    $q++;
                }
                $dif = $this->estimateBitsMode8($p) // + 4 + l8
                    + $this->estimateBitsModeNum($q - $p) + 4 + $ln
                    - $this->estimateBitsMode8($q); // - 4 - l8
                if ($dif < 0) {
                    break;
                }
                else {
                    $p = $q;
                }
            }
            elseif ($mode == self::ALPHANUMERIC) {
                $q = $p;
                while ($this->isalnumat($this->dataString, $q)) {
                    $q++;
                }
                $dif = $this->estimateBitsMode8($p)  // + 4 + l8
                    + $this->estimateBitsModeAn($q - $p) + 4 + $la
                    - $this->estimateBitsMode8($q); // - 4 - l8
                if ($dif < 0) {
                    break;
                }
                else {
                    $p = $q;
                }
            }
            else {
                $p++;
            }
        }
        $run         = $p;
        $this->items = $this->appendNewInputItem($this->items, self::EIGHT_BIT, $run, str_split($this->dataString));
        return $run;
    }

    /**
     * splitString
     * @return int
     */
    protected function splitString()
    {
        while (strlen($this->dataString) > 0) {
            $mode = $this->identifyMode(0);
            switch ($mode) {
                case self::NUMERIC:
                {
                    $length = $this->eatNum();
                    break;
                }
                case self::ALPHANUMERIC:
                {
                    $length = $this->eatAn();
                    break;
                }
                case self::KANJI:
                {
                    if ($this->mode == self::KANJI) {
                        $length = $this->eatKanji();
                    }
                    else {
                        $length = $this->eat8();
                    }
                    break;
                }
                default:
                {
                    $length = $this->eat8();
                    break;
                }
            }
            if ($length == 0) {
                return 0;
            }
            if ($length < 0) {
                return -1;
            }
            $this->dataString = substr($this->dataString, $length);
        }
        return 0;
    }

    /**
     * toUpper
     */
    protected function toUpper()
    {
        $stringLen = strlen($this->dataString);
        $p         = 0;
        while ($p < $stringLen) {
            $mode = $this->identifyMode(substr($this->dataString, $p));
            if ($mode == self::KANJI) {
                $p += 2;
            }
            else {
                if ((ord($this->dataString[$p]) >= ord('a')) and (ord($this->dataString[$p]) <= ord('z'))) {
                    $this->dataString[$p] = chr(ord($this->dataString[$p]) - 32);
                }
                $p++;
            }
        }
        return $this->dataString;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - -

    // QRinputItem

    /**
     * newInputItem
     * @param int   $mode
     * @param int   $size
     * @param array $data
     * @param array $bitStream
     * @return array input item
     */
    protected function newInputItem($mode, $size, $data, $bitStream = null)
    {
        $setData = array_slice($data, 0, $size);
        if (count($setData) < $size) {
            $setData = array_merge($setData, array_fill(0, ($size - count($setData)), 0));
        }
        if (!$this->check($mode, $size, $setData)) {
            return null;
        }
        $inputItem              = [];
        $inputItem['mode']      = $mode;
        $inputItem['size']      = $size;
        $inputItem['data']      = $setData;
        $inputItem['bitStream'] = $bitStream;
        return $inputItem;
    }

    /**
     * encodeModeNum
     * @param array $inputItem
     * @param int   $version
     * @return array input item
     */
    protected function encodeModeNum($inputItem, $version)
    {
        $words                  = (int) ($inputItem['size'] / 3);
        $inputItem['bitStream'] = [];
        $val                    = 0x1;
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 4, $val);
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], $this->lengthIndicator(self::NUMERIC, $version), $inputItem['size']);
        for ($i = 0; $i < $words; ++$i) {
            $val                    = (ord($inputItem['data'][$i * 3]) - ord('0')) * 100;
            $val                    += (ord($inputItem['data'][$i * 3 + 1]) - ord('0')) * 10;
            $val                    += (ord($inputItem['data'][$i * 3 + 2]) - ord('0'));
            $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 10, $val);
        }
        if ($inputItem['size'] - $words * 3 == 1) {
            $val                    = ord($inputItem['data'][$words * 3]) - ord('0');
            $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 4, $val);
        }
        elseif (($inputItem['size'] - ($words * 3)) == 2) {
            $val                    = (ord($inputItem['data'][$words * 3]) - ord('0')) * 10;
            $val                    += (ord($inputItem['data'][$words * 3 + 1]) - ord('0'));
            $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 7, $val);
        }
        return $inputItem;
    }

    /**
     * encodeModeAn
     * @param array $inputItem
     * @param int   $version
     * @return array input item
     */
    protected function encodeModeAn($inputItem, $version)
    {
        $words                  = (int) ($inputItem['size'] / 2);
        $inputItem['bitStream'] = [];
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 4, 0x02);
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], $this->lengthIndicator(self::ALPHANUMERIC, $version), $inputItem['size']);
        for ($i = 0; $i < $words; ++$i) {
            $val                    = (int) ($this->lookAnTable(ord($inputItem['data'][$i * 2])) * 45);
            $val                    += (int) ($this->lookAnTable(ord($inputItem['data'][($i * 2) + 1])));
            $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 11, $val);
        }
        if ($inputItem['size'] & 1) {
            $val                    = $this->lookAnTable(ord($inputItem['data'][($words * 2)]));
            $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 6, $val);
        }
        return $inputItem;
    }

    /**
     * encodeMode8
     * @param array $inputItem
     * @param int   $version
     * @return array input item
     */
    protected function encodeMode8($inputItem, $version)
    {
        $inputItem['bitStream'] = [];
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 4, 0x4);
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], $this->lengthIndicator(self::EIGHT_BIT, $version), $inputItem['size']);
        for ($i = 0; $i < $inputItem['size']; ++$i) {
            $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 8, ord($inputItem['data'][$i]));
        }
        return $inputItem;
    }

    /**
     * encodeModeKanji
     * @param array $inputItem
     * @param int   $version
     * @return array input item
     */
    protected function encodeModeKanji($inputItem, $version)
    {
        $inputItem['bitStream'] = [];
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 4, 0x8);
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], $this->lengthIndicator(self::KANJI, $version), (int) ($inputItem['size'] / 2));
        for ($i = 0; $i < $inputItem['size']; $i += 2) {
            $val = (ord($inputItem['data'][$i]) << 8) | ord($inputItem['data'][$i + 1]);
            if ($val <= 0x9ffc) {
                $val -= 0x8140;
            }
            else {
                $val -= 0xc140;
            }
            $h                      = ($val >> 8) * 0xc0;
            $val                    = ($val & 0xff) + $h;
            $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 13, $val);
        }
        return $inputItem;
    }

    /**
     * encodeModeStructure
     * @param array $inputItem
     * @return array input item
     */
    protected function encodeModeStructure($inputItem)
    {
        $inputItem['bitStream'] = [];
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 4, 0x03);
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 4, ord($inputItem['data'][1]) - 1);
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 4, ord($inputItem['data'][0]) - 1);
        $inputItem['bitStream'] = $this->appendNum($inputItem['bitStream'], 8, ord($inputItem['data'][2]));
        return $inputItem;
    }

    /**
     * encodeBitStream
     * @param array $inputItem
     * @param int   $version
     * @return array input item
     */
    protected function encodeBitStream($inputItem, $version)
    {
        $inputItem['bitStream'] = [];
        $words                  = $this->maximumWords($inputItem['mode'], $version);
        if ($inputItem['size'] > $words) {
            $st1                    = $this->newInputItem($inputItem['mode'], $words, $inputItem['data']);
            $st2                    = $this->newInputItem($inputItem['mode'], $inputItem['size'] - $words, array_slice($inputItem['data'], $words));
            $st1                    = $this->encodeBitStream($st1, $version);
            $st2                    = $this->encodeBitStream($st2, $version);
            $inputItem['bitStream'] = [];
            $inputItem['bitStream'] = $this->appendBitstream($inputItem['bitStream'], $st1['bitStream']);
            $inputItem['bitStream'] = $this->appendBitstream($inputItem['bitStream'], $st2['bitStream']);
        }
        else {
            switch ($inputItem['mode']) {
                case self::NUMERIC:
                {
                    $inputItem = $this->encodeModeNum($inputItem, $version);
                    break;
                }
                case self::ALPHANUMERIC:
                {
                    $inputItem = $this->encodeModeAn($inputItem, $version);
                    break;
                }
                case self::EIGHT_BIT:
                {
                    $inputItem = $this->encodeMode8($inputItem, $version);
                    break;
                }
                case self::KANJI:
                {
                    $inputItem = $this->encodeModeKanji($inputItem, $version);
                    break;
                }
                case self::STRUCTURED:
                {
                    $inputItem = $this->encodeModeStructure($inputItem);
                    break;
                }
                default:
                {
                    break;
                }
            }
        }
        return $inputItem;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - -

    // QRinput

    /**
     * Append data to an input object.
     * The data is copied and appended to the input object.
     * @param array $items input items
     * @param int   $mode  encoding mode.
     * @param int   $size  size of data (byte).
     * @param array $data  array of input data.
     * @return array items
     *
     */
    protected function appendNewInputItem($items, $mode, $size, $data)
    {
        $newitem = $this->newInputItem($mode, $size, $data);
        if (!empty($newitem)) {
            $items[] = $newitem;
        }
        return $items;
    }

    /**
     * insertStructuredAppendHeader
     * @param array $items
     * @param int   $size
     * @param int   $index
     * @param int   $parity
     * @return array items
     */
    protected function insertStructuredAppendHeader($items, $size, $index, $parity)
    {
        if ($size > self::MAX_SYMBOLS) {
            return -1;
        }
        if (($index <= 0) or ($index > self::MAX_SYMBOLS)) {
            return -1;
        }
        $buf   = [$size, $index, $parity];
        $entry = $this->newInputItem(self::STRUCTURED, 3, $buf);
        array_unshift($items, $entry);
        return $items;
    }

    /**
     * checkModeNum
     * @param int   $size
     * @param array $data
     * @return boolean true or false
     */
    protected function checkModeNum($size, $data)
    {
        for ($i = 0; $i < $size; ++$i) {
            if ((ord($data[$i]) < ord('0')) or (ord($data[$i]) > ord('9'))) {
                return false;
            }
        }
        return true;
    }

    /**
     * Look up the alphabet-numeric conversion table (see JIS X0510:2004, pp.19).
     * @param int $c character value
     * @return int value
     */
    protected function lookAnTable($c)
    {
        return (($c > 127) ? -1 : $this->anTable[$c]);
    }

    /**
     * checkModeAn
     * @param int   $size
     * @param array $data
     * @return boolean true or false
     */
    protected function checkModeAn($size, $data)
    {
        for ($i = 0; $i < $size; ++$i) {
            if ($this->lookAnTable(ord($data[$i])) == -1) {
                return false;
            }
        }
        return true;
    }

    /**
     * estimateBitsModeNum
     * @param int $size
     * @return int number of bits
     */
    protected function estimateBitsModeNum($size)
    {
        $w    = (int) ($size / 3);
        $bits = ($w * 10);
        switch ($size - ($w * 3)) {
            case 1:
            {
                $bits += 4;
                break;
            }
            case 2:
            {
                $bits += 7;
                break;
            }
        }
        return $bits;
    }

    /**
     * estimateBitsModeAn
     * @param int $size
     * @return int number of bits
     */
    protected function estimateBitsModeAn($size)
    {
        $bits = (int) ($size * 5.5); // (size / 2 ) * 11
        if ($size & 1) {
            $bits += 6;
        }
        return $bits;
    }

    /**
     * estimateBitsMode8
     * @param int $size
     * @return int number of bits
     */
    protected function estimateBitsMode8($size)
    {
        return (int) ($size * 8);
    }

    /**
     * estimateBitsModeKanji
     * @param int $size
     * @return int number of bits
     */
    protected function estimateBitsModeKanji($size)
    {
        return (int) ($size * 6.5); // (size / 2 ) * 13
    }

    /**
     * checkModeKanji
     * @param int   $size
     * @param array $data
     * @return boolean true or false
     */
    protected function checkModeKanji($size, $data)
    {
        if ($size & 1) {
            return false;
        }
        for ($i = 0; $i < $size; $i += 2) {
            $val = (ord($data[$i]) << 8) | ord($data[$i + 1]);
            if (($val < 0x8140) or (($val > 0x9ffc) and ($val < 0xe040)) or ($val > 0xebbf)) {
                return false;
            }
        }
        return true;
    }

    /**
     * Validate the input data.
     * @param int   $mode encoding mode.
     * @param int   $size size of data (byte).
     * @param array $data data to validate
     * @return boolean true in case of valid data, false otherwise
     */
    protected function check($mode, $size, $data)
    {
        if ($size <= 0) {
            return false;
        }
        switch ($mode) {
            case self::NUMERIC:
            {
                return $this->checkModeNum($size, $data);
            }
            case self::ALPHANUMERIC:
            {
                return $this->checkModeAn($size, $data);
            }
            case self::KANJI:
            {
                return $this->checkModeKanji($size, $data);
            }
            case self::EIGHT_BIT:
            {
                return true;
            }
            case self::STRUCTURED:
            {
                return true;
            }
            default:
            {
                break;
            }
        }
        return false;
    }

    /**
     * estimateBitStreamSize
     * @param array $items
     * @param int   $version
     * @return int bits
     */
    protected function estimateBitStreamSize($items, $version)
    {
        $bits = 0;
        if ($version == 0) {
            $version = 1;
        }
        foreach ($items as $item) {
            switch ($item['mode']) {
                case self::NUMERIC:
                {
                    $bits = $this->estimateBitsModeNum($item['size']);
                    break;
                }
                case self::ALPHANUMERIC:
                {
                    $bits = $this->estimateBitsModeAn($item['size']);
                    break;
                }
                case self::EIGHT_BIT:
                {
                    $bits = $this->estimateBitsMode8($item['size']);
                    break;
                }
                case self::KANJI:
                {
                    $bits = $this->estimateBitsModeKanji($item['size']);
                    break;
                }
                case self::STRUCTURED:
                {
                    return self::HEADER_BITS;
                }
                default:
                {
                    return 0;
                }
            }
            $l    = $this->lengthIndicator($item['mode'], $version);
            $m    = 1 << $l;
            $num  = (int) (($item['size'] + $m - 1) / $m);
            $bits += $num * (4 + $l);
        }
        return $bits;
    }

    /**
     * estimateVersion
     * @param array $items
     * @return int version
     */
    protected function estimateVersion($items)
    {
        $version = 0;
        $prev    = 0;
        do {
            $prev    = $version;
            $bits    = $this->estimateBitStreamSize($items, $prev);
            $version = $this->getMinimumVersion((int) (($bits + 7) / 8), $this->ecLevel);
            if ($version < 0) {
                return -1;
            }
        } while ($version > $prev);
        return $version;
    }

    /**
     * lengthOfCode
     * @param int $mode
     * @param int $version
     * @param int $bits
     * @return int size
     */
    protected function lengthOfCode($mode, $version, $bits)
    {
        $payload = $bits - 4 - $this->lengthIndicator($mode, $version);
        switch ($mode) {
            case self::NUMERIC:
            {
                $chunks = (int) ($payload / 10);
                $remain = $payload - $chunks * 10;
                $size   = $chunks * 3;
                if ($remain >= 7) {
                    $size += 2;
                }
                elseif ($remain >= 4) {
                    $size += 1;
                }
                break;
            }
            case self::ALPHANUMERIC:
            {
                $chunks = (int) ($payload / 11);
                $remain = $payload - $chunks * 11;
                $size   = $chunks * 2;
                if ($remain >= 6) {
                    ++$size;
                }
                break;
            }
            case self::EIGHT_BIT:
            {
                $size = (int) ($payload / 8);
                break;
            }
            case self::KANJI:
            {
                $size = (int) (($payload / 13) * 2);
                break;
            }
            case self::STRUCTURED:
            {
                $size = (int) ($payload / 8);
                break;
            }
            default:
            {
                $size = 0;
                break;
            }
        }
        $maxsize = $this->maximumWords($mode, $version);
        if ($size < 0) {
            $size = 0;
        }
        if ($size > $maxsize) {
            $size = $maxsize;
        }
        return $size;
    }

    /**
     * createBitStream
     * @param array $items
     * @return array of items and total bits
     */
    protected function createBitStream($items)
    {
        $total = 0;
        foreach ($items as $key => $item) {
            $items[$key] = $this->encodeBitStream($item, $this->version);
            $bits        = count($items[$key]['bitStream']);
            $total       += $bits;
        }
        return [$items, $total];
    }

    /**
     * convertData
     * @param array $items
     * @return array items
     */
    protected function convertData($items)
    {
        $ver = $this->estimateVersion($items);
        if ($ver > $this->version) {
            $this->version = $ver;
        }
        while (true) {
            $cbs   = $this->createBitStream($items);
            $items = $cbs[0];
            $bits  = $cbs[1];
            if ($bits < 0) {
                return -1;
            }
            $ver = $this->getMinimumVersion((int) (($bits + 7) / 8), $this->ecLevel);
            if ($ver < 0) {
                return -1;
            }
            elseif ($ver > $this->version) {
                $this->version = $ver;
            }
            else {
                break;
            }
        }
        return $items;
    }

    /**
     * Append Padding Bit to bitstream
     * @param array $bitStream
     * @return array bitstream
     */
    protected function appendPaddingBit($bitStream)
    {
        if (is_null($bitStream)) {
            return null;
        }
        $bits     = count($bitStream);
        $maxWords = $this->getDataLength($this->version, $this->ecLevel);
        $maxBits  = $maxWords * 8;
        if ($maxBits == $bits) {
            return $bitStream;
        }
        if ($maxBits - $bits < 5) {
            return $this->appendNum($bitStream, $maxBits - $bits, 0);
        }
        $bits    += 4;
        $words   = (int) (($bits + 7) / 8);
        $padding = [];
        $padding = $this->appendNum($padding, $words * 8 - $bits + 4, 0);
        $padlen  = $maxWords - $words;
        if ($padlen > 0) {
            $padbuf = [];
            for ($i = 0; $i < $padlen; ++$i) {
                $padbuf[$i] = ($i & 1) ? 0x11 : 0xec;
            }
            $padding = $this->appendBytes($padding, $padlen, $padbuf);
        }
        return $this->appendBitstream($bitStream, $padding);
    }

    /**
     * mergeBitStream
     * @param array $items items
     * @return array bitstream
     */
    protected function mergeBitStream($items)
    {
        $items = $this->convertData($items);
        if (!is_array($items)) {
            return null;
        }
        $bitStream = [];
        foreach ($items as $item) {
            $bitStream = $this->appendBitstream($bitStream, $item['bitStream']);
        }
        return $bitStream;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - -

    // QRbitstream

    /**
     * Return an array with zeros
     * @param int $setLength array size
     * @return array
     */
    protected function allocate($setLength)
    {
        return array_fill(0, $setLength, 0);
    }

    /**
     * Return new bitstream from number
     * @param int $bits number of bits
     * @param int $num  number
     * @return array bitstream
     */
    protected function newFromNum($bits, $num)
    {
        $bitStream = $this->allocate($bits);
        $mask      = 1 << ($bits - 1);
        for ($i = 0; $i < $bits; ++$i) {
            if ($num & $mask) {
                $bitStream[$i] = 1;
            }
            else {
                $bitStream[$i] = 0;
            }
            $mask = $mask >> 1;
        }
        return $bitStream;
    }

    /**
     * Return new bitstream from bytes
     * @param int   $size size
     * @param array $data bytes
     * @return array bitstream
     */
    protected function newFromBytes($size, $data)
    {
        $bitStream = $this->allocate($size * 8);
        $p         = 0;
        for ($i = 0; $i < $size; ++$i) {
            $mask = 0x80;
            for ($j = 0; $j < 8; ++$j) {
                if ($data[$i] & $mask) {
                    $bitStream[$p] = 1;
                }
                else {
                    $bitStream[$p] = 0;
                }
                $p++;
                $mask = $mask >> 1;
            }
        }
        return $bitStream;
    }

    /**
     * Append one bitstream to another
     * @param array $bitstream original bitstream
     * @param array $append    bitstream to append
     * @return array bitstream
     */
    protected function appendBitstream($bitstream, $append)
    {
        if ((!is_array($append)) or (count($append) == 0)) {
            return $bitstream;
        }
        if (count($bitstream) == 0) {
            return $append;
        }
        return array_values(array_merge($bitstream, $append));
    }

    /**
     * Append one bitstream created from number to another
     * @param array $bitstream original bitstream
     * @param int   $bits      number of bits
     * @param int   $num       number
     * @return array bitstream
     */
    protected function appendNum($bitstream, $bits, $num)
    {
        if ($bits == 0) {
            return 0;
        }
        $b = $this->newFromNum($bits, $num);
        return $this->appendBitstream($bitstream, $b);
    }

    /**
     * Append one bitstream created from bytes to another
     * @param array $bitstream original bitstream
     * @param int   $size      size
     * @param array $data      bytes
     * @return array bitstream
     */
    protected function appendBytes($bitstream, $size, $data)
    {
        if ($size == 0) {
            return 0;
        }
        $b = $this->newFromBytes($size, $data);
        return $this->appendBitstream($bitstream, $b);
    }

    /**
     * Convert bitstream to bytes
     * @param array $bitStream original bitstream
     * @return array of bytes
     */
    protected function bitstreamToByte($bitStream)
    {
        if (is_null($bitStream)) {
            return null;
        }
        $size = count($bitStream);
        if ($size == 0) {
            return [];
        }
        $data  = array_fill(0, (int) (($size + 7) / 8), 0);
        $bytes = (int) ($size / 8);
        $p     = 0;
        for ($i = 0; $i < $bytes; $i++) {
            $v = 0;
            for ($j = 0; $j < 8; $j++) {
                $v = $v << 1;
                $v |= $bitStream[$p];
                $p++;
            }
            $data[$i] = $v;
        }
        if ($size & 7) {
            $v = 0;
            for ($j = 0; $j < ($size & 7); $j++) {
                $v = $v << 1;
                $v |= $bitStream[$p];
                $p++;
            }
            $data[$bytes] = $v;
        }
        return $data;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - -

    // QRspec

    /**
     * Replace a value on the array at the specified position
     * @param array  $srctab
     * @param int    $x       X position
     * @param int    $y       Y position
     * @param string $repl    value to replace
     * @param int    $replLen length of the repl string
     * @return array srctab
     */
    protected function qrstrset($srctab, $x, $y, $repl, $replLen = false)
    {
        $srctab[$y] = substr_replace($srctab[$y], ($replLen !== false) ? substr($repl, 0, $replLen) : $repl, $x, ($replLen !== false) ? $replLen : strlen($repl));
        return $srctab;
    }

    /**
     * Return maximum data code length (bytes) for the version.
     * @param int $version version
     * @param int $level   error correction level
     * @return int maximum size (bytes)
     */
    protected function getDataLength($version, $level)
    {
        return $this->capacity[$version][self::COUNT] - $this->capacity[$version][self::LEVEL][$level];
    }

    /**
     * Return a version number that satisfies the input code length.
     * @param int $size  input code length (bytes)
     * @param int $level error correction level
     * @return int version number
     */
    protected function getMinimumVersion($size, $level)
    {
        for ($i = 1; $i <= self::MAX_VERSION; ++$i) {
            $words = ($this->capacity[$i][self::COUNT] - $this->capacity[$i][self::LEVEL][$level]);
            if ($words >= $size) {
                return $i;
            }
        }
        // the size of input data is greater than QR capacity, try to lover the error correction mode
        return -1;
    }

    /**
     * Return the size of length indicator for the mode and version.
     * @param int $mode    encoding mode
     * @param int $version version
     * @return int the size of the appropriate length indicator (bits).
     */
    protected function lengthIndicator($mode, $version)
    {
        if ($mode == self::STRUCTURED) {
            return 0;
        }
        if ($version <= 9) {
            $l = 0;
        }
        elseif ($version <= 26) {
            $l = 1;
        }
        else {
            $l = 2;
        }
        return $this->lengthTableBits[$mode][$l];
    }

    /**
     * Return the maximum length for the mode and version.
     * @param int $mode    encoding mode
     * @param int $version version
     * @return int the maximum length (bytes)
     */
    protected function maximumWords($mode, $version)
    {
        if ($mode == self::STRUCTURED) {
            return 3;
        }
        if ($version <= 9) {
            $l = 0;
        }
        else if ($version <= 26) {
            $l = 1;
        }
        else {
            $l = 2;
        }
        $bits  = $this->lengthTableBits[$mode][$l];
        $words = (1 << $bits) - 1;
        if ($mode == self::KANJI) {
            $words *= 2; // the number of bytes is required
        }
        return $words;
    }

    /**
     * Return an array of ECC specification.
     * @param int   $version version
     * @param int   $level   error correction level
     * @param array $spec    an array of ECC specification contains as following: {# of type1 blocks, # of data code, # of ecc code, # of type2 blocks, # of data code}
     * @return array spec
     */
    protected function getEccSpec($version, $level, $spec)
    {
        if (count($spec) < 5) {
            $spec = [0, 0, 0, 0, 0];
        }
        $b1   = $this->eccCodes[$version][$level][0];
        $b2   = $this->eccCodes[$version][$level][1];
        $data = $this->getDataLength($version, $level);
        $ecc  = $this->capacity[$version][self::LEVEL][$level];
        if ($b2 == 0) {
            $spec[0] = $b1;
            $spec[1] = (int) ($data / $b1);
            $spec[2] = (int) ($ecc / $b1);
            $spec[3] = 0;
            $spec[4] = 0;
        }
        else {
            $spec[0] = $b1;
            $spec[1] = (int) ($data / ($b1 + $b2));
            $spec[2] = (int) ($ecc / ($b1 + $b2));
            $spec[3] = $b2;
            $spec[4] = $spec[1] + 1;
        }
        return $spec;
    }

    /**
     * Put an alignment marker.
     * @param array $frame frame
     * @param int   $ox    X center coordinate of the pattern
     * @param int   $oy    Y center coordinate of the pattern
     * @return array frame
     */
    protected function putAlignmentMarker($frame, $ox, $oy)
    {
        $finder = [
            "\xa1\xa1\xa1\xa1\xa1",
            "\xa1\xa0\xa0\xa0\xa1",
            "\xa1\xa0\xa1\xa0\xa1",
            "\xa1\xa0\xa0\xa0\xa1",
            "\xa1\xa1\xa1\xa1\xa1"
        ];
        $yStart = $oy - 2;
        $xStart = $ox - 2;
        for ($y = 0; $y < 5; $y++) {
            $frame = $this->qrstrset($frame, $xStart, $yStart + $y, $finder[$y]);
        }
        return $frame;
    }

    /**
     * Put an alignment pattern.
     * @param int   $version version
     * @param array $frame   frame
     * @param int   $width   width
     * @return array frame
     */
    protected function putAlignmentPattern($version, $frame, $width)
    {
        if ($version < 2) {
            return $frame;
        }
        $d = $this->alignmentPattern[$version][1] - $this->alignmentPattern[$version][0];
        if ($d < 0) {
            $w = 2;
        }
        else {
            $w = (int) (($width - $this->alignmentPattern[$version][0]) / $d + 2);
        }
        if ($w * $w - 3 == 1) {
            $x     = $this->alignmentPattern[$version][0];
            $y     = $this->alignmentPattern[$version][0];
            $frame = $this->putAlignmentMarker($frame, $x, $y);
            return $frame;
        }
        $cx = $this->alignmentPattern[$version][0];
        $wo = $w - 1;
        for ($x = 1; $x < $wo; ++$x) {
            $frame = $this->putAlignmentMarker($frame, 6, $cx);
            $frame = $this->putAlignmentMarker($frame, $cx, 6);
            $cx    += $d;
        }
        $cy = $this->alignmentPattern[$version][0];
        for ($y = 0; $y < $wo; ++$y) {
            $cx = $this->alignmentPattern[$version][0];
            for ($x = 0; $x < $wo; ++$x) {
                $frame = $this->putAlignmentMarker($frame, $cx, $cy);
                $cx    += $d;
            }
            $cy += $d;
        }
        return $frame;
    }

    /**
     * Return BCH encoded version information pattern that is used for the symbol of version 7 or greater. Use lower 18 bits.
     * @param int $version version
     * @return string BCH encoded version information pattern
     */
    protected function getVersionPattern($version)
    {
        if (($version < 7) or ($version > self::MAX_VERSION)) {
            return 0;
        }
        return $this->versionPattern[($version - 7)];
    }

    /**
     * Return BCH encoded format information pattern.
     * @param int $level error correction level
     * @param int $mask
     * @return string BCH encoded format information pattern
     */
    protected function formatInfo(int $level, int $mask)
    {
        $oobIndex = ($mask < 0 or $mask > 7);
        $oobLevel = ($level < 0 or $level > 3);

        if ($oobIndex or $oobLevel) {
            return 0;
        }

        return $this->formatInfo[$level][$mask];
    }

    /**
     * Put a finder pattern.
     * @param array $frame frame
     * @param int   $ox    X center coordinate of the pattern
     * @param int   $oy    Y center coordinate of the pattern
     * @return array frame
     */
    protected function putFinderPattern($frame, $ox, $oy)
    {
        $finder = [
            "\xc1\xc1\xc1\xc1\xc1\xc1\xc1",
            "\xc1\xc0\xc0\xc0\xc0\xc0\xc1",
            "\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
            "\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
            "\xc1\xc0\xc1\xc1\xc1\xc0\xc1",
            "\xc1\xc0\xc0\xc0\xc0\xc0\xc1",
            "\xc1\xc1\xc1\xc1\xc1\xc1\xc1"
        ];
        for ($y = 0; $y < 7; $y++) {
            $frame = $this->qrstrset($frame, $ox, ($oy + $y), $finder[$y]);
        }
        return $frame;
    }

    /**
     * Return a copy of initialized frame.
     * @param int $version version
     * @return array array of unsigned char.
     */
    protected function createFrame($version)
    {
        $width     = $this->capacity[$version][self::WIDTH];
        $frameLine = str_repeat("\0", $width);
        $frame     = array_fill(0, $width, $frameLine);
        // Finder pattern
        $frame = $this->putFinderPattern($frame, 0, 0);
        $frame = $this->putFinderPattern($frame, $width - 7, 0);
        $frame = $this->putFinderPattern($frame, 0, $width - 7);
        // Separator
        $yOffset = $width - 7;
        for ($y = 0; $y < 7; ++$y) {
            $frame[$y][7]          = "\xc0";
            $frame[$y][$width - 8] = "\xc0";
            $frame[$yOffset][7]    = "\xc0";
            ++$yOffset;
        }
        $setPattern = str_repeat("\xc0", 8);
        $frame      = $this->qrstrset($frame, 0, 7, $setPattern);
        $frame      = $this->qrstrset($frame, $width - 8, 7, $setPattern);
        $frame      = $this->qrstrset($frame, 0, $width - 8, $setPattern);
        // Format info
        $setPattern = str_repeat("\x84", 9);
        $frame      = $this->qrstrset($frame, 0, 8, $setPattern);
        $frame      = $this->qrstrset($frame, $width - 8, 8, $setPattern, 8);
        $yOffset    = $width - 8;
        for ($y = 0; $y < 8; ++$y, ++$yOffset) {
            $frame[$y][8]       = "\x84";
            $frame[$yOffset][8] = "\x84";
        }
        // Timing pattern
        $wo = $width - 15;
        for ($i = 1; $i < $wo; ++$i) {
            $frame[6][7 + $i] = chr(0x90 | ($i & 1));
            $frame[7 + $i][6] = chr(0x90 | ($i & 1));
        }
        // Alignment pattern
        $frame = $this->putAlignmentPattern($version, $frame, $width);
        // Version information
        if ($version >= 7) {
            $vinf = $this->getVersionPattern($version);
            $v    = $vinf;
            for ($x = 0; $x < 6; ++$x) {
                for ($y = 0; $y < 3; ++$y) {
                    $frame[($width - 11) + $y][$x] = chr(0x88 | ($v & 1));
                    $v                             = $v >> 1;
                }
            }
            $v = $vinf;
            for ($y = 0; $y < 6; ++$y) {
                for ($x = 0; $x < 3; ++$x) {
                    $frame[$y][$x + ($width - 11)] = chr(0x88 | ($v & 1));
                    $v                             = $v >> 1;
                }
            }
        }
        // and a little bit...
        $frame[$width - 8][8] = "\x81";
        return $frame;
    }

    /**
     * Set new frame for the specified version.
     * @param int $version version
     * @return array|null array of unsigned char.
     */
    protected function newFrame(int $version): array|null
    {
        if ($version < 1 or $version > self::MAX_VERSION) {
            return null;
        }

        if (!isset($this->frames[$version])) {
            $this->frames[$version] = $this->createFrame($version);
        }
        if (is_null($this->frames[$version])) {
            return null;
        }
        return $this->frames[$version];
    }

    /**
     * Return block number 0
     * @param array $spec
     * @return int value
     */
    protected function rsBlockNum($spec)
    {
        return ($spec[0] + $spec[3]);
    }

    /**
     * Return block number 1
     * @param array $spec
     * @return int value
     */
    protected function rsBlockNum1($spec)
    {
        return $spec[0];
    }

    /**
     * Return data codes 1
     * @param array $spec
     * @return int value
     */
    protected function rsDataCodes1($spec)
    {
        return $spec[1];
    }

    /**
     * Return ecc codes 1
     * @param array $spec
     * @return int value
     */
    protected function rsEccCodes1($spec)
    {
        return $spec[2];
    }

    /**
     * Return block number 2
     * @param array $spec
     * @return int value
     */
    protected function rsBlockNum2($spec)
    {
        return $spec[3];
    }

    /**
     * Return data codes 2
     * @param array $spec
     * @return int value
     */
    protected function rsDataCodes2($spec)
    {
        return $spec[4];
    }

    /**
     * Return ecc codes 2
     * @param array $spec
     * @return int value
     */
    protected function rsEccCodes2($spec)
    {
        return $spec[2];
    }

    /**
     * Return data length
     * @param array $spec
     * @return int value
     */
    protected function rsDataLength($spec)
    {
        return ($spec[0] * $spec[1]) + ($spec[3] * $spec[4]);
    }

    /**
     * Return ecc length
     * @param array $spec
     * @return int value
     */
    protected function rsEccLength($spec)
    {
        return ($spec[0] + $spec[3]) * $spec[2];
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - -

    // QRrs

    /**
     * Initialize a Reed-Solomon codec and add it to existing rsitems
     * @param int $symsize symbol size, bits
     * @param int $gfpoly  Field generator polynomial coefficients
     * @param int $fcr     first root of RS code generator polynomial, index form
     * @param int $prim    primitive element to generate polynomial roots
     * @param int $nroots  RS code generator polynomial degree (number of roots)
     * @param int $pad     padding bytes at front of shortened block
     * @return array Array of RS values:<ul><li>mm = Bits per symbol;</li><li>nn = Symbols per block;</li><li>alpha_to = log lookup table array;</li><li>index_of = Antilog lookup table array;</li><li>genpoly = Generator polynomial array;</li><li>nroots = Number of generator;</li><li>roots = number of parity symbols;</li><li>fcr = First consecutive root, index form;</li><li>prim = Primitive element, index form;</li><li>iprim = prim-th root of 1, index form;</li><li>pad = Padding bytes in shortened block;</li><li>gfpoly</ul>.
     */
    protected function init_rs($symsize, $gfpoly, $fcr, $prim, $nroots, $pad)
    {
        foreach ($this->rsItems as $rs) {
            if (($rs['pad'] != $pad) or ($rs['nroots'] != $nroots) or ($rs['mm'] != $symsize)
                or ($rs['gfpoly'] != $gfpoly) or ($rs['fcr'] != $fcr) or ($rs['prim'] != $prim)) {
                continue;
            }
            return $rs;
        }
        $rs = $this->init_rs_char($symsize, $gfpoly, $fcr, $prim, $nroots, $pad);
        array_unshift($this->rsItems, $rs);
        return $rs;
    }

    // - - - - - - - - - - - - - - - - - - - - - - - - -

    // QRrsItem

    /**
     * modnn
     * @param array $rs RS values
     * @param int   $x  X position
     * @return int X osition
     */
    protected function modnn($rs, $x)
    {
        while ($x >= $rs['nn']) {
            $x -= $rs['nn'];
            $x = ($x >> $rs['mm']) + ($x & $rs['nn']);
        }
        return $x;
    }

    /**
     * Initialize a Reed-Solomon codec and returns an array of values.
     * @param int $symsize symbol size, bits
     * @param int $gfpoly  Field generator polynomial coefficients
     * @param int $fcr     first root of RS code generator polynomial, index form
     * @param int $prim    primitive element to generate polynomial roots
     * @param int $nroots  RS code generator polynomial degree (number of roots)
     * @param int $pad     padding bytes at front of shortened block
     * @return array Array of RS values:<ul><li>mm = Bits per symbol;</li><li>nn = Symbols per block;</li><li>alpha_to = log lookup table array;</li><li>index_of = Antilog lookup table array;</li><li>genpoly = Generator polynomial array;</li><li>nroots = Number of generator;</li><li>roots = number of parity symbols;</li><li>fcr = First consecutive root, index form;</li><li>prim = Primitive element, index form;</li><li>iprim = prim-th root of 1, index form;</li><li>pad = Padding bytes in shortened block;</li><li>gfpoly</ul>.
     */
    protected function init_rs_char($symsize, $gfpoly, $fcr, $prim, $nroots, $pad)
    {
        // Based on Reed solomon encoder by Phil Karn, KA9Q (GNU-LGPLv2)
        $rs = null;
        // Check parameter ranges
        if (($symsize < 0) or ($symsize > 8)) {
            return $rs;
        }
        if (($fcr < 0) or ($fcr >= (1 << $symsize))) {
            return $rs;
        }
        if (($prim <= 0) or ($prim >= (1 << $symsize))) {
            return $rs;
        }
        if (($nroots < 0) or ($nroots >= (1 << $symsize))) {
            return $rs;
        }
        if (($pad < 0) or ($pad >= ((1 << $symsize) - 1 - $nroots))) {
            return $rs;
        }
        $rs             = [];
        $rs['mm']       = $symsize;
        $rs['nn']       = (1 << $symsize) - 1;
        $rs['pad']      = $pad;
        $rs['alpha_to'] = array_fill(0, ($rs['nn'] + 1), 0);
        $rs['index_of'] = array_fill(0, ($rs['nn'] + 1), 0);
        // PHP style macro replacement ;)
        $NN =& $rs['nn'];
        $A0 =& $NN;
        // Generate Galois field lookup tables
        $rs['index_of'][0]   = $A0; // log(zero) = -inf
        $rs['alpha_to'][$A0] = 0; // alpha**-inf = 0
        $sr                  = 1;
        for ($i = 0; $i < $rs['nn']; ++$i) {
            $rs['index_of'][$sr] = $i;
            $rs['alpha_to'][$i]  = $sr;
            $sr                  <<= 1;
            if ($sr & (1 << $symsize)) {
                $sr ^= $gfpoly;
            }
            $sr &= $rs['nn'];
        }
        if ($sr != 1) {
            // field generator polynomial is not primitive!
            return null;
        }
        // Form RS code generator polynomial from its roots
        $rs['genpoly'] = array_fill(0, ($nroots + 1), 0);
        $rs['fcr']     = $fcr;
        $rs['prim']    = $prim;
        $rs['nroots']  = $nroots;
        $rs['gfpoly']  = $gfpoly;
        // Find prim-th root of 1, used in decoding
        for ($iprim = 1; ($iprim % $prim) != 0; $iprim += $rs['nn']) {
            ; // intentional empty-body loop!
        }
        $rs['iprim']      = (int) ($iprim / $prim);
        $rs['genpoly'][0] = 1;
        for ($i = 0, $root = $fcr * $prim; $i < $nroots; $i++, $root += $prim) {
            $rs['genpoly'][$i + 1] = 1;
            // Multiply rs->genpoly[] by  @**(root + x)
            for ($j = $i; $j > 0; --$j) {
                if ($rs['genpoly'][$j] != 0) {
                    $rs['genpoly'][$j] = $rs['genpoly'][$j - 1] ^ $rs['alpha_to'][$this->modnn($rs, $rs['index_of'][$rs['genpoly'][$j]] + $root)];
                }
                else {
                    $rs['genpoly'][$j] = $rs['genpoly'][$j - 1];
                }
            }
            // rs->genpoly[0] can never be zero
            $rs['genpoly'][0] = $rs['alpha_to'][$this->modnn($rs, $rs['index_of'][$rs['genpoly'][0]] + $root)];
        }
        // convert rs->genpoly[] to index form for quicker encoding
        for ($i = 0; $i <= $nroots; ++$i) {
            $rs['genpoly'][$i] = $rs['index_of'][$rs['genpoly'][$i]];
        }
        return $rs;
    }

    /**
     * Encode a Reed-Solomon codec and returns the parity array
     * @param array $rs     RS values
     * @param array $data   data
     * @param array $parity parity
     * @return array
     */
    protected function encode_rs_char($rs, $data, $parity)
    {
        $MM       =& $rs['mm']; // bits per symbol
        $NN       =& $rs['nn']; // the total number of symbols in a RS block
        $ALPHA_TO =& $rs['alpha_to']; // the address of an array of NN elements to convert Galois field elements in index (log) form to polynomial form
        $INDEX_OF =& $rs['index_of']; // the address of an array of NN elements to convert Galois field elements in polynomial form to index (log) form
        $GENPOLY  =& $rs['genpoly']; // an array of NROOTS+1 elements containing the generator polynomial in index form
        $NROOTS   =& $rs['nroots']; // the number of roots in the RS code generator polynomial, which is the same as the number of parity symbols in a block
        $FCR      =& $rs['fcr']; // first consecutive root, index form
        $PRIM     =& $rs['prim']; // primitive element, index form
        $IPRIM    =& $rs['iprim']; // prim-th root of 1, index form
        $PAD      =& $rs['pad']; // the number of pad symbols in a block
        $A0       =& $NN;
        $parity   = array_fill(0, $NROOTS, 0);
        for ($i = 0; $i < ($NN - $NROOTS - $PAD); $i++) {
            $feedback = $INDEX_OF[$data[$i] ^ $parity[0]];
            if ($feedback != $A0) {
                // feedback term is non-zero
                // This line is unnecessary when GENPOLY[NROOTS] is unity, as it must
                // always be for the polynomials constructed by init_rs()
                $feedback = $this->modnn($rs, $NN - $GENPOLY[$NROOTS] + $feedback);
                for ($j = 1; $j < $NROOTS; ++$j) {
                    $parity[$j] ^= $ALPHA_TO[$this->modnn($rs, $feedback + $GENPOLY[($NROOTS - $j)])];
                }
            }
            // Shift
            array_shift($parity);
            if ($feedback != $A0) {
                array_push($parity, $ALPHA_TO[$this->modnn($rs, $feedback + $GENPOLY[0])]);
            }
            else {
                array_push($parity, 0);
            }
        }
        return $parity;
    }
}