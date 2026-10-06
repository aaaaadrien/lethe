<?php
// Lethe - front controller.
declare(strict_types=1);

require_once __DIR__ . '/src/bootstrap.php';

use Lethe\Router;

// API actions
if (isset($_GET['action'])) {
    Router::create()->dispatch($_GET['action']);
    exit;
}

// Public share links (no account required)
if (isset($_GET['code'])) {
    require __DIR__ . '/src/Views/download.php';
    exit;
}
if (isset($_GET['deposit'])) {
    require __DIR__ . '/src/Views/deposit.php';
    exit;
}
if (isset($_GET['secret'])) {
    require __DIR__ . '/src/Views/secret_view.php';
    exit;
}

// Main single-page application
require __DIR__ . '/src/Views/page.php';
