<?php
declare(strict_types=1);

namespace Lethe\Controllers;

use Lethe\Auth;
use Lethe\Language;
use Lethe\Request;
use Lethe\Response;
use Lethe\Security;
use Lethe\Session;

final class AuthController
{
    private const LOGIN_MAX_ATTEMPTS = 5;
    private const LOGIN_LOCKOUT_SECONDS = 60;

    /**
     * Current session state (used by the SPA on load).
     */
    public function getSession(): void
    {
        Response::json([
            'status' => 'ok',
            'user' => Auth::currentUser(),
            'csrf_token' => Session::csrfToken(),
        ]);
    }

    /**
     * Login with a simple in-session rate limit (anti brute-force).
     */
    public function login(): void
    {
        Security::verifyCsrf();

        $lockUntil = (int)($_SESSION['login_lock_until'] ?? 0);
        if ($lockUntil > time()) {
            Response::json([
                'status' => 'error',
                'message' => Language::t('api.too_many_attempts', ['seconds' => $lockUntil - time()]),
            ], 429);
        }

        $username = trim((string)Request::field('username', ''));
        $password = (string)Request::field('password', '');

        if ($username === '' || $password === '') {
            Response::json(['status' => 'error', 'message' => Language::t('api.credentials_required')], 400);
        }

        $user = Auth::attempt($username, $password);
        if ($user) {
            unset($_SESSION['login_failures'], $_SESSION['login_lock_until']);
            Response::json(['status' => 'ok', 'user' => Auth::currentUser(), 'csrf_token' => Session::csrfToken()]);
            return;
        }

        $failures = (int)($_SESSION['login_failures'] ?? 0) + 1;
        $_SESSION['login_failures'] = $failures;
        if ($failures >= self::LOGIN_MAX_ATTEMPTS) {
            $_SESSION['login_lock_until'] = time() + self::LOGIN_LOCKOUT_SECONDS;
            $_SESSION['login_failures'] = 0;
            Response::json([
                'status' => 'error',
                'message' => Language::t('api.account_locked', ['seconds' => self::LOGIN_LOCKOUT_SECONDS]),
            ], 429);
            return;
        }

        Response::json(['status' => 'error', 'message' => Language::t('api.bad_credentials')], 401);
    }

    public function logout(): void
    {
        Security::verifyCsrf();
        Auth::logout();
        Response::json(['status' => 'ok']);
    }
}
