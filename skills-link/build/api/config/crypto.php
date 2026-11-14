<?php

function encryption_key(): string
{
    $hex = getenv('ENCRYPTION_KEY');
    if (!$hex) {
        throw new RuntimeException('ENCRYPTION_KEY environment variable is not set.');
    }
    return hex2bin($hex);
}

function encrypt_field(string $plaintext): string
{
    $key = encryption_key();
    $iv = random_bytes(12);
    $tag = '';
    $cipher = openssl_encrypt($plaintext, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    return $iv . $cipher . $tag;
}

function decrypt_field(string $stored): string
{
    $key = encryption_key();
    $iv = substr($stored, 0, 12);
    $tag = substr($stored, -16);
    $cipher = substr($stored, 12, -16);
    $plain = openssl_decrypt($cipher, 'aes-256-gcm', $key, OPENSSL_RAW_DATA, $iv, $tag);
    if ($plain === false) {
        throw new RuntimeException('Decryption failed — data may be corrupted or key is wrong.');
    }
    return $plain;
}

function hash_field(string $plaintext): string
{
    return hash('sha256', $plaintext);
}
