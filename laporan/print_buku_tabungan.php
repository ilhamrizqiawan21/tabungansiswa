<?php
/**
 * PRINT BUKU TABUNGAN SISWA
 */

require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/print.php';
redirectIfNotLoggedIn();

$siswa_id = sanitizeInt($_GET['siswa_id'] ?? null);

if (!$siswa_id) {
    $_SESSION['error'] = "Siswa tidak ditemukan";
    header("Location: ../siswa/index.php");
    exit;
}

// Generate HTML print format
$html = generateBukuTabunganHTML($pdo, $siswa_id);

// Output directly
header('Content-Type: text/html; charset=utf-8');
echo $html;
?>
