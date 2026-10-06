<?php
declare(strict_types=1);

namespace Lethe;

final class Request
{
    public static function query(string $key, ?string $default = null): ?string
    {
        return $_GET[$key] ?? $default;
    }

    public static function post(string $key, ?string $default = null): ?string
    {
        return $_POST[$key] ?? $default;
    }

    /**
     * Decode the raw JSON request body.
     *
     * @return array<string, mixed>
     */
    public static function jsonBody(): array
    {
        $raw = file_get_contents('php://input');
        $data = json_decode($raw === false ? '' : $raw, true);
        return is_array($data) ? $data : [];
    }

    /**
     * Read a string field from the JSON body (preferred) or from $_POST.
     */
    public static function field(string $key, ?string $default = null): ?string
    {
        $body = self::jsonBody();
        if (array_key_exists($key, $body) && is_string($body[$key])) {
            return $body[$key];
        }
        return self::post($key, $default);
    }

    /**
     * Read an integer field from the JSON body (preferred) or from $_POST.
     */
    public static function intField(string $key, int $default = 0): int
    {
        $body = self::jsonBody();
        if (array_key_exists($key, $body) && is_numeric($body[$key])) {
            return (int)$body[$key];
        }
        return self::post($key) !== null ? (int)self::post($key) : $default;
    }
}
