<?php

// Validate email addresses before saving.
final class EmailValidator
{
    private const GENERIC = 'Please enter a valid email address.';

    // Check a trimmed, lowercase email address.
    public static function check(string $email): ?string
    {
        if ($email === '') {
            return self::GENERIC;
        }

        if (mb_strlen($email) > 100) {
            return 'Email address must be 100 characters or fewer.';
        }

        if (substr_count($email, '@') !== 1) {
            return self::GENERIC;
        }

        [$local, $domain] = explode('@', $email, 2);

        if ($local === '' || $domain === '' || strlen($local) > 64) {
            return self::GENERIC;
        }

        // Apply Gmail username rules.
        $isGmail = $domain === 'gmail.com' || $domain === 'googlemail.com';

        if ($isGmail) {
            if (!preg_match('/^[a-z0-9.]+$/', $local)) {
                return 'Sorry, only letters (a-z), numbers (0-9), and periods (.) are allowed.';
            }
        } elseif (!preg_match('/^[a-z0-9._+-]+$/', $local)) {
            return 'Email can only use letters, numbers, and . _ + - before the @.';
        }

        if (!preg_match('/^[a-z0-9]/', $local) || !preg_match('/[a-z0-9]$/', $local)) {
            return 'Email must start and end with a letter or number before the @.';
        }

        if (preg_match('/[._+-]{2,}/', $local)) {
            return 'Email cannot have two special characters in a row, like .. or --.';
        }

        if ($isGmail && (strlen($local) < 6 || strlen($local) > 30)) {
            return 'Gmail usernames must be between 6 and 30 characters.';
        }

        if (!preg_match('/^(?:[a-z0-9](?:[a-z0-9-]{0,61}[a-z0-9])?\.)+[a-z]{2,24}$/', $domain)) {
            return 'Please enter a valid email domain, like gmail.com.';
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            return self::GENERIC;
        }

        return null;
    }
}
