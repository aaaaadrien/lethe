<?php
declare(strict_types=1);

namespace Lethe\Controllers;

use Lethe\Auth;
use Lethe\Database;
use Lethe\Helpers;
use Lethe\Language;
use Lethe\Request;
use Lethe\Response;
use Lethe\Security;
use PDOException;

final class UserController
{
    public function getUsers(): void
    {
        Auth::requireAdmin();

        $rows = Database::connect()->query('SELECT * FROM users ORDER BY created_at ASC')->fetchAll();
        $users = array_map(fn(array $u) => [
            'id' => (int)$u['id'],
            'username' => $u['username'],
            'is_admin' => (int)$u['is_admin'],
            'created_at' => $u['created_at'],
            'oidc_sub' => $u['oidc_sub'] ?? null,
            'oidc_provider' => $u['oidc_provider'] ?? null,
        ], $rows);
        Response::json(['status' => 'ok', 'users' => $users]);
    }

    /**
     * Creates a user. JSON body: username, password, is_admin (0/1).
     */
    public function createUser(): void
    {
        Auth::requireAdmin();
        Security::verifyCsrf();

        $username = trim((string)Request::field('username', ''));
        $password = (string)Request::field('password', '');
        $isAdmin = (int)Request::intField('is_admin') === 1;

        if ($username === '' || strlen($password) < 4) {
            Response::json(['status' => 'error', 'message' => Language::t('api.user_credentials_required')], 400);
        }

        try {
            $stmt = Database::connect()->prepare('INSERT INTO users (username, password_hash, is_admin, created_at) VALUES (?, ?, ?, ?)');
            $stmt->execute([$username, password_hash($password, PASSWORD_DEFAULT), $isAdmin ? 1 : 0, Helpers::now()]);
            Response::json(['status' => 'ok', 'message' => Language::t('api.user_created')]);
        } catch (PDOException $e) {
            Response::json(['status' => 'error', 'message' => Language::t('api.user_exists')], 409);
        }
    }

    public function deleteUser(): void
    {
        Auth::requireAdmin();
        Security::verifyCsrf();

        $userId = Request::intField('id');
        if ($userId === Auth::id()) {
            Response::json(['status' => 'error', 'message' => Language::t('api.cannot_delete_self')], 400);
        }

        $stmt = Database::connect()->prepare('DELETE FROM users WHERE id = ?');
        $stmt->execute([$userId]);

        if ($stmt->rowCount() === 0) {
            Response::json(['status' => 'error', 'message' => Language::t('api.user_not_found')], 404);
        }
        Response::json(['status' => 'ok', 'message' => Language::t('api.user_deleted')]);
    }

    /**
     * Resets a user's password. JSON body: id, new_password.
     */
    public function resetUserPassword(): void
    {
        Auth::requireAdmin();
        Security::verifyCsrf();

        $userId = Request::intField('id');
        $newPassword = (string)Request::field('new_password', '');

        if (strlen($newPassword) < 4) {
            Response::json(['status' => 'error', 'message' => Language::t('api.password_too_short')], 400);
        }

        $stmt = Database::connect()->prepare('UPDATE users SET password_hash = ? WHERE id = ?');
        $stmt->execute([password_hash($newPassword, PASSWORD_DEFAULT), $userId]);

        if ($stmt->rowCount() === 0) {
            Response::json(['status' => 'error', 'message' => Language::t('api.user_not_found')], 404);
        }
        Response::json(['status' => 'ok', 'message' => Language::t('api.password_reset')]);
    }

    /**
     * Get the current user's profile (email).
     */
    public function getProfile(): void
    {
        Auth::requireLogin();

        $stmt = Database::connect()->prepare('SELECT email FROM users WHERE id = ?');
        $stmt->execute([Auth::id()]);
        $user = $stmt->fetch();

        Response::json(['status' => 'ok', 'email' => $user['email'] ?? null]);
    }

    /**
     * Update the current user's email. JSON body: email.
     */
    public function updateProfile(): void
    {
        Auth::requireLogin();
        Security::verifyCsrf();

        $email = trim((string)Request::field('email', ''));

        if ($email !== '' && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            Response::json(['status' => 'error', 'message' => Language::t('api.invalid_email')], 400);
        }

        $stmt = Database::connect()->prepare('UPDATE users SET email = ? WHERE id = ?');
        $stmt->execute([strlen($email) > 0 ? $email : null, Auth::id()]);

        Response::json(['status' => 'ok', 'message' => Language::t('api.profile_updated')]);
    }
}
