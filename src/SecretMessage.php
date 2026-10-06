<?php
declare(strict_types=1);

namespace Lethe;

/**
 * Secret messages: a text encrypted at rest, accessible through a one-time
 * link with a limited lifetime. Reuses the same user base (table "users") and
 * the same shared building blocks as the rest of the application.
 */
final class SecretMessage
{
    /**
     * Encrypts and stores a new secret message. Returns the unique link code.
     */
    public static function create(int $ownerUserId, string $plaintext, int $expirationDays): string
    {
        $encrypted = Crypto::encrypt($plaintext);

        $code = Helpers::generateCode(24);
        $expiresAt = (new \DateTime())->modify('+' . $expirationDays . ' days')->format('c');

        $stmt = Database::connect()->prepare('
            INSERT INTO secrets (code, owner_user_id, ciphertext, iv, tag, expires_at, created_at, consumed, consumed_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, 0, NULL)
        ');
        $stmt->execute([
            $code,
            $ownerUserId,
            $encrypted['ciphertext'],
            $encrypted['iv'],
            $encrypted['tag'],
            $expiresAt,
            Helpers::now(),
        ]);

        return $code;
    }

    /**
     * Loads the metadata of a secret message (without the decrypted content),
     * for the confirmation screen shown before the reveal.
     *
     * @return array<string, mixed>|null
     */
    public static function find(string $code): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM secrets WHERE code = ?');
        $stmt->execute([$code]);
        $secret = $stmt->fetch();
        return $secret ?: null;
    }

    public static function isExpired(array $secret): bool
    {
        return Helpers::isExpired($secret['expires_at']);
    }

    public static function isConsumed(array $secret): bool
    {
        return (int)$secret['consumed'] === 1;
    }

    /**
     * Consumes the message atomically: marks the row as read (UPDATE guarded
     * by consumed = 0), and only if that update affected exactly one row
     * (guaranteeing no concurrent request read it in the meantime) decrypts
     * and returns the plaintext. The ciphertext is then wiped from the
     * database: even the remaining row no longer reveals the message.
     *
     * @return string|null The plaintext, or null if the message was already
     *                     consumed, expired, or not found.
     */
    public static function consume(string $code): ?string
    {
        $pdo = Database::connect();

        $stmt = $pdo->prepare('SELECT * FROM secrets WHERE code = ?');
        $stmt->execute([$code]);
        $secret = $stmt->fetch();

        if (!$secret || self::isConsumed($secret) || self::isExpired($secret)) {
            return null;
        }

        $update = $pdo->prepare('UPDATE secrets SET consumed = 1, consumed_at = ? WHERE id = ? AND consumed = 0');
        $update->execute([Helpers::now(), $secret['id']]);

        if ($update->rowCount() !== 1) {
            // Consumed in the meantime by a concurrent request (double click, prefetch...).
            return null;
        }

        $plaintext = Crypto::decrypt($secret['ciphertext'], $secret['iv'], $secret['tag']);

        // The ciphertext has no reason to be kept once it has been read.
        $pdo->prepare('UPDATE secrets SET ciphertext = ?, iv = ?, tag = ? WHERE id = ?')
            ->execute(['', '', '', $secret['id']]);

        return $plaintext;
    }
}
