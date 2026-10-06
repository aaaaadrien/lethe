<?php
// Lethe - public secret message reveal page (one-time link).
use Lethe\Helpers;
use Lethe\Language;
use Lethe\Security;
use Lethe\SecretMessage;
use Lethe\Session;

$code = (string)($_GET['secret'] ?? $_POST['secret'] ?? '');
$secret = $code !== '' ? SecretMessage::find($code) : null;

$error = null;
$revealedMessage = null;

if (!$secret) {
    $error = Language::t('error.secret_not_found');
} elseif (SecretMessage::isConsumed($secret)) {
    $error = Language::t('error.secret_consumed');
} elseif (SecretMessage::isExpired($secret)) {
    $error = Language::t('error.link_expired');
}

// The reveal only happens on an explicit action (POST), never on a simple
// GET, so that a link scanner or a browser/proxy prefetch cannot consume the
// message on behalf of the recipient.
if (!$error && $_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['reveal'])) {
    if (!Security::verifyFormToken($_POST['csrf_token'] ?? null)) {
        $error = Language::t('error.session_expired');
    } else {
        $revealedMessage = SecretMessage::consume($code);
        if ($revealedMessage === null) {
            $error = Language::t('error.secret_just_consumed');
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(Language::current(), ENT_QUOTES, 'UTF-8') ?>">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Language::t('public.secret.title', ['app' => APP_NAME]), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" rx="24" fill="#0d0d0d"/><path d="M16 42 Q 28 30 40 42 T 64 42 T 88 42" stroke="#ffffff" stroke-width="8" fill="none" stroke-linecap="round"/><path d="M16 62 Q 28 50 40 62 T 64 62 T 88 62" stroke="#6C5CFF" stroke-width="8" fill="none" stroke-linecap="round"/></svg>') ?>"/>
    <meta name="theme-color" content="#121212"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/lethe.css?v=<?= Helpers::assetVersion('assets/css/lethe.css') ?>">
  </head>
  <body class="public-page">
    <div class="public-shell">
      <header class="public-header">
        <div class="logo">
          <svg width="26" height="26" viewBox="0 0 100 100"><rect width="100" height="100" rx="24" fill="#0d0d0d"/><path d="M16 42 Q 28 30 40 42 T 64 42 T 88 42" stroke="#ffffff" stroke-width="8" fill="none" stroke-linecap="round"/><path d="M16 62 Q 28 50 40 62 T 64 62 T 88 62" stroke="#6C5CFF" stroke-width="8" fill="none" stroke-linecap="round"/></svg>
          <?= APP_NAME ?>
        </div>
      </header>
      <main class="public-content">
        <div class="card public-card">
          <div class="card-body p-4 p-md-5">
            <?php if ($error): ?>
              <div class="text-center">
                <div class="public-icon text-danger"><i class="bi bi-x-circle"></i></div>
                <h1 class="h5 mb-3"><?= htmlspecialchars(Language::t('public.secret.unavailable'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-secondary mb-0"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            <?php elseif ($revealedMessage !== null): ?>
              <div class="text-center">
                <div class="public-icon"><i class="bi bi-envelope-open"></i></div>
                <h1 class="h5 mb-3"><?= htmlspecialchars(Language::t('public.secret.revealed'), ENT_QUOTES, 'UTF-8') ?></h1>
              </div>
              <div class="alert alert-warning py-2 small mb-3">
                <i class="bi bi-exclamation-triangle me-2"></i>
                <?= htmlspecialchars(Language::t('public.secret.deleted_note'), ENT_QUOTES, 'UTF-8') ?>
              </div>
              <pre class="secret-message"><?= htmlspecialchars($revealedMessage, ENT_QUOTES, 'UTF-8') ?></pre>
            <?php else: ?>
              <div class="text-center">
                <div class="public-icon"><i class="bi bi-envelope"></i></div>
                <h1 class="h5 mb-3"><?= htmlspecialchars(Language::t('public.secret.received'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-secondary">
                  <?= htmlspecialchars(Language::t('public.secret.received_note'), ENT_QUOTES, 'UTF-8') ?>
                </p>
                <form method="post" class="text-center">
                  <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="secret" value="<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>">
                  <input type="hidden" name="reveal" value="1">
                  <button type="submit" class="btn btn-accent rounded-pill px-4">
                    <i class="bi bi-eye me-2"></i><?= htmlspecialchars(Language::t('public.secret.reveal'), ENT_QUOTES, 'UTF-8') ?>
                  </button>
                </form>
              </div>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </body>
</html>
