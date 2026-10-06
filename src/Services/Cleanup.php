<?php
declare(strict_types=1);

namespace Lethe\Services;

use Lethe\Database;
use Lethe\Helpers;

/**
 * Purge of expired/revoked shared files, expired deposit files, expired or
 * consumed secret messages, expired deposits and abandoned upload fragments.
 * Shared between the cron script and any future on-demand maintenance.
 */
final class Cleanup
{
    /**
     * Runs the full cleanup.
     *
     * @return array{deleted: int, freed_bytes: int, log: string[]}
     */
    public static function run(): array
    {
        $pdo = Database::connect();
        $now = Helpers::now();

        $deleted = 0;
        $freedBytes = 0;
        $log = [];

        // --- Shared files (table "files"): expired or revoked ---
        $stmt = $pdo->prepare('SELECT * FROM files WHERE expires_at < ? OR revoked = 1');
        $stmt->execute([$now]);
        foreach ($stmt->fetchAll() as $file) {
            $path = STORAGE_PATH . '/' . $file['stored_name'];
            if (file_exists($path)) {
                $freedBytes += (int)filesize($path);
                @unlink($path);
            }
            $pdo->prepare('DELETE FROM files WHERE id = ?')->execute([$file['id']]);
            $deleted++;
            $log[] = "[files] Supprimé : {$file['original_name']} (code {$file['code']})";
        }

        // --- Deposit files (table "deposit_files"): expired ---
        $stmt = $pdo->prepare('SELECT * FROM deposit_files WHERE expires_at < ?');
        $stmt->execute([$now]);
        foreach ($stmt->fetchAll() as $file) {
            $path = STORAGE_PATH . '/' . $file['stored_name'];
            if (file_exists($path)) {
                $freedBytes += (int)filesize($path);
                @unlink($path);
            }
            $pdo->prepare('DELETE FROM deposit_files WHERE id = ?')->execute([$file['id']]);
            $deleted++;
            $log[] = "[deposit_files] Supprimé : {$file['original_name']}";
        }

        // --- Secret messages (table "secrets"): expired or already consumed ---
        $stmt = $pdo->prepare('SELECT id, code FROM secrets WHERE expires_at < ? OR consumed = 1');
        $stmt->execute([$now]);
        foreach ($stmt->fetchAll() as $secret) {
            $pdo->prepare('DELETE FROM secrets WHERE id = ?')->execute([$secret['id']]);
            $deleted++;
            $log[] = "[secrets] Supprimé : code {$secret['code']}";
        }

        // --- Expired deposits: deactivated (their content is purged above) ---
        $pdo->prepare('UPDATE deposits SET active = 0 WHERE expires_at < ? AND active = 1')->execute([$now]);

        // --- Abandoned upload fragments (older than 24h) ---
        if (is_dir(TMP_PATH)) {
            foreach (glob(TMP_PATH . '/*', GLOB_ONLYDIR) ?: [] as $dir) {
                if (filemtime($dir) < time() - 86400) {
                    foreach (glob($dir . '/*.part') ?: [] as $part) {
                        @unlink($part);
                    }
                    @rmdir($dir);
                    $log[] = "[tmp] Fragments abandonnés supprimés : $dir";
                }
            }
        }

        return ['deleted' => $deleted, 'freed_bytes' => $freedBytes, 'log' => $log];
    }
}
