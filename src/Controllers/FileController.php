<?php
declare(strict_types=1);

namespace Lethe\Controllers;

use Lethe\Auth;
use Lethe\Database;
use Lethe\Exceptions\UserException;
use Lethe\FileManager;
use Lethe\Helpers;
use Lethe\Language;
use Lethe\Mailer;
use Lethe\Request;
use Lethe\Response;
use Lethe\Security;

final class FileController
{
    /**
     * Receives one upload fragment (multipart: upload_id, index, chunk).
     */
    public function uploadChunk(): void
    {
        Auth::requireLogin();
        Security::verifyCsrf();

        $uploadId = (string)($_POST['upload_id'] ?? '');
        $index = $_POST['index'] ?? null;

        if (!preg_match('/^[a-zA-Z0-9]+$/', $uploadId) || $index === null || !isset($_FILES['chunk'])) {
            Response::json(['status' => 'error', 'message' => Language::t('api.invalid_request')], 400);
        }

        try {
            // Extra guard: the cumulative size must not exceed the limit.
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
     * Assembles the fragments, stores the file row and (optionally) e-mails
     * the share link. JSON body: upload_id, total_chunks, original_name,
     * file_size, password, expiration_days, delivery_method, recipient_email,
     * custom_message.
     */
    public function finalizeUpload(): void
    {
        Auth::requireLogin();
        Security::verifyCsrf();

        $uploadId = (string)Request::field('upload_id', '');
        $totalChunks = Request::intField('total_chunks');
        $originalName = (string)Request::field('original_name', Language::t('helpers.default_filename'));
        $password = (string)Request::field('password', '');
        $expirationDays = Request::intField('expiration_days', DEFAULT_EXPIRATION_DAYS);
        $deliveryMethod = (string)Request::field('delivery_method', 'link');
        $recipientEmail = trim((string)Request::field('recipient_email', ''));
        $customMessage = (string)Request::field('custom_message', '');

        if (!preg_match('/^[a-zA-Z0-9]+$/', $uploadId) || $totalChunks <= 0) {
            Response::json(['status' => 'error', 'message' => Language::t('api.invalid_request')], 400);
        }
        if ($expirationDays < MIN_EXPIRATION_DAYS || $expirationDays > MAX_EXPIRATION_DAYS) {
            Response::json(['status' => 'error', 'message' => Language::t('api.expiration_range', ['min' => MIN_EXPIRATION_DAYS, 'max' => MAX_EXPIRATION_DAYS])], 400);
        }
        if ($deliveryMethod === 'email' && !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Response::json(['status' => 'error', 'message' => Language::t('api.invalid_email')], 400);
        }

        try {
            $assembled = FileManager::assemble($uploadId, $totalChunks, $originalName);

            $code = Helpers::generateCode(20);
            $expiresAt = (new \DateTime())->modify("+{$expirationDays} days")->format('c');
            $passwordHash = $password !== '' ? password_hash($password, PASSWORD_DEFAULT) : null;

            $stmt = Database::connect()->prepare('INSERT INTO files
                (code, owner_user_id, original_name, stored_name, size, password_hash, recipient_email, expires_at, created_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)');
            $stmt->execute([
                $code,
                Auth::id(),
                Helpers::sanitizeFilename($originalName),
                $assembled['stored_name'],
                $assembled['size'],
                $passwordHash,
                $deliveryMethod === 'email' ? $recipientEmail : null,
                $expiresAt,
                Helpers::now(),
            ]);

            $link = Helpers::appUrl() . '/?code=' . $code;

            if ($deliveryMethod === 'email') {
                $expiresAtHuman = Helpers::formatDate($expiresAt);
                if (!Mailer::sendShareLink($recipientEmail, $link, $expiresAtHuman, $passwordHash !== null, $customMessage)) {
                    Response::json(['status' => 'error', 'message' => Language::t('api.email_failed')], 502);
                    return;
                }
                Response::json(['status' => 'ok', 'sent_by_email' => true]);
                return;
            }

            Response::json(['status' => 'ok', 'link' => $link]);
        } catch (UserException $e) {
            FileManager::cleanupChunks($uploadId);
            Response::json(['status' => 'error', 'message' => $e->getMessage()], 400);
        }
    }

    /**
     * Lists the files shared by the current user.
     */
    public function getMyFiles(): void
    {
        Auth::requireLogin();

        $stmt = Database::connect()->prepare('SELECT * FROM files WHERE owner_user_id = ? ORDER BY created_at DESC');
        $stmt->execute([Auth::id()]);

        $files = array_map(fn(array $f) => [
            'id' => (int)$f['id'],
            'name' => $f['original_name'],
            'size' => (int)$f['size'],
            'has_password' => $f['password_hash'] !== null,
            'status' => (int)$f['revoked'] === 1 ? 'revoked' : (Helpers::isExpired($f['expires_at']) ? 'expired' : 'active'),
            'expires_at' => $f['expires_at'],
            'created_at' => $f['created_at'],
            'download_count' => (int)$f['download_count'],
            'link' => Helpers::appUrl() . '/?code=' . $f['code'],
        ], $stmt->fetchAll());

        Response::json(['status' => 'ok', 'files' => $files]);
    }

    public function revokeFile(): void
    {
        Auth::requireLogin();
        Security::verifyCsrf();

        $fileId = Request::intField('id');
        $stmt = Database::connect()->prepare('UPDATE files SET revoked = 1 WHERE id = ? AND owner_user_id = ? AND revoked = 0');
        $stmt->execute([$fileId, Auth::id()]);

        if ($stmt->rowCount() === 0) {
            Response::json(['status' => 'error', 'message' => Language::t('api.file_not_found_or_revoked')], 404);
        }
        Response::json(['status' => 'ok', 'message' => Language::t('api.link_revoked_msg')]);
    }

    public function deleteFile(): void
    {
        Auth::requireLogin();
        Security::verifyCsrf();

        $fileId = Request::intField('id');
        $stmt = Database::connect()->prepare('SELECT * FROM files WHERE id = ? AND owner_user_id = ?');
        $stmt->execute([$fileId, Auth::id()]);
        $file = $stmt->fetch();

        if (!$file) {
            Response::json(['status' => 'error', 'message' => Language::t('api.file_not_found')], 404);
        }

        $path = STORAGE_PATH . '/' . $file['stored_name'];
        if (file_exists($path)) {
            @unlink($path);
        }
        Database::connect()->prepare('DELETE FROM files WHERE id = ?')->execute([$fileId]);
        Response::json(['status' => 'ok', 'message' => Language::t('api.file_deleted')]);
    }
}
