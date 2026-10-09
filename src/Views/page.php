<?php
// Lethe - main single-page application view.
use Lethe\Helpers;
use Lethe\Language;
use Lethe\Session;

$i18n = json_encode(
    ['lang' => Language::current(), 'strings' => Language::catalog()],
    JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars(Language::current(), ENT_QUOTES, 'UTF-8') ?>">
  <head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= APP_NAME ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" rx="24" fill="#0d0d0d"/><path d="M16 42 Q 28 30 40 42 T 64 42 T 88 42" stroke="#ffffff" stroke-width="8" fill="none" stroke-linecap="round"/><path d="M16 62 Q 28 50 40 62 T 64 62 T 88 62" stroke="#6C5CFF" stroke-width="8" fill="none" stroke-linecap="round"/></svg>') ?>"/>
    <meta name="theme-color" content="#121212"/>
    <meta name="csrf-token" content="<?= htmlspecialchars(Session::csrfToken(), ENT_QUOTES, 'UTF-8') ?>">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/lethe.css?v=<?= Helpers::assetVersion('assets/css/lethe.css') ?>">
    <script>window.letheConfig = {
      appName: <?= json_encode(APP_NAME) ?>,
      chunkSize: <?= CHUNK_SIZE ?>,
      maxFileSize: <?= MAX_FILE_SIZE ?>,
      minExpirationDays: <?= MIN_EXPIRATION_DAYS ?>,
      maxExpirationDays: <?= MAX_EXPIRATION_DAYS ?>,
      defaultExpirationDays: <?= DEFAULT_EXPIRATION_DAYS ?>,
      secretExpirationOptions: <?= json_encode(SECRET_EXPIRATION_OPTIONS) ?>,
      secretDefaultExpirationDays: <?= SECRET_DEFAULT_EXPIRATION_DAYS ?>,
      secretMaxLength: <?= SECRET_MAX_LENGTH ?>
    };</script>
    <script>window.letheI18n = <?= $i18n ?>;</script>
  </head>
  <body class="logged-out">
    <div class="app-container">
      <nav class="sidebar offcanvas-md offcanvas-start" tabindex="-1" id="main-nav-offcanvas">
        <div class="offcanvas-header">
          <div class="logo">
            <svg width="24" height="24" viewBox="0 0 100 100"><rect width="100" height="100" rx="24" fill="#0d0d0d"/><path d="M16 42 Q 28 30 40 42 T 64 42 T 88 42" stroke="#ffffff" stroke-width="8" fill="none" stroke-linecap="round"/><path d="M16 62 Q 28 50 40 62 T 64 62 T 88 62" stroke="#6C5CFF" stroke-width="8" fill="none" stroke-linecap="round"/></svg>
            Lethe
          </div>
          <button type="button" class="btn-close btn-close-white" data-bs-dismiss="offcanvas" data-bs-target="#main-nav-offcanvas" aria-label="Close"></button>
        </div>
        <div class="offcanvas-body d-flex flex-column">
          <div class="logo d-none d-md-flex">
            <svg width="28" height="28" viewBox="0 0 100 100"><rect width="100" height="100" rx="24" fill="#0d0d0d"/><path d="M16 42 Q 28 30 40 42 T 64 42 T 88 42" stroke="#ffffff" stroke-width="8" fill="none" stroke-linecap="round"/><path d="M16 62 Q 28 50 40 62 T 64 62 T 88 62" stroke="#6C5CFF" stroke-width="8" fill="none" stroke-linecap="round"/></svg>
            Lethe <small class="text-accent fs-6"><?= htmlspecialchars(Language::t('brand.tagline'), ENT_QUOTES, 'UTF-8') ?></small>
            <br><small class="text-muted" style="font-size:0.7rem">v<?= \Lethe\Version::get() ?></small>
          </div>
          <a href="#" class="nav-link" data-view="dashboard">
            <i class="bi bi-house"></i>
            <span class="text-truncate"><?= htmlspecialchars(Language::t('nav.dashboard'), ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <a href="#" class="nav-link" data-view="send_file">
            <i class="bi bi-send"></i>
            <span class="text-truncate"><?= htmlspecialchars(Language::t('nav.send_file'), ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <a href="#" class="nav-link" data-view="my_files">
            <i class="bi bi-folder2"></i>
            <span class="text-truncate"><?= htmlspecialchars(Language::t('nav.my_files'), ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <a href="#" class="nav-link" data-view="my_deposits">
            <i class="bi bi-inbox"></i>
            <span class="text-truncate"><?= htmlspecialchars(Language::t('nav.my_deposits'), ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <a href="#" class="nav-link" data-view="secret">
            <i class="bi bi-envelope"></i>
            <span class="text-truncate"><?= htmlspecialchars(Language::t('nav.secret'), ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <a href="#" class="nav-link" data-view="my_secrets">
            <i class="bi bi-key"></i>
            <span class="text-truncate"><?= htmlspecialchars(Language::t('nav.my_secrets'), ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <a href="#" class="nav-link" data-view="profile">
            <i class="bi bi-person"></i>
            <span class="text-truncate"><?= htmlspecialchars(Language::t('nav.profile'), ENT_QUOTES, 'UTF-8') ?></span>
          </a>
          <div class="logged-in-only">
            <a href="#" class="nav-link admin-only" data-view="users" style="display:none;">
              <i class="bi bi-people"></i>
              <span class="text-truncate"><?= htmlspecialchars(Language::t('nav.users'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
            <a href="#" class="nav-link" id="sidebar-logout-btn">
              <i class="bi bi-box-arrow-left"></i>
              <span class="text-truncate"><?= htmlspecialchars(Language::t('nav.logout'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
          </div>
          <div class="logged-out-only">
            <a href="#" class="nav-link" data-bs-toggle="modal" data-bs-target="#login-modal">
              <i class="bi bi-box-arrow-in-right"></i>
              <span class="text-truncate"><?= htmlspecialchars(Language::t('nav.login'), ENT_QUOTES, 'UTF-8') ?></span>
            </a>
          </div>
        </div>
      </nav>
      <main class="main-content" id="main-content">
        <div class="mobile-header d-md-none">
          <button class="header-btn" type="button" data-bs-toggle="offcanvas" data-bs-target="#main-nav-offcanvas">
            <i class="bi bi-list"></i>
          </button>
          <div class="logo">
            <svg width="22" height="22" viewBox="0 0 100 100"><rect width="100" height="100" rx="24" fill="#0d0d0d"/><path d="M16 42 Q 28 30 40 42 T 64 42 T 88 42" stroke="#ffffff" stroke-width="8" fill="none" stroke-linecap="round"/><path d="M16 62 Q 28 50 40 62 T 64 62 T 88 62" stroke="#6C5CFF" stroke-width="8" fill="none" stroke-linecap="round"/></svg>
            Lethe
          </div>
        </div>
        <div class="page-header">
          <h1 id="content-title" class="content-title text-truncate"><?= htmlspecialchars(Language::t('nav.dashboard'), ENT_QUOTES, 'UTF-8') ?></h1>
        </div>
        <div id="content-area" class="content-area-wrapper"></div>
      </main>
    </div>

    <!-- Login Modal -->
    <div class="modal fade" id="login-modal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-2">
          <div class="modal-header">
            <h5 class="modal-title"><?= htmlspecialchars(Language::t('login.title'), ENT_QUOTES, 'UTF-8') ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <form id="login-form">
              <div class="mb-3">
                <label for="login-username" class="form-label text-secondary small"><?= htmlspecialchars(Language::t('common.username'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" class="form-control" id="login-username" required autofocus>
              </div>
              <div class="mb-3">
                <label for="login-password" class="form-label text-secondary small"><?= htmlspecialchars(Language::t('common.password'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="password" class="form-control" id="login-password" required>
              </div>
              <div id="login-error" class="alert alert-danger py-2 small" style="display:none;"></div>
              <button type="submit" class="btn btn-accent w-100 rounded-pill mt-2"><?= htmlspecialchars(Language::t('login.submit'), ENT_QUOTES, 'UTF-8') ?></button>
              <button type="button" class="btn btn-outline-secondary w-100 rounded-pill mt-2" id="oidc-login-btn" style="display:none;">
                <i class="bi bi-shield-lock me-2"></i><?= htmlspecialchars(Language::t('login.oidc'), ENT_QUOTES, 'UTF-8') ?>
              </button>
            </form>
          </div>
        </div>
      </div>
    </div>

    <!-- Generic Confirm Modal -->
    <div class="modal fade" id="confirm-modal" tabindex="-1">
      <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content p-2">
          <div class="modal-header">
            <h5 class="modal-title" id="confirm-modal-title"><?= htmlspecialchars(Language::t('common.confirm'), ENT_QUOTES, 'UTF-8') ?></h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
          </div>
          <div class="modal-body">
            <p class="text-secondary" id="confirm-modal-message"></p>
          </div>
          <div class="modal-footer border-0 pt-0">
            <button type="button" class="btn btn-outline-secondary rounded-pill" data-bs-dismiss="modal"><?= htmlspecialchars(Language::t('common.cancel'), ENT_QUOTES, 'UTF-8') ?></button>
            <button type="button" class="btn btn-accent rounded-pill" id="confirm-modal-btn"><?= htmlspecialchars(Language::t('common.confirm'), ENT_QUOTES, 'UTF-8') ?></button>
          </div>
        </div>
      </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>
    <script src="assets/js/utils.js?v=<?= Helpers::assetVersion('assets/js/utils.js') ?>"></script>
    <script src="assets/js/upload.js?v=<?= Helpers::assetVersion('assets/js/upload.js') ?>"></script>
    <script src="assets/js/views.js?v=<?= Helpers::assetVersion('assets/js/views.js') ?>"></script>
    <script src="assets/js/app.js?v=<?= Helpers::assetVersion('assets/js/app.js') ?>"></script>
  </body>
</html>
