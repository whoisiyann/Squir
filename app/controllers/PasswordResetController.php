<?php
require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/PasswordReset.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/admin/Admin.php';
require_once __DIR__ . '/../models/admin/AdminPasswordReset.php';
require_once __DIR__ . '/../../includes/mailer.php';

class PasswordResetController
{
    public function __construct(
        private User $userModel,
        private PasswordReset $resetModel,
        private ActivityLog $activityLog,
        private ?Admin $adminModel = null,
        private ?AdminPasswordReset $adminResetModel = null
    )
    {
    }

    // Find account
    /** @return array{type: string, id: int, name: string}|null */
    private function findAccount(string $email): ?array
    {
        $user = $this->userModel->findByEmail($email);
        if ($user) {
            return ['type' => 'user', 'id' => (int) $user['user_id'], 'name' => (string) $user['full_name']];
        }

        if ($this->adminModel !== null && $this->adminResetModel !== null) {
            $admin = $this->adminModel->findByEmail($email);
            if ($admin) {
                return [
                    'type'   => 'admin',
                    'id'     => (int) $admin['admin_id'],
                    'name'   => (string) $admin['full_name'],
                    'active' => ($admin['status'] ?? 'active') === 'active',
                    'hash'   => (string) $admin['password_hash'],
                ];
            }
        }

        return null;
    }

    // Request reset code
    public function requestCode(array $input): array
    {
        $email = strtolower(trim((string) ($input['email'] ?? '')));
        $errors = [];

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
            return ['errors' => $errors, 'email' => $email, 'dev_code' => null];
        }

        $account = $this->findAccount($email);

        if (!$account) {
            // Hide unknown accounts
            $errors['email'] = 'This email address is not registered.';
            return ['errors' => $errors, 'email' => $email, 'dev_code' => null];
        }

        if ($account['type'] === 'admin' && empty($account['active'])) {
            $errors['email'] = 'This administrator account is not currently active.';
            return ['errors' => $errors, 'email' => $email, 'dev_code' => null];
        }

        $devCode = $this->issueAndSend($account, $email);

        return ['errors' => [], 'email' => $email, 'dev_code' => $devCode];
    }

    // Resend code
    public function resendCode(string $email): ?string
    {
        $account = $this->findAccount($email);
        if (!$account || ($account['type'] === 'admin' && empty($account['active']))) {
            return null;
        }

        return $this->issueAndSend($account, $email);
    }

    // Code check
    public function verifyCode(string $email, string $code): array
    {
        $code = trim($code);

        if (!preg_match('/^\d{' . PasswordReset::CODE_LENGTH . '}$/', $code)) {
            return ['ok' => false, 'error' => 'Enter all ' . PasswordReset::CODE_LENGTH . ' digits.'];
        }

        $account = $this->findAccount($email);
        if (!$account) {
            // Hide unknown accounts
            return ['ok' => false, 'error' => 'That code is incorrect or has expired. Please request a new one.'];
        }

        if ($account['type'] === 'admin') {
            return $this->adminResetModel->verify($account['id'], $code);
        }

        return $this->resetModel->verify($account['id'], $code);
    }

    // Set new password
    public function resetPassword(string $email, array $input): array
    {
        $password = (string) ($input['password'] ?? '');
        $confirmation = (string) ($input['password_confirmation'] ?? '');
        $errors = [];

        $account = $this->findAccount($email);
        $isAdmin = $account !== null && $account['type'] === 'admin';

        if ($password === '') {
            $errors['password'] = 'Password is required.';
        } elseif ($isAdmin) {
            // Admins follow the same rule as Settings > Change Password
            if (!preg_match('/^(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $password)) {
                $errors['password'] = 'Password must be at least 8 characters and include a number and a special character.';
            } elseif (password_verify($password, $account['hash'])) {
                $errors['password'] = 'Your new password must be different from your current password.';
            }
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

        if ($isAdmin) {
            if (empty($account['active']) || !$this->adminResetModel->isVerified($account['id'])) {
                return ['errors' => ['form' => 'Your reset session has expired. Please start again.']];
            }

            $this->adminModel->updatePassword($account['id'], password_hash($password, PASSWORD_DEFAULT));
            $this->adminResetModel->markUsed($account['id']);
            $this->activityLog->logAdmin($account['id'], 'admin_password_reset', 'Reset password from the login page');

            return ['errors' => []];
        }

        if (!$account || !$this->resetModel->isVerified($account['id'])) {
            return ['errors' => ['form' => 'Your reset session has expired. Please start again.']];
        }

        $this->userModel->updatePassword($account['id'], password_hash($password, PASSWORD_DEFAULT));
        $this->resetModel->markUsed($account['id']);
        $this->activityLog->log($account['id'], 'password_changed');

        return ['errors' => []];
    }

    // Issue and send a new code
    private function issueAndSend(array $account, string $email): ?string
    {
        $reset = $account['type'] === 'admin'
            ? $this->adminResetModel->createForAdmin($account['id'])
            : $this->resetModel->createForUser($account['id']);

        $sent = sendPasswordResetEmail($email, $account['name'], $reset['code'], $reset['ttl_minutes']);

        if (!$sent && defined('MAIL_DEV_FALLBACK') && MAIL_DEV_FALLBACK) {
            return $reset['code'];
        }

        return null;
    }
}
