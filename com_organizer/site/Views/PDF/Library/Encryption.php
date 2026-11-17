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

namespace THM\Organizer\Views\PDF\Library;

trait Encryption
{
    protected bool $encrypted = false;
    protected array $encryptionSettings = [];
    protected array $lastComputedKey = [];
    protected string $lastEncryptedKey = '';

    /**
     * Convert password for AES-256 encryption mode
     * @param string $password password
     * @return string
     */
    protected function convertPasswordAES256(string $password): string
    {
        $return     = ''; // password to be returned
        $characters = $this->utf8StringToArray($password);

        foreach ($characters as $character) {
            $return .= $this->unicodeCharacter($character, $this->isUnicode);
        }

        return substr($return, 0, 127);
    }

    /**
     * Returns the input text encrypted using AES algorithm and the specified key. Text is padded to 16-byte blocks.
     * @param string $key
     * @param string $text
     * @return string
     */
    public function encryptAESPadded(string $key, string $text): string
    {
        // padding (RFC 2898, PKCS #5: Password-Based Cryptography Specification Version 2.0)
        $padding = 16 - (strlen($text) % 16);
        $text    .= str_repeat(chr($padding), $padding);
        $algo    = 'aes-256-cbc';
        if (strlen($key) === 16) {
            $algo = 'aes-128-cbc';
        }
        $iv   = openssl_random_pseudo_bytes(openssl_cipher_iv_length($algo));
        $text = openssl_encrypt($text, $algo, $key, OPENSSL_RAW_DATA, $iv);
        return $iv . substr($text, 0, -16);
    }

    /**
     * Returns the input text encrypted using AES algorithm and the specified key. Text is not padded.
     * @param string $key
     * @param string $text
     * @return string
     */
    public function encryptAESPadless(string $key, string $text): string
    {
        $algo = 'aes-256-cbc';
        if (strlen($key) == 16) {
            $algo = 'aes-128-cbc';
        }
        $iv   = str_repeat("\x00", openssl_cipher_iv_length($algo));
        $text = openssl_encrypt($text, $algo, $key, OPENSSL_RAW_DATA, $iv);
        return substr($text, 0, -16);
    }

    /**
     * Encrypt the input string.
     * @param int    $objectNumber object number
     * @param string $string       data string to encrypt
     * @return string
     */
    protected function encryptData(int $objectNumber, string $string): string
    {
        if (!$this->encrypted) {
            return $string;
        }
        return match ($this->encryptionSettings['mode']) {
            self::RC4_40, self::RC4_128 => $this->encryptRC4($this->objectKey($objectNumber), $string),
            self::AES_128 => $this->encryptAESPadded($this->objectKey($objectNumber), $string),
            self::AES_256 => $this->encryptAESPadded($this->encryptionSettings['key'], $string),
            default => $string,
        };
    }

    /**
     * Encrypts a string using MD5 and returns it's value as a binary string.
     * @param string $string
     * @return string
     */
    public function encryptMD5(string $string): string
    {
        return pack('H*', md5($string));
    }

    /**
     * Returns the input text encrypted using RC4 algorithm and the specified key.
     * RC4 is the standard encryption algorithm used in PDF format.
     * @param string $key
     * @param string $text
     * @return string
     */
    public function encryptRC4(string $key, string $text): string
    {
        if ($this->lastEncryptedKey != $key) {
            $k   = str_repeat($key, (int) ((256 / strlen($key)) + 1));
            $rc4 = range(0, 255);
            $j   = 0;
            for ($i = 0; $i < 256; ++$i) {
                $t       = $rc4[$i];
                $j       = ($j + $t + ord($k[$i])) % 256;
                $rc4[$i] = $rc4[$j];
                $rc4[$j] = $t;
            }
            $this->lastEncryptedKey = $key;
            $this->lastComputedKey  = $rc4;
        }
        else {
            $rc4 = $this->lastComputedKey;
        }
        $len = strlen($text);
        $a   = 0;
        $b   = 0;
        $out = '';
        for ($i = 0; $i < $len; ++$i) {
            $a       = ($a + 1) % 256;
            $t       = $rc4[$a];
            $b       = ($b + $t) % 256;
            $rc4[$a] = $rc4[$b];
            $rc4[$b] = $t;
            $k       = $rc4[($rc4[$a] + $rc4[$b]) % 256];
            $out     .= chr(ord($text[$i]) ^ $k);
        }

        return $out;
    }

