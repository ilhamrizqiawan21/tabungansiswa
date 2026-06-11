<?php
function formatRupiah($angka) {
    return 'Rp ' . number_format($angka, 2, ',', '.');
}

function updateSaldo($pdo, $siswa_id) {
    // Hitung saldo terakhir
    $stmt = $pdo->prepare("SELECT saldo FROM transaksi WHERE siswa_id = ? ORDER BY id DESC LIMIT 1");
    $stmt->execute([$siswa_id]);
    $saldo = $stmt->fetchColumn();
    
    return $saldo ? $saldo : 0;
}

function getSaldoAwal($pdo, $siswa_id) {
    $stmt = $pdo->prepare("SELECT saldo FROM transaksi WHERE siswa_id = ? ORDER BY id ASC LIMIT 1");
    $stmt->execute([$siswa_id]);
    return $stmt->fetchColumn() ?: 0;
}
?>