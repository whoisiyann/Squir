<?php

final class NameValidator
{
    // Validate a full name.
    public static function check(string $name): ?string
    {
        if ($name === '') {
            return 'Full name is required.';
        }

        if (mb_strlen($name) > 100) {
            return 'Full name must be 100 characters or fewer.';
        }

        if (!preg_match('/^[\p{L}\p{M}][\p{L}\p{M}\s.\'’-]*$/u', $name)) {
            return 'Full name can only contain letters, spaces, and . \' - (no numbers or symbols).';
        }

        $words = preg_split('/\s+/u', $name, -1, PREG_SPLIT_NO_EMPTY);

        if (count($words) < 2) {
            return 'Please enter your full name (first and last name), like "Juan Dela Cruz".';
        }

        foreach ([$words[0], $words[count($words) - 1]] as $word) {
            $letters = preg_replace('/[^\p{L}]/u', '', $word);
            if (mb_strlen($letters) < 2) {
                return 'Please enter your full name (first and last name), like "Juan Dela Cruz".';
            }
        }

        foreach ($words as $word) {
            if (!preg_match('/\p{L}/u', $word)) {
                return 'Please enter a valid full name.';
            }
        }

        // Allow common initials and short forms without vowels.
        $allowedNoVowel = ['ng', 'mj', 'jm', 'cj', 'rj', 'aj', 'jr', 'sr'];
        foreach ($words as $word) {
            $letters = mb_strtolower(preg_replace('/[^\p{L}]/u', '', $word));
            if (mb_strlen($letters) >= 2
                && !in_array($letters, $allowedNoVowel, true)
                && !preg_match('/[aeiouyàáâãäåèéêëìíîïòóôõöùúûüýÿ]/u', $letters)) {
                return 'Please enter a valid full name.';
            }
        }

        $first = mb_strtolower(preg_replace('/[^\p{L}]/u', '', $words[0]));
        $last = mb_strtolower(preg_replace('/[^\p{L}]/u', '', $words[count($words) - 1]));
        if ($first === $last) {
            return 'Please enter a valid full name.';
        }

        // Reject repeated-letter input.
        if (preg_match('/(\p{L})\1{3,}/iu', $name)) {
            return 'Please enter a valid full name.';
        }

        return null;
    }
}