    /**
     * Compute encryption key depending on object number where the encrypted data is stored.
     * This is used for all strings and streams without crypt filter specifier.
     * @param int $objectNumber
     * @return string
     */
    protected function objectKey(int $objectNumber): string
    {
        $objectKey = $this->encryptionSettings['key'] . pack('VXxx', $objectNumber);

        if ($this->encryptionSettings['mode'] == 2) { // AES-128
            // AES padding
            $objectKey .= "\x73\x41\x6C\x54"; // sAlT
        }

        $objectKey = substr($this->encryptMD5($objectKey), 0, (($this->encryptionSettings['Length'] / 8) + 5));
        return substr($objectKey, 0, 16);
    }

    /**
     * Return the permission code used on encryption (P value).
     * @param array $permissions the set of permissions (specify the ones you want to block).
     * @param int   $mode        encryption strength: 0 = RC4 40 bit; 1 = RC4 128 bit; 2 = AES 128 bit; 3 = AES 256 bit.
     * @author Nicola Asuni
     */
    public function permissionsToInteger(array $permissions, int $mode = self::RC4_40): int
    {
        $protection = 2147422012; // 32 bit: (01111111 11111111 00001111 00111100)
        if (!in_array($mode, self::ENCRYPTION_MODES)) {
            return $protection;
        }

        $options = [
            'owner'       => 2, // bit 2 -- inverted logic: cleared by default
            'print'       => 4, // bit 3
            'modify'      => 8, // bit 4
            'copy'        => 16, // bit 5
            'annot-forms' => 32, // bit 6
            'fill-forms'  => 256, // bit 9
            'extract'     => 512, // bit 10
            'assemble'    => 1024,// bit 11
            'print-high'  => 2048 // bit 12
        ];

        foreach ($permissions as $permission) {
            if (isset($options[$permission])) {
                // the logic for bit 2 is inverted (cleared by default)
                $protection = $options[$permission] == 2 ? $protection + $options[$permission] : $protection - $options[$permission];
            }
        }

        return $protection;
    }

    /**
     * Convert encryption P value to a string of bytes, low-order byte first.
     * @param string $permissionValue 32bit encryption permission value (P value)
     * @return string
     */
    public function permissionsToString(string $permissionValue): string
    {
        $binary = sprintf('%032b', $permissionValue);
        $return = chr(bindec(substr($binary, 24, 8)));
        $return .= chr(bindec(substr($binary, 16, 8)));
        $return .= chr(bindec(substr($binary, 8, 8)));
        $return .= chr(bindec(substr($binary, 0, 8)));
        return $return;
    }

    /**
     * Returns a string containing random data to be used as a seed for encryption methods.
     * @param string $seed starting seed value
     * @return string containing random data
     * @author Nicola Asuni
     */
    public function randomSeed(string $seed = ''): string
    {
        $rnd = uniqid(rand() . microtime(true), true);
        $rnd .= posix_getpid();
        $rnd .= openssl_random_pseudo_bytes(512);
        return $rnd . $seed . __FILE__ . serialize($_SERVER) . microtime(true);
    }

    /**
     * Compute U value for encryption settings
     * @return string
     */
    protected function uValue(): string
    {
        $settings =& $this->encryptionSettings;
        if ($settings['mode'] === self::RC4_40) {
            return $this->encryptRC4($settings['key'], self::ENCRYPTION_PADDING);
        }

        if ($settings['mode'] === self::AES_256) {
            $seed = $this->encryptMD5($this->randomSeed());
            // User Validation Salt
            $settings['UVS'] = substr($seed, 0, 8);
            // User Key Salt
            $settings['UKS'] = substr($seed, 8, 16);
            return hash('sha256', $settings['user_password'] . $settings['UVS'], true) . $settings['UVS'] . $settings['UKS'];
        }

        $tmp    = $this->encryptMD5(self::ENCRYPTION_PADDING . $settings['fileid']);
        $enc    = $this->encryptRC4($settings['key'], $tmp);
        $length = strlen($tmp);
        for ($outer = 1; $outer <= 19; ++$outer) {
            $key = '';
            for ($inner = 0; $inner < $length; ++$inner) {
                $key .= chr(ord($settings['key'][$inner]) ^ $outer);
            }
            $enc = $this->encryptRC4($key, $enc);
        }
        $enc .= str_repeat("\x00", 16);
        return substr($enc, 0, 32);
    }

/////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////PROCESSED


///////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////UNPROCESSED


