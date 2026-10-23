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

    private const ALPHANUMERIC = 1, EIGHT_BIT = 2, MULTIBYTE = 3, NORMAL = -1, NUMERIC = 0, STRUCTURED = 4;

    /**
     * Encoding modes:
     *     ALPHANUMERIC => 1  : (0-9A-Z $%*+-./:) 45characters. 2 characters / 11 bits ~4296 characters.
     *     EIGHT_BIT    => 2  : 8 bits. ~2953 characters.
     *     MULTIBYTE    => 3  : 13 bits ~1817 characters.
     *     NORMAL       => -1 : It's a mystery!
     *     NUMERIC      => 0  : 3 characters / 10 bits ~7089 characters.
     */
    private const MODES = [self::ALPHANUMERIC, self::EIGHT_BIT, self::MULTIBYTE, self::NORMAL, self::NUMERIC, self::STRUCTURED];

    private const HIGH = 3, LOW = 0, MEDIUM = 1, QUARTER = 2;

    /**
     * Error correction levels:
     *     LOW     => 0 : < 7%
     *     MEDIUM  => 1 : < 15%
     *     QUARTER => 2 : < 25%
     *     HIGH    => 3 : < 30%
     */
    private const LEVELS = ['H' => self::HIGH, 'L' => self::LOW, 'M' => self::MEDIUM, 'Q' => self::QUARTER];

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

    private const DEFAULT_MASK = 2, HEADER_BITS = 20, RANDOM_MASK = 2;

    /**
     * Descriptions for the properties of a Reed-Solomon Block. Allows descriptions to be referenced and not put directly into every RS docblock.
     * Based on Reed solomon encoder by Phil Karn, KA9Q (GNU-LGPLv2)
     */
    private const RS_DESCRIPTIONS = [
        'alphaTo'        => 'the address of an array of NN elements to convert Galois field elements in index (log) form to polynomial form',
        'symbolSize'     => 'bits per symbol',
        'fcRoot'         => 'first consecutive root, index form',
        'fgPolynomial'   => 'field generator polynomial coefficient',
        'genPolynomial'  => 'an array of numberRoots + 1 elements containing the generator polynomial in index form',
        'indexOf'        => 'the address of an array of NN elements to convert Galois field elements in polynomial form to index (log) form',
        'numberRoots'    => 'the number of roots in the RS code generator polynomial, which is the same as the number of parity symbols in a block',
        'symbolsInBlock' => 'the total number of symbols in a RS block',
        'paddingSize'    => 'the number padding bytes at the front of a shortened block',
        'pElement'       => 'primitive element, index form',
        'pRoot'          => 'prim-th root of 1, index form',
    ];

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

    private array $formatInfo = [
        [0x77c4, 0x72f3, 0x7daa, 0x789d, 0x662f, 0x6318, 0x6c41, 0x6976],
        [0x5412, 0x5125, 0x5e7c, 0x5b4b, 0x45f9, 0x40ce, 0x4f97, 0x4aa0],
        [0x355f, 0x3068, 0x3f31, 0x3a06, 0x24b4, 0x2183, 0x2eda, 0x2bed],
        [0x1689, 0x13be, 0x1ce7, 0x19d0, 0x0762, 0x0255, 0x0d0c, 0x083b],
    ];

    private array $frame = [];

    private array $frames = [];

    protected array $items = [];

    /** Array Length indicator. */
    protected array $lengthTableBits = [[10, 12, 14], [9, 11, 13], [8, 16, 16], [8, 10, 12]];

    private int $mode = self::EIGHT_BIT;

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
     * @param string $content the content to represent using QR code
     * @param string $ecLevel error correction level
     */
    public function __construct(string $content, string $ecLevel = 'L')
    {
        if (empty($content)) {
            return;
        }

        $this->dataString = $content;
        $this->ecLevel    = self::LEVELS[$ecLevel] ?: self::LOW;

        if (!$this->chunk()) {
            return;
        }

        $this->encodeMask();

        if (empty($this->data)) {
            return;
        }

        $qrTab = $this->binarize($this->data);
        $size  = count($qrTab);

        $barcode = ['barcode' => [], 'columnCount' => $size, 'rowCount' => $size];

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
     * Chunks segments of the content. Returns true if the length of the chunks equals the length of the data string. Inferred valid resolution.
     * @return bool
     */
    private function chunk(): bool
    {
        while (strlen($this->dataString) > 0) {
            $mode = $this->identifyMode(0);
            switch ($mode) {
                case self::NUMERIC:
                {
                    $length = $this->chunkNumeric();
                    break;
                }
                case self::ALPHANUMERIC:
                {
                    $length = $this->chunkAlphaNumeric();
                    break;
                }
                default:
                {
                    $length = $this->chunkEightBit();
                    break;
                }
            }

            if ($length <= 0) {
                return $length === 0;
            }

            $this->dataString = substr($this->dataString, $length);
        }

        return true;
    }

    /**
     * Chunks alphanumeric segments of the content. Returns the total length of the segments chunked.
     * @return int
     */
    private function chunkAlphaNumeric(): int
    {
        $alphaLength   = $this->lengthIndicator(self::ALPHANUMERIC, $this->version);
        $numericLength = $this->lengthIndicator(self::NUMERIC, $this->version);
        $position      = 1;

        while ($this->isAlphaNumeric($this->dataString, $position)) {
            if ($this->isNumeric($this->dataString, $position)) {
                $current = $position;
                while ($this->isNumeric($this->dataString, $current)) {
                    $current++;
                }
                $dif = $this->estimateAlphaNumeric($position) // + 4 + la
                    + $this->estimateNumeric($current - $position) + 4 + $numericLength
                    - $this->estimateAlphaNumeric($current); // - 4 - la
                if ($dif < 0) {
                    break;
                }
                else {
                    $position = $current;
                }
            }
            else {
                $position++;
            }
        }

        $length = $position;
        if (!$this->isAlphaNumeric($this->dataString, $position)) {
            $dif = $this->estimateAlphaNumeric($length) + 4 + $alphaLength + 8 - (($length + 1) * 8);
            if ($dif > 0) {
                return $this->chunkEightBit();
            }
        }

        $this->items = $this->appendNewInputItem($this->items, self::ALPHANUMERIC, $length, str_split($this->dataString));

        return $length;
    }

    /**
     * Chunks eight-bit segments of the content. Returns the total length of the segments chunked.
     * @return int
     */
    private function chunkEightBit(): int
    {
        $alphaLength   = $this->lengthIndicator(self::ALPHANUMERIC, $this->version);
        $numericLength = $this->lengthIndicator(self::NUMERIC, $this->version);
        $position      = 1;
        $dataStrLen    = strlen($this->dataString);

        while ($position < $dataStrLen) {
            $mode = $this->identifyMode($position);
            if ($mode == self::MULTIBYTE) {
                break;
            }
            if ($mode == self::NUMERIC) {
                $current = $position;
                while ($this->isNumeric($this->dataString, $current)) {
                    $current++;
                }
                $difference = ($position * 8) + $this->estimateNumeric($current - $position) + 4 + $numericLength - ($current * 8);
                if ($difference < 0) {
                    break;
                }
                else {
                    $position = $current;
                }
            }
            elseif ($mode == self::ALPHANUMERIC) {
                $current = $position;
                while ($this->isAlphaNumeric($this->dataString, $current)) {
                    $current++;
                }
                $difference = ($position * 8) + $this->estimateAlphaNumeric($current - $position) + 4 + $alphaLength - ($current * 8);
                if ($difference < 0) {
                    break;
                }
                else {
                    $position = $current;
                }
            }
            else {
                $position++;
            }
        }

        $length      = $position;
        $this->items = $this->appendNewInputItem($this->items, self::EIGHT_BIT, $length, str_split($this->dataString));
        return $length;
    }

    /**
     * Chunks numeric segments of the content. Returns the total length of the segments chunked.
     * @return int
     */
    private function chunkNumeric(): int
    {
        $numericLength = $this->lengthIndicator(self::NUMERIC, $this->version);
        $position      = 0;

        while ($this->isNumeric($this->dataString, $position)) {
            $position++;
        }

        $length = $position;
        $mode   = $this->identifyMode($position);

        if ($mode === self::EIGHT_BIT) {
            $difference = $this->estimateNumeric($length) + 4 + $numericLength + 8 - (($length + 1) * 8);
            if ($difference > 0) {
                return $this->chunkEightBit();
            }
        }

        if ($mode === self::ALPHANUMERIC) {
            $difference = $this->estimateNumeric($length) + 4 + $numericLength
                + $this->estimateAlphaNumeric(1)
                - $this->estimateAlphaNumeric($length + 1);
            if ($difference > 0) {
                return $this->chunkAlphaNumeric();
            }
        }

        $this->items = $this->appendNewInputItem($this->items, self::NUMERIC, $length, str_split($this->dataString));
        return $length;
    }

    /**
     * Encode item data as an alphanumeric value in the item's and return modified item.
     * @param array $item
     * @param int   $version
     * @return array
     */
    private function encodeAlphaNumeric(array $item, int $version): array
    {
        $words = (int) ($item['size'] / 2);

        $item['bitStream'] = [];
        $item['bitStream'] = $this->appendNum($item['bitStream'], 4, 0x02);
        $item['bitStream'] = $this->appendNum($item['bitStream'], $this->lengthIndicator(self::ALPHANUMERIC, $version), $item['size']);

        for ($index = 0; $index < $words; ++$index) {
            $value = $this->lookAnTable(ord($item['data'][$index * 2])) * 45;
            $value += $this->lookAnTable(ord($item['data'][($index * 2) + 1]));

            $item['bitStream'] = $this->appendNum($item['bitStream'], 11, $value);
        }

        if ($item['size'] & 1) {
            $value = $this->lookAnTable(ord($item['data'][($words * 2)]));

            $item['bitStream'] = $this->appendNum($item['bitStream'], 6, $value);
        }
        return $item;
    }

    /**
     * Encode item data to its bitStream property and return the item.
     * @param array $item
     * @param int   $version
     * @return array input item
     */
    private function encodeBitStream(array $item, int $version): array
    {
        $words = $this->maximumWords($item['mode'], $version);

        if ($item['size'] > $words) {
            $st1 = $this->newInputItem($item['mode'], $words, $item['data']);
            $st2 = $this->newInputItem($item['mode'], $item['size'] - $words, array_slice($item['data'], $words));
            $st1 = $this->encodeBitStream($st1, $version);
            $st2 = $this->encodeBitStream($st2, $version);

            $item['bitStream'] = [];
            $item['bitStream'] = $this->appendBitstream($item['bitStream'], $st1['bitStream']);
            $item['bitStream'] = $this->appendBitstream($item['bitStream'], $st2['bitStream']);
            return $item;
        }

        // Re-initialized directly here for default case.
        $item['bitStream'] = [];
        return match ($item['mode']) {
            self::ALPHANUMERIC => $this->encodeAlphaNumeric($item, $version),
            self::EIGHT_BIT => $this->encodeEightBit($item, $version),
            self::MULTIBYTE => $this->encodeModeMultiByte($item, $version),
            self::NUMERIC => $this->encodeNumeric($item, $version),
            self::STRUCTURED => $this->encodeStructure($item),
            default => $item,
        };
    }

    /**
     * Encode item data as an eight-bit value and return modified item.
     * @param array $item
     * @param int   $version
     * @return array
     */
    private function encodeEightBit(array $item, int $version): array
    {
        $item['bitStream'] = [];
        $item['bitStream'] = $this->appendNum($item['bitStream'], 4, 0x4);
        $item['bitStream'] = $this->appendNum($item['bitStream'], $this->lengthIndicator(self::EIGHT_BIT, $version), $item['size']);

        for ($index = 0; $index < $item['size']; ++$index) {
            $item['bitStream'] = $this->appendNum($item['bitStream'], 8, ord($item['data'][$index]));
        }

        return $item;
    }

    /**
     * Encode item data as a multibyte value and return modified item.
     * @param array $item
     * @param int   $version
     * @return array
     */
    private function encodeModeMultiByte(array $item, int $version): array
    {
        $item['bitStream'] = [];
        $item['bitStream'] = $this->appendNum($item['bitStream'], 4, 0x8);
        $item['bitStream'] = $this->appendNum($item['bitStream'], $this->lengthIndicator(self::MULTIBYTE, $version), (int) ($item['size'] / 2));

        for ($i = 0; $i < $item['size']; $i += 2) {
            $value   = (ord($item['data'][$i]) << 8) | ord($item['data'][$i + 1]);
            $value   -= $value <= 0x9ffc ? 0x8140 : 0xc140;
            $unknown = ($value >> 8) * 0xc0;
            $value   = ($value & 0xff) + $unknown;

            $item['bitStream'] = $this->appendNum($item['bitStream'], 13, $value);
        }

        return $item;
    }

    /**
     * Encode item data as a numeric value and return modified item.
     * @param array $item
     * @param int   $version
     * @return array
     */
    private function encodeNumeric(array $item, int $version): array
    {
        $value = 0x1;
        $words = (int) ($item['size'] / 3);

        $item['bitStream'] = [];
        $item['bitStream'] = $this->appendNum($item['bitStream'], 4, $value);
        $item['bitStream'] = $this->appendNum($item['bitStream'], $this->lengthIndicator(self::NUMERIC, $version), $item['size']);

        for ($i = 0; $i < $words; ++$i) {
            $value             = (ord($item['data'][$i * 3]) - ord('0')) * 100;
            $value             += (ord($item['data'][$i * 3 + 1]) - ord('0')) * 10;
            $value             += (ord($item['data'][$i * 3 + 2]) - ord('0'));
            $item['bitStream'] = $this->appendNum($item['bitStream'], 10, $value);
        }

        if ($item['size'] - $words * 3 == 1) {
            $value             = ord($item['data'][$words * 3]) - ord('0');
            $item['bitStream'] = $this->appendNum($item['bitStream'], 4, $value);
        }
        elseif (($item['size'] - ($words * 3)) == 2) {
            $value             = (ord($item['data'][$words * 3]) - ord('0')) * 10;
            $value             += (ord($item['data'][$words * 3 + 1]) - ord('0'));
            $item['bitStream'] = $this->appendNum($item['bitStream'], 7, $value);
        }

        return $item;
    }

    /**
     * Encode item data as a structure and return modified item.
     * @param array $item
     * @return array
     */
    private function encodeStructure(array $item): array
    {
        $item['bitStream'] = [];
        $item['bitStream'] = $this->appendNum($item['bitStream'], 4, 0x03);
        $item['bitStream'] = $this->appendNum($item['bitStream'], 4, ord($item['data'][1]) - 1);
        $item['bitStream'] = $this->appendNum($item['bitStream'], 4, ord($item['data'][0]) - 1);
        $item['bitStream'] = $this->appendNum($item['bitStream'], 8, ord($item['data'][2]));
        return $item;
    }

    /**
     * Estimates the number of bits for an alphanumeric segment of a given character count.
     * @param int $count
     * @return int
     */
    private function estimateAlphaNumeric(int $count): int
    {
        $bits = (int) ($count * 5.5);
        if ($count & 1) {
            $bits += 6;
        }
        return $bits;
    }

    /**
     * estimateBitsModeMultiByte
     * @param int $count
     * @return int number of bits
     */
    private function estimateMultiByte(int $count): int
    {
        return (int) ($count * 6.5);
    }

    /**
     * estimateBitsModeNum
     * @param int $count
     * @return int number of bits
     */
    private function estimateNumeric(int $count): int
    {
        $w    = (int) ($count / 3);
        $bits = ($w * 10);
        return match ($count - ($w * 3)) {
            1 => $bits + 4,
            2 => $bits + 7,
            default => $bits,
        };
    }

    /**
     * Searches for a frame at the given version index, initializing and appending it if non-existent.
     * @param int $version
     * @return array
     */
    private function frame(int $version): array
    {
        if ($version < 1 or $version > self::MAX_VERSION) {
            return [];
        }

        if ($frame = $this->frames[$version] ?? $this->frameInitialize($version)) {
            $this->frames[$version] = $frame;
            return $frame;
        }

        return [];
    }

    /**
     * Getter / setter for a frame value at a specified position
     * @param array    $coordinates x,y position
     * @param int|null $value       value of the character to set
     * @return int
     */
    private function frameAt(array $coordinates, int|null $value = null): int
    {
        if (is_int($value)) {
            $this->frame[$coordinates['y']][$coordinates['x']] = chr($value);
        }

        return ord($this->frame[$coordinates['y']][$coordinates['x']]);
    }

    /**
     * Adds an alignment marker to the frame.
     * @param array $frame frame
     * @param int   $x     X center coordinate of the pattern
     * @param int   $y     Y center coordinate of the pattern
     * @return void
     */
    private function frameAlignmentMarker(array &$frame, int $x, int $y): void
    {
        $finder = ["\xa1\xa1\xa1\xa1\xa1", "\xa1\xa0\xa0\xa0\xa1", "\xa1\xa0\xa1\xa0\xa1", "\xa1\xa0\xa0\xa0\xa1", "\xa1\xa1\xa1\xa1\xa1"];

        $x -= 2;
        $y -= 2;

        for ($offset = 0; $offset < 5; $offset++) {
            $frame[$y + $offset] = substr_replace($frame[$y + $offset], $finder[$offset], $x, strlen($finder[$offset]));
        }
    }

    /**
     * Adds an alignment pattern to the frame.
     * @param array &$frame
     * @param int    $version
     * @param int    $width
     * @return void
     */
    private function frameAlignmentPattern(array &$frame, int $version, int $width): void
    {
        if ($version < 2) {
            return;
        }

        $delta = $this->alignmentPattern[$version][1] - $this->alignmentPattern[$version][0];
        $width = $delta < 0 ? 2 : (int) (($width - $this->alignmentPattern[$version][0]) / $delta + 2);

        if ($width * $width - 3 === 1) {
            $x = $this->alignmentPattern[$version][0];
            $y = $this->alignmentPattern[$version][0];
            $this->frameAlignmentMarker($frame, $x, $y);
            return;
        }

        $cx = $this->alignmentPattern[$version][0];
        $wo = $width - 1;
        for ($x = 1; $x < $wo; ++$x) {
            $this->frameAlignmentMarker($frame, 6, $cx);
            $this->frameAlignmentMarker($frame, $cx, 6);
            $cx += $delta;
        }

        $cy = $this->alignmentPattern[$version][0];
        for ($y = 0; $y < $wo; ++$y) {
            $cx = $this->alignmentPattern[$version][0];
            for ($x = 0; $x < $wo; ++$x) {
                $this->frameAlignmentMarker($frame, $cx, $cy);
                $cx += $delta;
            }
            $cy += $delta;
        }
    }

    /**
     * Adds a finder pattern to the frame.
     * @param array $frame frame
     * @param int   $x     X center coordinate of the pattern
     * @param int   $y     Y center coordinate of the pattern
     * @return void
     */
    private function frameFinderPattern(array &$frame, int $x, int $y): void
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
        for ($offset = 0; $offset < 7; $offset++) {
            $frame[$y + $offset] = substr_replace($frame[$y + $offset], $finder[$offset], $x, strlen($finder[$offset]));
        }
    }

    /**
     * Initializes a frame of unsigned character values and returns it.
     * @param int $version
     * @return array
     */
    private function frameInitialize(int $version): array
    {
        $width     = $this->capacity[$version][self::WIDTH];
        $frameLine = str_repeat("\0", $width);
        $frame     = array_fill(0, $width, $frameLine);

        // Finder pattern
        $this->frameFinderPattern($frame, 0, 0);
        $this->frameFinderPattern($frame, $width - 7, 0);
        $this->frameFinderPattern($frame, 0, $width - 7);

        // Separator
        $yOffset = $width - 7;
        for ($y = 0; $y < 7; ++$y) {
            $frame[$y][7]          = "\xc0";
            $frame[$y][$width - 8] = "\xc0";
            $frame[$yOffset][7]    = "\xc0";
            ++$yOffset;
        }

        $setPattern        = str_repeat("\xc0", 8);
        $frame[7]          = substr_replace($frame[7], $setPattern, 0, strlen($setPattern));
        $frame[7]          = substr_replace($frame[7], $setPattern, $width - 8, strlen($setPattern));
        $frame[$width - 8] = substr_replace($frame[$width - 8], $setPattern, 0, strlen($setPattern));

        // Format info
        $setPattern = str_repeat("\x84", 9);
        $frame[8]   = substr_replace($frame[8], $setPattern, 0, strlen($setPattern));
        $frame[8]   = substr_replace($frame[8], substr($setPattern, 0, 8), $width - 8, 8);

        $yOffset = $width - 8;
        for ($y = 0; $y < 8; ++$y, ++$yOffset) {
            $frame[$y][8]       = "\x84";
            $frame[$yOffset][8] = "\x84";
        }

        // Timing pattern
        $wo = $width - 15;
        for ($index = 1; $index < $wo; ++$index) {
            $frame[6][7 + $index] = chr(0x90 | ($index & 1));
            $frame[7 + $index][6] = chr(0x90 | ($index & 1));
        }

        $this->frameAlignmentPattern($frame, $version, $width);

        // Version information
        if ($version >= 7) {
            $vInf = $version > self::MAX_VERSION ? 0 : $this->versionPattern[($version - 7)];

            $v = $vInf;
            for ($x = 0; $x < 6; ++$x) {
                for ($y = 0; $y < 3; ++$y) {
                    $frame[($width - 11) + $y][$x] = chr(0x88 | ($v & 1));
                    $v                             = $v >> 1;
                }
            }

            $v = $vInf;
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
     * identifyMode
     * @param int $position
     * @return int mode
     * @see self::MODES
     */
    private function identifyMode(int $position): int
    {
        if ($position >= strlen($this->dataString)) {
            return self::NORMAL;
        }

        if ($this->isNumeric($this->dataString, $position)) {
            return self::NUMERIC;
        }

        if ($this->isAlphaNumeric($this->dataString, $position)) {
            return self::ALPHANUMERIC;
        }

        return self::EIGHT_BIT;
    }

    /**
     * If the character at specified position is alphanumeric.
     * @param string $string
     * @param int    $position character position
     * @return bool
     */
    private function isAlphaNumeric(string $string, int $position): bool
    {
        if ($position >= strlen($string)) {
            return false;
        }

        return $this->lookAnTable(ord($string[$position])) >= 0;
    }

    /**
     * If the character at specified position is numeric.
     * @param string $string
     * @param int    $position character position
     * @return bool
     */
    private function isNumeric(string $string, int $position): bool
    {
        if ($position >= strlen($string)) {
            return false;
        }

        return (ord($string[$position]) >= ord('0') and ord($string[$position]) <= ord('9'));
    }

    /**
     * Creates a binary mask for the given x & y positions. Variant 0.
     * @param int $x X position
     * @param int $y Y position
     * @return int
     */
    private function mask0(int $x, int $y): int
    {
        return ($x + $y) & 1;
    }

    /**
     * Creates a binary mask for the given x & y positions. Variant 1.
     * @param int $x X position
     * @param int $y Y position
     * @return int
     */
    private function mask1(int $x, int $y): int
    {
        return ($y & 1);
    }

    /**
     * Creates a binary mask for the given x & y positions. Variant 2.
     * @param int $x X position
     * @param int $y Y position
     * @return int
     */
    private function mask2(int $x, int $y): int
    {
        return ($x % 3);
    }

    /**
     * Creates a binary mask for the given x & y positions. Variant 3.
     * @param int $x X position
     * @param int $y Y position
     * @return int
     */
    private function mask3(int $x, int $y): int
    {
        return ($x + $y) % 3;
    }

    /**
     * Creates a binary mask for the given x & y positions. Variant 4.
     * @param int $x X position
     * @param int $y Y position
     * @return int
     */
    private function mask4(int $x, int $y): int
    {
        return (((int) ($y / 2)) + ((int) ($x / 3))) & 1;
    }

    /**
     * Creates a binary mask for the given x & y positions. Variant 5.
     * @param int $x X position
     * @param int $y Y position
     * @return int
     */
    private function mask5(int $x, int $y): int
    {
        return (($x * $y) & 1) + ($x * $y) % 3;
    }

    /**
     * Creates a binary mask for the given x & y positions. Variant 6.
     * @param int $x X position
     * @param int $y Y position
     * @return int
     */
    private function mask6(int $x, int $y): int
    {
        return ((($x * $y) & 1) + ($x * $y) % 3) & 1;
    }

    /**
     * Creates a binary mask for the given x & y positions. Variant 7.
     * @param int $x X position
     * @param int $y Y position
     * @return int
     */
    private function mask7(int $x, int $y): int
    {
        return ((($x * $y) % 3) + (($x + $y) & 1)) & 1;
    }

    /**
     * Searches for a rs codec with the given properties, initializing and appending it if non-existent.
     * @param int $symbolSize
     * @param int $fgPolynomial
     * @param int $fcRoot
     * @param int $pElement
     * @param int $numberRoots
     * @param int $paddingSize
     * @return array
     * @see self::RS_DESCRIPTIONS
     */
    protected function rsCodec(int $symbolSize, int $fgPolynomial, int $fcRoot, int $pElement, int $numberRoots, int $paddingSize): array
    {
        // Search for an existing codec matching the given criteria
        foreach ($this->rsItems as $rs) {
            $wrongElement    = $rs['pElement'] !== $pElement;
            $wrongNumber     = $rs['numberRoots'] !== $numberRoots;
            $wrongPadding    = $rs['paddingSize'] !== $paddingSize;
            $wrongPolynomial = $rs['fgPolynomial'] !== $fgPolynomial;
            $wrongRoot       = $rs['fcRoot'] !== $fcRoot;
            $wrongSize       = $rs['symbolSize'] !== $symbolSize;
            if ($wrongElement or $wrongNumber or $wrongPadding or $wrongPolynomial or $wrongSize or $wrongRoot) {
                continue;
            }

            return $rs;
        }

        if ($rs = $this->rsInitializeCodec($symbolSize, $fgPolynomial, $fcRoot, $pElement, $numberRoots, $paddingSize)) {
            array_unshift($this->rsItems, $rs);
        }

        return $rs;
    }

    /**
     * Encode a Reed-Solomon codec. Returns the parity array.
     * @param array $rsValues
     * @param array $data
     * @return array
     * @see self::RS_DESCRIPTIONS
     */
    private function rsEncodeCodec(array $rsValues, array $data): array
    {
        $symbolsInBlock =& $rsValues['symbolsInBlock'];
        $alphaTo        =& $rsValues['alphaTo'];
        $indexOf        =& $rsValues['indexOf'];
        $genPolynomial  =& $rsValues['genPolynomial'];
        $numberRoots    =& $rsValues['numberRoots'];
        $paddingSize    =& $rsValues['paddingSize'];
        $A0             =& $symbolsInBlock;

        $parity = array_fill(0, $numberRoots, 0);

        for ($i = 0; $i < ($symbolsInBlock - $numberRoots - $paddingSize); $i++) {
            $feedback = $indexOf[$data[$i] ^ $parity[0]];
            if ($feedback != $A0) {
                // feedback term is non-zero
                // This line is unnecessary when genPolynomial[numberRoots] is unity, as it must
                // always be for the polynomials constructed by init_rs()
                $feedback = $this->rsRepositionX($rsValues, $symbolsInBlock - $genPolynomial[$numberRoots] + $feedback);
                for ($j = 1; $j < $numberRoots; ++$j) {
                    $parity[$j] ^= $alphaTo[$this->rsRepositionX($rsValues, $feedback + $genPolynomial[($numberRoots - $j)])];
                }
            }

            // Shift
            array_shift($parity);
            $parity[] = $feedback === $A0 ? 0 : $alphaTo[$this->rsRepositionX($rsValues, $feedback + $genPolynomial[0])];
        }

        return $parity;
    }

    /**
     * Initialize a Reed-Solomon codec and returns an array of values.
     * @param int $symbolSize
     * @param int $fgPolynomial
     * @param int $fcRoot
     * @param int $pElement
     * @param int $numberRoots
     * @param int $paddingSize
     * @return array
     * @see self::RS_DESCRIPTIONS
     */
    protected function rsInitializeCodec(int $symbolSize, int $fgPolynomial, int $fcRoot, int $pElement, int $numberRoots, int $paddingSize): array
    {

        $invalidElement = ($pElement <= 0 or $pElement >= (1 << $symbolSize));
        $invalidNumber  = ($numberRoots < 0 or $numberRoots >= (1 << $symbolSize));
        $invalidPadding = ($paddingSize < 0 or $paddingSize >= (1 << $symbolSize) - 1 - $numberRoots);
        $invalidRoot    = ($fcRoot < 0 or $fcRoot >= (1 << $symbolSize));
        $invalidSize    = ($symbolSize < 0 or $symbolSize > 8);

        if ($invalidElement or $invalidNumber or $invalidPadding or $invalidRoot or $invalidSize) {
            return [];
        }

        $rs                   = [];
        $rs['symbolSize']     = $symbolSize;
        $rs['paddingSize']    = $paddingSize;
        $rs['symbolsInBlock'] = (1 << $symbolSize) - 1;

        $rs['alphaTo'] = array_fill(0, ($rs['symbolsInBlock'] + 1), 0);
        $rs['indexOf'] = array_fill(0, ($rs['symbolsInBlock'] + 1), 0);

        // PHP style macro replacement ;)
        $symbolsInBlock =& $rs['symbolsInBlock'];
        $A0             =& $symbolsInBlock;

        // Generate Galois field lookup tables
        $rs['indexOf'][0]   = $A0; // log(zero) = -inf
        $rs['alphaTo'][$A0] = 0; // alpha**-inf = 0

        $sr = 1;
        for ($i = 0; $i < $rs['symbolsInBlock']; ++$i) {
            $rs['indexOf'][$sr] = $i;
            $rs['alphaTo'][$i]  = $sr;

            $sr <<= 1;
            if ($sr & (1 << $symbolSize)) {
                $sr ^= $fgPolynomial;
            }

            $sr &= $rs['symbolsInBlock'];
        }

        // field generator polynomial is not primitive!
        if ($sr !== 1) {
            return [];
        }

        // Form RS code generator polynomial from its roots
        $rs['fcRoot']        = $fcRoot;
        $rs['genPolynomial'] = array_fill(0, ($numberRoots + 1), 0);
        $rs['pElement']      = $pElement;
        $rs['numberRoots']   = $numberRoots;
        $rs['fgPolynomial']  = $fgPolynomial;

        // Find prim-th root of 1, used in decoding
        for ($pRoot = 1; ($pRoot % $pElement) != 0; $pRoot += $rs['symbolsInBlock']) {
            // Intentional empty-body loop
        }

        $rs['pRoot']            = (int) ($pRoot / $pElement);
        $rs['genPolynomial'][0] = 1;
        for ($outer = 0, $root = $fcRoot * $pElement; $outer < $numberRoots; $outer++, $root += $pElement) {
            $rs['genPolynomial'][$outer + 1] = 1;
            // Multiply rs->genPolynomial[] by  @**(root + x)
            for ($inner = $outer; $inner > 0; --$inner) {
                if ($rs['genPolynomial'][$inner] != 0) {
                    $rs['genPolynomial'][$inner] = $rs['genPolynomial'][$inner - 1] ^
                        $rs['alphaTo'][$this->rsRepositionX($rs, $rs['indexOf'][$rs['genPolynomial'][$inner]] + $root)];
                }
                else {
                    $rs['genPolynomial'][$inner] = $rs['genPolynomial'][$inner - 1];
                }
            }
            // rs->genPolynomial[0] can never be zero
            $rs['genPolynomial'][0] = $rs['alphaTo'][$this->rsRepositionX($rs, $rs['indexOf'][$rs['genPolynomial'][0]] + $root)];
        }

        // convert rs->genPolynomial[] to index form for quicker encoding
        for ($index = 0; $index <= $numberRoots; ++$index) {
            $rs['genPolynomial'][$index] = $rs['indexOf'][$rs['genPolynomial'][$index]];
        }

        return $rs;
    }

    /**
     * Calculates a new x positon based on the number of bits per symbol and number of symbols in block.
     * @param array $rs RS values
     * @param int   $x  X position
     * @return int
     * @see self::RS_DESCRIPTIONS
     */
    protected function rsRepositionX(array $rs, int $x): int
    {
        while ($x >= $rs['symbolsInBlock']) {
            $x -= $rs['symbolsInBlock'];
            $x = ($x >> $rs['symbolSize']) + ($x & $rs['symbolsInBlock']);
        }

        return $x;
    }

    /**
     * Write format information on frame and returns the number of black bits.
     * @param array &$frame
     * @param int    $width
     * @param int    $maskNumber masking mode number
     * @param int    $level      error correction level
     * @return int
     */
    private function writeFormatInformation(array &$frame, int $width, int $maskNumber, int $level): int
    {
        $blacks   = 0;
        $oobIndex = ($maskNumber < 0 or $maskNumber > 7);
        $oobLevel = ($level < 0 or $level > 3);
        $format   = ($oobIndex or $oobLevel) ? 0 : $this->formatInfo[$level][$maskNumber];

        for ($index = 0; $index < 8; ++$index) {
            if ($format & 1) {
                $blacks += 2;
                $value  = 0x85;
            }
            else {
                $value = 0x84;
            }

            $frame[8][$width - 1 - $index] = chr($value);

            if ($index < 6) {
                $frame[$index][8] = chr($value);
            }
            else {
                $frame[$index + 1][8] = chr($value);
            }

            $format = $format >> 1;
        }

        for ($index = 0; $index < 7; ++$index) {
            if ($format & 1) {
                $blacks += 2;
                $value  = 0x85;
            }
            else {
                $value = 0x84;
            }

            $frame[$width - 7 + $index][8] = chr($value);

            if ($index == 0) {
                $frame[8][7] = chr($value);
            }
            else {
                $frame[8][6 - $index] = chr($value);
            }

            $format = $format >> 1;
        }

        return $blacks;
    }

    //////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////TODO

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
     * Encode mask
     */
    protected function encodeMask()
    {
        $bitStream      = $this->mergeBitStream($this->items);
        $bitStream      = $this->appendPaddingBit($bitStream);
        $this->dataCode = $this->bitstreamToByte($bitStream);

        if (is_null($this->dataCode)) {
            return null;
        }
        $spec             = [0, 0, 0, 0, 0];
        $spec             = $this->getEccSpec($this->version, $this->ecLevel, $spec);
        $this->block1     = $spec[0];
        $this->dataLength = ($spec[0] * $spec[1]) + ($spec[3] * $spec[4]);
        $this->ecLength   = ($spec[0] + $spec[3]) * $spec[2];
        $this->ecCode     = array_fill(0, $this->ecLength, 0);
        $this->blocks     = $spec[0] + $spec[3];
        $ret              = $this->init($spec);
        if ($ret < 0) {
            return null;
        }
        $this->counter = 0;
        $this->width   = $this->capacity[$this->version][self::WIDTH];;
        $this->frame     = $this->frame($this->version);
        $this->x         = $this->width - 1;
        $this->y         = $this->width - 1;
        $this->direction = -1;
        $this->bit       = -1;
        // interleaved data and ecc codes
        for ($i = 0; $i < ($this->dataLength + $this->ecLength); $i++) {
            $code = $this->getCode();
            $bit  = 0x80;
            for ($j = 0; $j < 8; $j++) {
                $addr = $this->getNextPosition();
                $this->frameAt($addr, 0x02 | (($bit & $code) != 0));
                $bit = $bit >> 1;
            }
        }
        // remainder bits
        $j = $this->capacity[$this->version][self::REMAINDER];
        for ($i = 0; $i < $j; $i++) {
            $addr = $this->getNextPosition();
            $this->frameAt($addr, 0x02);
        }
        // masking
        $this->runLength = array_fill(0, self::MAX_WIDTH + 1, 0);
        $masked          = $this->mask($this->width, $this->frame, $this->ecLevel);
        if ($masked == null) {
            return null;
        }
        $this->data = $masked;
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

    /**
     * Initialize code.
     * @param array $spec array of ECC specification
     * @return int 0 in case of success, -1 in case of error
     */
    protected function init($spec)
    {
        $dl      = $spec[1];
        $el      = $spec[2];
        $rs      = $this->rsCodec(8, 0x11d, 0, 1, $el, 255 - $dl - $el);
        $blockNo = 0;
        $dataPos = 0;
        $eccPos  = 0;
        $endfor  = $spec[0];
        for ($i = 0; $i < $endfor; ++$i) {
            $this->rsBlocks[$blockNo]               = [];
            $this->rsBlocks[$blockNo]['dataLength'] = $dl;
            $this->rsBlocks[$blockNo]['data']       = array_slice($this->dataCode, $dataPos);
            $this->rsBlocks[$blockNo]['eccLength']  = $el;
            $ecc                                    = $this->rsEncodeCodec($rs, $this->rsBlocks[$blockNo]['data']);
            $this->rsBlocks[$blockNo]['ecc']        = $ecc;
            $this->ecCode                           = array_merge(array_slice($this->ecCode, 0, $eccPos), $ecc);
            $dataPos                                += $dl;
            $eccPos                                 += $el;
            $blockNo++;
        }

        if ($spec[3] === 0) {
            return 0;
        }

        $dl = $spec[4];
        $el = $spec[2];
        $rs = $this->rsCodec(8, 0x11d, 0, 1, $el, 255 - $dl - $el);
        if ($rs == null) {
            return -1;
        }
        $endfor = $spec[3];

        for ($i = 0; $i < $endfor; ++$i) {
            $this->rsBlocks[$blockNo]               = [];
            $this->rsBlocks[$blockNo]['dataLength'] = $dl;
            $this->rsBlocks[$blockNo]['data']       = array_slice($this->dataCode, $dataPos);
            $this->rsBlocks[$blockNo]['eccLength']  = $el;
            $ecc                                    = $this->rsEncodeCodec($rs, $this->rsBlocks[$blockNo]['data']);
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
        $howManuOut    = 8 - (self::RANDOM_MASK % 9);
        for ($i = 0; $i < $howManuOut; ++$i) {
            $remPos = rand(0, count($checked_masks) - 1);
            unset($checked_masks[$remPos]);
            $checked_masks = array_values($checked_masks);
        }
        $bestMask = $frame;
        foreach ($checked_masks as $maskNumber) {
            $mask    = array_fill(0, $width, str_repeat("\0", $width));
            $demerit = 0;
            $blacks  = 0;
            $blacks  = $this->makeMaskNo($maskNumber, $width, $frame, $mask);
            $blacks  += $this->writeFormatInformation($mask, $width, $maskNumber, $level);
            $blacks  = (int) (100 * $blacks / ($width * $width));
            $demerit = (int) ((int) (abs($blacks - 50) / 5) * self::MP4);
            $demerit += $this->evaluateSymbol($width, $mask);
            if ($demerit < $minDemerit) {
                $minDemerit  = $demerit;
                $bestMask    = $mask;
                $bestMaskNum = $maskNumber;
            }
        }
        return $bestMask;
    }

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
     * checkModeMultiByte
     * @param int   $size
     * @param array $data
     * @return boolean true or false
     */
    protected function checkModeMultiByte($size, $data)
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
            case self::MULTIBYTE:
            {
                return $this->checkModeMultiByte($size, $data);
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
                    $bits = $this->estimateNumeric($item['size']);
                    break;
                }
                case self::ALPHANUMERIC:
                {
                    $bits = $this->estimateAlphaNumeric($item['size']);
                    break;
                }
                case self::EIGHT_BIT:
                {
                    $bits = $item['size'] * 8;
                    break;
                }
                case self::MULTIBYTE:
                {
                    $bits = $this->estimateMultiByte($item['size']);
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
            case self::MULTIBYTE:
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
        if ($mode == self::MULTIBYTE) {
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
}