<?php
declare(strict_types=1);

namespace Lethe;

final class Response
{
    /**
     * Send a JSON response and stop execution.
     *
     * @param mixed $data
     */
    public static function json($data, int $code = 200): void
    {
        if (!headers_sent()) {
            http_response_code($code);
            header('Content-Type: application/json; charset=utf-8');
            header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        }
        echo json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE);
        exit;
    }
}
