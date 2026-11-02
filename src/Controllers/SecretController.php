<?php
declare(strict_types=1);

namespace Lethe\Controllers;

use Lethe\Auth;
use Lethe\Database;
use Lethe\Helpers;
use Lethe\Language;
use Lethe\Mailer;
use Lethe\Request;
use Lethe\Response;
use Lethe\SecretMessage;
use Lethe\Security;

final class SecretController
{
    /**
     * Lists the current user's secret messages (metadata only).
     */
    public function getMySecrets(): void
    {
        Auth::requireLogin();

        $stmt = Database::connect()->prepare('SELECT * FROM secrets WHERE owner_user_id = ? ORDER BY created_at DESC');
        $stmt->execute([Auth::id()]);

        $secrets = array_map(function (array $s) {
            $consumed = (int)$s['consumed'] === 1;
            $expired = Helpers::isExpired($s['expires_at']);
            return [
                'id' => (int)$s['id'],
                'created_at' => $s['created_at'],
                'expires_at' => $s['expires_at'],
                'consumed_at' => $s['consumed_at'],
                'status' => $consumed ? 'consumed' : ($expired ? 'expired' : 'active'),
            ];
        }, $stmt->fetchAll());

        Response::json(['status' => 'ok', 'secrets' => $secrets]);
    }

    /**
     * Creates a secret message and (optionally) e-mails the share link.
     * JSON body: message, expiration_days, delivery_method, recipient_email,
     * custom_message.
     */
    public function createSecret(): void
    {
        Auth::requireLogin();
        Security::verifyCsrf();

        $message = trim((string)Request::field('message', ''));
        $expirationDays = Request::intField('expiration_days', SECRET_DEFAULT_EXPIRATION_DAYS);
        $deliveryMethod = (string)Request::field('delivery_method', 'link');
        $recipientEmail = trim((string)Request::field('recipient_email', ''));
        $customMessage = (string)Request::field('custom_message', '');

        if ($message === '') {
            Response::json(['status' => 'error', 'message' => Language::t('api.message_empty')], 400);
        }
        $messageLength = function_exists('mb_strlen') ? mb_strlen($message) : strlen($message);
        if ($messageLength > SECRET_MAX_LENGTH) {
            Response::json(['status' => 'error', 'message' => Language::t('api.message_too_long', ['count' => SECRET_MAX_LENGTH])], 400);
        }
        if (!in_array($expirationDays, SECRET_EXPIRATION_OPTIONS, true)) {
            Response::json(['status' => 'error', 'message' => Language::t('api.invalid_validity')], 400);
        }
        if ($deliveryMethod === 'email' && !filter_var($recipientEmail, FILTER_VALIDATE_EMAIL)) {
            Response::json(['status' => 'error', 'message' => Language::t('api.invalid_email')], 400);
        }

        $code = SecretMessage::create(Auth::id(), $message, $expirationDays, $deliveryMethod === 'email' ? $recipientEmail : null);
        $link = Helpers::appUrl() . '/?secret=' . $code;

        if ($deliveryMethod === 'email') {
            $secret = SecretMessage::find($code);
            $expiresAtHuman = $secret ? Helpers::formatDate($secret['expires_at']) : '';
            if (!Mailer::sendSecretLink($recipientEmail, $link, $expiresAtHuman, $customMessage)) {
                Response::json(['status' => 'error', 'message' => Language::t('api.secret_email_failed')], 502);
                return;
            }
            Response::json(['status' => 'ok', 'sent_by_email' => true]);
            return;
        }

        Response::json(['status' => 'ok', 'message' => Language::t('api.secret_created'), 'link' => $link]);
    }

    public function deleteSecret(): void
    {
        Auth::requireLogin();
        Security::verifyCsrf();

        $secretId = Request::intField('id');
        $stmt = Database::connect()->prepare('DELETE FROM secrets WHERE id = ? AND owner_user_id = ?');
        $stmt->execute([$secretId, Auth::id()]);

        if ($stmt->rowCount() === 0) {
            Response::json(['status' => 'error', 'message' => Language::t('api.secret_not_found')], 404);
        }
        Response::json(['status' => 'ok', 'message' => Language::t('api.secret_deleted')]);
    }
}
