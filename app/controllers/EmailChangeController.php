<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/EmailChange.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../../includes/mailer.php';

class EmailChangeController
{
    public function __construct(
        private User $userModel,
        private EmailChange $emailChangeModel,
        private ActivityLog $activityLog
    ) {
    }

    // Step 1-4: validate the new email, check it isn't already registered, send the code
    public function requestChange(int $userId, array $input): array
    {
        $newEmail = strtolower(trim((string) ($input['new_email'] ?? '')));
        $errors = [];

        if ($newEmail === '' || !filter_var($newEmail, FILTER_VALIDATE_EMAIL)) {
            $errors['new_email'] = 'Please enter a valid email address.';
        } elseif (mb_strlen($newEmail) > 100) {
            $errors['new_email'] = 'Email address must be 100 characters or fewer.';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $currentUser = $this->userModel->findById($userId);
        if ($currentUser && strtolower((string) $currentUser['email']) === $newEmail) {
            return ['errors' => ['new_email' => 'That is already your current email address.']];
        }

        // Step 2-3: the new email must not already exist in the database
        if ($this->userModel->emailExists($newEmail, $userId)) {
            return ['errors' => ['new_email' => 'An account with this email already exists.']];
        }

        $fullName = $currentUser['full_name'] ?? '';
        $devCode = $this->issueAndSend($userId, $newEmail, $fullName);

        return ['errors' => [], 'new_email' => $newEmail, 'dev_code' => $devCode];
    }

    // Resend the verification code for the pending request
    public function resendCode(int $userId): array
    {
        $pending = $this->emailChangeModel->findPendingForUser($userId);
        if (!$pending || $pending['used_at'] !== null) {
            return ['errors' => ['form' => 'There is no pending email change. Please start again.']];
        }

        $currentUser = $this->userModel->findById($userId);
        $fullName = $currentUser['full_name'] ?? '';
        $devCode = $this->issueAndSend($userId, $pending['new_email'], $fullName);

        return ['errors' => [], 'new_email' => $pending['new_email'], 'dev_code' => $devCode];
    }

    // Step 5-9: verify the code, then update the user's login email
    public function verifyAndApply(int $userId, string $code): array
    {
        $code = trim($code);

        if (!preg_match('/^\d{' . EmailChange::CODE_LENGTH . '}$/', $code)) {
            return ['errors' => ['code' => 'Enter all ' . EmailChange::CODE_LENGTH . ' digits.']];
        }

        $result = $this->emailChangeModel->verify($userId, $code);
        if (!$result['ok']) {
            return ['errors' => ['code' => $result['error']]];
        }

        $newEmail = $result['new_email'];

        // Guard against a race where the email got taken while the code was pending
        if ($this->userModel->emailExists($newEmail, $userId)) {
            $this->emailChangeModel->clearForUser($userId);
            return ['errors' => ['code' => 'An account with this email already exists.']];
        }

        $this->userModel->updateEmail($userId, $newEmail);
        $this->emailChangeModel->markUsed($userId);
        $this->activityLog->log($userId, 'email_changed');

        return ['errors' => [], 'email' => $newEmail];
    }

    // Issue and send a new code, returning the dev fallback code when mail isn't configured
    private function issueAndSend(int $userId, string $newEmail, string $fullName): ?string
    {
        $change = $this->emailChangeModel->createForUser($userId, $newEmail);
        $sent = sendEmailChangeVerificationEmail($newEmail, $fullName, $change['code'], $change['ttl_minutes']);

        if (!$sent && defined('MAIL_DEV_FALLBACK') && MAIL_DEV_FALLBACK) {
            return $change['code'];
        }

        return null;
    }
}
