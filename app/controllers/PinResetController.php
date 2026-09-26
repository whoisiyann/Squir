<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../models/PinReset.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../../includes/mailer.php';

class PinResetController
{
    public function __construct(
        private User $userModel,
        private PinReset $resetModel,
        private ActivityLog $activityLog,
        private UserPin $pinModel
    ) {
    }

    // Handle "forgot PIN" code requests for the logged-in user
    public function requestCode(int $userId, array $input): array
    {
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
            return ['errors' => $errors, 'dev_code' => null];
        }

        $user = $this->userModel->findById($userId);

        if (!$user || strtolower((string) $user['email']) !== $email) {
            // Don't confirm/deny which part was wrong — just that it doesn't match this account
            $errors['email'] = 'That email does not match the one on your account.';
            return ['errors' => $errors, 'dev_code' => null];
        }

        $devCode = $this->issueAndSend($userId, $user['email'], $user['full_name']);

        return ['errors' => [], 'dev_code' => $devCode];
    }

    // Resend the reset code to the account's own email
    public function resendCode(int $userId): ?string
    {
        $user = $this->userModel->findById($userId);
        if (!$user) {
            return null;
        }

        return $this->issueAndSend($userId, $user['email'], $user['full_name']);
    }

    // Verify the OTP code
    public function verifyCode(int $userId, string $code): array
    {
        $code = trim($code);

        if (!preg_match('/^\d{' . PinReset::CODE_LENGTH . '}$/', $code)) {
            return ['ok' => false, 'error' => 'Enter all ' . PinReset::CODE_LENGTH . ' digits.'];
        }

        return $this->resetModel->verify($userId, $code);
    }

    // Apply the new PIN once the code has been verified
    public function applyNewPin(int $userId, array $input): array
    {
        $new = trim((string) ($input['new_pin'] ?? ''));
        $confirm = trim((string) ($input['confirm_pin'] ?? ''));
        $length = UserPin::length();
        $errors = [];

        if (!preg_match('/^\d{' . $length . '}$/', $new)) {
            $errors['new_pin'] = 'Your new PIN must be exactly ' . $length . ' digits.';
        }

        if ($confirm === '' || $confirm !== $new) {
            $errors['confirm_pin'] = 'The two PINs do not match.';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        if (!$this->resetModel->isVerified($userId)) {
            return ['errors' => ['form' => 'Your reset session has expired. Please start again.']];
        }

        $this->pinModel->create($userId, $new);
        $this->resetModel->markUsed($userId);
        $this->activityLog->log($userId, 'pin_updated');

        return ['errors' => []];
    }

    // Issue and send a new code
    private function issueAndSend(int $userId, string $email, string $fullName): ?string
    {
        $reset = $this->resetModel->createForUser($userId);
        $sent = sendPinResetEmail($email, $fullName, $reset['code'], $reset['ttl_minutes']);

        if (!$sent && defined('MAIL_DEV_FALLBACK') && MAIL_DEV_FALLBACK) {
            return $reset['code'];
        }

        return null;
    }
}
