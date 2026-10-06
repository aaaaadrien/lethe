<?php
declare(strict_types=1);

namespace Lethe;

final class Security
{
    public static function sendHeaders(): void
    {
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header("Content-Security-Policy: default-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com data: blob:; img-src 'self' data: blob:; media-src 'self' blob:;");
    }

    /**
     * Verify the CSRF token sent by the SPA (X-CSRF-Token header).
     */
    public static function verifyCsrf(): void
    {
        $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (!$token || !hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
            Response::json(['status' => 'error', 'message' => Language::t('api.csrf_invalid')], 403);
        }
    }

    /**
     * Verify a CSRF token submitted inside a (server-rendered) form.
     */
    public static function verifyFormToken(?string $token): bool
    {
        return is_string($token) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
    }

    /**
     * Build a safe Content-Disposition header value for a download filename,
     * preventing HTTP header injection (quotes, backslashes, CRLF).
     */
    public static function contentDisposition(string $originalName): string
    {
        $ascii = preg_replace('/[^A-Za-z0-9._-]/', '_', $originalName) ?: 'fichier';
        $encoded = rawurlencode($originalName);
        return 'attachment; filename="' . $ascii . '"; filename*=UTF-8\'\'' . $encoded;
    }
}
