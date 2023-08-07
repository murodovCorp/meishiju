<?php

namespace App\Helpers;

use InvalidArgumentException;
use RuntimeException;
use SodiumException;
use function base64_decode;
use function openssl_decrypt;
use function openssl_get_cipher_methods;
use function Sodium\crypto_aead_aes256gcm_decrypt;
use function Sodium\crypto_aead_aes256gcm_is_available;
use function sodium_crypto_aead_aes256gcm_decrypt;
use function sodium_crypto_aead_aes256gcm_is_available;
use const OPENSSL_RAW_DATA;

class AesUtil {
    /**
     * AES key
     *
     * @var string
     */
    private string $aesKey;

    const KEY_LENGTH_BYTE = 32;
    const AUTH_TAG_LENGTH_BYTE = 16;

    /**
     * Constructor
     */
    public function __construct($aesKey) {
        if (strlen($aesKey) != self::KEY_LENGTH_BYTE) {
            throw new InvalidArgumentException('无效的ApiV3Key，长度应为32个字节');
        }
        $this->aesKey = $aesKey;
	}

    /**
     * Decrypt AEAD_AES_256_GCM ciphertext
     *
     * @param string $associatedData AES GCM additional authentication data
     * @param string $nonceStr AES GCM nonce
     * @param string $ciphertext AES GCM cipher text
     *
     * @return bool      Decrypted string on success or FALSE on failure
     * @throws SodiumException
     */
    public function decryptToString(string $associatedData, string $nonceStr, string $ciphertext): bool
    {
        $ciphertext = base64_decode($ciphertext);
        if (strlen($ciphertext) <= self::AUTH_TAG_LENGTH_BYTE) {
            return false;
        }

        // ext-sodium (default installed on >= PHP 7.2)
        if (function_exists('\sodium_crypto_aead_aes256gcm_is_available') && sodium_crypto_aead_aes256gcm_is_available()) {
            return sodium_crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->aesKey);
		}

        // ext-libsodium (need install libsodium-php 1.x via pecl)
        if (function_exists('\Sodium\crypto_aead_aes256gcm_is_available') && crypto_aead_aes256gcm_is_available()) {
            return crypto_aead_aes256gcm_decrypt($ciphertext, $associatedData, $nonceStr, $this->aesKey);
		}

        // openssl (PHP >= 7.1 support AEAD)
        if (PHP_VERSION_ID >= 70100 && in_array('aes-256-gcm', openssl_get_cipher_methods())) {
            $cText = substr($ciphertext, 0, -self::AUTH_TAG_LENGTH_BYTE);
            $authTag = substr($ciphertext, -self::AUTH_TAG_LENGTH_BYTE);

            return openssl_decrypt(
                $cText,
                'aes-256-gcm',
                $this->aesKey,
                OPENSSL_RAW_DATA,
                $nonceStr,
				$authTag,
                $associatedData
            );
		}

        throw new RuntimeException('AEAD_AES_256_GCM需要PHP 7.1以上或者安装libsodium-php');
    }
}
