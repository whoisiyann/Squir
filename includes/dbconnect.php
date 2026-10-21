<?php

    require_once __DIR__ . '/env.php';

    // Database settings come from .env (see .env.example).
    define('DB_HOST', env('DB_HOST', 'localhost'));
    define('DB_PORT', env('DB_PORT', '3306'));
    define('DB_USER', env('DB_USER', 'root'));
    define('DB_PASS', env('DB_PASS', ''));
    define('DB_NAME', env('DB_NAME', 'squirdb'));

    try {
        $dbh = new PDO(
            'mysql:host=' . DB_HOST .
            ';port=' . DB_PORT .
            ';dbname=' . DB_NAME .
            ';charset=utf8mb4',
            DB_USER,
            DB_PASS,
            [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ]
        );


        $dbh->exec("SET time_zone = '+08:00'");
    } catch (PDOException $e) {
        error_log('[db] connection failed: ' . $e->getMessage());
        http_response_code(500);
        exit('The application is temporarily unavailable. Please try again later.');
    }
?>