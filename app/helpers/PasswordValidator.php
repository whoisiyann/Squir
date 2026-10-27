<?php

// Password rules for account creation and password changes.
final class PasswordValidator
{
    private const MIN_LENGTH = 8;

    // Common passwords, checked with simple substitutions and suffixes.
    private const COMMON = [
        'password', 'passw0rd', 'admin', 'administrator', 'qwerty', 'qwertyuiop', 'letmein', 'welcome',
        'iloveyou', 'login', 'abc123', 'dragon', 'monkey', 'master', 'sunshine', 'princess', 'football',
        'baseball', 'superman', 'batman', 'pilipinas', 'philippines', 'mahalkita', 'squir', 'asdfghjkl',
        'zxcvbnm', 'changeme', 'secret', 'default', 'user', 'guest', 'root', 'test', 'testing',
    ];

    // Keyboard rows used to detect common key sequences.
    private const KEYBOARD_ROWS = ['qwertyuiop', 'asdfghjkl', 'zxcvbnm', '1234567890'];

    // Return an error message, or null when the password is acceptable.
    // $personal may contain the account owner's name, username, and email.
    public static function check(string $password, array $personal = []): ?string
    {
        if (mb_strlen($password) < self::MIN_LENGTH) {
            return 'Password must be at least ' . self::MIN_LENGTH . ' characters.';
        }

        $lower = mb_strtolower($password);

        if (self::isRepeated($lower)) {
            return 'Password cannot repeat the same character or pattern, like aaaaaaaa or --------.';
        }

        if (self::hasSequence($lower)) {
            return 'Password cannot contain a sequence like 1234, abcd, or qwerty.';
        }

        if (self::containsPersonal($lower, $personal)) {
            return 'Password cannot contain your name, username, or email.';
        }

        if (self::isCommon($lower)) {
            return 'That password is too common. Please choose a less predictable one.';
        }

        return null;
    }

    // Reject four repeated characters or a repeated pattern.
    private static function isRepeated(string $lower): bool
    {
        return preg_match('/(.)\1{3,}/su', $lower) === 1
            || preg_match('/^(.{1,4})\1+$/su', $lower) === 1;
    }

    // Reject ascending or descending runs and keyboard sequences.
    private static function hasSequence(string $lower): bool
    {
        $chars = mb_str_split($lower);
        $ascending = 1;
        $descending = 1;

        for ($i = 1, $count = count($chars); $i < $count; $i++) {
            $previous = $chars[$i - 1];
            $current = $chars[$i];
            $bothAlnum = preg_match('/^[a-z0-9]$/', $previous) === 1 && preg_match('/^[a-z0-9]$/', $current) === 1;
            $difference = $bothAlnum ? mb_ord($current) - mb_ord($previous) : 0;

            $ascending = $difference === 1 ? $ascending + 1 : 1;
            $descending = $difference === -1 ? $descending + 1 : 1;

            if ($ascending >= 4 || $descending >= 4) {
                return true;
            }
        }

        foreach (self::KEYBOARD_ROWS as $row) {
            foreach ([$row, strrev($row)] as $line) {
                for ($start = 0, $last = strlen($line) - 5; $start <= $last; $start++) {
                    if (str_contains($lower, substr($line, $start, 5))) {
                        return true;
                    }
                }
            }
        }

        return false;
    }

    // Check whether the password contains part of the user's identity.
    private static function containsPersonal(string $lower, array $personal): bool
    {
        $terms = [];

        $fullName = mb_strtolower(trim((string) ($personal['full_name'] ?? '')));
        if ($fullName !== '') {
            $compact = preg_replace('/[^\p{L}\p{N}]/u', '', $fullName);
            if (mb_strlen($compact) >= 4) {
                $terms[] = $compact;
            }
            foreach (preg_split('/[^\p{L}\p{N}]+/u', $fullName, -1, PREG_SPLIT_NO_EMPTY) as $word) {
                if (mb_strlen($word) >= 4) {
                    $terms[] = $word;
                }
            }
        }

        $username = mb_strtolower(trim((string) ($personal['username'] ?? '')));
        if (mb_strlen($username) >= 3) {
            $terms[] = $username;
        }

        $email = mb_strtolower(trim((string) ($personal['email'] ?? '')));
        if ($email !== '') {
            $local = explode('@', $email)[0];
            if (mb_strlen($local) >= 3) {
                $terms[] = $local;
            }
            $localCompact = preg_replace('/[^\p{L}\p{N}]/u', '', $local);
            if (mb_strlen($localCompact) >= 3) {
                $terms[] = $localCompact;
            }
        }

        // Compare again without separators such as dots, dashes, or spaces.
        $compactPassword = preg_replace('/[^\p{L}\p{N}]/u', '', $lower);

        foreach (array_unique($terms) as $term) {
            if (str_contains($lower, $term) || str_contains($compactPassword, $term)) {
                return true;
            }
        }

        return false;
    }

    // Reject common passwords after substitutions and trailing suffixes.
    private static function isCommon(string $lower): bool
    {
        $swapped = strtr($lower, ['@' => 'a', '4' => 'a', '0' => 'o', '$' => 's', '5' => 's', '3' => 'e']);

        $candidates = [
            $lower,
            $swapped,
            rtrim($lower, '0123456789!@#$%^&*()_+-=.,?'),
            rtrim($swapped, '0123456789!@#$%^&*()_+-=.,?'),
        ];

        foreach ($candidates as $candidate) {
            if (in_array($candidate, self::COMMON, true)) {
                return true;
            }
        }

        return str_contains($swapped, 'password') || str_contains($lower, 'qwerty');
    }
}
