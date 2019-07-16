<?php

namespace OmniContactForm;

class Crypto
{
    private $method = 'AES-256-CBC';

    public function __construct() {
    }

    /**
     *
     *  Encrypts a string using openssl_encrypt()
     *
     *  Based on the answer by blade (Matúš Koprda) at https://stackoverflow.com/questions/3422759
     *
     *  @since 0.1.3
     *  @return string
     *
     */
    public function encrypt(string $plaintext, string $password): string {
        if (!extension_loaded('openssl')) {
            return '0';
        }

        $iv_length  = openssl_cipher_iv_length($this->method);

        $key        = hash('sha256', $password);
        $iv         = random_bytes($iv_length);
        $ciphertext = openssl_encrypt($plaintext, $this->method, $key, 0, $iv);
        $hash       = hash_hmac('sha256', $ciphertext, $key);

        return bin2hex($iv) . $hash . $ciphertext;
    }

    /**
     *
     *  Decrypts a string from Crypto::encrypt() using openssl_decrypt()
     *
     *  Based on the answer by blade (Matúš Koprda) at https://stackoverflow.com/questions/3422759
     *
     *  @since 0.1.3
     *  @return string|null
     *
     */
    public function decrypt(string $strings, string $password) {
        if (!extension_loaded('openssl')) {
            return '0';
        }

        $iv_length  = openssl_cipher_iv_length($this->method);

        $key        = hash('sha256', $password);
        $iv         = hex2bin(substr($strings, 0, $iv_length * 2));
        $hash       = substr($strings, $iv_length * 2, 64);
        $ciphertext = substr($strings, ($iv_length * 2) + 64);

        if (hash_hmac('sha256', $ciphertext, $key) !== $hash) {
            return null;
        }

        return openssl_decrypt($ciphertext, $this->method, $key, 0, $iv);
    }
}
