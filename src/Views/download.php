<?php
// Lethe - public download page (share link, no account required).
use Lethe\Database;
use Lethe\Helpers;
use Lethe\Language;
use Lethe\Security;
use Lethe\Session;

$code = (string)($_GET['code'] ?? '');
$stmt = Database::connect()->prepare('SELECT * FROM files WHERE code = ?');
$stmt->execute([$code]);
$file = $stmt->fetch();

$error = null;
if (!$file) {
    $error = Language::t('error.link_not_found');
} elseif ((int)$file['revoked'] === 1) {
    $error = Language::t('error.link_revoked');
} elseif (Helpers::isExpired($file['expires_at'])) {
    $error = Language::t('error.link_expired');
}

$passwordRequired = $file && $file['password_hash'] !== null;
$passwordVerified = false;
$passwordError = null;

if (!$error && $passwordRequired) {
    $sessionKey = 'file_access_' . $file['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyFormToken($_POST['csrf_token'] ?? null)) {
        if (password_verify((string)($_POST['password'] ?? ''), $file['password_hash'])) {
            $_SESSION[$sessionKey] = true;
        } else {
            $passwordError = Language::t('error.password_incorrect');
        }
    }

    $passwordVerified = !empty($_SESSION[$sessionKey]);
}

// Trigger the actual download.
if (!$error && (!$passwordRequired || $passwordVerified) && isset($_GET['confirm'])) {
    $path = STORAGE_PATH . '/' . $file['stored_name'];

    if (!file_exists($path)) {
        $error = Language::t('error.file_unavailable');
    } else {
        Database::connect()->prepare('UPDATE files SET download_count = download_count + 1 WHERE id = ?')->execute([$file['id']]);

        while (ob_get_level() > 0) {
            ob_end_clean();
        }
        set_time_limit(0);
        ignore_user_abort(true);

        header('Content-Description: File Transfer');
        header('Content-Type: application/octet-stream');
        header('Content-Disposition: ' . Security::contentDisposition($file['original_name']));
        header('Content-Transfer-Encoding: binary');
        header('Content-Length: ' . filesize($path));
        header('Cache-Control: must-revalidate');
        header('Pragma: public');

        $handle = fopen($path, 'rb');
        while (!feof($handle)) {
            echo fread($handle, 8 * 1024 * 1024);
            flush();
        }
        fclose($handle);
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(Language::current(), ENT_QUOTES, 'UTF-8') ?>">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars(Language::t('public.download.title', ['app' => APP_NAME]), ENT_QUOTES, 'UTF-8') ?></title>
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
          <div class="card-body p-4 p-md-5 text-center">
            <?php if ($error): ?>
              <div class="public-icon text-danger"><i class="bi bi-x-circle"></i></div>
              <h1 class="h5 mb-3"><?= htmlspecialchars(Language::t('public.download.unavailable'), ENT_QUOTES, 'UTF-8') ?></h1>
              <p class="text-secondary mb-0"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
            <?php elseif ($passwordRequired && !$passwordVerified): ?>
              <div class="public-icon"><i class="bi bi-lock"></i></div>
              <h1 class="h5 mb-3"><?= htmlspecialchars(Language::t('public.download.protected'), ENT_QUOTES, 'UTF-8') ?></h1>
              <p class="text-secondary"><?= htmlspecialchars($file['original_name'], ENT_QUOTES, 'UTF-8') ?> (<?= Helpers::formatBytes((int)$file['size']) ?>)</p>
              <?php if ($passwordError): ?>
                <div class="alert alert-danger py-2"><?= htmlspecialchars($passwordError, ENT_QUOTES, 'UTF-8') ?></div>
              <?php endif; ?>
              <form method="post" class="text-start">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
                <div class="mb-3">
                  <input type="password" name="password" class="form-control" placeholder="<?= htmlspecialchars(Language::t('common.password'), ENT_QUOTES, 'UTF-8') ?>" required autofocus>
                </div>
                <button type="submit" class="btn btn-accent w-100 rounded-pill"><?= htmlspecialchars(Language::t('common.unlock'), ENT_QUOTES, 'UTF-8') ?></button>
              </form>
            <?php else: ?>
              <div class="public-icon"><i class="bi bi-download"></i></div>
              <h1 class="h5 mb-3"><?= htmlspecialchars(Language::t('public.download.ready'), ENT_QUOTES, 'UTF-8') ?></h1>
              <p class="mb-1 fw-medium"><?= htmlspecialchars($file['original_name'], ENT_QUOTES, 'UTF-8') ?></p>
              <p class="text-secondary mb-4"><?= htmlspecialchars(Language::t('public.download.meta', [
                  'size' => Helpers::formatBytes((int)$file['size']),
                  'date' => Helpers::formatDate($file['expires_at']),
              ]), ENT_QUOTES, 'UTF-8') ?></p>
              <a href="?code=<?= urlencode($code) ?>&confirm=1" class="btn btn-accent btn-lg rounded-pill px-4">
                <i class="bi bi-download me-2"></i><?= htmlspecialchars(Language::t('common.download'), ENT_QUOTES, 'UTF-8') ?>
              </a>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </body>
</html>
