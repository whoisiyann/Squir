<?php

require_once __DIR__ . '/../models/User.php';
require_once __DIR__ . '/../models/UserPin.php';
require_once __DIR__ . '/../models/ActivityLog.php';
require_once __DIR__ . '/../models/Vault.php';

class SettingsController
{
    public function __construct(
        private PDO $db,
        private User $user,
        private UserPin $pinModel,
        private ActivityLog $activityLog
    ) {
    }

    // Update account information
    public function updateProfile(int $userId, array $post): array
    {
        $fullName = trim((string) ($post['full_name'] ?? ''));
        $username = trim((string) ($post['username'] ?? ''));
        $email = strtolower(trim((string) ($post['email'] ?? '')));
        $errors = [];

        if ($fullName === '') {
            $errors['full_name'] = 'Full name is required.';
        } elseif (mb_strlen($fullName) > 100) {
            $errors['full_name'] = 'Full name must be 100 characters or fewer.';
        }

        if ($username === '') {
            $errors['username'] = 'Username is required.';
        } elseif (!preg_match('/^[A-Za-z0-9_.-]{3,50}$/', $username)) {
            $errors['username'] = 'Username must be 3-50 letters, numbers, dots, dashes, or underscores.';
        } elseif ($this->user->usernameExists($username, $userId)) {
            $errors['username'] = 'Username is already taken.';
        }

        if ($email === '' || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors['email'] = 'Please enter a valid email address.';
        } elseif (mb_strlen($email) > 100) {
            $errors['email'] = 'Email address must be 100 characters or fewer.';
        } elseif ($this->user->emailExists($email, $userId)) {
            $errors['email'] = 'An account with this email already exists.';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $currentUser = $this->user->findById($userId);
        $this->user->updateProfile($userId, $fullName, $username, $email);

        if ($currentUser && $currentUser['username'] !== $username) {
            $this->activityLog->log($userId, 'username_changed');
        }
        if ($currentUser && strtolower((string) $currentUser['email']) !== $email) {
            $this->activityLog->log($userId, 'email_changed');
        }
        if ($currentUser && $currentUser['full_name'] !== $fullName) {
            $this->activityLog->log($userId, 'profile_updated');
        }

        return ['errors' => [], 'user' => $this->user->findById($userId)];
    }

    // Change account password
    public function changePassword(int $userId, array $post): array
    {
        $current = (string) ($post['current_password'] ?? '');
        $new = (string) ($post['new_password'] ?? '');
        $confirm = (string) ($post['confirm_password'] ?? '');
        $errors = [];

        $user = $this->user->findById($userId);
        if (!$user || $current === '' || !password_verify($current, $user['password_hash'])) {
            $errors['current_password'] = 'Your current password is incorrect.';
        }

        if (!preg_match('/^(?=.*\d)(?=.*[^A-Za-z0-9]).{8,}$/', $new)) {
            $errors['new_password'] = 'Password must be at least 8 characters and include a number and a special character.';
        } elseif (isset($user['password_hash']) && password_verify($new, $user['password_hash'])) {
            $errors['new_password'] = 'Your new password must be different from your current password.';
        }

        if ($confirm === '' || $confirm !== $new) {
            $errors['confirm_password'] = 'Passwords do not match.';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $this->user->updatePassword($userId, password_hash($new, PASSWORD_DEFAULT));
        $this->activityLog->log($userId, 'password_changed');

        return ['errors' => []];
    }

    // Reset the vault PIN
    public function resetPin(int $userId, array $post): array
    {
        $current = trim((string) ($post['current_pin'] ?? ''));
        $new = trim((string) ($post['new_pin'] ?? ''));
        $confirm = trim((string) ($post['confirm_pin'] ?? ''));
        $length = UserPin::length();
        $errors = [];

        if (!preg_match('/^\d{' . $length . '}$/', $current)) {
            $errors['current_pin'] = 'Enter your current ' . $length . '-digit PIN.';
        }

        if (!preg_match('/^\d{' . $length . '}$/', $new)) {
            $errors['new_pin'] = 'Your new PIN must be exactly ' . $length . ' digits.';
        } elseif ($new === $current) {
            $errors['new_pin'] = 'Your new PIN must be different from your current PIN.';
        }

        if ($confirm === '' || $confirm !== $new) {
            $errors['confirm_pin'] = 'The two PINs do not match.';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        $result = $this->pinModel->change($userId, $current, $new);
        if (!$result['ok']) {
            return ['errors' => ['current_pin' => $result['error'] ?? 'That PIN is incorrect.']];
        }

        $this->activityLog->log($userId, 'pin_updated');

        return ['errors' => []];
    }

    // Load recent activity for the activity log panel
    public function listActivity(int $userId, int $limit = 50): array
    {
        return $this->activityLog->listForUser($userId, $limit);
    }

    // Clear all activity log entries for the user
    public function clearActivityLog(int $userId): bool
    {
        return $this->activityLog->clearForUser($userId);
    }

    // Delete the account and related data
    public function deleteAccount(int $userId): bool
    {
        return $this->user->delete($userId);
    }

    // Build the account export
    public function exportData(int $userId): array
    {
        $vaultModel = new Vault($this->db);
        $vaultOut = [];
        foreach ($vaultModel->searchForUser($userId) as $row) {
            $vaultOut[] = [
                'title'       => $row['title'],
                'username'    => $row['account_username'],
                'password'    => $row['account_password'] !== null && $row['account_password'] !== ''
                    ? $vaultModel->decryptSecret($row['account_password'])
                    : null,
                'website_url' => $row['website_url'],
                'notes'       => $row['notes'],
                'tags'        => $row['tags'],
            ];
        }

        $noteStatement = $this->db->prepare(
            'SELECT title, content, created_at, updated_at FROM notes WHERE user_id = :uid ORDER BY updated_at DESC'
        );
        $noteStatement->execute(['uid' => $userId]);

        $taskStatement = $this->db->prepare(
            'SELECT title, description, status, priority, due_date, created_at FROM tasks WHERE user_id = :uid ORDER BY created_at DESC'
        );
        $taskStatement->execute(['uid' => $userId]);

        $folderStatement = $this->db->prepare(
            'SELECT folder_name, folder_type, color FROM folders WHERE user_id = :uid ORDER BY folder_name ASC'
        );
        $folderStatement->execute(['uid' => $userId]);

        return [
            'exported_at' => date('c'),
            'vault'       => $vaultOut,
            'notes'       => $noteStatement->fetchAll(PDO::FETCH_ASSOC),
            'tasks'       => $taskStatement->fetchAll(PDO::FETCH_ASSOC),
            'folders'     => $folderStatement->fetchAll(PDO::FETCH_ASSOC),
        ];
    }

    // Check PDF support
    public function pdfAvailable(): bool
    {
        $autoload = __DIR__ . '/../../vendor/autoload.php';
        if (is_readable($autoload)) {
            require_once $autoload;
        }

        return class_exists(\Dompdf\Dompdf::class);
    }

    // Render the export PDF
    public function exportPdf(int $userId): string
    {
        $data = $this->exportData($userId);
        $user = $this->user->findById($userId) ?: [];

        $options = new \Dompdf\Options();
        $options->set('defaultFont', 'DejaVu Sans');
        $options->set('isRemoteEnabled', false);
        $options->set('isPhpEnabled', false);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($this->renderExportHtml($data, $user), 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        // Add page numbers
        $canvas = $dompdf->getCanvas();
        $font = $dompdf->getFontMetrics()->getFont('DejaVu Sans', 'normal');
        $footerY = $canvas->get_height() - 30;
        $canvas->page_text(40, $footerY, 'Squir - Confidential data export', $font, 8, [0.45, 0.45, 0.45]);
        $canvas->page_text($canvas->get_width() - 110, $footerY, 'Page {PAGE_NUM} of {PAGE_COUNT}', $font, 8, [0.45, 0.45, 0.45]);

        $pdf = $dompdf->output();

        $this->activityLog->log($userId, 'data_exported');

        return $pdf;
    }

    // Render the export view
    private function renderExportHtml(array $data, array $user): string
    {
        ob_start();
        require __DIR__ . '/../views/settings/export-pdf.php';

        return (string) ob_get_clean();
    }
}