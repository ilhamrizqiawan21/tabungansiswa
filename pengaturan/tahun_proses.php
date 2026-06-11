<?php
// File: pengaturan/tahun_proses.php
require_once '../config/auth.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $tahun = $_POST['tahun'];
    $semester = $_POST['semester'];
    $set_aktif = isset($_POST['set_aktif']);
    
    try {
        // Jika set aktif, nonaktifkan semua tahun pelajaran lainnya
        if ($set_aktif) {
            $pdo->exec("UPDATE tahun_pelajaran SET status = 'nonaktif'");
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO tahun_pelajaran (tahun, semester, status)
            VALUES (?, ?, ?)
        ");
        $status = $set_aktif ? 'aktif' : 'nonaktif';
        $stmt->execute([$tahun, $semester, $status]);
        
        $_SESSION['success'] = "Tahun pelajaran berhasil ditambahkan!";
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error'] = "Tahun pelajaran dengan semester tersebut sudah ada!";
        } else {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
} elseif (isset($_GET['set_aktif'])) {
    $id = $_GET['set_aktif'];
    
    try {
        // Nonaktifkan semua tahun pelajaran
        $pdo->exec("UPDATE tahun_pelajaran SET status = 'nonaktif'");
        
        // Aktifkan tahun pelajaran yang dipilih
        $stmt = $pdo->prepare("UPDATE tahun_pelajaran SET status = 'aktif' WHERE id = ?");
        $stmt->execute([$id]);
        
        $_SESSION['success'] = "Tahun pelajaran berhasil diaktifkan!";
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
} elseif (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    try {
        // Cek apakah tahun pelajaran memiliki kelas
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM kelas WHERE tahun_pelajaran_id = ?");
        $stmt->execute([$id]);
        $jumlah_kelas = $stmt->fetchColumn();
        
        if ($jumlah_kelas > 0) {
            $_SESSION['error'] = "Tidak dapat menghapus tahun pelajaran yang masih memiliki kelas!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM tahun_pelajaran WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Tahun pelajaran berhasil dihapus!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

header("Location: index.php");
exit();