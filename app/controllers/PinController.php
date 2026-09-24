<?php
require_once __DIR__ . '/../models/UserPin.php';

class PinController
{
    private UserPin $pinModel;

    public function __construct(UserPin $pinModel)
    {
        $this->pinModel = $pinModel;
    }

    // Check whether a user has a PIN
    public function hasPin(int $userId): bool
    {
        return $this->pinModel->exists($userId);
    }

    // Create a user PIN
    // Create a user PIN
    public function store(int $userId, array $post): array
    {
        $pin = trim((string) ($post['pin'] ?? ''));
        $confirm = trim((string) ($post['pin_confirmation'] ?? ''));
        $length = UserPin::length();
        $errors = [];

        if (!preg_match('/^\d{' . $length . '}$/', $pin)) {
            $errors['pin'] = 'Your PIN must be exactly ' . $length . ' digits.';
        } elseif ($confirm !== '' && $pin !== $confirm) {
            $errors['pin'] = 'The two PINs do not match. Start again.';
        } elseif ($this->isWeak($pin)) {
            $errors['pin'] = 'Pick a less predictable PIN — avoid repeats like 111111 or runs like 123456.';
        }

        if ($errors !== []) {
            return ['errors' => $errors];
        }

        if ($this->pinModel->exists($userId)) {
            return ['errors' => ['pin' => 'A PIN already exists for this account.']];
        }

        if (!$this->pinModel->create($userId, $pin)) {
            return ['errors' => ['pin' => 'We could not save your PIN. Please try again.']];
        }

        return ['errors' => []];
    }

    // Verify a user PIN
    public function verify(int $userId, string $pin): array
    {
        $length = UserPin::length();

        if (!preg_match('/^\d{' . $length . '}$/', $pin)) {
            return ['ok' => false, 'error' => 'Enter your ' . $length . '-digit PIN.'];
        }

        return $this->pinModel->verify($userId, $pin);
    }

    // Reject predictable PINs
    private function isWeak(string $pin): bool
    {
        if (preg_match('/^(\d)\1+$/', $pin)) {
            return true;
        }

        $ascending = true;
        $descending = true;
        for ($i = 1, $len = strlen($pin); $i < $len; $i++) {
            $diff = (int) $pin[$i] - (int) $pin[$i - 1];
            if ($diff !== 1) {
                $ascending = false;
            }
            if ($diff !== -1) {
                $descending = false;
            }
        }

        return $ascending || $descending;
    }
}