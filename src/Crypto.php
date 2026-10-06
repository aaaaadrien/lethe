<?php
declare(strict_types=1);

namespace Lethe;

use Lethe\Exceptions\UserException;

/**
 * AES-256-GCM encryption for secret messages. The key (256 bits) is generated
 * once and stored outside the webroot (SECRET_KEY_FILE).
 */
final class Crypto
{
    private const CIPHER = 'aes-256-gcm';

    /**
     * Encrypt a plaintext. Returns base64-encoded components ready to be
     * stored as-is in TEXT columns.
     *
     * @return array{iv: string, tag: string, ciphertext: string}
     */
    public static function encrypt(string $plaintext): array
    {
        $key = self::key();
        $iv = random_bytes(openssl_cipher_iv_length(self::CIPHER));
        $tag = '';

        $ciphertext = openssl_encrypt($plaintext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($ciphertext === false) {
            throw new UserException('Échec du chiffrement du message secret.');
        }

        return [
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ciphertext' => base64_encode($ciphertext),
        ];
    }

    /**
     * Decrypt a message previously encrypted by encrypt(). Throws when the
     * data was altered (invalid authentication tag) or corrupted.
     */
    public static function decrypt(string $ciphertextB64, string $ivB64, string $tagB64): string
    {
        $key = self::key();
        $iv = base64_decode($ivB64, true);
        $tag = base64_decode($tagB64, true);
        $ciphertext = base64_decode($ciphertextB64, true);

        if ($iv === false || $tag === false || $ciphertext === false) {
            throw new UserException('Données chiffrées invalides.');
        }

        $plaintext = openssl_decrypt($ciphertext, self::CIPHER, $key, OPENSSL_RAW_DATA, $iv, $tag);
        if ($plaintext === false) {
            throw new UserException('Échec du déchiffrement du message secret (donnée corrompue ou altérée).');
        }

        return $plaintext;
    }

    /**
     * Load (or generate on first use) the 32-byte encryption key.
     */
    private static function key(): string
    {
        static $key = null;
        if ($key !== null) {
            return $key;
        }

        $path = SECRET_KEY_FILE;

        if (!file_exists($path)) {
            $dir = dirname($path);
            if (!is_dir($dir)) {
                mkdir($dir, 0775, true);
            }
            $generated = random_bytes(32);
            file_put_contents($path, base64_encode($generated), LOCK_EX);
            @chmod($path, 0600);
            return $key = $generated;
        }

        $stored = trim((string)file_get_contents($path));
        $decoded = base64_decode($stored, true);
        if ($decoded === false || strlen($decoded) !== 32) {
            throw new \RuntimeException("Clé de chiffrement des messages secrets invalide ($path).");
        }

        return $key = $decoded;
    }
}
