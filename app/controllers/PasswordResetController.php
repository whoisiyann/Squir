<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PasswordReset.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../../includes/mailer.php';

class PasswordResetController
{
    public function __construct(
        private User $userModel,
        private PasswordReset $resetModel,
        private ActivityLog $activityLog
    )
    {
    }

    // Handle password reset requests
    public function requestCode(array $input): array
    {
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
            return ['errors' => $errors, 'email' => $email, 'dev_code' => null];
        }

        $user = $this->userModel->findByEmail($email);

        if (!$user) {
            // Hide unknown accounts
            $errors['email'] = 'This email address is not registered.';
            return ['errors' => $errors, 'email' => $email, 'dev_code' => null];
        }

        $devCode = $this->issueAndSend((int) $user['user_id'], $email, $user['full_name']);

        return ['errors' => [], 'email' => $email, 'dev_code' => $devCode];
    }

    // Resend the reset code
    public function resendCode(string $email): ?string
    {
        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            return null;
        }

        return $this->issueAndSend((int) $user['user_id'], $email, $user['full_name']);
    }

    // Verify the reset code
    public function verifyCode(string $email, string $code): array
    {
        $code = trim($code);

        if (!preg_match('/^\d{' . PasswordReset::CODE_LENGTH . '}$/', $code)) {
            return ['ok' => false, 'error' => 'Enter all ' . PasswordReset::CODE_LENGTH . ' digits.'];
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user) {
            // Hide unknown accounts
            return ['ok' => false, 'error' => 'That code is incorrect or has expired. Please request a new one.'];
        }

        return $this->resetModel->verify((int) $user['user_id'], $code);
    }

    // Apply the new password
    public function resetPassword(string $email, array $input): array
    {
        $password = (string) ($input['password'] ?? '');
        $confirmation = (string) ($input['password_confirmation'] ?? '');
        $errors = [];

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif (strlen($password) < 8) {
            $errors['password'] = 'Password must be at least 8 characters.';
        }

        if ($confirmation === '') {
            $errors['password_confirmation'] = 'Please confirm your password.';
        } elseif ($password !== $confirmation) {
            $errors['password_confirmation'] = 'Passwords do not match.';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $user = $this->userModel->findByEmail($email);
        if (!$user || !$this->resetModel->isVerified((int) $user['user_id'])) {
            return ['errors' => ['form' => 'Your reset session has expired. Please start again.']];
        }

        $this->userModel->updatePassword((int) $user['user_id'], password_hash($password, PASSWORD_DEFAULT));
        $this->resetModel->markUsed((int) $user['user_id']);
        $this->activityLog->log((int) $user['user_id'], 'password_changed');

        return ['errors' => []];
    }

    // Issue and send a new code
    private function issueAndSend(int $userId, string $email, string $fullName): ?string
    {
        $reset = $this->resetModel->createForUser($userId);
        $sent = sendPasswordResetEmail($email, $fullName, $reset['code'], $reset['ttl_minutes']);

        if (!$sent && defined('MAIL_DEV_FALLBACK') && MAIL_DEV_FALLBACK) {
            return $reset['code'];
        }

        return null;
    }
}