<?php
// config/db.php

$host     = '127.0.0.1';
$dbname   = 'aitamslack';
$username = 'root';      // Default XAMPP MySQL user
$password = '';          // Default XAMPP MySQL password (empty)
$charset  = 'utf8mb4';

$dsn = "mysql:host=$host;dbname=$dbname;charset=$charset";

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $username, $password, $options);
} catch (PDOException $e) {
    // Log the actual error privately; show a safe message to users
    error_log("Database Connection Failed: " . $e->getMessage());
    die("Database connection error. Please ensure MySQL is running in XAMPP.");
}