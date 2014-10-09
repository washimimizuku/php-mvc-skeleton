<?php
/**
 * RijndaelEncryption.class.php
 *
 * Path: /lib/utils/RijndaelEncryption.class.php
 *
 * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
 * @package utils
 */

/**
 * Main configurations class
 */
require_once(getenv('app_root').'/adserver/lib/core/ApplicationConfig.class.php');

/**
 * Rijndael Encryption class
 *
 * @author Nuno Barreto <n.barreto@ydigitalmedia.com>
 */
class RijndaelEncryption {
    /**
     * Encrypt string with Rijndael encryption
     * @param string $stringToEncrypt
     * @param string
     */
    public static function encrypt($stringToEncrypt) {
        $config = ApplicationConfig::getInstance();

        $hashKey = hash('sha256', $config->rijndaelSalt);
        return urlencode(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($hashKey), $stringToEncrypt, MCRYPT_MODE_CBC, md5(md5($hashKey)))));
    }

    /**
     * Decrypt string with Rijndael encryption
     * @param string $stringToDecrypt
     * @param string
     */
    public static function decrypt($stringToDecrypt) {
        $config = ApplicationConfig::getInstance();

        $hashKey = hash('sha256', $config->rijndaelSalt);
        return rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($hashKey), base64_decode(urldecode($stringToDecrypt)), MCRYPT_MODE_CBC, md5(md5($hashKey))), "\0");
    }

    /**
     * Encrypt string with Rijndael encryption
     * @param string $stringToEncrypt
     * @param string
     */
    public static function encryptComplex($stringToEncrypt) {
        $config = ApplicationConfig::getInstance();

        $hashKey = hash('sha256', $config->rijndaelSalt);
        return urlencode(base64_encode(mcrypt_encrypt(MCRYPT_RIJNDAEL_256, md5($hashKey), serialize($stringToEncrypt), MCRYPT_MODE_CBC, md5(md5($hashKey)))));
    }

    /**
     * Decrypt string with Rijndael encryption
     * @param string $stringToDecrypt
     * @param string
     */
    public static function decryptComplex($stringToDecrypt) {
        $config = ApplicationConfig::getInstance();

        $hashKey = hash('sha256', $config->rijndaelSalt);
        return unserialize(rtrim(mcrypt_decrypt(MCRYPT_RIJNDAEL_256, md5($hashKey), base64_decode(urldecode($stringToDecrypt)), MCRYPT_MODE_CBC, md5(md5($hashKey))), "\0"));
    }
}

?>
