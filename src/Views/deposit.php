<?php
// Lethe - public deposit page (guest upload via deposit link).
use Lethe\Database;
use Lethe\Helpers;
use Lethe\Language;
use Lethe\Security;
use Lethe\Session;

$code = (string)($_GET['deposit'] ?? '');
$stmt = Database::connect()->prepare('SELECT * FROM deposits WHERE code = ?');
$stmt->execute([$code]);
$deposit = $stmt->fetch();

$error = null;
if (!$deposit) {
    $error = Language::t('error.deposit_not_found');
} elseif (!(int)$deposit['active']) {
    $error = Language::t('error.deposit_disabled');
} elseif (Helpers::isExpired($deposit['expires_at'])) {
    $error = Language::t('error.deposit_expired');
}

$passwordRequired = $deposit && $deposit['password_hash'] !== null;
$passwordVerified = false;
$passwordError = null;

if (!$error && $passwordRequired) {
    $sessionKey = 'deposit_access_' . $deposit['id'];

    if ($_SERVER['REQUEST_METHOD'] === 'POST' && Security::verifyFormToken($_POST['csrf_token'] ?? null)) {
        if (password_verify((string)($_POST['password'] ?? ''), $deposit['password_hash'])) {
            $_SESSION[$sessionKey] = true;
        } else {
            $passwordError = Language::t('error.password_incorrect');
        }
    }
    $passwordVerified = !empty($_SESSION[$sessionKey]);
} elseif (!$error) {
    $passwordVerified = true;
}

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
    <title><?= htmlspecialchars(Language::t('public.deposit.title', ['app' => APP_NAME]), ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="icon" type="image/svg+xml" href="data:image/svg+xml,<?= rawurlencode('<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 100 100"><rect width="100" height="100" rx="24" fill="#0d0d0d"/><path d="M16 42 Q 28 30 40 42 T 64 42 T 88 42" stroke="#ffffff" stroke-width="8" fill="none" stroke-linecap="round"/><path d="M16 62 Q 28 50 40 62 T 64 62 T 88 62" stroke="#6C5CFF" stroke-width="8" fill="none" stroke-linecap="round"/></svg>') ?>"/>
    <meta name="theme-color" content="#121212"/>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.11.3/font/bootstrap-icons.min.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Roboto:wght@400;500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/lethe.css?v=<?= Helpers::assetVersion('assets/css/lethe.css') ?>">
    <script>window.letheConfig = { chunkSize: <?= CHUNK_SIZE ?>, maxFileSize: <?= MAX_FILE_SIZE ?> };</script>
    <script>window.letheI18n = <?= $i18n ?>;</script>
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
                <h1 class="h5 mb-3"><?= htmlspecialchars(Language::t('public.deposit.unavailable'), ENT_QUOTES, 'UTF-8') ?></h1>
                <p class="text-secondary mb-0"><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
              </div>
            <?php elseif ($passwordRequired && !$passwordVerified): ?>
              <div class="text-center">
                <div class="public-icon"><i class="bi bi-lock"></i></div>
                <h1 class="h5 mb-3"><?= htmlspecialchars(Language::t('public.deposit.protected', ['label' => $deposit['label']]), ENT_QUOTES, 'UTF-8') ?></h1>
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
              </div>
            <?php else: ?>
              <h1 class="h5 mb-2"><?= htmlspecialchars(Language::t('public.deposit.upload_title', ['label' => $deposit['label']]), ENT_QUOTES, 'UTF-8') ?></h1>
              <p class="text-secondary small mb-4">
                <?= htmlspecialchars(Language::t('public.deposit.info', [
                    'size' => Helpers::formatBytes(MAX_FILE_SIZE),
                    'date' => Helpers::formatDate($deposit['expires_at']),
                ]), ENT_QUOTES, 'UTF-8') ?>
              </p>

              <div class="mb-3">
                <label for="uploader_name" class="form-label small text-secondary"><?= htmlspecialchars(Language::t('public.deposit.your_name'), ENT_QUOTES, 'UTF-8') ?></label>
                <input type="text" id="uploader_name" class="form-control" placeholder="<?= htmlspecialchars(Language::t('public.deposit.name_placeholder'), ENT_QUOTES, 'UTF-8') ?>">
              </div>

              <div id="file-drop-zone" class="file-drop-zone mb-3">
                <i class="bi bi-cloud-arrow-up d-block mb-2"></i>
                <p class="mb-0"><?= htmlspecialchars(Language::t('dropzone.text'), ENT_QUOTES, 'UTF-8') ?></p>
                <input type="file" id="file-input" class="d-none">
              </div>
              <div id="selected-file" class="mb-3 text-secondary small"></div>

              <div id="progress-wrapper" class="mb-3" style="display:none;">
                <div class="progress">
                  <div id="progress-bar" class="progress-bar" role="progressbar" style="width:0%">0%</div>
                </div>
              </div>

              <button id="submit-btn" class="btn btn-accent rounded-pill" disabled>
                <i class="bi bi-send me-2"></i><?= htmlspecialchars(Language::t('public.deposit.submit'), ENT_QUOTES, 'UTF-8') ?>
              </button>

              <div id="result" class="alert alert-success mt-3" style="display:none;">
                <i class="bi bi-check-circle me-2"></i><?= htmlspecialchars(Language::t('public.deposit.success'), ENT_QUOTES, 'UTF-8') ?>
              </div>
              <div id="error" class="alert alert-danger mt-3" style="display:none;"></div>

              <script>
                const CSRF_TOKEN = "<?= Session::csrfToken() ?>";
                const CHUNK_SIZE = <?= CHUNK_SIZE ?>;
                const MAX_FILE_SIZE = <?= MAX_FILE_SIZE ?>;
                const DEPOSIT_CODE = "<?= htmlspecialchars($code, ENT_QUOTES, 'UTF-8') ?>";
              </script>
              <script src="assets/js/utils.js?v=<?= Helpers::assetVersion('assets/js/utils.js') ?>"></script>
              <script src="assets/js/upload.js?v=<?= Helpers::assetVersion('assets/js/upload.js') ?>"></script>
              <script>
                (function () {
                  Lethe.state.csrfToken = CSRF_TOKEN;
                  const T = (key, params) => Lethe.i18n.t(key, params);
                  const dropZone = document.getElementById('file-drop-zone');
                  const fileInput = document.getElementById('file-input');
                  const selectedFileEl = document.getElementById('selected-file');
                  const submitBtn = document.getElementById('submit-btn');
                  const progressWrapper = document.getElementById('progress-wrapper');
                  const progressBar = document.getElementById('progress-bar');
                  const resultEl = document.getElementById('result');
                  const errorEl = document.getElementById('error');

                  let selectedFile = null;

                  dropZone.addEventListener('click', () => fileInput.click());
                  dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('dragover'); });
                  dropZone.addEventListener('dragleave', () => dropZone.classList.remove('dragover'));
                  dropZone.addEventListener('drop', (e) => {
                    e.preventDefault();
                    dropZone.classList.remove('dragover');
                    if (e.dataTransfer.files.length) setFile(e.dataTransfer.files[0]);
                  });
                  fileInput.addEventListener('change', () => {
                    if (fileInput.files.length) setFile(fileInput.files[0]);
                  });

                  function setFile(file) {
                    if (file.size > MAX_FILE_SIZE) {
                      showError(T('error.file_too_large'));
                      selectedFile = null;
                      submitBtn.disabled = true;
                      selectedFileEl.textContent = '';
                      return;
                    }
                    selectedFile = file;
                    selectedFileEl.textContent = T('sendfile.file_selected', { name: file.name, size: Lethe.utils.formatBytes(file.size) });
                    submitBtn.disabled = false;
                    hideError();
                  }

                  function showError(msg) { errorEl.textContent = msg; errorEl.style.display = 'block'; }
                  function hideError() { errorEl.style.display = 'none'; }

                  submitBtn.addEventListener('click', async () => {
                    if (!selectedFile) return;
                    hideError();
                    resultEl.style.display = 'none';
                    submitBtn.disabled = true;
                    progressWrapper.style.display = 'block';

                    try {
                      const { uploadId, totalChunks } = await Lethe.upload.sendChunks(
                        selectedFile,
                        '?action=deposit_upload_chunk',
                        { deposit_code: DEPOSIT_CODE },
                        (done, total) => {
                          const percent = Math.round((done / total) * 100);
                          progressBar.style.width = percent + '%';
                          progressBar.textContent = percent + '%';
                        }
                      );

                      const fd = new FormData();
                      fd.append('csrf_token', CSRF_TOKEN);
                      fd.append('deposit_code', DEPOSIT_CODE);
                      fd.append('upload_id', uploadId);
                      fd.append('total_chunks', totalChunks);
                      fd.append('original_name', selectedFile.name);
                      fd.append('uploader_name', document.getElementById('uploader_name').value);

                      const res = await fetch('?action=deposit_finalize', { method: 'POST', body: fd, headers: { 'X-CSRF-Token': CSRF_TOKEN } });
                      const data = await res.json();
                      if (data.status !== 'ok') throw new Error(data.message || T('error.finalize'));

                      progressWrapper.style.display = 'none';
                      resultEl.style.display = 'block';
                      selectedFile = null;
                      selectedFileEl.textContent = '';
                      fileInput.value = '';
                    } catch (err) {
                      showError(err.message);
                      progressWrapper.style.display = 'none';
                    } finally {
                      submitBtn.disabled = false;
                    }
                  });
                })();
              </script>
            <?php endif; ?>
          </div>
        </div>
      </main>
    </div>
  </body>
</html>