    /**
     * Put encryption on PDF document.
     * @author Nicola Asuni
     */
    protected function _putencryption()
    {
        if (!$this->encrypted) {
            return;
        }
        $this->encryptionSettings['objid'] = $this->_newobj();
        $out                               = '<<';
        if (!isset($this->encryptionSettings['Filter']) or empty($this->encryptionSettings['Filter'])) {
            $this->encryptionSettings['Filter'] = 'Standard';
        }
        $out .= ' /Filter /' . $this->encryptionSettings['Filter'];
        if (isset($this->encryptionSettings['SubFilter']) and !empty($this->encryptionSettings['SubFilter'])) {
            $out .= ' /SubFilter /' . $this->encryptionSettings['SubFilter'];
        }
        if (!isset($this->encryptionSettings['V']) or empty($this->encryptionSettings['V'])) {
            $this->encryptionSettings['V'] = 1;
        }
        // V is a code specifying the algorithm to be used in encrypting and decrypting the document
        $out .= ' /V ' . $this->encryptionSettings['V'];
        if (isset($this->encryptionSettings['Length']) and !empty($this->encryptionSettings['Length'])) {
            // The length of the encryption key, in bits. The value shall be a multiple of 8, in the range 40 to 256
            $out .= ' /Length ' . $this->encryptionSettings['Length'];
        }
        else {
            $out .= ' /Length 40';
        }
        if ($this->encryptionSettings['V'] >= 4) {
            if (!isset($this->encryptionSettings['StmF']) or empty($this->encryptionSettings['StmF'])) {
                $this->encryptionSettings['StmF'] = 'Identity';
            }
            if (!isset($this->encryptionSettings['StrF']) or empty($this->encryptionSettings['StrF'])) {
                // The name of the crypt filter that shall be used when decrypting all strings in the document.
                $this->encryptionSettings['StrF'] = 'Identity';
            }
            // A dictionary whose keys shall be crypt filter names and whose values shall be the corresponding crypt filter dictionaries.
            if (isset($this->encryptionSettings['CF']) and !empty($this->encryptionSettings['CF'])) {
                $out .= ' /CF <<';
                $out .= ' /' . $this->encryptionSettings['StmF'] . ' <<';
                $out .= ' /Type /CryptFilter';
                if (isset($this->encryptionSettings['CF']['CFM']) and !empty($this->encryptionSettings['CF']['CFM'])) {
                    // The method used
                    $out .= ' /CFM /' . $this->encryptionSettings['CF']['CFM'];
                    if ($this->encryptionSettings['pubkey']) {
                        $out .= ' /Recipients [';
                        foreach ($this->encryptionSettings['Recipients'] as $rec) {
                            $out .= ' <' . $rec . '>';
                        }
                        $out .= ' ]';
                        if (isset($this->encryptionSettings['CF']['EncryptMetadata']) and (!$this->encryptionSettings['CF']['EncryptMetadata'])) {
                            $out .= ' /EncryptMetadata false';
                        }
                        else {
                            $out .= ' /EncryptMetadata true';
                        }
                    }
                }
                else {
                    $out .= ' /CFM /None';
                }
                if (isset($this->encryptionSettings['CF']['AuthEvent']) and !empty($this->encryptionSettings['CF']['AuthEvent'])) {
                    // The event to be used to trigger the authorization that is required to access encryption keys used by this filter.
                    $out .= ' /AuthEvent /' . $this->encryptionSettings['CF']['AuthEvent'];
                }
                else {
                    $out .= ' /AuthEvent /DocOpen';
                }
                if (isset($this->encryptionSettings['CF']['Length']) and !empty($this->encryptionSettings['CF']['Length'])) {
                    // The bit length of the encryption key.
                    $out .= ' /Length ' . $this->encryptionSettings['CF']['Length'];
                }
                $out .= ' >> >>';
            }
            // The name of the crypt filter that shall be used by default when decrypting streams.
            $out .= ' /StmF /' . $this->encryptionSettings['StmF'];
            // The name of the crypt filter that shall be used when decrypting all strings in the document.
            $out .= ' /StrF /' . $this->encryptionSettings['StrF'];
            if (isset($this->encryptionSettings['EFF']) and !empty($this->encryptionSettings['EFF'])) {
                // The name of the crypt filter that shall be used when encrypting embedded file streams that do not have their own crypt filter specifier.
                $out .= ' /EFF /' . $this->encryptionSettings[''];
            }
        }
        // Additional encryption dictionary entries for the standard security handler
        if ($this->encryptionSettings['pubkey']) {
            if (($this->encryptionSettings['V'] < 4) and isset($this->encryptionSettings['Recipients']) and !empty($this->encryptionSettings['Recipients'])) {
                $out .= ' /Recipients [';
                foreach ($this->encryptionSettings['Recipients'] as $rec) {
                    $out .= ' <' . $rec . '>';
                }
                $out .= ' ]';
            }
        }
        else {
            $out .= ' /R';
            if ($this->encryptionSettings['V'] == 5) { // AES-256
                $out .= ' 5';
                $out .= ' /OE (' . StaticMethods::_escape($this->encryptionSettings['OE']) . ')';
                $out .= ' /UE (' . StaticMethods::_escape($this->encryptionSettings['UE']) . ')';
                $out .= ' /Perms (' . StaticMethods::_escape($this->encryptionSettings['perms']) . ')';
            }
            elseif ($this->encryptionSettings['V'] == 4) { // AES-128
                $out .= ' 4';
            }
            elseif ($this->encryptionSettings['V'] < 2) { // RC-40
                $out .= ' 2';
            }
            else { // RC-128
                $out .= ' 3';
            }
            $out .= ' /O (' . StaticMethods::_escape($this->encryptionSettings['O']) . ')';
            $out .= ' /U (' . StaticMethods::_escape($this->encryptionSettings['U']) . ')';
            $out .= ' /P ' . $this->encryptionSettings['P'];
            if (isset($this->encryptionSettings['EncryptMetadata']) and (!$this->encryptionSettings['EncryptMetadata'])) {
                $out .= ' /EncryptMetadata false';
            }
            else {
                $out .= ' /EncryptMetadata true';
            }
        }
        $out .= ' >>';
        $out .= "\n" . 'endobj';
        $this->append($out);
    }

