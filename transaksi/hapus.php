<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
redirectIfNotLoggedIn();

$transaksi_id = $_GET['id'] ?? null;

if (!$transaksi_id) {
    $_SESSION['error'] = "Transaksi tidak valid!";
    header("Location: index.php");
    exit();
}

// Ambil data transaksi yang akan dihapus
$stmt = $pdo->prepare("
    SELECT * FROM transaksi 
    WHERE id = ?
");
$stmt->execute([$transaksi_id]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    $_SESSION['error'] = "Transaksi tidak ditemukan!";
    header("Location: index.php");
    exit();
}

// Proses penghapusan
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $pdo->beginTransaction();
        
        // 1. Hapus transaksi
        $stmt = $pdo->prepare("DELETE FROM transaksi WHERE id = ?");
        $stmt->execute([$transaksi_id]);
        
        // 2. Hitung selisih untuk update saldo setelahnya
        $selisih = $transaksi['jenis'] == 'masuk' 
            ? -$transaksi['jumlah'] 
            : $transaksi['jumlah'];
        
        // 3. Update saldo transaksi setelahnya
        $stmt = $pdo->prepare("
            UPDATE transaksi 
            SET saldo = saldo + ? 
            WHERE siswa_id = ? AND id > ?
        ");
        $stmt->execute([
            $selisih,
            $transaksi['siswa_id'],
            $transaksi_id
        ]);
        
        $pdo->commit();
        
        $_SESSION['success'] = "Transaksi berhasil dihapus!";
        header("Location: index.php?siswa_id=" . $transaksi['siswa_id']);
        exit();
        
    } catch (PDOException $e) {
        $pdo->rollBack();
        $_SESSION['error'] = "Gagal menghapus transaksi: " . $e->getMessage();
        header("Location: index.php?siswa_id=" . $transaksi['siswa_id']);
        exit();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<div class="container mt-4">
    <div class="card">
        <div class="card-header bg-danger text-white">
            <h4><i class="fas fa-trash-alt"></i> Konfirmasi Penghapusan Transaksi</h4>
        </div>
        <div class="card-body">
            <div class="alert alert-warning">
                <strong>Peringatan!</strong> Anda akan menghapus transaksi ini:
            </div>
            
            <table class="table table-bordered">
                <tr>
                    <th width="30%">Tanggal</th>
                    <td><?= date('d/m/Y', strtotime($transaksi['tanggal'])) ?></td>
                </tr>
                <tr>
                    <th>Jenis</th>
                    <td>
                        <span class="badge bg-<?= $transaksi['jenis'] == 'masuk' ? 'success' : 'danger' ?>">
                            <?= $transaksi['jenis'] == 'masuk' ? 'Setoran' : 'Penarikan' ?>
                        </span>
                    </td>
                </tr>
                <tr>
                    <th>Jumlah</th>
                    <td><?= formatRupiah($transaksi['jumlah']) ?></td>
                </tr>
                <tr>
                    <th>Saldo Setelah</th>
                    <td><?= formatRupiah($transaksi['saldo']) ?></td>
                </tr>
                <tr>
                    <th>Keterangan</th>
                    <td><?= $transaksi['keterangan'] ?? '-' ?></td>
                </tr>
            </table>
            
            <div class="alert alert-danger">
                <strong>Dampak Penghapusan:</strong> 
                Semua saldo transaksi setelah ini akan disesuaikan!
            </div>
            
            <form method="post">
                <input type="hidden" name="id" value="<?= $transaksi_id ?>">
                
                <div class="d-flex justify-content-between">
                    <a href="index.php?siswa_id=<?= $transaksi['siswa_id'] ?>" class="btn btn-secondary">
                        <i class="fas fa-times"></i> Batal
                    </a>
                    <button type="submit" class="btn btn-danger">
                        <i class="fas fa-trash-alt"></i> Hapus Permanen
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>