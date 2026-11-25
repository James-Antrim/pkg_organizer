<?php
/**
 * @package     Organizer
 * @extension   com_organizer
 * @author      James Antrim, <james.antrim@nm.thm.de>
 * @copyright   2026 TH Mittelhessen
 * @license     GNU GPL v.3
 * @link        www.thm.de
 * @see         PDF (http://www.tcpdf.org) => Nicola Asuni
 * @see         FPDF (http://www.fpdf.org) => Olivier Plathey
 */

namespace THM\Organizer\Views\PDF\Library\Barcodes;

use THM\Organizer\Views\PDF\Library\PDF;

/**
 * Creates array representations for common 1D barcodes:
 *  $code[
 *       'barcode'   => [
 *           $position => [ single bar or space in $position
 *               'height' => bar height in units,
 *               'position' => bar top position (0 = top, 1 = middle),
 *               'stripe' => bar type: true = bar, false = space,
 *               'width' => bar width in units,
 *           ]
 *       ]
 *      'code'      => code to be printed on text label,
 *      'maxHeight' => max barcode height,
 *      'maxWidth'  => max barcode width,
 * ]
 */
class Barcode1D
{
    use Barcoded;

    private const MIDDLE = 1, SPACE = false, STRIPE = true, TOP = 0;

    public const CODE_11 = 'CODE11', CODE_128 = 'C128', CODE_128_A = 'C128A', CODE_128_B = 'C128B', CODE_128_C = 'C128C', CODE_39 = 'C39',
        CODE_39_CS = 'C39+', CODE_39_EX = 'C39E', CODE_39_EX_CS = 'C39E+', CODE_93 = 'C93', CODABAR = 'CODABAR', EAN_2 = 'EAN2', EAN_5 = 'EAN5',
        EAN_8 = 'EAN8', EAN_13 = 'EAN13', I25 = 'I25', I25_CS = 'I25+', KLANT_INDEX = 'KIX', MSI = 'MSI', MSI_CS = 'MSI+', PHARMACODE = 'PHARMA',
        PHARMACODE_2T = 'PHARMA2T', POSTNET = 'POSTNET', PLANET = 'PLANET', RMS4CC = 'RMS4CC', S25 = 'S25', S25_CS = 'S25+', UPC_A = 'UPCA',
        UPC_E = 'UPCE';
    public const TYPES = [
        self::CODE_11 . self::CODE_128, self::CODE_128_A, self::CODE_128_B, self::CODE_128_C, self::CODE_39, self::CODE_39_CS, self::CODE_39_EX,
        self::CODE_39_EX_CS, self::CODE_93, self::CODABAR, self::EAN_2, self::EAN_5, self::EAN_8, self::EAN_13, self::I25, self::I25_CS,
        self::KLANT_INDEX, self::MSI, self::MSI_CS, self::PHARMACODE, self::PHARMACODE_2T, self::POSTNET, self::PLANET, self::RMS4CC, self::S25,
        self::S25_CS, self::UPC_A, self::UPC_E
    ];

    /**
     * Creates a 1D Barcode.
     * @param string $code code to print
     * @param string $type
     * @see self::TYPES, PDF::UNITS
     */
    public function __construct(string $code, string $type)
    {
        switch (strtoupper($type)) {
            case self::CODE_11:
                $this->Code11($code);
                return;
            case self::CODE_39:
                $this->Code39($code);
                return;
            case self::CODE_39_CS:
                $this->Code39($code, false, true);
                return;
            case self::CODE_39_EX:
                $this->Code39($code, true);
                return;
            case self::CODE_39_EX_CS:
                $this->Code39($code, true, true);
                return;
            case self::CODE_93:
                $this->Code93($code);
                return;
            case self::I25:
                $this->I25($code);
                return;
            case self::I25_CS:
                $this->I25($code, true);
                return;
            case self::CODE_128:
                $this->C128($code);
                return;
            case self::CODE_128_A:
                $this->C128($code, 'A');
                return;
            case self::CODE_128_B:
                $this->C128($code, 'B');
                return;
            case self::CODE_128_C:
                $this->C128($code, 'C');
                return;
            case self::CODABAR:
                $this->Codabar($code);
                return;
            case self::EAN_2:
                $this->EANExtensions($code, 2);
                return;
            case self::EAN_5:
                $this->EANExtensions($code);
                return;
            case self::EAN_8:
                $this->EAN13_UPCA($code, 8);
                return;
            case self::EAN_13:
                $this->EAN13_UPCA($code);
                return;
            case self::KLANT_INDEX:
                $this->RMS4CC($code, true);
                return;
            case self::MSI:
                $this->MSI($code);
                return;
            case self::MSI_CS:
                $this->MSI($code, true);
                return;
            case self::PHARMACODE:
                $this->PharmaCode($code);
                return;
            case self::PHARMACODE_2T:
                $this->Pharmacode2Track($code);
                return;
            case self::POSTNET:
                $this->Postal($code);
                return;
            case self::PLANET:
                $this->Postal($code, true);
                return;
            case self::RMS4CC:
                $this->RMS4CC($code);
                return;
            case self::S25:
                $this->S25($code);
                return;
            case self::S25_CS:
                $this->S25($code, true);
                return;
            case self::UPC_A:
                $this->EAN13_UPCA($code, 12);
                return;
            case self::UPC_E:
                $this->EAN13_UPCA($code, 6);
                return;
            default:
            {
                return;
            }
        }
    }

    /**
     * CODABAR barcodes.
     * Older code often used in library systems, sometimes in blood banks
     * @param string $code
     * @return void
     */
    protected function Codabar(string $code): void
    {
        $chr = [
            '0' => '11111221',
            '1' => '11112211',
            '2' => '11121121',
            '3' => '22111111',
            '4' => '11211211',
            '5' => '21111211',
            '6' => '12111121',
            '7' => '12112111',
            '8' => '12211111',
            '9' => '21121111',
            '-' => '11122111',
            '$' => '11221111',
            ':' => '21112121',
            '/' => '21211121',
            '.' => '21212111',
            '+' => '11222221',
            'A' => '11221211',
            'B' => '12121121',
            'C' => '11121221',
            'D' => '11122211'
        ];

        $bararray = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
        $position = 0;
        $code     = 'A' . strtoupper($code) . 'A';
        $len      = strlen($code);
        for ($outer = 0; $outer < $len; ++$outer) {
            if (!isset($chr[$code[$outer]])) {
                return;
            }
            $sequence = $chr[$code[$outer]];
            for ($inner = 0; $inner < 8; ++$inner) {
                $this->CodabarStripe($inner, $sequence, $bararray, $position);
                ++$position;
            }
        }
        $this->barcode = $bararray;
    }

    /**
     * Adds Codabar / Code 11 stripes.
     * @param int    $index
     * @param string $sequence
     * @param array  $bararray
     * @param int    $key
     * @return void
     */
    private function CodabarStripe(int $index, string $sequence, array &$bararray, int $key): void
    {
        $stripe = ($index % 2) === 0;
        $width  = (float) $sequence[$index];

        $bararray['barcode'][$key] = ['height' => 1, 'position' => self::TOP, 'stripe' => $stripe, 'width' => $width];
        $bararray['maxWidth']      += $width;
    }

    /**
     * CODE11 barcodes.
     * Used primarily for labeling telecommunications equipment
     * @param string $code
     * @return void
     */
    protected function Code11(string $code): void
    {
        $chr = [
            '0' => '111121',
            '1' => '211121',
            '2' => '121121',
            '3' => '221111',
            '4' => '112121',
            '5' => '212111',
            '6' => '122111',
            '7' => '111221',
            '8' => '211211',
            '9' => '211111',
            '-' => '112111',
            'S' => '112211'
        ];

        $bararray   = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
        $position   = 0;
        $codeLength = strlen($code);
        // calculate check digit C
        $topPosition = 1;
        $check       = 0;
        for ($index = $codeLength - 1; $index >= 0; --$index) {
            $digit = $code[$index];
            if ($digit == '-') {
                $digitValue = 10;
            }
            else {
                $digitValue = intval($digit);
            }
            $check += ($digitValue * $topPosition);
            ++$topPosition;
            if ($topPosition > 10) {
                $topPosition = 1;
            }
        }
        $check %= 11;
        if ($check == 10) {
            $check = '-';
        }
        $code .= $check;
        if ($codeLength > 10) {
            // calculate check digit K
            $topPosition = 1;
            $check       = 0;
            for ($index = $codeLength; $index >= 0; --$index) {
                $digit = $code[$index];
                if ($digit == '-') {
                    $digitValue = 10;
                }
                else {
                    $digitValue = intval($digit);
                }
                $check += ($digitValue * $topPosition);
                ++$topPosition;
                if ($topPosition > 9) {
                    $topPosition = 1;
                }
            }
            $check %= 11;
            $code  .= $check;
            ++$codeLength;
        }
        $code       = 'S' . $code . 'S';
        $codeLength += 3;
        for ($outer = 0; $outer < $codeLength; ++$outer) {
            if (!isset($chr[$code[$outer]])) {
                return;
            }
            $sequence = $chr[$code[$outer]];
            for ($inner = 0; $inner < 6; ++$inner) {
                $this->CodabarStripe($inner, $sequence, $bararray, $position);
                ++$position;
            }
        }

        $this->barcode = $bararray;
    }