    /**
     * Compute UE value (used for encryption)
     * @return string UE value
     * @author Nicola Asuni
     */
    protected function _UEvalue()
    {
        $hashkey = hash('sha256', $this->encryptionSettings['user_password'] . $this->encryptionSettings['UKS'], true);
        return $this->encryptAESPadless($hashkey, $this->encryptionSettings['key']);
    }

    /**
     * Compute O value (used for encryption)
     * @return string O value
     * @author Nicola Asuni
     */
    protected function _Ovalue()
    {
        if ($this->encryptionSettings['mode'] < 3) { // RC4-40, RC4-128, AES-128
            $tmp = $this->encryptMD5($this->encryptionSettings['owner_password']);
            if ($this->encryptionSettings['mode'] > 0) {
                for ($i = 0; $i < 50; ++$i) {
                    $tmp = $this->encryptMD5($tmp);
                }
            }
            $owner_key = substr($tmp, 0, ($this->encryptionSettings['Length'] / 8));
            $enc       = $this->encryptRC4($owner_key, $this->encryptionSettings['user_password']);
            if ($this->encryptionSettings['mode'] > 0) {
                $len = strlen($owner_key);
                for ($i = 1; $i <= 19; ++$i) {
                    $ek = '';
                    for ($j = 0; $j < $len; ++$j) {
                        $ek .= chr(ord($owner_key[$j]) ^ $i);
                    }
                    $enc = $this->encryptRC4($ek, $enc);
                }
            }
            return $enc;
        }
        elseif ($this->encryptionSettings['mode'] == 3) { // AES-256
            $seed = $this->encryptMD5($this->randomSeed());
            // Owner Validation Salt
            $this->encryptionSettings['OVS'] = substr($seed, 0, 8);
            // Owner Key Salt
            $this->encryptionSettings['OKS'] = substr($seed, 8, 16);
            return hash('sha256', $this->encryptionSettings['owner_password'] . $this->encryptionSettings['OVS'] . $this->encryptionSettings['U'], true) . $this->encryptionSettings['OVS'] . $this->encryptionSettings['OKS'];
        }
    }

