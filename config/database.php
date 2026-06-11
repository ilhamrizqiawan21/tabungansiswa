<?php
// config/database.php

$host = 'localhost';
$dbname = 'tabungan_siswa';
$username = 'root';
$password = '';

// Set timezone ke Asia/Jakarta (opsional, sesuaikan)
date_default_timezone_set('Asia/Jakarta');

$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,   // Melempar exception jika error
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC, // Fetch sebagai array asosiatif
    PDO::ATTR_EMULATE_PREPARES => false,           // Gunakan prepared statement asli
    PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci" // Charset lengkap
];

try {
    $pdo = new PDO("mysql:host=$host;dbname=$dbname;charset=utf8mb4", $username, $password, $options);
} catch (PDOException $e) {
    // Log error ke file (opsional) dan tampilkan pesan umum ke user
    error_log("Database connection failed: " . $e->getMessage(), 0);
    die("Maaf, terjadi kesalahan koneksi database. Silakan coba lagi nanti. (Error: " . $e->getCode() . ")");
}

// Fungsi helper untuk mendapatkan koneksi (jika diperlukan di file lain)
function getDB() {
    global $pdo;
    return $pdo;
}
?>