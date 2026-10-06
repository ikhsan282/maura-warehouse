<?php
// Database configuration — change these for cPanel deployment
define('DB_HOST', 'localhost');
define('DB_USER', 'root');
define('DB_PASS', '');
define('DB_NAME', 'db_maura_warehouse');
define('DB_PORT', 3306);
define('DB_CHARSET', 'utf8mb4');

function getDB(): mysqli {
    static $conn = null;
    if ($conn !== null) return $conn;

    $conn = new mysqli(DB_HOST, DB_USER, DB_PASS, DB_NAME, DB_PORT);
    if ($conn->connect_error) {
        error_log('DB connect failed: ' . $conn->connect_error);
        die(json_encode(['error' => 'Koneksi database gagal.']));
    }
    $conn->set_charset(DB_CHARSET);
    return $conn;
}
