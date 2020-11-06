<?php

namespace OmniContactForm;

class Crypto
{
    private $method = 'AES-256-CBC';
    private $iv_length = null;

    public function __construct() {
        if (!extension_loaded('openssl')) {
            throw new \Exception('OpenSSL extension is not loaded.');
        }

        $this->iv_length = openssl_cipher_iv_length($this->method);
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
        $key        = hash('sha256', $password);
        $iv         = random_bytes($this->iv_length);
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
     *  @return string
     *
     */
    public function decrypt(string $strings, string $password): string {
        $key        = hash('sha256', $password);
        $iv         = hex2bin(substr($strings, 0, $this->iv_length * 2));
        $hash       = substr($strings, $this->iv_length * 2, 64);
        $ciphertext = substr($strings, ($this->iv_length * 2) + 64);

        if (!hash_equals(hash_hmac('sha256', $ciphertext, $key), $hash)) {
            throw new \Exception('Message has been tampered with.');
        }

        return openssl_decrypt($ciphertext, $this->method, $key, 0, $iv);
    }
}
