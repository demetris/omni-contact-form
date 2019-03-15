<?php

namespace OmniContactForm;

class Crypto
{
    private $method = 'AES-256-CBC';

    public function __construct() {
    }

    /**
    *
    *   Encrypts a string using openssl_encrypt()
    *
    *   @since 0.1.3
    *   @author blade (Matúš Koprda)
    *   @see https://stackoverflow.com/questions/3422759
    *
    */
    public function encrypt(string $plaintext, string $password): string {
        if (!extension_loaded('openssl')) {
            return '0';
        }

        $key        = hash('sha256', $password);
        $nonce      = bin2hex(random_bytes(8));
        $ciphertext = openssl_encrypt($plaintext, $this->method, $key, 0, $nonce);
        $hash       = hash_hmac('sha256', $ciphertext, $key);

        return $nonce . $hash . $ciphertext;
    }

    /**
    *
    *   Decrypts a string from Crypto::encrypt() using openssl_decrypt()
    *
    *   @since 0.1.3
    *   @author blade (Matúš Koprda)
    *   @see https://stackoverflow.com/questions/3422759
    *   @return string|null
    *
    */
    public function decrypt(string $strings, string $password) {
        if (!extension_loaded('openssl')) {
            return '0';
        }

        $key        = hash('sha256', $password);
        $nonce      = substr($strings, 0, 16);
        $hash       = substr($strings, 16, 64);
        $ciphertext = substr($strings, 80);

        if (hash_hmac('sha256', $ciphertext, $key) !== $hash) {
            return null;
        }

        return openssl_decrypt($ciphertext, $this->method, $key, 0, $nonce);
    }
}