    /**
     * Compute OE value (used for encryption)
     * @return string OE value
     * @author Nicola Asuni
     */
    protected function _OEvalue()
    {
        $hashkey = hash('sha256', $this->encryptionSettings['owner_password'] . $this->encryptionSettings['OKS'] . $this->encryptionSettings['U'], true);
        return $this->encryptAESPadless($hashkey, $this->encryptionSettings['key']);
    }

    /**
     * Compute encryption key
     * @author Nicola Asuni
     */
    protected function _generateencryptionkey()
    {
        $keybytelen = ($this->encryptionSettings['Length'] / 8);
        if (!$this->encryptionSettings['pubkey']) { // standard mode
            if ($this->encryptionSettings['mode'] === self::AES_256) {
                $this->encryptionSettings['key'] = substr(hash('sha256', $this->randomSeed(), true), 0, $keybytelen);
                // truncate passwords
                $this->encryptionSettings['user_password']  = $this->convertPasswordAES256($this->encryptionSettings['user_password']);
                $this->encryptionSettings['owner_password'] = $this->convertPasswordAES256($this->encryptionSettings['owner_password']);
                // Compute U value
                $this->encryptionSettings['U'] = $this->uValue();
                // Compute UE value
                $this->encryptionSettings['UE'] = $this->_UEvalue();
                // Compute O value
                $this->encryptionSettings['O'] = $this->_Ovalue();
                // Compute OE value
                $this->encryptionSettings['OE'] = $this->_OEvalue();
                // Compute P value
                $this->encryptionSettings['P'] = $this->encryptionSettings['protection'];
                // Computing the encryption dictionary's Perms (permissions) value
                $perms = $this->permissionsToString($this->encryptionSettings['protection']); // bytes 0-3
                $perms .= chr(255) . chr(255) . chr(255) . chr(255); // bytes 4-7
                if (isset($this->encryptionSettings['CF']['EncryptMetadata']) and (!$this->encryptionSettings['CF']['EncryptMetadata'])) { // byte 8
                    $perms .= 'F';
                }
                else {
                    $perms .= 'T';
                }
                $perms                             .= 'adb'; // bytes 9-11
                $perms                             .= 'nick'; // bytes 12-15
                $this->encryptionSettings['perms'] = $this->encryptAESPadless($this->encryptionSettings['key'], $perms);
            }
            else { // RC4-40, RC4-128, AES-128
                // Pad passwords
                $this->encryptionSettings['user_password']  = substr($this->encryptionSettings['user_password'] . self::ENCRYPTION_PADDING, 0, 32);
                $this->encryptionSettings['owner_password'] = substr($this->encryptionSettings['owner_password'] . self::ENCRYPTION_PADDING, 0, 32);
                // Compute O value
                $this->encryptionSettings['O'] = $this->_Ovalue();
                // get default permissions (reverse byte order)
                $permissions = $this->permissionsToString($this->encryptionSettings['protection']);
                // Compute encryption key
                $tmp = $this->encryptMD5($this->encryptionSettings['user_password'] . $this->encryptionSettings['O'] . $permissions . $this->encryptionSettings['fileid']);
                if ($this->encryptionSettings['mode'] > 0) {
                    for ($i = 0; $i < 50; ++$i) {
                        $tmp = $this->encryptMD5(substr($tmp, 0, $keybytelen));
                    }
                }
                $this->encryptionSettings['key'] = substr($tmp, 0, $keybytelen);
                // Compute U value
                $this->encryptionSettings['U'] = $this->uValue();
                // Compute P value
                $this->encryptionSettings['P'] = $this->encryptionSettings['protection'];
            }
        }
        else { // Public-Key mode
            // random 20-byte seed
            $seed            = sha1($this->randomSeed(), true);
            $recipient_bytes = '';
            foreach ($this->encryptionSettings['pubkeys'] as $pubkey) {
                // for each public certificate
                if (isset($pubkey['p'])) {
                    $pkprotection = $this->permissionsToInteger($pubkey['p'], $this->encryptionSettings['mode']);
                }
                else {
                    $pkprotection = $this->encryptionSettings['protection'];
                }
                // get default permissions (reverse byte order)
                $pkpermissions = $this->permissionsToString($pkprotection);
                // envelope data
                $envelope = $seed . $pkpermissions;
                // write the envelope data to a temporary file
                $tempkeyfile = $this->getObjFilename('key', $this->fileID);
                $f           = $this->openLocal($tempkeyfile, 'wb');
                if (!$f) {
                    $this->Error('Unable to create temporary key file: ' . $tempkeyfile);
                }
                $envelope_length = strlen($envelope);
                fwrite($f, $envelope, $envelope_length);
                fclose($f);
                $tempencfile = $this->getObjFilename('enc', $this->fileID);
                if (!openssl_pkcs7_encrypt($tempkeyfile, $tempencfile, $pubkey['c'], [], PKCS7_BINARY | PKCS7_DETACHED)) {
                    $this->Error('Unable to encrypt the file: ' . $tempkeyfile);
                }
                // read encryption signature
                $signature = file_get_contents($tempencfile, false, null, $envelope_length);
                // extract signature
                $signature = substr($signature, strpos($signature, 'Content-Disposition'));
                $tmparr    = explode("\n\n", $signature);
                $signature = trim($tmparr[1]);
                unset($tmparr);
                // decode signature
                $signature = base64_decode($signature);
                // convert signature to hex
                $hexsignature = current(unpack('H*', $signature));
                // store signature on recipients array
                $this->encryptionSettings['Recipients'][] = $hexsignature;
                // The bytes of each item in the Recipients array of PKCS#7 objects in the order in which they appear in the array
                $recipient_bytes .= $signature;
            }
            // calculate encryption key
            if ($this->encryptionSettings['mode'] == 3) { // AES-256
                $this->encryptionSettings['key'] = substr(hash('sha256', $seed . $recipient_bytes, true), 0, $keybytelen);
            }
            else { // RC4-40, RC4-128, AES-128
                $this->encryptionSettings['key'] = substr(sha1($seed . $recipient_bytes, true), 0, $keybytelen);
            }
        }
    }

