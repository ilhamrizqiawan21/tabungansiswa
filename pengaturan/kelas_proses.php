<?php
// File: pengaturan/kelas_proses.php
require_once '../config/auth.php';
require_once '../config/database.php';
redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan'])) {
    $id = $_POST['id'] ?? null;
    $nama_kelas = $_POST['nama_kelas'];
    $tingkat = $_POST['tingkat'];
    $tahun_pelajaran_id = $_POST['tahun_pelajaran_id'];
    $wali_kelas = $_POST['wali_kelas'] ?? null;
    
    try {
        if ($id) {
            // Update kelas
            $stmt = $pdo->prepare("
                UPDATE kelas 
                SET nama_kelas = ?, tingkat = ?, tahun_pelajaran_id = ?, wali_kelas = ?
                WHERE id = ?
            ");
            $stmt->execute([$nama_kelas, $tingkat, $tahun_pelajaran_id, $wali_kelas, $id]);
            $_SESSION['success'] = "Kelas berhasil diperbarui!";
        } else {
            // Tambah kelas baru
            $stmt = $pdo->prepare("
                INSERT INTO kelas (nama_kelas, tingkat, tahun_pelajaran_id, wali_kelas)
                VALUES (?, ?, ?, ?)
            ");
            $stmt->execute([$nama_kelas, $tingkat, $tahun_pelajaran_id, $wali_kelas]);
            $_SESSION['success'] = "Kelas berhasil ditambahkan!";
        }
    } catch (PDOException $e) {
        if ($e->getCode() == 23000) {
            $_SESSION['error'] = "Kelas dengan nama tersebut sudah ada pada tahun pelajaran yang dipilih!";
        } else {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
} elseif (isset($_GET['hapus'])) {
    $id = $_GET['hapus'];
    
    try {
        // Cek apakah kelas memiliki siswa
        $stmt = $pdo->prepare("SELECT COUNT(*) FROM siswa WHERE kelas_id = ?");
        $stmt->execute([$id]);
        $jumlah_siswa = $stmt->fetchColumn();
        
        if ($jumlah_siswa > 0) {
            $_SESSION['error'] = "Tidak dapat menghapus kelas yang masih memiliki siswa!";
        } else {
            $stmt = $pdo->prepare("DELETE FROM kelas WHERE id = ?");
            $stmt->execute([$id]);
            $_SESSION['success'] = "Kelas berhasil dihapus!";
        }
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

header("Location: index.php");
exit();