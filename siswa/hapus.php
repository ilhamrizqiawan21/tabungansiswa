<?php
require_once '../config/auth.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

// Check if ID parameter exists
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    $_SESSION['error'] = "ID siswa tidak valid!";
    header("Location: index.php");
    exit();
}

$id = $_GET['id'];

try {
    // Check if student exists
    $stmt = $pdo->prepare("SELECT id FROM siswa WHERE id = ?");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() === 0) {
        $_SESSION['error'] = "Siswa tidak ditemukan!";
        header("Location: index.php");
        exit();
    }
    
    // Check if student has transactions
    $stmt = $pdo->prepare("SELECT id FROM transaksi WHERE siswa_id = ? LIMIT 1");
    $stmt->execute([$id]);
    
    if ($stmt->rowCount() > 0) {
        $_SESSION['error'] = "Tidak dapat menghapus siswa yang memiliki transaksi!";
        header("Location: index.php");
        exit();
    }
    
    // Delete student
    $stmt = $pdo->prepare("DELETE FROM siswa WHERE id = ?");
    $stmt->execute([$id]);
    
    $_SESSION['success'] = "Data siswa berhasil dihapus!";
    header("Location: index.php");
    exit();
    
} catch (PDOException $e) {
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: index.php");
    exit();
}