    /**
     * C128 barcodes.
     * Very capable code, excellent density, high reliability; in very wide use world-wide
     * @param string $code
     * @param string $type barcode type: A, B, C or empty for automatic switch (AUTO mode)
     * @return void
     */
    protected function C128(string $code, string $type = ''): void
    {
        $chr        = [
            '212222', /* 00 */
            '222122', /* 01 */
            '222221', /* 02 */
            '121223', /* 03 */
            '121322', /* 04 */
            '131222', /* 05 */
            '122213', /* 06 */
            '122312', /* 07 */
            '132212', /* 08 */
            '221213', /* 09 */
            '221312', /* 10 */
            '231212', /* 11 */
            '112232', /* 12 */
            '122132', /* 13 */
            '122231', /* 14 */
            '113222', /* 15 */
            '123122', /* 16 */
            '123221', /* 17 */
            '223211', /* 18 */
            '221132', /* 19 */
            '221231', /* 20 */
            '213212', /* 21 */
            '223112', /* 22 */
            '312131', /* 23 */
            '311222', /* 24 */
            '321122', /* 25 */
            '321221', /* 26 */
            '312212', /* 27 */
            '322112', /* 28 */
            '322211', /* 29 */
            '212123', /* 30 */
            '212321', /* 31 */
            '232121', /* 32 */
            '111323', /* 33 */
            '131123', /* 34 */
            '131321', /* 35 */
            '112313', /* 36 */
            '132113', /* 37 */
            '132311', /* 38 */
            '211313', /* 39 */
            '231113', /* 40 */
            '231311', /* 41 */
            '112133', /* 42 */
            '112331', /* 43 */
            '132131', /* 44 */
            '113123', /* 45 */
            '113321', /* 46 */
            '133121', /* 47 */
            '313121', /* 48 */
            '211331', /* 49 */
            '231131', /* 50 */
            '213113', /* 51 */
            '213311', /* 52 */
            '213131', /* 53 */
            '311123', /* 54 */
            '311321', /* 55 */
            '331121', /* 56 */
            '312113', /* 57 */
            '312311', /* 58 */
            '332111', /* 59 */
            '314111', /* 60 */
            '221411', /* 61 */
            '431111', /* 62 */
            '111224', /* 63 */
            '111422', /* 64 */
            '121124', /* 65 */
            '121421', /* 66 */
            '141122', /* 67 */
            '141221', /* 68 */
            '112214', /* 69 */
            '112412', /* 70 */
            '122114', /* 71 */
            '122411', /* 72 */
            '142112', /* 73 */
            '142211', /* 74 */
            '241211', /* 75 */
            '221114', /* 76 */
            '413111', /* 77 */
            '241112', /* 78 */
            '134111', /* 79 */
            '111242', /* 80 */
            '121142', /* 81 */
            '121241', /* 82 */
            '114212', /* 83 */
            '124112', /* 84 */
            '124211', /* 85 */
            '411212', /* 86 */
            '421112', /* 87 */
            '421211', /* 88 */
            '212141', /* 89 */
            '214121', /* 90 */
            '412121', /* 91 */
            '111143', /* 92 */
            '111341', /* 93 */
            '131141', /* 94 */
            '114113', /* 95 */
            '114311', /* 96 */
            '411113', /* 97 */
            '411311', /* 98 */
            '113141', /* 99 */
            '114131', /* 100 */
            '311141', /* 101 */
            '411131', /* 102 */
            '211412', /* 103 START A */
            '211214', /* 104 START B */
            '211232', /* 105 START C */
            '233111', /* STOP */
            '200000'  /* END */
        ];
        $codeLength = strlen($code);
        // ASCII characters for code A (ASCII 00 - 95)
        $keysA = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_';
        $keysA .= chr(0) . chr(1) . chr(2) . chr(3) . chr(4) . chr(5) . chr(6) . chr(7) . chr(8) . chr(9);
        $keysA .= chr(10) . chr(11) . chr(12) . chr(13) . chr(14) . chr(15) . chr(16) . chr(17) . chr(18) . chr(19);
        $keysA .= chr(20) . chr(21) . chr(22) . chr(23) . chr(24) . chr(25) . chr(26) . chr(27) . chr(28) . chr(29);
        $keysA .= chr(30) . chr(31);
        // ASCII characters for code B (ASCII 32 - 127)
        $keysB         = ' !"#$%&\'()*+,-./0123456789:;<=>?@ABCDEFGHIJKLMNOPQRSTUVWXYZ[\\]^_`abcdefghijklmnopqrstuvwxyz{|}~' . chr(127);
        $specialCodesA = [241 => 102, 242 => 97, 243 => 96, 244 => 101];
        $specialCodesB = [241 => 102, 242 => 97, 243 => 96, 244 => 100];
        $symbolArray   = [];
        switch (strtoupper($type)) {
            case 'A':
                $startID = 103;
                for ($index = 0; $index < $codeLength; ++$index) {
                    $char      = $code[$index];
                    $byteValue = ord($char);
                    if ($byteValue >= 241 and $byteValue <= 244) {
                        $symbolArray[] = $specialCodesA[$byteValue];
                    }
                    elseif ($byteValue <= 95) {
                        $symbolArray[] = strpos($keysA, $char);
                    }
                    else {
                        return;
                    }
                }
                break;
            case 'B':
                $startID = 104;
                for ($index = 0; $index < $codeLength; ++$index) {
                    $char      = $code[$index];
                    $byteValue = ord($char);
                    if (($byteValue >= 241) and ($byteValue <= 244)) {
                        $symbolArray[] = $specialCodesB[$byteValue];
                    }
                    elseif (($byteValue >= 32) and ($byteValue <= 127)) {
                        $symbolArray[] = strpos($keysB, $char);
                    }
                    else {
                        return;
                    }
                }
                break;
            case 'C':
                $startID = 105;
                if (ord($code[0]) == 241) {
                    $symbolArray[] = 102;
                    $code          = substr($code, 1);
                    --$codeLength;
                }
                if (($codeLength % 2) != 0) {
                    // the length must be even
                    return;
                }
                for ($index = 0; $index < $codeLength; $index += 2) {
                    $characterNumber = $code[$index] . $code[$index + 1];
                    if (preg_match('/([0-9]{2})/', $characterNumber) > 0) {
                        $symbolArray[] = intval($characterNumber);
                    }
                    else {
                        return;
                    }
                }
                break;
            default:
                // This one is guesswork the original authors overlooked the default non-ABC case
                $startID = 103;
                // split code into sequences
                $sequences = [];
                // get numeric sequences (if any)
                $numberSequence = [];
                preg_match_all('/([0-9]{4,})/', $code, $numberSequence, PREG_OFFSET_CAPTURE);
                if (isset($numberSequence[1]) and !empty($numberSequence[1])) {
                    $end_offset = 0;
                    foreach ($numberSequence[1] as $val) {
                        $offset = $val[1];
                        if ($offset > $end_offset) {
                            // non numeric sequence
                            $sequences = array_merge($sequences, $this->Code128ABSequence(substr($code, $end_offset, ($offset - $end_offset))));
                        }
                        // numeric sequence
                        $stringLength = strlen($val[0]);
                        if (($stringLength % 2) != 0) {
                            // the length must be even
                            --$stringLength;
                        }
                        $sequences[] = ['C', substr($code, $offset, $stringLength), $stringLength];
                        $end_offset  = $offset + $stringLength;
                    }
                    if ($end_offset < $codeLength) {
                        $sequences = array_merge($sequences, $this->Code128ABSequence(substr($code, $end_offset)));
                    }
                }
                else {
                    // text code (non C mode)
                    $sequences = array_merge($sequences, $this->Code128ABSequence($code));
                }
                // process the sequence
                foreach ($sequences as $key => $sequence) {
                    switch ($sequence[0]) {
                        case 'A':
                            if ($key == 0) {
                                $startID = 103;
                            }
                            elseif ($sequences[($key - 1)][0] != 'A') {
                                if (($sequence[2] == 1) and ($key > 0) and ($sequences[($key - 1)][0] == 'B') and (!isset($sequences[($key - 1)][3]))) {
                                    // single character shift
                                    $symbolArray[] = 98;
                                    // mark shift
                                    $sequences[$key][3] = true;
                                }
                                elseif (!isset($sequences[($key - 1)][3])) {
                                    $symbolArray[] = 101;
                                }
                            }
                            $this->Code128Fill($sequence, $symbolArray, $specialCodesA, $keysA);
                            break;
                        case 'B':
                            if ($key == 0) {
                                $character = ord($sequence[1][0]);
                                if (($sequence[2] == 1) and ($character >= 241) and ($character <= 244) and isset($sequences[($key + 1)]) and ($sequences[($key + 1)][0] != 'B')) {
                                    switch ($sequences[($key + 1)][0]) {
                                        case 'A':
                                            $startID            = 103;
                                            $sequences[$key][0] = 'A';
                                            $symbolArray[]      = $specialCodesA[$character];
                                            break;
                                        case 'C':
                                            $startID            = 105;
                                            $sequences[$key][0] = 'C';
                                            $symbolArray[]      = $specialCodesA[$character];
                                            break;
                                    }
                                    break;
                                }
                                else {
                                    $startID = 104;
                                }
                            }
                            elseif ($sequences[($key - 1)][0] != 'B') {
                                if (($sequence[2] == 1) and ($key > 0) and ($sequences[($key - 1)][0] == 'A') and (!isset($sequences[($key - 1)][3]))) {
                                    // single character shift
                                    $symbolArray[] = 98;
                                    // mark shift
                                    $sequences[$key][3] = true;
                                }
                                elseif (!isset($sequences[($key - 1)][3])) {
                                    $symbolArray[] = 100;
                                }
                            }
                            $this->Code128Fill($sequence, $symbolArray, $specialCodesB, $keysB);
                            break;
                        case 'C':
                            if ($key == 0) {
                                $startID = 105;
                            }
                            elseif ($sequences[($key - 1)][0] != 'C') {
                                $symbolArray[] = 99;
                            }
                            for ($index = 0; $index < $sequence[2]; $index += 2) {
                                $characterNumber = $sequence[1][$index] . $sequence[1][$index + 1];
                                $symbolArray[]   = intval($characterNumber);
                            }
                            break;
                    }
                }
        }
        // calculate check character
        $sum = $startID;
        foreach ($symbolArray as $key => $val) {
            $sum += ($val * ($key + 1));
        }
        // add check character
        $symbolArray[] = ($sum % 103);
        // add stop sequence
        $symbolArray[] = 106;
        $symbolArray[] = 107;
        // add start code at the beginning
        array_unshift($symbolArray, $startID);
        // build barcode array
        $bararray = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
        foreach ($symbolArray as $val) {
            $seq = $chr[$val];
            for ($index = 0; $index < 6; ++$index) {
                $stripe = ($index % 2) === 0;
                $width  = (float) $seq[$index];

                $bararray['barcode'][] = ['height' => 1, 'position' => self::TOP, 'stripe' => $stripe, 'width' => $width];
                $bararray['maxWidth']  += $width;
            }
        }
        $this->barcode = $bararray;
    }

