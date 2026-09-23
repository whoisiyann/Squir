<?php

require_once __DIR__ . '/../vendor/autoload.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as PHPMailerException;

function squirMailLog(string $line): void
{
    if (!defined('MAIL_DEBUG') || !MAIL_DEBUG) {
        return;
    }

    $dir = __DIR__ . '/logs';
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }

    file_put_contents($dir . '/mail-debug.log', '[' . date('Y-m-d H:i:s') . '] ' . $line . PHP_EOL, FILE_APPEND);
}

// Send a password reset code.
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
        // Gmail SMTP settings
        $mail->isSMTP();
        $mail->Host       = defined('SMTP_HOST') ? SMTP_HOST : 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = defined('SMTP_USERNAME') ? SMTP_USERNAME : '';
        $mail->Password   = defined('SMTP_PASSWORD') ? SMTP_PASSWORD : ''; // Gmail app password
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = defined('SMTP_PORT') ? SMTP_PORT : 587;

        if ($mail->Username === '' || $mail->Password === '') {
            // SMTP credentials are missing.
            throw new PHPMailerException('SMTP_USERNAME / SMTP_PASSWORD ay wala pang value sa .env');
        }

        // Write SMTP details to the debug log.
        if (defined('MAIL_DEBUG') && MAIL_DEBUG) {
            $mail->SMTPDebug   = SMTP::DEBUG_SERVER;
            $mail->Debugoutput = static function (string $str, int $level): void {
                squirMailLog('[SMTP] ' . trim($str));
            };
        }

        $mail->setFrom($fromAddress, $fromName);
        $mail->addAddress($toEmail, $toName);
        $mail->addReplyTo($fromAddress, $fromName);

        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = $body;
        $mail->AltBody = $plainBody;

        $mail->send();
        squirMailLog("SUCCESS sending to {$toEmail} (auth user: {$mail->Username})");
        return true;
    } catch (PHPMailerException $e) {
        // Write mail errors to the PHP log.
        error_log('sendPasswordResetEmail failed: ' . $mail->ErrorInfo);
        squirMailLog("FAILED sending to {$toEmail} (auth user: {$mail->Username}) — ErrorInfo: {$mail->ErrorInfo}");
        return false;
    }
}