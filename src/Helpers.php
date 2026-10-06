<?php
declare(strict_types=1);

namespace Lethe;

final class Helpers
{
    /**
     * Base URL of the application (no trailing slash). Uses APP_URL when set,
     * otherwise auto-detects it from $_SERVER (scheme, host, base path).
     */
    public static function appUrl(): string
    {
        if (APP_URL !== '') {
            return rtrim(APP_URL, '/');
        }

        $scheme = 'http';
        $forwardedProto = strtolower(trim(explode(',', $_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '')[0]));
        if (in_array($forwardedProto, ['https', 'on', '1', 'true'], true)) {
            $scheme = 'https';
        } elseif ($forwardedProto === 'http') {
            $scheme = 'http';
        } elseif ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || (($_SERVER['SERVER_PORT'] ?? '') == 443)) {
            $scheme = 'https';
        }

        $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? $_SERVER['SERVER_NAME'] ?? 'localhost';
        $host = trim(explode(',', $host)[0]);

        $basePath = str_replace('\\', '/', dirname($_SERVER['SCRIPT_NAME'] ?? '/'));
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }

        return $scheme . '://' . $host . $basePath;
    }

    public static function generateCode(int $length = 16): string
    {
        return bin2hex(random_bytes((int)ceil($length / 2)));
    }

    public static function formatBytes(int $bytes, int $precision = 2): string
    {
        $units = Language::byteUnits();
        $bytes = max($bytes, 0);
        $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
        $pow = min($pow, count($units) - 1);
        $bytes /= (1 << (10 * $pow));
        return round($bytes, $precision) . ' ' . $units[$pow];
    }

    public static function sanitizeFilename(string $name): string
    {
        $name = basename($name);
        $name = preg_replace('/[^A-Za-z0-9._-]/', '_', $name);
        return $name === '' ? Language::t('helpers.default_filename') : $name;
    }

    /**
     * Format an ISO-8601 date stored in the database using the locale
     * format (d/m/Y H:i in French, m/d/Y H:i otherwise).
     */
    public static function formatDate(string $iso): string
    {
        return (new \DateTime($iso))->format(Language::dateFormat());
    }

    /**
     * Cache-busting version for a static asset (its file mtime), so the
     * browser always picks up the latest copy after a deployment.
     */
    public static function assetVersion(string $relativePath): string
    {
        $file = dirname(__DIR__) . '/' . $relativePath;
        return is_file($file) ? (string)filemtime($file) : '0';
    }

    public static function now(): string
    {
        return gmdate('c');
    }

    public static function isExpired(string $isoDate): bool
    {
        return new \DateTime($isoDate) < new \DateTime();
    }
}