    /**
     * Split text code in A/B sequence for 128 code
     * @param string $code code to split.
     * @return array
     */
    protected function Code128ABSequence(string $code): array
    {
        $codeLength = strlen($code);
        $sequence   = [];
        // get A sequences (if any)
        $numberSequence = [];
        preg_match_all('/([\0-\31])/', $code, $numberSequence, PREG_OFFSET_CAPTURE);
        if (isset($numberSequence[1]) and !empty($numberSequence[1])) {
            $end_offset = 0;
            foreach ($numberSequence[1] as $val) {
                $offset = $val[1];
                if ($offset > $end_offset) {
                    // B sequence
                    $sequence[] = ['B', substr($code, $end_offset, ($offset - $end_offset)), ($offset - $end_offset)];
                }
                // A sequence
                $sequenceLength = strlen($val[0]);
                $sequence[]     = ['A', substr($code, $offset, $sequenceLength), $sequenceLength];
                $end_offset     = $offset + $sequenceLength;
            }
            if ($end_offset < $codeLength) {
                $sequence[] = ['B', substr($code, $end_offset), ($codeLength - $end_offset)];
            }
        }
        else {
            // only B sequence
            $sequence[] = ['B', $code, $codeLength];
        }
        return $sequence;
    }

    /**
     * Fills the .
     * @param array  $sequence     the sequence being processed
     * @param array  $symbolArray  the array to fill
     * @param array  $specialCodes a value mapping array
     * @param string $keys         a string containing ASCII characters
     * @return void
     */
    private function Code128Fill(array $sequence, array &$symbolArray, array $specialCodes, string $keys): void
    {
        for ($index = 0; $index < $sequence[2]; ++$index) {
            $char          = $sequence[1][$index];
            $byteValue     = ord($char);
            $symbolArray[] = ($byteValue >= 241 and $byteValue <= 244) ? $specialCodes[$byteValue] : strpos($keys, $char);
        }
    }

    /**
     * CODE 39 - ANSI MH10.8M-1983 - USD-3 - 3 of 9.
     * General-purpose code in very wide use world-wide
     * @param string $code
     * @param bool   $extended if true uses the extended mode.
     * @param bool   $checksum if true add a checksum to the code.
     * @return void
     */
    protected function Code39(string $code, bool $extended = false, bool $checksum = false): void
    {
        $mapping = [
            '0' => '111331311', '1' => '311311113', '2' => '113311113', '3' => '313311111', '4' => '111331113', '5' => '311331111',
            '6' => '113331111', '7' => '111311313', '8' => '311311311', '9' => '113311311', 'A' => '311113113', 'B' => '113113113',
            'C' => '313113111', 'D' => '111133113', 'E' => '311133111', 'F' => '113133111', 'G' => '111113313', 'H' => '311113311',
            'I' => '113113311', 'J' => '111133311', 'K' => '311111133', 'L' => '113111133', 'M' => '313111131', 'N' => '111131133',
            'O' => '311131131', 'P' => '113131131', 'Q' => '111111333', 'R' => '311111331', 'S' => '113111331', 'T' => '111131331',
            'U' => '331111113', 'V' => '133111113', 'W' => '333111111', 'X' => '131131113', 'Y' => '331131111', 'Z' => '133131111',
            '-' => '131111313', '.' => '331111311', ' ' => '133111311', '$' => '131313111', '/' => '131311131', '+' => '131113131',
            '%' => '111313131', '*' => '131131311',
        ];
        $code    = strtoupper($code);
        if ($extended) {
            $code = $this->Code39Encode($code);
        }
        if (!$code) {
            return;
        }
        if ($checksum) {
            // checksum
            $code .= $this->Code39Checksum($code);
        }
        // add start and stop codes
        $code     = '*' . $code . '*';
        $bararray = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
        $position = 0;
        $clen     = strlen($code);
        for ($outer = 0; $outer < $clen; ++$outer) {
            $char = $code[$outer];
            if (!isset($mapping[$char])) {
                return;
            }
            for ($inner = 0; $inner < 9; ++$inner) {
                $this->Code39Stripe($inner, $mapping, $char, $bararray, $position);
                ++$position;
            }
            // intercharacter gap
            $bararray['barcode'][$position] = ['height' => 1, 'position' => self::TOP, 'stripe' => self::SPACE, 'width' => 1];
            $bararray['maxWidth']           += 1;
            ++$position;
        }
        $this->barcode = $bararray;
    }

    /**
     * Calculate CODE 39 checksum (modulo 43).
     * @param string $code
     * @return string
     */
    protected function Code39Checksum(string $code): string
    {
        $chars  = [
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K',
            'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V',
            'W', 'X', 'Y', 'Z', '-', '.', ' ', '$', '/', '+', '%'];
        $sum    = 0;
        $length = strlen($code);
        for ($index = 0; $index < $length; ++$index) {
            $keys = array_keys($chars, $code[$index]);
            $sum  += $keys[0];
        }
        $remainder = ($sum % 43);
        return $chars[$remainder];
    }

