<?php
declare(strict_types=1);

namespace Lethe\Controllers;

use Lethe\Auth;
use Lethe\Database;
use Lethe\Exceptions\UserException;
use Lethe\FileManager;
use Lethe\Helpers;
use Lethe\Language;
use Lethe\Request;
use Lethe\Response;
use Lethe\Security;

final class DepositController
{
    /**
     * Lists the current user's deposits with their received files.
     */
    public function getMyDeposits(): void
    {
        Auth::requireLogin();
        $pdo = Database::connect();

        $stmt = $pdo->prepare('SELECT * FROM deposits WHERE owner_user_id = ? ORDER BY created_at DESC');
        $stmt->execute([Auth::id()]);
        $deposits = $stmt->fetchAll();

        $filesStmt = $pdo->prepare('SELECT * FROM deposit_files WHERE deposit_id = ? ORDER BY created_at DESC');
        $mapped = [];
        foreach ($deposits as $deposit) {
            $filesStmt->execute([$deposit['id']]);
            $mapped[] = [
                'id' => (int)$deposit['id'],
                'label' => $deposit['label'],
                'has_password' => $deposit['password_hash'] !== null,
                'active' => (int)$deposit['active'] === 1,
                'expires_at' => $deposit['expires_at'],
                'created_at' => $deposit['created_at'],
                'link' => Helpers::appUrl() . '/?deposit=' . $deposit['code'],
                'files' => array_map(fn(array $f) => [
                    'id' => (int)$f['id'],
                    'name' => $f['original_name'],
                    'size' => (int)$f['size'],
                    'uploader_name' => $f['uploader_name'],
                    'created_at' => $f['created_at'],
                ], $filesStmt->fetchAll()),
            ];
        }

        Response::json(['status' => 'ok', 'deposits' => $mapped]);
    }

    /**
     * Creates a new deposit link. JSON body: label, password, expiration_days.
     */
    public function createDeposit(): void
    {
        Auth::requireLogin();
        Security::verifyCsrf();

        $label = trim((string)Request::field('label', '')) ?: Language::t('api.deposit_default_label');
        $password = (string)Request::field('password', '');
        $expirationDays = Request::intField('expiration_days', DEFAULT_EXPIRATION_DAYS);

        if ($expirationDays < MIN_EXPIRATION_DAYS || $expirationDays > MAX_EXPIRATION_DAYS) {
            Response::json(['status' => 'error', 'message' => Language::t('api.expiration_range', ['min' => MIN_EXPIRATION_DAYS, 'max' => MAX_EXPIRATION_DAYS])], 400);
        }

        $code = Helpers::generateCode(20);
        $expiresAt = (new \DateTime())->modify("+{$expirationDays} days")->format('c');
        $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;

        $stmt = Database::connect()->prepare('INSERT INTO deposits (code, owner_user_id, label, password_hash, expires_at, created_at) VALUES (?, ?, ?, ?, ?, ?)');
        $stmt->execute([$code, Auth::id(), $label, $passwordHash, $expiresAt, Helpers::now()]);

        Response::json(['status' => 'ok', 'message' => Language::t('api.deposit_created'), 'link' => Helpers::appUrl() . '/?deposit=' . $code]);
    }

    public function toggleDeposit(): void
    {
        Auth::requireLogin();
        Security::verifyCsrf();

        $depositId = Request::intField('id');
        $stmt = Database::connect()->prepare('SELECT * FROM deposits WHERE id = ? AND owner_user_id = ?');
        $stmt->execute([$depositId, Auth::id()]);
        $deposit = $stmt->fetch();

        if (!$deposit) {
            Response::json(['status' => 'error', 'message' => Language::t('api.deposit_not_found')], 404);
        }

        Database::connect()->prepare('UPDATE deposits SET active = ? WHERE id = ?')
            ->execute([(int)$deposit['active'] ? 0 : 1, $depositId]);
        Response::json(['status' => 'ok', 'message' => Language::t('api.deposit_status_updated')]);
    }

