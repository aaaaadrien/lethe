<?php
// Lethe - application bootstrap.
declare(strict_types=1);

require_once __DIR__ . '/../config.php';
require_once __DIR__ . '/autoload.php';

use Lethe\Database;
use Lethe\Language;
use Lethe\Security;
use Lethe\Session;

// Detect the interface language from the browser (Accept-Language header).
// Unknown languages fall back to English (see src/Language.php).
Language::init();

set_time_limit(0);

// Explicit PHP error logging to a dedicated file (useful when server logs are
// not accessible). The data/ directory must be writable.
ini_set('display_errors', '0');
ini_set('log_errors', '1');
ini_set('error_log', dirname(DB_FILE) . '/php-error.log');
error_reporting(E_ALL);

/**
 * True when the current request is an API call (?action=...), so fatal errors
 * are reported as JSON instead of a blank page.
 */
function is_api_request(): bool
{
    return isset($_GET['action']);
}

function fatal_error_output(string $message): void
{
    if (headers_sent()) {
        return;
    }
    http_response_code(500);
    if (is_api_request()) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['status' => 'error', 'message' => Language::t('error.server')]);
        return;
    }
    $title = htmlspecialchars(Language::t('error.page_title'), ENT_QUOTES, 'UTF-8');
    $hint = htmlspecialchars(Language::t('error.page_hint'), ENT_QUOTES, 'UTF-8');
    $lang = htmlspecialchars(Language::current(), ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="' . $lang . '"><head><meta charset="UTF-8"><title>' . $title . '</title></head>'
        . '<body style="font-family:sans-serif;background:#030303;color:#fff;display:flex;align-items:center;'
        . 'justify-content:center;height:100vh;margin:0"><div style="background:#121212;border-radius:16px;'
        . 'padding:2rem 2.5rem;max-width:480px;text-align:center"><h1 style="font-size:1.1rem;margin:0 0 .5rem">' . $title . '</h1>'
        . '<p style="color:#aaa;font-size:.9rem;margin:0">' . $hint . '</p></div></body></html>';
}

set_exception_handler(function (Throwable $e): void {
    error_log('[Exception non interceptée] ' . $e->getMessage() . ' in ' . $e->getFile() . ':' . $e->getLine());
    fatal_error_output($e->getMessage());
});

register_shutdown_function(function (): void {
    $error = error_get_last();
    if ($error && in_array($error['type'], [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR], true)) {
        error_log('[Erreur fatale] ' . $error['message'] . ' in ' . $error['file'] . ':' . $error['line']);
        fatal_error_output($error['message']);
    }
});

Session::start();
Security::sendHeaders();

// Prepare storage directories before any operation.
foreach ([STORAGE_PATH, TMP_PATH, dirname(DB_FILE)] as $dir) {
    if (!is_dir($dir)) {
        mkdir($dir, 0775, true);
    }
}

// Initialize the database (tables + default admin) on first use.
try {
    Database::connect();
} catch (Throwable $e) {
    error_log('[Database::connect] ' . $e->getMessage());
    fatal_error_output($e->getMessage());
    exit;
}
