<?php
declare(strict_types=1);

namespace Lethe;

use PDO;
use PDOException;

final class Database
{
    private static ?PDO $pdo = null;

    public static function connect(): PDO
    {
        if (self::$pdo instanceof PDO) {
            return self::$pdo;
        }

        $dir = dirname(DB_FILE);
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }

        try {
            $db = new PDO('sqlite:' . DB_FILE, null, null, [PDO::ATTR_TIMEOUT => 30]);
            $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
            $db->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
            $db->exec("PRAGMA journal_mode=WAL;");
            $db->exec("PRAGMA foreign_keys = ON;");
            self::initSchema($db);
            self::seedAdmin($db);
            self::$pdo = $db;
            return $db;
        } catch (PDOException $e) {
            throw new \RuntimeException('Impossible d\'accéder à la base de données. Vérifiez les droits d\'écriture sur le dossier data/.', 0, $e);
        }
    }

    public static function initSchema(PDO $db): void
    {
        $db->exec("
            CREATE TABLE IF NOT EXISTS users (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                username TEXT UNIQUE NOT NULL,
                password_hash TEXT NOT NULL,
                is_admin INTEGER NOT NULL DEFAULT 0,
                created_at TEXT NOT NULL
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT UNIQUE NOT NULL,
                owner_user_id INTEGER NOT NULL,
                original_name TEXT NOT NULL,
                stored_name TEXT NOT NULL,
                size INTEGER NOT NULL,
                password_hash TEXT,
                recipient_email TEXT,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                revoked INTEGER NOT NULL DEFAULT 0,
                download_count INTEGER NOT NULL DEFAULT 0,
                FOREIGN KEY (owner_user_id) REFERENCES users(id)
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS deposits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT UNIQUE NOT NULL,
                owner_user_id INTEGER NOT NULL,
                label TEXT NOT NULL,
                password_hash TEXT,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                active INTEGER NOT NULL DEFAULT 1,
                FOREIGN KEY (owner_user_id) REFERENCES users(id)
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS secrets (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                code TEXT UNIQUE NOT NULL,
                owner_user_id INTEGER NOT NULL,
                ciphertext TEXT NOT NULL,
                iv TEXT NOT NULL,
                tag TEXT NOT NULL,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                consumed INTEGER NOT NULL DEFAULT 0,
                consumed_at TEXT,
                FOREIGN KEY (owner_user_id) REFERENCES users(id)
            );
        ");

        $db->exec("
            CREATE TABLE IF NOT EXISTS deposit_files (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                deposit_id INTEGER NOT NULL,
                original_name TEXT NOT NULL,
                stored_name TEXT NOT NULL,
                size INTEGER NOT NULL,
                uploader_name TEXT,
                expires_at TEXT NOT NULL,
                created_at TEXT NOT NULL,
                FOREIGN KEY (deposit_id) REFERENCES deposits(id)
            );
        ");

        $db->exec("CREATE INDEX IF NOT EXISTS files_owner_idx ON files(owner_user_id);");
        $db->exec("CREATE INDEX IF NOT EXISTS deposits_owner_idx ON deposits(owner_user_id);");
        $db->exec("CREATE INDEX IF NOT EXISTS secrets_owner_idx ON secrets(owner_user_id);");
        $db->exec("CREATE INDEX IF NOT EXISTS deposit_files_deposit_id_idx ON deposit_files(deposit_id);");
    }

    /**
     * Create the default admin account on a brand-new database.
     * Default credentials: admin / admin -> change immediately after first login.
     */
    private static function seedAdmin(PDO $db): void
    {
        $count = (int)$db->query('SELECT COUNT(*) FROM users')->fetchColumn();
        if ($count === 0) {
            $stmt = $db->prepare('INSERT INTO users (username, password_hash, is_admin, created_at) VALUES (?, ?, 1, ?)');
            $stmt->execute(['admin', password_hash('admin', PASSWORD_DEFAULT), gmdate('c')]);
        }
    }
}
