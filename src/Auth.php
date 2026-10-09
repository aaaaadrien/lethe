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

    /**
     * Validate an OIDC ID token (JWT).
     * Checks: signature (RS256), issuer, audience, expiry, and required claims.
     *
     * @param string $token The raw JWT string (header.payload.signature)
     * @return array{iss: string, sub: string, aud: string, exp: int, iat: int, preferred_username: string, email: string}|null
     */
    public static function validateIdToken(string $token): ?array
    {
        if (!defined('OIDC_ENABLED') || OIDC_ENABLED !== '1') {
            return null;
        }

        $parts = explode('.', $token);
        if (count($parts) !== 3) {
            return null;
        }

        // Decode header and payload (base64url)
        $header = json_decode(self::base64UrlDecode($parts[0]), true);
        $payload = json_decode(self::base64UrlDecode($parts[1]), true);

        if (!is_array($header) || !is_array($payload)) {
            return null;
        }

        // Check algorithm
        if (($header['alg'] ?? '') !== 'RS256') {
            return null;
        }
        if (($header['typ'] ?? '') !== 'JWT') {
            return null;
        }

        // Check required claims
        $required = ['iss', 'sub', 'aud', 'exp', 'iat'];
        foreach ($required as $claim) {
            if (!isset($payload[$claim])) {
                return null;
            }
        }

        // Check issuer
        if ($payload['iss'] !== OIDC_AUTH_SERVER) {
            return null;
        }

        // Check audience (must contain OIDC_CLIENT_ID)
        $aud = $payload['aud'];
        if (is_array($aud)) {
            if (!in_array(OIDC_CLIENT_ID, $aud, true)) {
                return null;
            }
        } elseif ($aud !== OIDC_CLIENT_ID) {
            return null;
        }

        // Check expiry with clock skew
        $now = time();
        if ($payload['exp'] < $now - OIDC_CLOCK_SKEW) {
            return null;
        }

        // Verify JWT signature using JWKS
        $kid = $header['kid'] ?? '';
        if (!$kid) {
            return null;
        }
        $jwksUri = OIDC_JWKS_URI ?: OIDC_AUTH_SERVER . '/protocol/openid-connect/certs';
        $jwks = self::fetchJwks($jwksUri);
        if (!$jwks) {
            return null;
        }

        $key = null;
        foreach ($jwks['keys'] as $k) {
            if (($k['kid'] ?? '') === $kid && ($k['kty'] ?? '') === 'RSA') {
                $key = $k;
                break;
            }
        }
        if (!$key) {
            return null;
        }

        // Verify signature
        $signature = self::base64UrlDecode($parts[2]);
        $publicKey = self::jwkToPublicKey($key);
        if ($publicKey === null) {
            return null;
        }
        $result = openssl_verify(
            $parts[0] . '.' . $parts[1],
            $signature,
            $publicKey,
            OPENSSL_ALGO_SHA256
        );
        if ($result !== 1) {
            return null;
        }

        return $payload;
    }

    /**
     * Authenticate a user via OIDC. Validates the ID token, finds or creates
     * the user, and establishes a session. Returns the user row on success.
     */
    public static function attemptOidc(string $token): ?array
    {
        $payload = self::validateIdToken($token);
        if (!$payload) {
            return null;
        }

        // Extract username from preferred_username or email
        $username = $payload['preferred_username'] ?? $payload['email'] ?? $payload['sub'];

        // Find or create user
        $user = Database::getOrCreateUserByOidc(Database::connect(), $payload['sub'], OIDC_AUTH_SERVER, $username);
        if (!$user) {
            return null;
        }

        // Update email from OIDC payload on every login (email can change).
        if (!empty($payload['email'])) {
            Database::connect()->prepare('UPDATE users SET email = ? WHERE id = ?')
                ->execute([$payload['email'], $user['id']]);
        }

        // Establish session
        Session::regenerate();
        $_SESSION['user_id'] = (int)$user['id'];
        $_SESSION['username'] = $user['username'];
        $_SESSION['is_admin'] = (int)$user['is_admin'];
        $_SESSION['oidc_sub'] = $payload['sub'];
        $_SESSION['oidc_provider'] = OIDC_AUTH_SERVER;

        return $user;
    }

    /**
     * Get OIDC provider configuration for the SPA (authorization URL, scopes, etc.).
     * Returns null if OIDC is not enabled.
     */
    public static function getOidcConfig(): ?array
    {
        if (!defined('OIDC_ENABLED') || OIDC_ENABLED !== '1') {
            return null;
        }
        return [
            'enabled' => true,
            'authorization_url' => OIDC_AUTH_SERVER . '/protocol/openid-connect/auth',
            'client_id' => OIDC_CLIENT_ID,
            'redirect_uri' => OIDC_REDIRECT_URI,
            'scopes' => OIDC_SCOPES,
            'pkce' => true,
        ];
    }

    // --- Internal helpers ---

    private static function base64UrlDecode(string $input): string
    {
        $remainder = strlen($input) % 4;
        if ($remainder) {
            $input .= str_repeat('=', 4 - $remainder);
        }
        return base64_decode(strtr($input, '-_', '+/'));
    }

    private static function fetchJwks(string $uri): ?array
    {
        $cacheFile = dirname(DB_FILE) . '/.oidc_jwks_cache.json';
        $cacheTtl = OIDC_JWKS_CACHE_TTL;

        // Use cache if available and not expired
        if (is_file($cacheFile)) {
            $cacheData = json_decode(file_get_contents($cacheFile), true);
            if (is_array($cacheData) && isset($cacheData['keys']) && $cacheData['expires'] > time()) {
                return $cacheData;
            }
        }

        $ch = curl_init($uri);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
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
        if (!is_array($data) || !isset($data['keys']) || !is_array($data['keys'])) {
            return null;
        }

        // Cache the result
        $data['expires'] = time() + $cacheTtl;
        file_put_contents($cacheFile, json_encode($data), LOCK_EX);

        return $data;
    }

    private static function jwkToPublicKey(array $jwk): mixed
    {
        $n = self::base64UrlDecode($jwk['n']);
        $e = self::base64UrlDecode($jwk['e']);

        // Remove leading zeros
        $n = ltrim($n, "\x00");
        $e = ltrim($e, "\x00");

        // Add leading 0x00 if high bit is set (to indicate positive)
        if ((ord($n[0]) & 0x80) !== 0) {
            $n = "\x00" . $n;
        }
        if ((ord($e[0]) & 0x80) !== 0) {
            $e = "\x00" . $e;
        }

        // Build RSA public key in DER format (ASN.1 SEQUENCE)
        $der = self::buildRsaPublicKeyDer($n, $e);

        return openssl_pkey_get_public(
            '-----BEGIN PUBLIC KEY-----' . "\n"
            . chunk_split(base64_encode($der), 64)
            . '-----END PUBLIC KEY-----'
        );
    }

    private static function buildRsaPublicKeyDer(string $n, string $e): string
    {
        // RSA public key DER format:
        // SEQUENCE {
        //   SEQUENCE { OID rsaEncryption(1.2.840.113549.1.1.1), NULL }
        //   BIT STRING { SEQUENCE { INTEGER n, INTEGER e } }
        // }
        $rsaOid = "\x06\x09\x2a\x86\x48\x86\xf7\x0d\x01\x01\x01"; // 1.2.840.113549.1.1.1
        $nullByte = "\x05\x00";

        // ASN.1 INTEGER for n
        $nDer = self::asn1Integer($n);
        // ASN.1 INTEGER for e
        $eDer = self::asn1Integer($e);

        // SEQUENCE { INTEGER n, INTEGER e }
        $keyContent = $nDer . $eDer;
        $keySeq = self::asn1Sequence($keyContent);

        // BIT STRING wrapping the key SEQUENCE
        $bitString = "\x03" . self::asn1Length(strlen($keySeq) + 1) . "\x00" . $keySeq;

        // SEQUENCE { SEQUENCE { OID, NULL }, BIT STRING }
        $algSeq = $rsaOid . $nullByte;
        $algSeq = self::asn1Sequence($algSeq);

        return self::asn1Sequence($algSeq . $bitString);
    }

    private static function asn1Integer(string $value): string
    {
        $len = strlen($value);
        return "\x02" . self::asn1Length($len) . $value;
    }

    private static function asn1Sequence(string $content): string
    {
        $len = strlen($content);
        return "\x30" . self::asn1Length($len) . $content;
    }

    private static function asn1Length(int $length): string
    {
        if ($length < 0x80) {
            return chr($length);
        }
        $bytes = [];
        while ($length > 0) {
            array_unshift($bytes, chr($length & 0xFF));
            $length >>= 8;
        }
        return chr(0x80 | count($bytes)) . implode('', $bytes);
    }
}
