<?php
declare(strict_types=1);

namespace Lethe;

/**
 * Lightweight i18n for the Lethe interface.
 *
 * Catalogs live in lang/<code>.php, one file per language, each returning a
 * flat "key => translated string" array. English (lang/en.php) is the source
 * of truth and the fallback: any key missing from another language, and any
 * unknown browser language, falls back to English.
 *
 * The active language is detected from the browser's Accept-Language header
 * (see detect()), once per request, by Language::init() in the bootstrap.
 * The full catalog of the detected language is embedded in the pages that
 * run JavaScript (window.letheI18n) so the SPA can translate client-side.
 */
final class Language
{
    public const FALLBACK = 'en';

    /** Languages that can be detected (code => native name). */
    private const AVAILABLE = [
        'en' => 'English',
        'fr' => 'Français',
    ];

    private static ?string $current = null;
    /** @var array<string, string>|null */
    private static ?array $catalog = null;
    /** @var array<string, string>|null */
    private static ?array $fallbackCatalog = null;

    /**
     * Detect the browser language and select the matching catalog.
     * Called once from the bootstrap; safe to call again (idempotent).
     */
    public static function init(): void
    {
        self::$current = self::detect($_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '');
        self::$catalog = null;
    }

    /**
     * Languages the application can be displayed in.
     *
     * @return array<string, string> code => native name
     */
    public static function available(): array
    {
        return self::AVAILABLE;
    }

    public static function current(): string
    {
        return self::$current ?? self::FALLBACK;
    }

    /**
     * Pick the best available language from an Accept-Language header value
     * (e.g. "fr-FR,fr;q=0.9,en-US;q=0.8,en;q=0.7"). Tags are matched by
     * full tag first ("fr-FR" => fr), then by primary subtag, in order of
     * quality (q). Unknown or missing languages fall back to English.
     */
    public static function detect(string $acceptLanguage): string
    {
        $tags = [];
        foreach (explode(',', $acceptLanguage) as $part) {
            $segments = array_map('trim', explode(';', $part));
            $tag = strtolower($segments[0]);
            if ($tag === '' || $tag === '*') {
                continue;
            }
            $quality = 1.0;
            foreach (array_slice($segments, 1) as $param) {
                if (preg_match('/^q=([0-9]*\.?[0-9]+)$/', $param, $m)) {
                    $quality = (float)$m[1];
                }
            }
            $tags[] = ['tag' => $tag, 'q' => $quality];
        }

        usort($tags, fn(array $a, array $b): int => $b['q'] <=> $a['q']);

        foreach ($tags as $entry) {
            if (isset(self::AVAILABLE[$entry['tag']])) {
                return $entry['tag'];
            }
            $primary = strtolower(substr($entry['tag'], 0, 2));
            if (isset(self::AVAILABLE[$primary])) {
                return $primary;
            }
        }

        return self::FALLBACK;
    }

    /**
     * Translate a key, substituting ":name" placeholders from $params.
     * Falls back to the English catalog, then to the key itself.
     */
    public static function t(string $key, array $params = []): string
    {
        $text = self::catalog()[$key] ?? self::fallbackCatalog()[$key] ?? $key;
        foreach ($params as $name => $value) {
            $text = str_replace(':' . $name, (string)$value, $text);
        }
        return $text;
    }

    /**
     * Full catalog of the current language (flat key => string array).
     * Used both by t() and to embed the strings in the SPA.
     *
     * @return array<string, string>
     */
    public static function catalog(): array
    {
        if (self::$catalog === null) {
            $file = __DIR__ . '/../lang/' . self::current() . '.php';
            self::$catalog = is_file($file) ? require $file : [];
        }
        return self::$catalog;
    }

    /**
     * Date format used by Helpers::formatDate() (locale dependent).
     */
    public static function dateFormat(): string
    {
        return self::current() === 'fr' ? 'd/m/Y H:i' : 'm/d/Y H:i';
    }

    /**
     * Byte unit suffixes used by Helpers::formatBytes() (locale dependent).
     *
     * @return string[]
     */
    public static function byteUnits(): array
    {
        return self::current() === 'fr' ? ['o', 'Ko', 'Mo', 'Go', 'To'] : ['B', 'KB', 'MB', 'GB', 'TB'];
    }

    /**
     * @return array<string, string>
     */
    private static function fallbackCatalog(): array
    {
        if (self::$fallbackCatalog === null) {
            self::$fallbackCatalog = require __DIR__ . '/../lang/' . self::FALLBACK . '.php';
        }
        return self::$fallbackCatalog;
    }
}
