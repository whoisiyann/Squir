<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

/**
 * Email a password reset code to a user via Gmail SMTP (PHPMailer).
 *
 * Kailangan naka-set sa .env ang SMTP_USERNAME (Gmail address) at
 * SMTP_PASSWORD (Gmail App Password — 16 chars, hindi yung regular
 * password). Tignan ang config/config.php para sa full setup notes.
 *
 * @return bool true if the mail transport reported success
 */
function sendPasswordResetEmail(string $toEmail, string $toName, string $code, int $ttlMinutes): bool
{
    $fromAddress = defined('MAIL_FROM_ADDRESS') ? MAIL_FROM_ADDRESS : 'no-reply@squir.local';
    $fromName = defined('MAIL_FROM_NAME') ? MAIL_FROM_NAME : 'Squir';

    $subject = 'Your Squir password reset code';
    $safeName = htmlspecialchars($toName, ENT_QUOTES, 'UTF-8');

    $body = '<p>Hi ' . $safeName . ',</p>'
        . '<p>Use the code below to reset your Squir password:</p>'
        . '<p style="font-size:28px;font-weight:700;letter-spacing:6px;">' . htmlspecialchars($code, ENT_QUOTES, 'UTF-8') . '</p>'
        . '<p>This code expires in ' . $ttlMinutes . ' minutes. If you did not request this, you can safely ignore this email.</p>';

    $plainBody = "Hi {$toName},\n\nUse the code below to reset your Squir password:\n\n{$code}\n\nThis code expires in {$ttlMinutes} minutes. If you did not request this, you can safely ignore this email.";

    $mail = new PHPMailer(true);

    try {
        // --- Gmail SMTP settings ---
        $mail->isSMTP();
        $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $mail->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : ''; // Gmail App Password, hindi regular password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;

        if ($mail->Username === '' || $mail->Password === '') {
            // Walang na-configure na Gmail credentials sa .env pa.
            throw new PHPMailerException('SMTP_USERNAME / SMTP_PASSWORD ay wala pang value sa .env');
        }

        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo($fromAddress, $fromName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $plainBody;

        $mail->send();
        return true;
    } catch (PHPMailerException $e) {
        // Sa production, mas maganda kung nasa proper log file ito imbes na error_log lang.
        error_log('sendPasswordResetEmail failed: ' . $mail->ErrorInfo);
        return false;
    }
}



