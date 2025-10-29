<?php

/**
 * @package     encrypt-fields module
 * @author      giorgio <g.consorti@lynxlab.com>
 * @copyright   Copyright (c) 2025, Lynx s.r.l.
 * @license     http://www.gnu.org/licenses/gpl-2.0.html GNU Public License v.2
 * @version     0.1
 */

namespace Lynxlab\ADA\Module\Encryptfields;

use Lynxlab\ADA\Module\Encryptfields\Exceptions\EncryptFieldsException;
use phpseclib3\Crypt\AES;
use Throwable;

/**
 * Utility class to manage and crypt/decrypt data.
 */
class CypherUtils
{
    private const KEYFILE = MODULES_ENCRYPTFIELDS_KEYFILE;
    private const SEPARATOR = '.';
    private const AESMODE = 'gcm';
    private const TAGLENGTH = 16; // GCM tag length is 16 bytes
    private const KEYLENGTH = 16;
    private const IVLENGTH = 12; // Initialization vector length

    /**
     * Encryption key
     *
     * @var string
     */
    private static $key = null;

    /**
     * Checks if the key file is there
     *
     * @return bool
     */
    public static function checkKeyFile()
    {
        return is_file(self::KEYFILE);
    }

    /**
     * Loads of generates and save the encryption key
     *
     * @return string
     */
    private static function generateSharedKey()
    {
        $key = null;
        if (static::checkKeyFile()) {
            $key = @file_get_contents(self::KEYFILE);
            if (false === $key) {
                $key = null;
                throw new EncryptFieldsException('Enc key file exists but is not readable');
            }
        } else {
            // Implement your logic here. Just return a 16 chars string
            $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ@!-;:.';
            $key = substr(str_shuffle($characters), 0, self::KEYLENGTH);
            if (false === @file_put_contents(self::KEYFILE, $key, LOCK_EX)) {
                $key = null;
                throw new EncryptFieldsException('Enc key file is not writable');
            } else {
                if (php_sapi_name() == "cli") {
                    echo EchoHelper::error(sprintf("\n*** key file generated at %s, check that is readable by the webserver user! ***", self::KEYFILE), true);
                }
                chmod(self::KEYFILE, 0400);
            }
        }
        return $key;
    }

    /**
     * Encrypt the passed string.
     *
     * @param string $data
     * @return string
     */
    public function encrypt(string $message): string
    {
        // Do not use a static IV because if you encrypt the same messages
        // again the encrypted data will be the same and that leaks information.
        $iv = base64_encode(openssl_random_pseudo_bytes(self::IVLENGTH));

        $aes = new AES(self::AESMODE);
        $aes->setKey($this->getKey());
        $aes->setNonce($iv);
        $encrypted = implode(
            self::SEPARATOR,
            [
                base64_encode($aes->encrypt($message) . $aes->getTag()),
                $iv,
            ]
        );
        return $encrypted;
    }

    /**
     * Decrypt the passed string.
     *
     * @param string $data
     * @return string
     */
    public function decrypt(string $encrypted): string
    {
        try {
            $parts = explode(self::SEPARATOR, $encrypted);
            $encWithTag = $parts[0] ?? null;
            $iv = $parts[1] ?? null;
            $combined = base64_decode($encWithTag);
            $tag = substr($combined, -self::TAGLENGTH);
            $encryptedMessage = substr($combined, 0, -self::TAGLENGTH);

            $aes = new AES(self::AESMODE);
            $aes->setKey($this->getKey());
            $aes->setNonce($iv);
            $aes->setTag($tag);
            $decryptedMessage = $aes->decrypt($encryptedMessage);
            return $decryptedMessage;
        } catch (Throwable $e) {
            throw new EncryptFieldsException($e->getMessage(), $e->getCode());
        }
    }

    /**
     * Get the value of key.
     */
    private function getKey()
    {
        if (static::$key === null) {
            static::$key = static::generateSharedKey();
        }
        return static::$key;
    }

    /**
     * Runs the data handler toggle encription method
     *
     * @param boolean $dryRun true to not write to the database
     * @param boolean $encrypt true to do encryption. false to do decryption
     * @return void
     */
    public static function toggleEncryption($dryRun = true, $encrypt = false)
    {
        AMAEncryptFieldsDataHandler::toggleEncryption($dryRun, $encrypt);
    }
}
