<?php
// Provides a reusable PDO connection factory for the application.
function getDatabaseConnection(): PDO
{
    static $connection = null;

    if ($connection instanceof PDO) {
        return $connection;
    }

    $host = 'localhost';
    $dbname = 'course';
    $user = 'root';
    $pass = 'password123';

    $dsn = "mysql:host=$host;dbname=$dbname;charset=utf8mb4";

    $options = [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ];

    $connection = new PDO($dsn, $user, $pass, $options);

    return $connection;
}
