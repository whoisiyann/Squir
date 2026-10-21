<?php

// Reads the .env file and gives the app env() / envBool().
// Real values (keys, passwords) live in .env and are never committed. See .env.example.

if (!function_exists('squirLoadEnv')) {
    function squirLoadEnv(string $path): void
    {
        if (!is_readable($path)) {
            error_log('[env] .env file not found or not readable. Copy .env.example to .env and fill it in.');
            return;
        }

        $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\"'");

            if ($key !== '') {
                putenv($key . '=' . $value);
                $_ENV[$key] = $value;
            }
        }
    }
}

if (!function_exists('env')) {
    // Read a setting. Empty or missing values return the default.
    function env(string $key, ?string $default = null): ?string
    {
        if (isset($_ENV[$key]) && $_ENV[$key] !== '') {
            return (string) $_ENV[$key];
        }

        $value = getenv($key);
        if ($value !== false && $value !== '') {
            return (string) $value;
        }

        return $default;
    }
}

if (!function_exists('envBool')) {
    function envBool(string $key, bool $default = false): bool
    {
        $value = env($key);
        if ($value === null) {
            return $default;
        }

        return in_array(strtolower($value), ['1', 'true', 'yes', 'on'], true);
    }
}

squirLoadEnv(__DIR__ . '/../.env');