    /**
     * Encode a string to be used for CODE 39 Extended mode.
     * @param string $code
     * @return string
     */
    protected function Code39Encode(string $code): string
    {
        $encode   = [
            chr(0)   => '%U', chr(1) => '$A', chr(2) => '$B', chr(3) => '$C',
            chr(4)   => '$D', chr(5) => '$E', chr(6) => '$F', chr(7) => '$G',
            chr(8)   => '$H', chr(9) => '$I', chr(10) => '$J', chr(11) => '£K',
            chr(12)  => '$L', chr(13) => '$M', chr(14) => '$N', chr(15) => '$O',
            chr(16)  => '$P', chr(17) => '$Q', chr(18) => '$R', chr(19) => '$S',
            chr(20)  => '$T', chr(21) => '$U', chr(22) => '$V', chr(23) => '$W',
            chr(24)  => '$X', chr(25) => '$Y', chr(26) => '$Z', chr(27) => '%A',
            chr(28)  => '%B', chr(29) => '%C', chr(30) => '%D', chr(31) => '%E',
            chr(32)  => ' ', chr(33) => '/A', chr(34) => '/B', chr(35) => '/C',
            chr(36)  => '/D', chr(37) => '/E', chr(38) => '/F', chr(39) => '/G',
            chr(40)  => '/H', chr(41) => '/I', chr(42) => '/J', chr(43) => '/K',
            chr(44)  => '/L', chr(45) => '-', chr(46) => '.', chr(47) => '/O',
            chr(48)  => '0', chr(49) => '1', chr(50) => '2', chr(51) => '3',
            chr(52)  => '4', chr(53) => '5', chr(54) => '6', chr(55) => '7',
            chr(56)  => '8', chr(57) => '9', chr(58) => '/Z', chr(59) => '%F',
            chr(60)  => '%G', chr(61) => '%H', chr(62) => '%I', chr(63) => '%J',
            chr(64)  => '%V', chr(65) => 'A', chr(66) => 'B', chr(67) => 'C',
            chr(68)  => 'D', chr(69) => 'E', chr(70) => 'F', chr(71) => 'G',
            chr(72)  => 'H', chr(73) => 'I', chr(74) => 'J', chr(75) => 'K',
            chr(76)  => 'L', chr(77) => 'M', chr(78) => 'N', chr(79) => 'O',
            chr(80)  => 'P', chr(81) => 'Q', chr(82) => 'R', chr(83) => 'S',
            chr(84)  => 'T', chr(85) => 'U', chr(86) => 'V', chr(87) => 'W',
            chr(88)  => 'X', chr(89) => 'Y', chr(90) => 'Z', chr(91) => '%K',
            chr(92)  => '%L', chr(93) => '%M', chr(94) => '%N', chr(95) => '%O',
            chr(96)  => '%W', chr(97) => '+A', chr(98) => '+B', chr(99) => '+C',
            chr(100) => '+D', chr(101) => '+E', chr(102) => '+F', chr(103) => '+G',
            chr(104) => '+H', chr(105) => '+I', chr(106) => '+J', chr(107) => '+K',
            chr(108) => '+L', chr(109) => '+M', chr(110) => '+N', chr(111) => '+O',
            chr(112) => '+P', chr(113) => '+Q', chr(114) => '+R', chr(115) => '+S',
            chr(116) => '+T', chr(117) => '+U', chr(118) => '+V', chr(119) => '+W',
            chr(120) => '+X', chr(121) => '+Y', chr(122) => '+Z', chr(123) => '%P',
            chr(124) => '%Q', chr(125) => '%R', chr(126) => '%S', chr(127) => '%T'
        ];
        $code_ext = '';
        $length   = strlen($code);
        for ($index = 0; $index < $length; ++$index) {
            if (ord($code[$index]) > 127) {
                return false;
            }
            $code_ext .= $encode[$code[$index]];
        }
        return $code_ext;
    }

    /**
     * Adds Code 39/93 stripes.
     * @param int        $index
     * @param array      $mapping
     * @param int|string $character
     * @param array      $bararray
     * @param int        $key
     * @return void
     */
    private function Code39Stripe(int $index, array $mapping, int|string $character, array &$bararray, int $key): void
    {
        $stripe = $index % 2 === 0;
        $width  = $mapping[$character][$index];

        $bararray['barcode'][$key] = ['height' => 1, 'position' => self::TOP, 'stripe' => $stripe, 'width' => $width];
        $bararray['maxWidth']      += (int) $width;
    }

    /**
     * CODE 93 - USS-93 => Compact code similar to Code 39
     * @param string $code
     * @return void
     */
    protected function Code93(string $code): void
    {
        $mapping = [
            32  => '311211',
            36  => '321111',
            37  => '211131',
            42  => '111141',
            43  => '113121',
            45  => '121131',
            46  => '311112',
            47  => '112131',
            48  => '131112',
            49  => '111213',
            50  => '111312',
            51  => '111411',
            52  => '121113',
            53  => '121212',
            54  => '121311',
            55  => '111114',
            56  => '131211',
            57  => '141111',
            65  => '211113',
            66  => '211212',
            67  => '211311',
            68  => '221112',
            69  => '221211',
            70  => '231111',
            71  => '112113',
            72  => '112212',
            73  => '112311',
            74  => '122112',
            75  => '132111',
            76  => '111123',
            77  => '111222',
            78  => '111321',
            79  => '121122',
            80  => '131121',
            81  => '212112',
            82  => '212211',
            83  => '211122',
            84  => '211221',
            85  => '221121',
            86  => '222111',
            87  => '112122',
            88  => '112221',
            89  => '122121',
            90  => '123111',
            128 => '121221',
            129 => '311121',
            130 => '122211',
            131 => '312111',
        ];
        $code    = strtoupper($code);
        $encode  = [
            chr(0)   => chr(131) . 'U', chr(1) => chr(128) . 'A', chr(2) => chr(128) . 'B',
            chr(3)   => chr(128) . 'C', chr(4) => chr(128) . 'D', chr(5) => chr(128) . 'E',
            chr(6)   => chr(128) . 'F', chr(7) => chr(128) . 'G', chr(8) => chr(128) . 'H',
            chr(9)   => chr(128) . 'I', chr(10) => chr(128) . 'J', chr(11) => '£K',
            chr(12)  => chr(128) . 'L', chr(13) => chr(128) . 'M', chr(14) => chr(128) . 'N',
            chr(15)  => chr(128) . 'O', chr(16) => chr(128) . 'P', chr(17) => chr(128) . 'Q',
            chr(18)  => chr(128) . 'R', chr(19) => chr(128) . 'S', chr(20) => chr(128) . 'T',
            chr(21)  => chr(128) . 'U', chr(22) => chr(128) . 'V', chr(23) => chr(128) . 'W',
            chr(24)  => chr(128) . 'X', chr(25) => chr(128) . 'Y', chr(26) => chr(128) . 'Z',
            chr(27)  => chr(131) . 'A', chr(28) => chr(131) . 'B', chr(29) => chr(131) . 'C',
            chr(30)  => chr(131) . 'D', chr(31) => chr(131) . 'E', chr(32) => ' ',
            chr(33)  => chr(129) . 'A', chr(34) => chr(129) . 'B', chr(35) => chr(129) . 'C',
            chr(36)  => chr(129) . 'D', chr(37) => chr(129) . 'E', chr(38) => chr(129) . 'F',
            chr(39)  => chr(129) . 'G', chr(40) => chr(129) . 'H', chr(41) => chr(129) . 'I',
            chr(42)  => chr(129) . 'J', chr(43) => chr(129) . 'K', chr(44) => chr(129) . 'L',
            chr(45)  => '-', chr(46) => '.', chr(47) => chr(129) . 'O', chr(48) => '0',
            chr(49)  => '1', chr(50) => '2', chr(51) => '3', chr(52) => '4', chr(53) => '5',
            chr(54)  => '6', chr(55) => '7', chr(56) => '8', chr(57) => '9',
            chr(58)  => chr(129) . 'Z', chr(59) => chr(131) . 'F', chr(60) => chr(131) . 'G',
            chr(61)  => chr(131) . 'H', chr(62) => chr(131) . 'I', chr(63) => chr(131) . 'J',
            chr(64)  => chr(131) . 'V', chr(65) => 'A', chr(66) => 'B', chr(67) => 'C',
            chr(68)  => 'D', chr(69) => 'E', chr(70) => 'F', chr(71) => 'G', chr(72) => 'H',
            chr(73)  => 'I', chr(74) => 'J', chr(75) => 'K', chr(76) => 'L', chr(77) => 'M',
            chr(78)  => 'N', chr(79) => 'O', chr(80) => 'P', chr(81) => 'Q', chr(82) => 'R',
            chr(83)  => 'S', chr(84) => 'T', chr(85) => 'U', chr(86) => 'V', chr(87) => 'W',
            chr(88)  => 'X', chr(89) => 'Y', chr(90) => 'Z', chr(91) => chr(131) . 'K',
            chr(92)  => chr(131) . 'L', chr(93) => chr(131) . 'M', chr(94) => chr(131) . 'N',
            chr(95)  => chr(131) . 'O', chr(96) => chr(131) . 'W', chr(97) => chr(130) . 'A',
            chr(98)  => chr(130) . 'B', chr(99) => chr(130) . 'C', chr(100) => chr(130) . 'D',
            chr(101) => chr(130) . 'E', chr(102) => chr(130) . 'F', chr(103) => chr(130) . 'G',
            chr(104) => chr(130) . 'H', chr(105) => chr(130) . 'I', chr(106) => chr(130) . 'J',
            chr(107) => chr(130) . 'K', chr(108) => chr(130) . 'L', chr(109) => chr(130) . 'M',
            chr(110) => chr(130) . 'N', chr(111) => chr(130) . 'O', chr(112) => chr(130) . 'P',
            chr(113) => chr(130) . 'Q', chr(114) => chr(130) . 'R', chr(115) => chr(130) . 'S',
            chr(116) => chr(130) . 'T', chr(117) => chr(130) . 'U', chr(118) => chr(130) . 'V',
            chr(119) => chr(130) . 'W', chr(120) => chr(130) . 'X', chr(121) => chr(130) . 'Y',
            chr(122) => chr(130) . 'Z', chr(123) => chr(131) . 'P', chr(124) => chr(131) . 'Q',
            chr(125) => chr(131) . 'R', chr(126) => chr(131) . 'S', chr(127) => chr(131) . 'T'
        ];

        $codeExt    = '';
        $codeLength = strlen($code);
        for ($index = 0; $index < $codeLength; ++$index) {
            if (ord($code[$index]) > 127) {
                // Beyond scope?
                return;
            }
            $codeExt .= $encode[$code[$index]];
        }

        $codeExt .= $this->Code93Checksum($codeExt);
        // add start and stop codes
        $code     = '*' . $codeExt . '*';
        $bararray = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
        $position = 0;

        for ($outer = 0; $outer < $codeLength; ++$outer) {
            $char = ord($code[$outer]);
            if (!isset($mapping[$char])) {
                // invalid character
                return;
            }
            for ($inner = 0; $inner < 6; ++$inner) {
                $this->Code39Stripe($inner, $mapping, $char, $bararray, $position);
                ++$position;
            }
        }

        $bararray['barcode'][$position] = ['height' => 1, 'position' => self::TOP, 'stripe' => self::STRIPE, 'width' => 1];
        $bararray['maxWidth']           += 1;

        $this->barcode = $bararray;
    }

