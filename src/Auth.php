<?php
declare(strict_types=1);

namespace Lethe;

use Lethe\Exceptions\UserException;

final class Auth
{
    /**
     * Attempt a login. Returns the user row on success, null on failure.
     */
    public static function attempt(string $username, string $password): ?array
    {
        $stmt = Database::connect()->prepare('SELECT * FROM users WHERE username = ?');
        $stmt->execute([$username]);
        $user = $stmt->fetch();

        if ($user && password_verify($password, $user['password_hash'])) {
            Session::regenerate();
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['username'] = $user['username'];
            $_SESSION['is_admin'] = (int)$user['is_admin'];
            return $user;
        }

        return null;
    }

    public static function logout(): void
    {
        Session::destroy();
    }

    public static function check(): bool
    {
        return isset($_SESSION['user_id']);
    }

    public static function id(): ?int
    {
        return $_SESSION['user_id'] ?? null;
    }

    public static function username(): ?string
    {
        return $_SESSION['username'] ?? null;
    }

    public static function isAdmin(): bool
    {
        return (int)($_SESSION['is_admin'] ?? 0) === 1;
    }

    /**
     * Current user as an array (id, username, is_admin) or null.
     *
     * @return array{id: int, username: string, is_admin: bool}|null
     */
    public static function currentUser(): ?array
    {
        if (!self::check()) {
            return null;
        }
        return [
            'id' => self::id(),
            'username' => self::username(),
            'is_admin' => self::isAdmin(),
        ];
    }

    /**
     * Require an authenticated user for API calls (JSON 401 otherwise).
     */
    public static function requireLogin(): void
    {
        if (!self::check()) {
            Response::json(['status' => 'error', 'message' => Language::t('api.login_required')], 401);
        }
    }

    /**
     * Require an admin for API calls (JSON 403 otherwise).
     */
    public static function requireAdmin(): void
    {
        self::requireLogin();
        if (!self::isAdmin()) {
            Response::json(['status' => 'error', 'message' => Language::t('api.admin_only')], 403);
        }
    }
}
