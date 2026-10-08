# Lethe

**Lethe** is a self-hosted alternative to WeTransfer: a PHP 8+ application (no framework, no
Composer) backed by SQLite, for sending large files (up to 10 GB) via a download link, with
optional password protection, mandatory expiration and e-mail notification. It also provides
**deposit links** so that guests can send you files without an account, and **secret
messages**: text encrypted with AES-256-GCM, shared through a one-time link with a limited
lifetime (1, 7, 14 or 30 days), permanently destroyed after its first view.

The interface is a dark-themed single-page application (SPA): a sidebar gives access to each
tool (send a file, my files, my deposits, secret messages, administration), and data is
loaded via AJAX through a `?action=...` API. The UI is multilingual (English and French) and
is automatically detected from the browser's `Accept-Language` header.

## Prerequisites

- PHP 8.1+ with the **pdo_sqlite**, **openssl** and **fileinfo** extensions (plus `mbstring`
  if available, otherwise an automatic fallback to `strlen`)
- Apache (with `mod_php` or PHP-FPM) or Nginx + PHP-FPM
- A working e-mail channel: a local MTA (`sendmail`/`postfix`) so that `mail()` works, or an
  SMTP relay configured in `config.php` (see [Email sending](#email-sending))

## Installation

1. Copy the entire `lethe/` folder to the server.
2. **The `DocumentRoot` (or Nginx `root`) must point to the `lethe/` folder itself** (the
   front controller `index.php` is at the root). The `src/`, `data/`, `uploads/`, `cron/`
   and `lang/` folders and the `config.php` file are protected by `.htaccess`
   `Require all denied` files (Apache); for Nginx, add the equivalents:
   ```nginx
   location ~ ^/(src|data|uploads|cron|lang)/ { deny all; }
   location = /config.php { deny all; }
   ```
3. Make the `uploads/` (and `uploads/tmp/`) and `data/` folders writable by the web server
   user:
   ```
   chown -R www-data:www-data lethe/uploads lethe/data
   chmod -R 775 lethe/uploads lethe/data
   ```
   If SELinux is present:
   ```
   chcon -R -t httpd_sys_rw_content_t lethe/data
   chcon -R -t httpd_sys_rw_content_t lethe/uploads
   ```
4. Open `config.php` (at the root, outside the webroot) and adapt at minimum:
   - `APP_URL`: the public URL of the application (e.g. `https://transfer.example.com`) —
     leave empty for automatic detection
   - `MAIL_FROM_ADDRESS` / `MAIL_FROM_NAME`: the e-mail sender
   - `MAIL_BODY_TEMPLATE`: the default e-mail body (users can also customize it per send)
   - `MAX_FILE_SIZE`, `MIN_EXPIRATION_DAYS`, `MAX_EXPIRATION_DAYS`, `DEFAULT_EXPIRATION_DAYS`
   - the `SMTP_*` block if `MAIL_TRANSPORT` is `'smtp'`
   - the OIDC block (see [OIDC login](#oidc-login))
5. Log in for the first time with the automatically created account:
   - **User: `admin`**
   - **Password: `admin`**
   - **Change it immediately** from Users → Reset (or directly in the database).

The SQLite database (`data/database.sqlite`) and its tables are created automatically on
first access. The secret-message encryption key (`data/secret.key`) is generated on first
use (permissions 0600).

## Server configuration (important for large files)

Uploads are performed in **1 MB chunks** sent via AJAX, which bypasses most classic PHP
limits (`upload_max_filesize`, `post_max_size`, `max_execution_time`). It is still
recommended to adjust `php.ini` for comfort:

```ini
upload_max_filesize = 16M
post_max_size = 16M
max_execution_time = 300
max_input_time = 300
memory_limit = 256M
```

For Nginx, raise the request size limit (if a proxy is used):
```nginx
client_max_body_size 20M;
```

Downloads disable timeouts on their own (`set_time_limit(0)`) and stream the file in 8 MB
blocks, so no server limit blocks a 10 GB file on the download side.

## Configuration

### Cron task

Add to the web server user's crontab (e.g. `www-data`):

```
0 * * * * /usr/bin/php /path/to/lethe/cron/cleanup.php >> /var/log/lethe-cleanup.log 2>&1
```

This script (shared logic in `src/Services/Cleanup.php`, CLI only) will:
- delete from disk every expired or revoked shared file (and its database row),
- delete every file received via a deposit link whose expiration has passed,
- disable expired deposit links,
- delete expired or already-viewed secret messages (for safety: a viewed message is already
  emptied by the application itself on read; the cron only cleans up the residual row),
- clean up abandoned upload chunks older than 24 hours.

### Email sending

Two modes are available in `config.php`, via `MAIL_TRANSPORT`:

#### a) `sendmail`
Uses PHP's native `mail()` function, which relies on the server's local MTA
(sendmail/postfix). Simple, but depends on a properly configured MTA on the machine.

#### b) `smtp`
Sends e-mails directly via an SMTP server (internal or external), with no external
dependency (home-grown SMTP client based on PHP sockets, no Composer/PHPMailer required).
Configuration in `config.php`:

```php
define('MAIL_TRANSPORT', 'smtp');
define('SMTP_HOST', 'smtp.example.com');
define('SMTP_PORT', 587);                 // 587 (STARTTLS), 465 (implicit SSL), 25 (cleartext)
define('SMTP_ENCRYPTION', 'tls');         // 'tls' | 'ssl' | 'none'
define('SMTP_VERIFY_CERT', true);         // only disable for troubleshooting
define('SMTP_USERNAME', '');
define('SMTP_PASSWORD', '');
define('SMTP_TIMEOUT', 15);
```

If sending the e-mail fails, the file is still stored and its link remains available in
"My files" (the failure is reported to the user and logged).

The default subject and body of the e-mail are **translated into the sender's language**
(keys `mail.subject` / `mail.body` / `mail.password_notice` in `lang/*.php`). They can be
overridden in `config.php` via `MAIL_SUBJECT` and `MAIL_BODY_TEMPLATE` (leave empty to use
the built-in templates). The user can also customize the message on each send ("Custom
message" field).

### OIDC login

Lethe supports login via OpenID Connect (tested with **Keycloak**). It uses the
**Authorization Code Flow with PKCE** (S256), which means no client secret is needed
for public clients.

#### Keycloak setup

1. In your Keycloak realm, create a new **Client** (e.g. `lethe`):
   - **Client type**: `OpenID Connect`
   - **Client ID**: `lethe` (or any name you choose)
   - **Client authentication**: **On** (recommended for CLI clients)
   - **Valid redirect URIs**: `https://lethe.linuxtrickslab.lan/index.php?action=oidc_callback`
   - **Valid post-logout redirect URIs**: `https://lethe.linuxtrickslab.lan/`
   - **Root URL**: `https://lethe.linuxtrickslab.lan/`
   - **Web origins**: `+https://lethe.linuxtrickslab.lan`
   - **Standard flow**: **Enabled**
   - **Direct access grants**: **Off** (recommended)
   - **Client authentication**: **On** (PKCE still required)

2. Ensure the Keycloak realm has the `openid`, `profile` and `email` scopes available.

3. In `config.php`, configure the OIDC block:

```php
// Enable OIDC login (set to '1')
define('OIDC_ENABLED', '1');

// Keycloak realm base URL (the "Issuer" in Keycloak's realm settings)
// Example: 'https://sso.linuxtrickslab.lan/realms/linuxtrickslab'
define('OIDC_AUTH_SERVER', '');

// The client ID registered in Keycloak
define('OIDC_CLIENT_ID', 'lethe');

// Client secret (only needed for confidential clients — leave empty for public clients)
define('OIDC_CLIENT_SECRET', '');

// Redirect URI registered in Keycloak (must match exactly)
// Example: 'https://lethe.linuxtrickslab.lan/index.php?action=oidc_callback'
define('OIDC_REDIRECT_URI', '');

// Scopes to request (openid is always included)
define('OIDC_SCOPES', 'openid profile email');

// JWKS URI (leave empty to auto-discover from the issuer)
define('OIDC_JWKS_URI', '');

// JWKS cache TTL in seconds (keys change rarely)
define('OIDC_JWKS_CACHE_TTL', 3600);

// Allowed clock skew for token expiry (seconds)
define('OIDC_CLOCK_SKEW', 30);
```

#### How it works

- The SPA calls `GET /?action=oidc_init` to get the Keycloak authorization URL (with a
  PKCE `code_challenge` and a random `state` stored in the session).
- The user is redirected to Keycloak, authenticates, and is sent back to the callback URL
  with an authorization `code`.
- The server exchanges the code for an ID token (JWT) at the Keycloak token endpoint,
  validates the signature against the Keycloak JWKS (RSA256), checks issuer, audience,
  and expiry.
- If valid, the user is **auto-created** (if not already present) using the `preferred_username`
  or `email` claim from the ID token, and a session is established.

> **Note**: OIDC and password login can coexist. If OIDC is disabled (`OIDC_ENABLED` = `''`),
> only the username/password login remains.

## Usage

The interface is a single-page application with a sidebar. Each module is shown below
(screenshots in the `doc/` folder).

### Dashboard

![Dashboard](doc/lethe-0-dashboard.png)

Welcome screen with quick access to every tool.

### Send a file

![Send a file](doc/lethe-1-sendfile.png)

Drag & drop or pick a file, choose the expiration (1–30 days), set an optional password, and
optionally e-mail the link to a recipient with a custom message. The upload runs in 1 MB
chunks with a progress bar; when it finishes, you get a shareable download link.

### My files

![My files](doc/lethe-2-myfiles.png)

List of the files you have sent: name, size, expiration, status and download count. Copy
the link, revoke it (disable without deleting) or delete the file entirely.

### My deposits

![My deposits](doc/lethe-3-mydeposits.png)

Create a deposit link (label, optional password, expiration) and share it: anyone holding
the link can upload a file to you without an account. You can enable/disable a deposit, and
download or delete the files received through it.

### Secret message

![Secret message](doc/lethe-4-secretmessage.png)

Write a confidential text (password, API key, …), choose a lifetime (1, 7, 14 or 30 days)
and get a one-time link. The message is encrypted at rest (AES-256-GCM); the recipient sees
a confirmation screen before revealing it, and the content is permanently destroyed after
the first view.

### My secret messages

![My secret messages](doc/lethe-5-mysecrets.png)

Track the secret messages you have sent: active, viewed or expired. Delete them at any time.

### Users (admin)

![Users](doc/lethe-6-users.png)

Admin-only module: create users, delete users and reset passwords.

### Multilingual interface

The UI (menus, public pages, API messages, default e-mails) is available in English and
French and is **automatically detected from the browser's `Accept-Language` header** — no
URL parameter or cookie needed. Translations live in `lang/<code>.php` (English is the
source of truth and the fallback).

![French interface](doc/lethe-fr.png)

## AI-assisted development

This project was developed with the assistance of an AI model, **Qwen3.8 27B, run locally**:

- the **multilingual interface** (the application was initially developed in French only)
  was built with its assistance;
- the **CSS styling** of the application was produced with its assistance, following
  precisely requested instructions.
