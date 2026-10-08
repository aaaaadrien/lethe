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
            'oidc' => Auth::getOidcConfig(),
        ]);
    }

    /**
     * OIDC callback: exchange authorization code for ID token, validate it,
     * and establish a session. Uses PKCE (code_verifier stored in session).
     */
    public function oidcCallback(): void
    {
        if (!defined('OIDC_ENABLED') || OIDC_ENABLED !== '1') {
            Response::json(['status' => 'error', 'message' => 'OIDC non configuré'], 500);
            return;
        }

        $code = Request::query('code');
        if ($code === '') {
            Response::json(['status' => 'error', 'message' => 'code requis'], 400);
            return;
        }

        $state = Request::query('state');
        $sessionState = $_SESSION['oidc_state'] ?? '';
        if (!$state || !$sessionState || !hash_equals($sessionState, $state)) {
            Response::json(['status' => 'error', 'message' => 'state invalide'], 400);
            return;
        }

        $codeVerifier = $_SESSION['oidc_code_verifier'] ?? '';
        if (!$codeVerifier) {
            Response::json(['status' => 'error', 'message' => 'code_verifier manquant'], 400);
            return;
        }

        // Exchange code for token
        $tokenResponse = self::exchangeCodeForToken($code, $codeVerifier);
        if (!$tokenResponse) {
            Response::json(['status' => 'error', 'message' => 'Échec de l\'échange de token'], 500);
            return;
        }

        $idToken = $tokenResponse['id_token'] ?? '';
        if (!$idToken) {
            Response::json(['status' => 'error', 'message' => 'id_token manquant'], 400);
            return;
        }

        // Validate and authenticate
        $user = Auth::attemptOidc($idToken);
        if (!$user) {
            Response::json(['status' => 'error', 'message' => 'Échec de l\'authentification OIDC'], 401);
            return;
        }

        // Clear OIDC session data
        unset($_SESSION['oidc_state'], $_SESSION['oidc_code_verifier']);

        // Redirect to dashboard (SPA will detect logged-in session on next load)
        header('Location: index.php');
        exit;
    }

    /**
     * Initialize OIDC login: generate PKCE code_verifier and state, store in session,
     * and return the authorization URL for the SPA to redirect to.
     */
    public function oidcInit(): void
    {
        if (!defined('OIDC_ENABLED') || OIDC_ENABLED !== '1') {
            Response::json(['status' => 'error', 'message' => 'OIDC non configuré'], 500);
            return;
        }

        $codeVerifier = bin2hex(random_bytes(32));
        $state = bin2hex(random_bytes(16));

        $_SESSION['oidc_code_verifier'] = $codeVerifier;
        $_SESSION['oidc_state'] = $state;

        // Generate PKCE code_challenge (S256)
        $codeChallenge = hash('sha256', $codeVerifier, true);
        $codeChallenge = rtrim(strtr(base64_encode($codeChallenge), '+/', '-_'), '=');

        $params = http_build_query([
            'response_type' => 'code',
            'client_id' => OIDC_CLIENT_ID,
            'redirect_uri' => OIDC_REDIRECT_URI,
            'scope' => OIDC_SCOPES,
            'code_challenge' => $codeChallenge,
            'code_challenge_method' => 'S256',
            'state' => $state,
        ]);

        Response::json([
            'status' => 'ok',
            'authorization_url' => OIDC_AUTH_SERVER . '/protocol/openid-connect/auth?' . $params,
        ]);
    }

    /**
     * Exchange PKCE code for tokens at the authorization server.
     */
    private static function exchangeCodeForToken(string $code, string $codeVerifier): ?array
    {
        $ch = curl_init(OIDC_AUTH_SERVER . '/protocol/openid-connect/token');
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'grant_type' => 'authorization_code',
                'code' => $code,
                'redirect_uri' => OIDC_REDIRECT_URI,
                'client_id' => OIDC_CLIENT_ID,
                'code_verifier' => $codeVerifier,
                'client_secret' => OIDC_CLIENT_SECRET,
            ]),
            CURLOPT_TIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_SSL_VERIFYHOST => 2,
        ]);
        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        if (!is_array($data) || !isset($data['id_token'])) {
            return null;
        }

        return $data;
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
