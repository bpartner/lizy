<?php

namespace Lyzi\Helpers;

class Crypter
{
    const ENCRYPT_KEY = 'PRESTA_LYZI_ENCRYPT_asAS$213gdfgSAq314';
    const ENCRYPT_METHOD = 'AES-256-CBC';
    const ENCRYPT_IV_VECTOR = '1121';

    public static function encrypt(string $string)
    {

        return openssl_encrypt(
            $string,
            self::ENCRYPT_METHOD,
            self::ENCRYPT_KEY,
            0,
            static::getIv()
        );
    }

    public static function decrypt(string $string)
    {
        return openssl_decrypt(
            $string,
            self::ENCRYPT_METHOD,
            self::ENCRYPT_KEY,
            0,
            static::getIv()
        );
    }

    public static function getIv()
    {
        return substr(hash('sha256', self::ENCRYPT_IV_VECTOR), 0, 16);
    }
}