    /**
     * Set document protection
     * Remark: the protection against modification is for people who have the full Acrobat product.
     * If you don't set any password, the document will open as usual. If you set a user password, the PDF viewer will ask for it before displaying the document. The master password, if different from the user one, can be used to get full access.
     * Note: protecting a document requires to encrypt it, which increases the processing time a lot. This can cause a PHP time-out in some cases, especially if the document contains images or fonts.
     * @param array       $permissions the set of permissions (specify the ones you want to block):<ul><li>print : Print the document;</li><li>modify : Modify the contents of the document by operations other than those controlled by 'fill-forms', 'extract' and 'assemble';</li><li>copy : Copy or otherwise extract text and graphics from the document;</li><li>annot-forms : Add or modify text annotations, fill in interactive form fields, and, if 'modify' is also set, create or modify interactive form fields (including signature fields);</li><li>fill-forms : Fill in existing interactive form fields (including signature fields), even if 'annot-forms' is not specified;</li><li>extract : Extract text and graphics (in support of accessibility to users with disabilities or for other purposes);</li><li>assemble : Assemble the document (insert, rotate, or delete pages and create bookmarks or thumbnail images), even if 'modify' is not set;</li><li>print-high : Print the document to a representation from which a faithful digital copy of the PDF content could be generated. When this is not set, printing is limited to a low-level representation of the appearance, possibly of degraded quality.</li><li>owner : (inverted logic - only for public-key) when set permits change of encryption and enables all other permissions.</li></ul>
     * @param string      $user_pass   user password. Empty by default.
     * @param string|null $owner_pass  owner password. If not specified, a random value is used.
     * @param int         $mode        encryption strength: 0 = RC4 40 bit; 1 = RC4 128 bit; 2 = AES 128 bit; 3 = AES 256 bit.
     * @param array|null  $pubkeys     array of recipients containing public-key certificates ('c') and permissions ('p'). For example: array(['c' => 'file://../examples/data/cert/tcpdf.crt', 'p' => ['print']])
     * @author Nicola Asuni
     */
    public function setProtection($permissions = ['print', 'modify', 'copy', 'annot-forms', 'fill-forms', 'extract', 'assemble', 'print-high'], $user_pass = '', $owner_pass = null, $mode = 0, $pubkeys = null)
    {
        $this->encryptionSettings['protection'] = $this->permissionsToInteger($permissions, $mode);
        if (($pubkeys !== null) and (is_array($pubkeys))) {
            // public-key mode
            $this->encryptionSettings['pubkeys'] = $pubkeys;
            if ($mode == 0) {
                // public-Key Security requires at least 128 bit
                $mode = 1;
            }
            if (!function_exists('openssl_pkcs7_encrypt')) {
                $this->Error('Public-Key Security requires openssl library.');
            }
            // Set Public-Key filter (available are: Entrust.PPKEF, Adobe.PPKLite, Adobe.PubSec)
            $this->encryptionSettings['pubkey'] = true;
            $this->encryptionSettings['Filter'] = 'Adobe.PubSec';
            $this->encryptionSettings['StmF']   = 'DefaultCryptFilter';
            $this->encryptionSettings['StrF']   = 'DefaultCryptFilter';
        }
        else {
            // standard mode (password mode)
            $this->encryptionSettings['pubkey'] = false;
            $this->encryptionSettings['Filter'] = 'Standard';
            $this->encryptionSettings['StmF']   = 'StdCF';
            $this->encryptionSettings['StrF']   = 'StdCF';
        }
        if ($mode > 1) { // AES
            if (!extension_loaded('openssl') && !extension_loaded('mcrypt')) {
                $this->Error('AES encryption requires openssl or mcrypt extension (http://www.php.net/manual/en/mcrypt.requirements.php).');
            }
            if (extension_loaded('openssl') && !in_array('aes-256-cbc', openssl_get_cipher_methods())) {
                $this->Error('AES encryption requires openssl/aes-256-cbc cypher.');
            }

            if (($mode == 3) and !function_exists('hash')) {
                // the Hash extension requires no external libraries and is enabled by default as of PHP 5.1.2.
                $this->Error('AES 256 encryption requires HASH Message Digest Framework (http://www.php.net/manual/en/book.hash.php).');
            }
        }
        if ($owner_pass === null) {
            $owner_pass = md5($this->randomSeed());
        }
        $this->encryptionSettings['user_password']  = $user_pass;
        $this->encryptionSettings['owner_password'] = $owner_pass;
        $this->encryptionSettings['mode']           = $mode;
        switch ($mode) {
            case 0:
            { // RC4 40 bit
                $this->encryptionSettings['V']         = 1;
                $this->encryptionSettings['Length']    = 40;
                $this->encryptionSettings['CF']['CFM'] = 'V2';
                break;
            }
            case 1:
            { // RC4 128 bit
                $this->encryptionSettings['V']         = 2;
                $this->encryptionSettings['Length']    = 128;
                $this->encryptionSettings['CF']['CFM'] = 'V2';
                if ($this->encryptionSettings['pubkey']) {
                    $this->encryptionSettings['SubFilter']  = 'adbe.pkcs7.s4';
                    $this->encryptionSettings['Recipients'] = [];
                }
                break;
            }
            case 2:
            { // AES 128 bit
                $this->encryptionSettings['V']            = 4;
                $this->encryptionSettings['Length']       = 128;
                $this->encryptionSettings['CF']['CFM']    = 'AESV2';
                $this->encryptionSettings['CF']['Length'] = 128;
                if ($this->encryptionSettings['pubkey']) {
                    $this->encryptionSettings['SubFilter']  = 'adbe.pkcs7.s5';
                    $this->encryptionSettings['Recipients'] = [];
                }
                break;
            }
            case 3:
            { // AES 256 bit
                $this->encryptionSettings['V']            = 5;
                $this->encryptionSettings['Length']       = 256;
                $this->encryptionSettings['CF']['CFM']    = 'AESV3';
                $this->encryptionSettings['CF']['Length'] = 256;
                if ($this->encryptionSettings['pubkey']) {
                    $this->encryptionSettings['SubFilter']  = 'adbe.pkcs7.s5';
                    $this->encryptionSettings['Recipients'] = [];
                }
                break;
            }
        }
        $this->encrypted                    = true;
        $this->encryptionSettings['fileid'] = StaticMethods::convertHexStringToString($this->fileID);
        $this->_generateencryptionkey();
    }
}