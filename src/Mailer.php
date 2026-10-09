<?php
declare(strict_types=1);

namespace Lethe;

/**
 * Sends the share-link e-mail, either through the native mail() function
 * (local MTA) or through the built-in SMTP client (no external library).
 */
final class Mailer
{
    public static function sendShareLink(string $to, string $link, string $expiresAtHuman, bool $hasPassword, ?string $customMessage = null): bool
    {
        $senderName = Auth::username() ?? MAIL_FROM_NAME;

        // The subject and the default body are translated in the sender's
        // language (see lang/*.php); config.php can still override them.
        $subject = MAIL_SUBJECT !== '' ? MAIL_SUBJECT : Language::t('mail.subject');

        $passwordNotice = $hasPassword
            ? Language::t('mail.password_notice')
            : '';

        $body = ($customMessage !== null && trim($customMessage) !== '')
            ? $customMessage
            : (MAIL_BODY_TEMPLATE !== '' ? MAIL_BODY_TEMPLATE : Language::t('mail.body'));

        $body = str_replace(
            ['{LINK}', '{EXPIRATION}', '{PASSWORD_NOTICE}', '{SENDER}'],
            [$link, $expiresAtHuman, $passwordNotice, $senderName],
            $body
        );

        if (MAIL_TRANSPORT === 'smtp') {
            return self::sendViaSmtp($to, $subject, $body);
        }

        return self::sendViaNativeMail($to, $subject, $body);
    }

    /**
     * Send a deposit notification to the deposit owner when a file is uploaded.
     * Uses the deposit notification subject/body from config or the translated
     * default (mail.deposit.subject / mail.deposit.body).
     */
    public static function sendDepositNotification(string $to, string $depositLabel, string $uploaderName, string $depositLink): bool
    {
        $subject = MAIL_DEPOSIT_SUBJECT !== '' ? MAIL_DEPOSIT_SUBJECT : Language::t('mail.deposit.subject');

        $body = MAIL_DEPOSIT_BODY_TEMPLATE !== ''
            ? MAIL_DEPOSIT_BODY_TEMPLATE
            : Language::t('mail.deposit.body');

        $body = str_replace(
            ['{DEPOSIT_LABEL}', '{UPLOADER_NAME}', '{DEPOSIT_LINK}'],
            [$depositLabel, $uploaderName ?: Language::t('mail.deposit.anonymous'), $depositLink],
            $body
        );

        if (MAIL_TRANSPORT === 'smtp') {
            return self::sendViaSmtp($to, $subject, $body);
        }

        return self::sendViaNativeMail($to, $subject, $body);
    }

    private static function sendViaNativeMail(string $to, string $subject, string $body): bool
    {
        $headers = [];
        $headers[] = 'From: ' . MAIL_FROM_NAME . ' <' . MAIL_FROM_ADDRESS . '>';
        $headers[] = 'Reply-To: ' . MAIL_FROM_ADDRESS;
        $headers[] = 'Content-Type: text/plain; charset=UTF-8';
        $headers[] = 'MIME-Version: 1.0';

        return mail($to, '=?UTF-8?B?' . base64_encode($subject) . '?=', $body, implode("\r\n", $headers));
    }

    private static function sendViaSmtp(string $to, string $subject, string $body): bool
    {
        $smtp = new SmtpMailer([
            'host' => SMTP_HOST,
            'port' => SMTP_PORT,
            'encryption' => SMTP_ENCRYPTION,
            'verify_cert' => SMTP_VERIFY_CERT,
            'username' => SMTP_USERNAME,
            'password' => SMTP_PASSWORD,
            'timeout' => SMTP_TIMEOUT,
        ]);
        $success = $smtp->send(MAIL_FROM_ADDRESS, MAIL_FROM_NAME, $to, $subject, $body);

        if (!$success) {
            error_log('[SmtpMailer] Échec envoi vers ' . $to . ' : ' . $smtp->getLastError());
        }

        return $success;
    }
}