    /**
     * Calculate CODE 93 checksum (modulo 47).
     * @param string $code
     * @return string
     */
    protected function Code93Checksum(string $code): string
    {
        $characters = [
            '0', '1', '2', '3', '4', '5', '6', '7', '8', '9',
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H', 'I', 'J', 'K',
            'L', 'M', 'N', 'O', 'P', 'Q', 'R', 'S', 'T', 'U', 'V',
            'W', 'X', 'Y', 'Z', '-', '.', ' ', '$', '/', '+', '%',
            '<', '=', '>', '?'
        ];

        // translate special characters
        $code = strtr($code, chr(128) . chr(131) . chr(129) . chr(130), '<=>?');
        $len  = strlen($code);

        // calculate check digit C
        $position = 1;
        $check    = 0;
        for ($index = $len - 1; $index >= 0; --$index) {
            $keys  = array_keys($characters, $code[$index]);
            $check += ($keys[0] * $position);
            ++$position;
            if ($position > 20) {
                $position = 1;
            }
        }

        $check     %= 47;
        $character = $characters[$check];
        $code      .= $character;

        // calculate check digit K
        $position = 1;
        $check    = 0;
        for ($index = $len; $index >= 0; --$index) {
            $keys  = array_keys($characters, $code[$index]);
            $check += ($keys[0] * $position);
            ++$position;
            if ($position > 15) {
                $position = 1;
            }
        }

        $check    %= 47;
        $checksum = $character . $characters[$check];

        // restore special characters
        return strtr($checksum, '<=>?', chr(128) . chr(131) . chr(129) . chr(130));
    }