    public function deleteDepositFile(): void
    {
        Auth::requireLogin();
        Security::verifyCsrf();

        $depositFileId = Request::intField('id');
        $stmt = Database::connect()->prepare('SELECT df.* FROM deposit_files df
            JOIN deposits d ON d.id = df.deposit_id
            WHERE df.id = ? AND d.owner_user_id = ?');
        $stmt->execute([$depositFileId, Auth::id()]);
        $df = $stmt->fetch();

        if (!$df) {
            Response::json(['status' => 'error', 'message' => Language::t('api.file_not_found')], 404);
        }

        $path = STORAGE_PATH . '/' . $df['stored_name'];
        if (file_exists($path)) {
            @unlink($path);
        }
        Database::connect()->prepare('DELETE FROM deposit_files WHERE id = ?')->execute([$depositFileId]);
        Response::json(['status' => 'ok', 'message' => Language::t('api.deposit_file_deleted')]);
    }

    /**
     * Guest endpoint: receives one fragment for a deposit link
     * (multipart: deposit_code, upload_id, index, chunk).
     */
    public function guestUploadChunk(): void
    {
        Security::verifyCsrf();

        $deposit = $this->guardDeposit((string)($_POST['deposit_code'] ?? ''));

        $uploadId = (string)($_POST['upload_id'] ?? '');
        $index = $_POST['index'] ?? null;

        if (!preg_match('/^[a-zA-Z0-9]+$/', $uploadId) || $index === null || !isset($_FILES['chunk'])) {
            Response::json(['status' => 'error', 'message' => Language::t('api.invalid_request')], 400);
        }

        try {
            $currentSize = FileManager::currentSize($uploadId);
            if ($currentSize + (int)$_FILES['chunk']['size'] > MAX_FILE_SIZE) {
                FileManager::cleanupChunks($uploadId);
                Response::json(['status' => 'error', 'message' => Language::t('api.max_size_exceeded')], 400);
            }

            FileManager::saveChunk($uploadId, (int)$index, $_FILES['chunk']['tmp_name']);
            Response::json(['status' => 'ok']);
        } catch (UserException $e) {
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Guest endpoint: assembles the fragments and stores the received file
     * (multipart: deposit_code, upload_id, total_chunks, original_name,
     * uploader_name).
     */
    public function guestFinalize(): void
    {
        Security::verifyCsrf();

        $deposit = $this->guardDeposit((string)($_POST['deposit_code'] ?? ''));

        $uploadId = (string)($_POST['upload_id'] ?? '');
        $totalChunks = (int)($_POST['total_chunks'] ?? 0);
        $originalName = (string)($_POST['original_name'] ?? Language::t('helpers.default_filename'));
        $uploaderName = trim((string)($_POST['uploader_name'] ?? ''));

        if (!preg_match('/^[a-zA-Z0-9]+$/', $uploadId) || $totalChunks <= 0) {
            Response::json(['status' => 'error', 'message' => Language::t('api.invalid_request')], 400);
        }

        try {
            $assembled = FileManager::assemble($uploadId, $totalChunks, $originalName);

            $stmt = Database::connect()->prepare('INSERT INTO deposit_files
                (deposit_id, original_name, stored_name, size, uploader_name, expires_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $deposit['id'],
                Helpers::sanitizeFilename($originalName),
                $assembled['stored_name'],
                $assembled['size'],
                $uploaderName !== '' ? $uploaderName : null,
                $deposit['expires_at'], // the file expires together with the deposit link
                Helpers::now(),
            ]);

            Response::json(['status' => 'ok', 'message' => Language::t('api.file_sent')]);
        } catch (UserException $e) {
            FileManager::cleanupChunks($uploadId);
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Streams a received deposit file to the owner (authenticated).
     */
    public function downloadDepositFile(): void
    {
        Auth::requireLogin();

        $id = (int)($_GET['id'] ?? 0);
        $stmt = Database::connect()->prepare('SELECT df.*, d.owner_user_id FROM deposit_files df
            JOIN deposits d ON d.id = df.deposit_id
            WHERE df.id = ?');
        $stmt->execute([$id]);
        $file = $stmt->fetch();

        if (!$file || (int)$file['owner_user_id'] !== Auth::id()) {
            Response::json(['status' => 'error', 'message' => Language::t('api.file_not_found')], 404);
        }

        $path = STORAGE_PATH . '/' . $file['stored_name'];
        if (!file_exists($path)) {
            Response::json(['status' => 'error', 'message' => Language::t('error.file_unavailable')], 404);
        }

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
            flush;
        }
        fclose($handle);
        exit;
    }

    /**
     * Checks that a deposit (by code) is valid, active, not expired and that
     * its password (if any) has been validated in the session.
     *
     * @return array<string, mixed>
     */
    private function guardDeposit(string $code): array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM deposits WHERE code = ?');
        $stmt->execute([$code]);
        $deposit = $stmt->fetch();

        if (!$deposit) {
            Response::json(['status' => 'error', 'message' => Language::t('api.deposit_link_not_found')], 404);
        }
        if (!(int)$deposit['active']) {
            Response::json(['status' => 'error', 'message' => Language::t('error.deposit_disabled')], 403);
        }
        if (Helpers::isExpired($deposit['expires_at'])) {
            Response::json(['status' => 'error', 'message' => Language::t('error.deposit_expired')], 403);
        }
        if ($deposit['password_hash'] !== null && empty($_SESSION['deposit_access_' . $deposit['id']])) {
            Response::json(['status' => 'error', 'message' => Language::t('error.password_required')], 403);
        }

        return $deposit;
    }
}
