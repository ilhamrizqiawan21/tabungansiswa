<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();

$id = $_GET['id'] ?? 0;

// Ambil data kelas
$stmt = $pdo->prepare("SELECT * FROM kelas WHERE id = ?");
$stmt->execute([$id]);
$kelas = $stmt->fetch();

if (!$kelas) {
    $_SESSION['error'] = "Kelas tidak ditemukan!";
    header("Location: index.php");
    exit();
}

// Cek apakah kelas memiliki siswa
$stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE kelas_id = ?");
$stmt->execute([$id]);
$jumlah_siswa = $stmt->fetchColumn();

if ($jumlah_siswa > 0) {
    $_SESSION['error'] = "Tidak dapat menghapus kelas! Masih ada $jumlah_siswa siswa dalam kelas ini.";
    header("Location: index.php");
    exit();
}

// Hapus kelas
try {
    $stmt = $pdo->prepare("DELETE FROM kelas WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = "Kelas '{$kelas['nama_kelas']}' berhasil dihapus!";
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
}

header("Location: index.php");
exit();