    /**
     * EAN13 and UPC-A barcodes.
     * EAN13: European Article Numbering international retail product code
     * UPC-A: Universal product code seen on almost all retail products in the USA and Canada
     * UPC-E: Short version of UPC symbol
     * @param string $code
     * @param int    $length barcode type: 6 = UPC-E, 8 = EAN8, 13 = EAN13, 12 = UPC-A
     * @return void
     */
    protected function EAN13_UPCA(string $code, int $length = 13): void
    {
        $upce = false;
        if ($length == 6) {
            $length = 12; // UPC-A
            $upce   = true; // UPC-E mode
        }
        $data_len = $length - 1;
        //Padding
        $code     = str_pad($code, $data_len, '0', STR_PAD_LEFT);
        $code_len = strlen($code);
        // calculate check digit
        $sum_a = 0;
        for ($index = 1; $index < $data_len; $index += 2) {
            $sum_a += $code[$index];
        }
        if ($length > 12) {
            $sum_a *= 3;
        }
        $sum_b = 0;
        for ($index = 0; $index < $data_len; $index += 2) {
            $sum_b += ($code[$index]);
        }
        if ($length < 13) {
            $sum_b *= 3;
        }
        $remainder = ($sum_a + $sum_b) % 10;
        if ($remainder > 0) {
            $remainder = (10 - $remainder);
        }
        if ($code_len == $data_len) {
            // add check digit
            $code .= $remainder;
        }
        elseif ($remainder !== intval($code[$data_len])) {
            // wrong check digit
            return;
        }
        if ($length == 12) {
            // UPC-A
            $code = '0' . $code;
            ++$length;
        }
        if ($upce) {
            // convert UPC-A to UPC-E
            $tmp = substr($code, 4, 3);
            if (($tmp == '000') or ($tmp == '100') or ($tmp == '200')) {
                // manufacturer code ends in 000, 100, or 200
                $upce_code = substr($code, 2, 2) . substr($code, 9, 3) . substr($code, 4, 1);
            }
            else {
                $tmp = substr($code, 5, 2);
                if ($tmp == '00') {
                    // manufacturer code ends in 00
                    $upce_code = substr($code, 2, 3) . substr($code, 10, 2) . '3';
                }
                else {
                    $tmp = substr($code, 6, 1);
                    if ($tmp == '0') {
                        // manufacturer code ends in 0
                        $upce_code = substr($code, 2, 4) . substr($code, 11, 1) . '4';
                    }
                    else {
                        // manufacturer code does not end in zero
                        $upce_code = substr($code, 2, 5) . substr($code, 11, 1);
                    }
                }
            }
        }
        //Convert digits to bars
        $codes            = [
            'A' => [ // left odd parity
                '0' => '0001101',
                '1' => '0011001',
                '2' => '0010011',
                '3' => '0111101',
                '4' => '0100011',
                '5' => '0110001',
                '6' => '0101111',
                '7' => '0111011',
                '8' => '0110111',
                '9' => '0001011'],
            'B' => [ // left even parity
                '0' => '0100111',
                '1' => '0110011',
                '2' => '0011011',
                '3' => '0100001',
                '4' => '0011101',
                '5' => '0111001',
                '6' => '0000101',
                '7' => '0010001',
                '8' => '0001001',
                '9' => '0010111'],
            'C' => [ // right
                '0' => '1110010',
                '1' => '1100110',
                '2' => '1101100',
                '3' => '1000010',
                '4' => '1011100',
                '5' => '1001110',
                '6' => '1010000',
                '7' => '1000100',
                '8' => '1001000',
                '9' => '1110100']
        ];
        $parities         = [
            '0' => ['A', 'A', 'A', 'A', 'A', 'A'],
            '1' => ['A', 'A', 'B', 'A', 'B', 'B'],
            '2' => ['A', 'A', 'B', 'B', 'A', 'B'],
            '3' => ['A', 'A', 'B', 'B', 'B', 'A'],
            '4' => ['A', 'B', 'A', 'A', 'B', 'B'],
            '5' => ['A', 'B', 'B', 'A', 'A', 'B'],
            '6' => ['A', 'B', 'B', 'B', 'A', 'A'],
            '7' => ['A', 'B', 'A', 'B', 'A', 'B'],
            '8' => ['A', 'B', 'A', 'B', 'B', 'A'],
            '9' => ['A', 'B', 'B', 'A', 'B', 'A']
        ];
        $upce_parities    = [];
        $upce_parities[0] = [
            '0' => ['B', 'B', 'B', 'A', 'A', 'A'],
            '1' => ['B', 'B', 'A', 'B', 'A', 'A'],
            '2' => ['B', 'B', 'A', 'A', 'B', 'A'],
            '3' => ['B', 'B', 'A', 'A', 'A', 'B'],
            '4' => ['B', 'A', 'B', 'B', 'A', 'A'],
            '5' => ['B', 'A', 'A', 'B', 'B', 'A'],
            '6' => ['B', 'A', 'A', 'A', 'B', 'B'],
            '7' => ['B', 'A', 'B', 'A', 'B', 'A'],
            '8' => ['B', 'A', 'B', 'A', 'A', 'B'],
            '9' => ['B', 'A', 'A', 'B', 'A', 'B']
        ];
        $upce_parities[1] = [
            '0' => ['A', 'A', 'A', 'B', 'B', 'B'],
            '1' => ['A', 'A', 'B', 'A', 'B', 'B'],
            '2' => ['A', 'A', 'B', 'B', 'A', 'B'],
            '3' => ['A', 'A', 'B', 'B', 'B', 'A'],
            '4' => ['A', 'B', 'A', 'A', 'B', 'B'],
            '5' => ['A', 'B', 'B', 'A', 'A', 'B'],
            '6' => ['A', 'B', 'B', 'B', 'A', 'A'],
            '7' => ['A', 'B', 'A', 'B', 'A', 'B'],
            '8' => ['A', 'B', 'A', 'B', 'B', 'A'],
            '9' => ['A', 'B', 'B', 'A', 'B', 'A']
        ];
        $key              = 0;
        $sequence         = '101'; // left guard bar
        if ($upce) {
            $bararray = ['code' => $upce_code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
            $position = $upce_parities[$code[1]][$remainder];
            for ($index = 0; $index < 6; ++$index) {
                $sequence .= $codes[$position[$index]][$upce_code[$index]];
            }
            $sequence .= '010101'; // right guard bar
        }
        else {
            $bararray = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
            $half_len = intval(ceil($length / 2));
            if ($length == 8) {
                for ($index = 0; $index < $half_len; ++$index) {
                    $sequence .= $codes['A'][$code[$index]];
                }
            }
            else {
                $position = $parities[$code[0]];
                for ($index = 1; $index < $half_len; ++$index) {
                    $sequence .= $codes[$position[$index - 1]][$code[$index]];
                }
            }
            $sequence .= '01010'; // center guard bar
            for ($index = $half_len; $index < $length; ++$index) {
                $sequence .= $codes['C'][$code[$index]];
            }
            $sequence .= '101'; // right guard bar
        }
        $clen  = strlen($sequence);
        $width = 0;
        for ($index = 0; $index < $clen; ++$index) {
            $width += 1;
            if (($index == ($clen - 1)) or (($index < ($clen - 1)) and ($sequence[$index] != $sequence[$index + 1]))) {
                $stripe = $sequence[$index] === '1';

                $bararray['barcode'][$key] = ['height' => 1, 'position' => self::TOP, 'stripe' => $stripe, 'width' => $width];
                $bararray['maxWidth']      += $width;
                ++$key;
                $width = 0;
            }
        }
        $this->barcode = $bararray;
    }

    /**
     * UPC-Based Extensions
     * 2-Digit Ext.: Used to indicate magazines and newspaper issue numbers
     * 5-Digit Ext.: Used to mark suggested retail price of books
     * @param string $code
     * @param int    $length barcode type: 2 = 2-Digit, 5 = 5-Digit
     * @return void
     */
    protected function EANExtensions(string $code, int $length = 5): void
    {
        //Padding
        $code = str_pad($code, $length, '0', STR_PAD_LEFT);
        // calculate check digit
        if ($length === 2) {
            $remainder = $code % 4;
        }
        elseif ($length === 5) {
            $remainder = (3 * ($code[0] + $code[2] + $code[4])) + (9 * ($code[1] + $code[3]));
            $remainder %= 10;
        }
        else {
            return;
        }
        //Convert digits to bars
        $codes       = [
            'A' => [ // left odd parity
                '0' => '0001101',
                '1' => '0011001',
                '2' => '0010011',
                '3' => '0111101',
                '4' => '0100011',
                '5' => '0110001',
                '6' => '0101111',
                '7' => '0111011',
                '8' => '0110111',
                '9' => '0001011'
            ],
            'B' => [ // left even parity
                '0' => '0100111',
                '1' => '0110011',
                '2' => '0011011',
                '3' => '0100001',
                '4' => '0011101',
                '5' => '0111001',
                '6' => '0000101',
                '7' => '0010001',
                '8' => '0001001',
                '9' => '0010111'
            ]
        ];
        $parities    = [];
        $parities[2] = [
            '0' => ['A', 'A'],
            '1' => ['A', 'B'],
            '2' => ['B', 'A'],
            '3' => ['B', 'B']
        ];
        $parities[5] = [
            '0' => ['B', 'B', 'A', 'A', 'A'],
            '1' => ['B', 'A', 'B', 'A', 'A'],
            '2' => ['B', 'A', 'A', 'B', 'A'],
            '3' => ['B', 'A', 'A', 'A', 'B'],
            '4' => ['A', 'B', 'B', 'A', 'A'],
            '5' => ['A', 'A', 'B', 'B', 'A'],
            '6' => ['A', 'A', 'A', 'B', 'B'],
            '7' => ['A', 'B', 'A', 'B', 'A'],
            '8' => ['A', 'B', 'A', 'A', 'B'],
            '9' => ['A', 'A', 'B', 'A', 'B']
        ];
        $position    = $parities[$length][$remainder];

        $sequence = '1011'; // left guard bar
        $sequence .= $codes[$position[0]][$code[0]];
        for ($index = 1; $index < $length; ++$index) {
            $sequence .= '01'; // separator
            $sequence .= $codes[$position[$index]][$code[$index]];
        }

        $bararray      = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
        $this->barcode = $this->sequence2Array($sequence, $bararray);
    }

    /**
     * Return an HTML representation of barcode.
     * @param int    $width  Width of a single bar element in pixels.
     * @param int    $height Height of a single bar element in pixels.
     * @param string $color  Foreground color for bar elements (background is transparent).
     * @return string
     */
    public function HTML(int $width = 2, int $height = 30, string $color = 'black'): string
    {
        $html = '<div style="font-size:0;position:relative;width:' . $this->barcode['maxWidth'] * $width . 'px;height:' . $height . 'px;">' . "\n";
        // print bars
        $x = 0;
        foreach ($this->barcode['barcode'] as $stripe) {
            $bw = round(($stripe['width'] * $width), 3);
            $bh = round(($stripe['height'] * $height / $this->barcode['maxHeight']), 3);
            if ($stripe['stripe']) {
                $y = round(($stripe['position'] * $height / $this->barcode['maxHeight']), 3);
                // draw a vertical bar
                $html .= '<div style="background-color:' . $color . ';width:' . $bw . 'px;height:' . $bh . 'px;position:absolute;left:' . $x . 'px;top:' . $y . 'px;">&nbsp;</div>' . "\n";
            }
            $x += $bw;
        }
        $html .= '</div>' . "\n";
        return $html;
    }

    /**
     * Interleaved 2 of 5 barcodes.
     * Compact numeric code, widely used in industry, air cargo
     * Contains digits (0 to 9) and encodes the data in the width of both bars and spaces.
     * @param string $code
     * @param bool   $checksum if true add a checksum to the code
     * @return void
     */
    protected function I25(string $code, bool $checksum = false): void
    {
        $chr = [
            '0' => '11221', '1' => '21112', '2' => '12112', '3' => '22111', '4' => '11212', '5' => '21211', '6' => '12211',
            '7' => '11122', '8' => '21121', '9' => '12121', 'A' => '11', 'Z' => '21',
        ];
        if ($checksum) {
            // add checksum
            $code .= $this->S25Checksum($code);
        }
        if ((strlen($code) % 2) != 0) {
            // add leading zero if code-length is odd
            $code = '0' . $code;
        }
        // add start and stop codes
        $code = 'AA' . strtolower($code) . 'ZA';

        $bararray   = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
        $position   = 0;
        $codeLength = strlen($code);
        for ($outer = 0; $outer < $codeLength; $outer = ($outer + 2)) {
            $char_bar   = $code[$outer];
            $char_space = $code[$outer + 1];
            if ((!isset($chr[$char_bar])) or (!isset($chr[$char_space]))) {
                // invalid character
                return;
            }
            // create a bar-space sequence
            $sequence  = '';
            $barLength = strlen($chr[$char_bar]);
            for ($inner = 0; $inner < $barLength; $inner++) {
                $sequence .= $chr[$char_bar][$inner] . $chr[$char_space][$inner];
            }
            $sequenceLength = strlen($sequence);
            for ($inner = 0; $inner < $sequenceLength; ++$inner) {
                $stripe = ($inner % 2) === 0;
                $width  = (float) $sequence[$inner];

                $bararray['barcode'][$position] = ['height' => 1, 'position' => self::TOP, 'stripe' => $stripe, 'width' => $width];
                $bararray['maxWidth']           += $width;
                ++$position;
            }
        }
        $this->barcode = $bararray;
    }

    /**
     * MSI.
     * Variation of Plessey code, with similar applications
     * Contains digits (0 to 9) and encodes the data only in the width of bars.
     * @param string $code
     * @param bool   $checksum if true add a checksum to the code (modulo 11)
     * @return void
     */
    protected function MSI(string $code, bool $checksum = false): void
    {
        $chr = [
            '0' => '100100100100', '1' => '100100100110', '2' => '100100110100', '3' => '100100110110', '4' => '100110100100', '5' => '100110100110',
            '6' => '100110110100', '7' => '100110110110', '8' => '110100100100', '9' => '110100100110', 'A' => '110100110100', 'B' => '110100110110',
            'C' => '110110100100', 'D' => '110110100110', 'E' => '110110110100', 'F' => '110110110110',
        ];
        if ($checksum) {
            // add checksum
            $clen     = strlen($code);
            $position = 2;
            $check    = 0;
            for ($index = ($clen - 1); $index >= 0; --$index) {
                $check += (hexdec($code[$index]) * $position);
                ++$position;
                if ($position > 7) {
                    $position = 2;
                }
            }
            $check %= 11;
            if ($check > 0) {
                $check = 11 - $check;
            }
            $code .= $check;
        }
        $seq  = '110'; // left guard
        $clen = strlen($code);
        for ($index = 0; $index < $clen; ++$index) {
            $digit = $code[$index];
            if (!isset($chr[$digit])) {
                // invalid character
                return;
            }
            $seq .= $chr[$digit];
        }
        $seq           .= '1001'; // right guard
        $bararray      = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
        $this->barcode = $this->sequence2Array($seq, $bararray);
    }

    /**
     * Pharmacode
     * Contains digits (0 to 9)
     * @param string $code
     * @return void
     */
    protected function PharmaCode(string $code): void
    {
        $seq  = '';
        $code = intval($code);
        while ($code > 0) {
            if (($code % 2) == 0) {
                $seq  .= '11100';
                $code -= 2;
            }
            else {
                $seq  .= '100';
                $code -= 1;
            }
            $code /= 2;
        }
        $seq           = substr($seq, 0, -2);
        $seq           = strrev($seq);
        $bararray      = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
        $this->barcode = $this->sequence2Array($seq, $bararray);
    }

    /**
     * Pharmacode two-track => Contains digits (0 to 9)
     * @param string $code
     * @return void
     */
    protected function Pharmacode2Track(string $code): void
    {
        $sequence = '';
        $code     = intval($code);
        do {
            switch ($code % 3) {
                case 0:
                    $sequence .= '3';
                    $code     = ($code - 3) / 3;
                    break;
                case 1:
                    $sequence .= '1';
                    $code     = ($code - 1) / 3;
                    break;
                case 2:
                    $sequence .= '2';
                    $code     = ($code - 2) / 3;
                    break;
            }
        } while ($code != 0);
        $sequence = strrev($sequence);
        $key      = 0;
        $bararray = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 2, 'barcode' => []];
        $len      = strlen($sequence);
        for ($index = 0; $index < $len; ++$index) {
            switch ($sequence[$index]) {
                case '1':
                    $position = 1;
                    $height   = 1;
                    break;
                case '2':
                    $position = 0;
                    $height   = 1;
                    break;
                default:
                    $position = 0;
                    $height   = 2;
                    break;
            }
            $bararray['barcode'][$key++] = ['height' => $height, 'position' => $position, 'stripe' => self::STRIPE, 'width' => 1];
            $bararray['barcode'][$key++] = ['height' => 2, 'position' => self::TOP, 'stripe' => self::SPACE, 'width' => 1];
            $bararray['maxWidth']        += 2;
        }
        unset($bararray['barcode'][($key - 1)]);
        --$bararray['maxWidth'];
        $this->barcode = $bararray;
    }

