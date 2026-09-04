<?php

    define('DB_HOST', 'localhost');
    define('DB_PORT', '3307');
    define('DB_USER', 'root');
    define('DB_PASS', '');
    define('DB_NAME', 'squir_db');

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
    } catch (PDOException $e) {
        http_response_code(500);
        exit('The application is temporarily unavailable. Please try again later.');
    }
?>