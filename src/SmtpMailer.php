<?php
declare(strict_types=1);

namespace Lethe;

/**
 * Minimal SMTP client based on PHP sockets (no external dependency such as
 * PHPMailer). Supports: cleartext connection, STARTTLS, implicit SSL/TLS,
 * LOGIN authentication, and enabling/disabling server certificate verification.
 */
final class SmtpMailer
{
    /** @var array<string, mixed> */
    private array $config;
    /** @var resource|null */
    private $socket = null;
    private string $lastError = '';

    /**
     * @param array<string, mixed> $smtpConfig
     */
    public function __construct(array $smtpConfig)
    {
        $this->config = $smtpConfig;
    }

    /**
     * Sends a plain-text e-mail. Returns true on success.
     * On failure, call getLastError() for details.
     */
    public function send(string $fromAddress, string $fromName, string $to, string $subject, string $body): bool
    {
        try {
            $this->connect();
            $this->handshake();
            $this->authenticateIfNeeded();
            $this->sendEnvelope($fromAddress, $to);
            $this->sendData($fromAddress, $fromName, $to, $subject, $body);
            $this->quit();
            return true;
        } catch (Throwable $e) {
            $this->lastError = $e->getMessage();
            if ($this->socket) {
                @fclose($this->socket);
            }
            return false;
        }
    }

    public function getLastError(): string
    {
        return $this->lastError;
    }

    private function connect(): void
    {
        $host = $this->config['host'];
        $port = (int)$this->config['port'];
        $encryption = $this->config['encryption'] ?? 'tls';
        $verifyCert = (bool)($this->config['verify_cert'] ?? true);
        $timeout = (int)($this->config['timeout'] ?? 15);

        $scheme = ($encryption === 'ssl') ? 'ssl://' : '';

        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => $verifyCert,
                'verify_peer_name' => $verifyCert,
                'allow_self_signed' => !$verifyCert,
            ],
        ]);

        $this->socket = @stream_socket_client(
            $scheme . $host . ':' . $port,
            $errno,
            $errstr,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );

        if (!$this->socket) {
            throw new \RuntimeException("Connexion SMTP impossible à $host:$port ($errstr)");
        }

        stream_set_timeout($this->socket, $timeout);
        $this->readResponse(220);
    }

    private function handshake(): void
    {
        $localName = $_SERVER['SERVER_NAME'] ?? 'localhost';

        $this->write('EHLO ' . $localName);
        $response = $this->readResponse(250);

        $encryption = $this->config['encryption'] ?? 'tls';
        if ($encryption === 'tls') {
            if (stripos($response, 'STARTTLS') === false) {
                throw new \RuntimeException("Le serveur SMTP n'annonce pas STARTTLS.");
            }

            $this->write('STARTTLS');
            $this->readResponse(220);

            $verifyCert = (bool)($this->config['verify_cert'] ?? true);
            stream_context_set_option($this->socket, 'ssl', 'verify_peer', $verifyCert);
            stream_context_set_option($this->socket, 'ssl', 'verify_peer_name', $verifyCert);
            stream_context_set_option($this->socket, 'ssl', 'allow_self_signed', !$verifyCert);

            if (!@stream_socket_enable_crypto($this->socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT)) {
                throw new \RuntimeException("Impossible d'établir la connexion TLS (STARTTLS) avec le serveur SMTP.");
            }

            // A second EHLO is required after STARTTLS.
            $this->write('EHLO ' . $localName);
            $this->readResponse(250);
        }
    }

    private function authenticateIfNeeded(): void
    {
        $username = $this->config['username'] ?? '';
        $password = $this->config['password'] ?? '';

        if ($username === '' || $password === '') {
            return;
        }

        $this->write('AUTH LOGIN');
        $this->readResponse(334);

        $this->write(base64_encode($username));
        $this->readResponse(334);

        $this->write(base64_encode($password));
        $this->readResponse(235);
    }

    private function sendEnvelope(string $fromAddress, string $to): void
    {
        $this->write('MAIL FROM:<' . $fromAddress . '>');
        $this->readResponse(250);

        $this->write('RCPT TO:<' . $to . '>');
        $this->readResponse(250, [251]);
    }

    private function sendData(string $fromAddress, string $fromName, string $to, string $subject, string $body): void
    {
        $this->write('DATA');
        $this->readResponse(354);

        $headers = [];
        $headers[] = 'From: ' . $fromName . ' <' . $fromAddress . '>';
        $headers[] = 'To: <' . $to . '>';
        $headers[] = 'Subject: =?UTF-8?B?' . base64_encode($subject) . '?=';
        $headers[] = 'MIME-Version: 1.0';
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'Date: ' . date('r');

        // Escape lines starting with a dot, required by the SMTP protocol.
        $escapedBody = preg_replace('/^\./m', '..', $body);

        $message = implode("\r\n", $headers) . "\r\n\r\n" . $escapedBody . "\r\n.";
        $this->write($message);
        $this->readResponse(250);
    }

    private function quit(): void
    {
        $this->write('QUIT');
        @fclose($this->socket);
    }

    private function write(string $line): void
    {
        fwrite($this->socket, $line . "\r\n");
    }

    /**
     * Reads an SMTP response and checks it matches the expected code(s).
     */
    private function readResponse(int $expectedCode, array $alsoAccepted = []): string
    {
        $response = '';
        while (($line = fgets($this->socket, 512)) !== false) {
            $response .= $line;
            // The final line of a multi-line response has a space after the code (not a dash).
            if (preg_match('/^\d{3} /', $line)) {
                break;
            }
        }

        $code = (int)substr($response, 0, 3);
        $accepted = array_merge([$expectedCode], $alsoAccepted);

        if (!in_array($code, $accepted, true)) {
            throw new \RuntimeException("Réponse SMTP inattendue (code $code, attendu $expectedCode) : " . trim($response));
        }

        return $response;
    }
}