    /**
     * Send a PNG image representation of barcode (requires GD or Imagick library).
     * @param int   $width  Width of a single bar element in pixels.
     * @param int   $height Height of a single bar element in pixels.
     * @param array $color  RGB (0-255) foreground color for bar elements (background is transparent).
     */
    public function PNG(int $width = 2, int $height = 30, array $color = PDF::BLACK): void
    {
        $data = $this->PNGData($width, $height, $color);
        header('Content-Type: image/png');
        header('Cache-Control: public, must-revalidate, max-age=0');
        header('Pragma: public');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT'); // Date in the past
        header('Last-Modified: ' . gmdate('D, d M Y H:i:s') . ' GMT');
        echo $data;
    }

    /**
     * POSTNET and PLANET barcodes.
     * Used by U.S. Postal Service for automated mail sorting
     * @param string $code   zip Must be a string containing a zip code of the form DDDDD or DDDDD-DDDD.
     * @param bool   $planet if true print the PLANET barcode, otherwise print POSTNET
     * @return void
     */
    protected function Postal(string $code, bool $planet = false): void
    {
        // bar length
        if ($planet) {
            $barLength = [
                0 => [1, 1, 2, 2, 2],
                1 => [2, 2, 2, 1, 1],
                2 => [2, 2, 1, 2, 1],
                3 => [2, 2, 1, 1, 2],
                4 => [2, 1, 2, 2, 1],
                5 => [2, 1, 2, 1, 2],
                6 => [2, 1, 1, 2, 2],
                7 => [1, 2, 2, 2, 1],
                8 => [1, 2, 2, 1, 2],
                9 => [1, 2, 1, 2, 2]
            ];
        }
        else {
            $barLength = [
                0 => [2, 2, 1, 1, 1],
                1 => [1, 1, 1, 2, 2],
                2 => [1, 1, 2, 1, 2],
                3 => [1, 1, 2, 2, 1],
                4 => [1, 2, 1, 1, 2],
                5 => [1, 2, 1, 2, 1],
                6 => [1, 2, 2, 1, 1],
                7 => [2, 1, 1, 1, 2],
                8 => [2, 1, 1, 2, 1],
                9 => [2, 1, 2, 1, 1]
            ];
        }
        $bararray = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 2, 'barcode' => []];
        $position = 0;
        $code     = str_replace('-', '', $code);
        $code     = str_replace(' ', '', $code);
        $len      = strlen($code);
        // calculate checksum
        $sum = 0;
        for ($index = 0; $index < $len; ++$index) {
            $sum += intval($code[$index]);
        }
        $checkDecimal = ($sum % 10);
        if ($checkDecimal > 0) {
            $checkDecimal = (10 - $checkDecimal);
        }
        $code .= $checkDecimal;
        $len  = strlen($code);
        // start bar
        $bararray['barcode'][$position++] = ['height' => 2, 'position' => self::TOP, 'stripe' => self::STRIPE, 'width' => 1];
        $bararray['barcode'][$position++] = ['height' => 2, 'position' => self::TOP, 'stripe' => self::SPACE, 'width' => 1];
        $bararray['maxWidth']             += 2;
        for ($outer = 0; $outer < $len; ++$outer) {
            for ($inner = 0; $inner < 5; ++$inner) {
                $height      = $barLength[$code[$outer]][$inner];
                $topPosition = floor(1 / $height);

                $bararray['barcode'][$position++] = ['height' => $height, 'position' => $topPosition, 'stripe' => self::STRIPE, 'width' => 1];
                $bararray['barcode'][$position++] = ['height' => 2, 'position' => self::TOP, 'stripe' => self::SPACE, 'width' => 1];
                $bararray['maxWidth']             += 2;
            }
        }
        // end bar
        $bararray['barcode'][$position] = ['height' => 2, 'position' => self::TOP, 'stripe' => self::STRIPE, 'width' => 1];
        $bararray['maxWidth']           += 1;
        $this->barcode                  = $bararray;
    }

    /**
     * Return a PNG image representation of barcode.
     * @param int   $width  Width of a single bar element in pixels.
     * @param int   $height Height of a single bar element in pixels.
     * @param array $color  RGB (0-255) foreground color for bar elements (background is transparent).
     * @return string|false
     */
    public function PNGData(int $width = 2, int $height = 30, array $color = PDF::BLACK): string|false
    {
        $png     = imagecreate(($this->barcode['maxWidth'] * $width), $height);
        $bgColor = imagecolorallocate($png, 255, 255, 255);
        imagecolortransparent($png, $bgColor);
        $fgColor = imagecolorallocate($png, $color[0], $color[1], $color[2]);
        // print bars
        $x = 0;
        foreach ($this->barcode['barcode'] as $stripe) {
            $bw = round(($stripe['width'] * $width), 3);
            $bh = round(($stripe['height'] * $height / $this->barcode['maxHeight']), 3);
            if ($stripe['stripe']) {
                $y = round(($stripe['position'] * $height / $this->barcode['maxHeight']), 3);
                // draw a vertical bar
                imagefilledrectangle($png, $x, $y, ($x + $bw - 1), ($y + $bh - 1), $fgColor);
            }
            $x += $bw;
        }

        ob_start();
        imagepng($png);
        $imagedata = ob_get_clean();
        imagedestroy($png);
        return $imagedata;
    }

    /**
     * RMS4CC - CBC - KIX
     * RMS4CC (Royal Mail 4-state Customer Code) - CBC (Customer Bar Code) - KIX (Klant index - Customer index)
     * RM4SCC is the name of the barcode symbology used by the Royal Mail for its Cleanmail service.
     * @param string $code code to print
     * @param bool   $kix  if true prints the KIX variation (doesn't use the start and end symbols, and the checksum) - in this case the house number must be sufficed with an X and placed at the end of the code.
     * @return void
     */
    protected function RMS4CC(string $code, bool $kix = false): void
    {
        $barmode    = [
            '0' => [3, 3, 2, 2], '1' => [3, 4, 1, 2], '2' => [3, 4, 2, 1], '3' => [4, 3, 1, 2], '4' => [4, 3, 2, 1], '5' => [4, 4, 1, 1],
            '6' => [3, 1, 4, 2], '7' => [3, 2, 3, 2], '8' => [3, 2, 4, 1], '9' => [4, 1, 3, 2], 'A' => [4, 1, 4, 1], 'B' => [4, 2, 3, 1],
            'C' => [3, 1, 2, 4], 'D' => [3, 2, 1, 4], 'E' => [3, 2, 2, 3], 'F' => [4, 1, 1, 4], 'G' => [4, 1, 2, 3], 'H' => [4, 2, 1, 3],
            'I' => [1, 3, 4, 2], 'J' => [1, 4, 3, 2], 'K' => [1, 4, 4, 1], 'L' => [2, 3, 3, 2], 'M' => [2, 3, 4, 1], 'N' => [2, 4, 3, 1],
            'O' => [1, 3, 2, 4], 'P' => [1, 4, 1, 4], 'Q' => [1, 4, 2, 3], 'R' => [2, 3, 1, 4], 'S' => [2, 3, 2, 3], 'T' => [2, 4, 1, 3],
            'U' => [1, 1, 4, 4], 'V' => [1, 2, 3, 4], 'W' => [1, 2, 4, 3], 'X' => [2, 1, 3, 4], 'Y' => [2, 1, 4, 3], 'Z' => [2, 2, 3, 3]
        ];
        $code       = strtoupper($code);
        $codeLength = strlen($code);

        $bararray = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 3, 'barcode' => []];
        $notKIX   = !$kix;
        if ($notKIX) {
            // table for checksum calculation (row,col)
            $checkTable = [
                '0' => [1, 1], '1' => [1, 2], '2' => [1, 3], '3' => [1, 4], '4' => [1, 5], '5' => [1, 0], '6' => [2, 1], '7' => [2, 2], '8' => [2, 3],
                '9' => [2, 4], 'A' => [2, 5], 'B' => [2, 0], 'C' => [3, 1], 'D' => [3, 2], 'E' => [3, 3], 'F' => [3, 4], 'G' => [3, 5], 'H' => [3, 0],
                'I' => [4, 1], 'J' => [4, 2], 'K' => [4, 3], 'L' => [4, 4], 'M' => [4, 5], 'N' => [4, 0], 'O' => [5, 1], 'P' => [5, 2], 'Q' => [5, 3],
                'R' => [5, 4], 'S' => [5, 5], 'T' => [5, 0], 'U' => [0, 1], 'V' => [0, 2], 'W' => [0, 3], 'X' => [0, 4], 'Y' => [0, 5], 'Z' => [0, 0],
            ];
            $row        = 0;
            $col        = 0;
            for ($index = 0; $index < $codeLength; ++$index) {
                $row += $checkTable[$code[$index]][0];
                $col += $checkTable[$code[$index]][1];
            }
            $row  %= 6;
            $col  %= 6;
            $chk  = array_keys($checkTable, [$row, $col]);
            $code .= $chk[0];
            ++$codeLength;
        }
        $position = 0;
        if ($notKIX) {
            // start bar
            $bararray['barcode'][$position++] = ['height' => 2, 'position' => self::TOP, 'stripe' => self::STRIPE, 'width' => 1];
            $bararray['barcode'][$position++] = ['height' => 2, 'position' => self::TOP, 'stripe' => self::SPACE, 'width' => 1];
            $bararray['maxWidth']             += 2;
        }
        for ($outer = 0; $outer < $codeLength; ++$outer) {
            for ($inner = 0; $inner < 4; ++$inner) {
                switch ($barmode[$code[$outer]][$inner]) {
                    case 2:
                        $topPosition = 0;
                        $height      = 3;
                        break;
                    case 3:
                        $topPosition = 1;
                        $height      = 1;
                        break;
                    case 4:
                        $topPosition = 1;
                        $height      = 2;
                        break;
                    default:
                        $topPosition = 0;
                        $height      = 2;
                }
                $bararray['barcode'][$position++] = ['height' => $height, 'position' => $topPosition, 'stripe' => self::STRIPE, 'width' => 1];
                $bararray['barcode'][$position++] = ['height' => 2, 'position' => self::TOP, 'stripe' => self::SPACE, 'width' => 1];
                $bararray['maxWidth']             += 2;
            }
        }

        if ($notKIX) {
            // stop bar
            $bararray['barcode'][$position] = ['height' => 3, 'position' => self::TOP, 'stripe' => self::STRIPE, 'width' => 1];
            $bararray['maxWidth']           += 1;
        }

        $this->barcode = $bararray;
    }

    /**
     * Standard 2 of 5 barcodes.
     * Used in airline ticket marking, photofinishing
     * Contains digits (0 to 9) and encodes the data only in the width of bars.
     * @param string $code
     * @param bool   $checksum if true add a checksum to the code
     * @return void
     */
    protected function S25(string $code, bool $checksum = false): void
    {
        $chr = [
            '0' => '10101110111010', '1' => '11101010101110', '2' => '10111010101110', '3' => '11101110101010', '4' => '10101110101110',
            '5' => '11101011101010', '6' => '10111011101010', '7' => '10101011101110', '8' => '11101010111010', '9' => '10111010111010',
        ];
        if ($checksum) {
            // add checksum
            $code .= $this->S25Checksum($code);
        }
        if ((strlen($code) % 2) != 0) {
            // add leading zero if code-length is odd
            $code = '0' . $code;
        }
        $seq  = '1110111010';
        $clen = strlen($code);
        for ($index = 0; $index < $clen; ++$index) {
            $digit = $code[$index];
            if (!isset($chr[$digit])) {
                // invalid character
                return;
            }
            $seq .= $chr[$digit];
        }
        $seq           .= '111010111';
        $bararray      = ['code' => $code, 'maxWidth' => 0, 'maxHeight' => 1, 'barcode' => []];
        $this->barcode = $this->sequence2Array($seq, $bararray);
    }

    /**
     * Checksum for standard 2 of 5 barcodes.
     * @param string $code code to process.
     * @return int
     */
    protected function S25Checksum(string $code): int
    {
        $length = strlen($code);
        $sum    = 0;

        for ($index = 0; $index < $length; $index += 2) {
            $sum += (int) $code[$index];
        }

        $sum *= 3;
        for ($index = 1; $index < $length; $index += 2) {
            $sum += (int) $code[$index];
        }

        $remainder = $sum % 10;
        if ($remainder > 0) {
            $remainder = (10 - $remainder);
        }

        return $remainder;
    }

    /**
     * Convert binary barcode sequence to WarnockPDF barcode array.
     * @param string $sequence barcode as binary sequence.
     * @param array  $barcode  barcode array to fill up
     * @return array
     */
    protected function sequence2Array(string $sequence, array $barcode): array
    {
        $len   = strlen($sequence);
        $width = 0;
        $key   = 0;
        for ($index = 0; $index < $len; ++$index) {
            $width += 1;
            if (($index == ($len - 1)) or (($index < ($len - 1)) and ($sequence[$index] != $sequence[($index + 1)]))) {
                $stripe = $sequence[$index] === '1';

                $barcode['barcode'][$key] = ['height' => 1, 'position' => self::TOP, 'stripe' => $stripe, 'width' => $width];
                $barcode['maxWidth']      += $width;
                ++$key;
                $width = 0;
            }
        }

        return $barcode;
    }

    /**
     * Return an SVG string representation of barcode.
     * @param int    $width  Minimum width of a single bar in user units.
     * @param int    $height Height of barcode in user units.
     * @param string $color  Foreground color (in SVG format) for bar elements (background is transparent).
     * @return string
     * @see self::UNITS
     */
    public function SVGCode(int $width = 2, int $height = 30, string $color = 'black'): string
    {
        $replace  = ["\0" => '', '&' => '&amp;', '<' => '&lt;', '>' => '&gt;'];
        $svgWidth = round(($this->barcode['maxWidth'] * $width), 3);

        $svg = $this->SVGHeader($svgWidth, $height, $replace);
        $svg .= "\t" . '<g id="bars" fill="' . $color . '" stroke="none">' . "\n";

        // print bars
        $x = 0;
        foreach ($this->barcode['barcode'] as $stripe) {
            $bw = round(($stripe['width'] * $width), 3);
            $bh = round(($stripe['height'] * $height / $this->barcode['maxHeight']), 3);
            if ($stripe['stripe']) {
                $y = round(($stripe['position'] * $height / $this->barcode['maxHeight']), 3);
                // draw a vertical bar
                $svg .= "\t\t" . '<rect x="' . $x . '" y="' . $y . '" width="' . $bw . '" height="' . $bh . '" />' . "\n";
            }
            $x += $bw;
        }

        $svg .= "\t" . '</g>' . "\n";
        $svg .= '</svg>' . "\n";

        return $svg;
    }

}