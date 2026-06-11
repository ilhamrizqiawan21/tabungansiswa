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

// Ambil data transaksi
$stmt = $pdo->prepare("
    SELECT t.*, s.nama as nama_siswa, s.nis 
    FROM transaksi t
    JOIN siswa s ON t.siswa_id = s.id
    WHERE t.id = ?
");
$stmt->execute([$transaksi_id]);
$transaksi = $stmt->fetch();

if (!$transaksi) {
    $_SESSION['error'] = "Transaksi tidak ditemukan!";
    header("Location: index.php");
    exit();
}

// Ambil saldo sebelum transaksi ini
$stmt = $pdo->prepare("
    SELECT saldo FROM transaksi 
    WHERE siswa_id = ? AND id < ?
    ORDER BY id DESC LIMIT 1
");
$stmt->execute([$transaksi['siswa_id'], $transaksi_id]);
$saldo_sebelumnya = $stmt->fetchColumn() ?? 0;

// Proses form edit
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $tanggal = $_POST['tanggal'];
    $jenis = $_POST['jenis'];
    $jumlah = (float) str_replace('.', '', $_POST['jumlah']);
    $keterangan = $_POST['keterangan'] ?? null;
    
    try {
        // Hitung selisih dengan transaksi sebelumnya
        $selisih = ($jenis == 'masuk' ? $jumlah : -$jumlah) - 
                  ($transaksi['jenis'] == 'masuk' ? $transaksi['jumlah'] : -$transaksi['jumlah']);
        
        // Update transaksi
        $stmt = $pdo->prepare("
            UPDATE transaksi 
            SET tanggal = ?, jenis = ?, jumlah = ?, keterangan = ?, saldo = saldo + ?
            WHERE id = ?
        ");
        $stmt->execute([
            $tanggal,
            $jenis,
            $jumlah,
            $keterangan,
            $selisih,
            $transaksi_id
        ]);
        
        // Update saldo transaksi setelahnya
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
        
        $_SESSION['success'] = "Transaksi berhasil diperbarui!";
        header("Location: index.php?siswa_id=" . $transaksi['siswa_id']);
        exit();
    } catch (PDOException $e) {
        $_SESSION['error'] = "Error: " . $e->getMessage();
    }
}

require_once __DIR__ . '/../includes/header.php';
?>
<style>
.rupiah-input {
    text-align: right;
    font-family: monospace;
}
</style>
<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-edit"></i> Edit Transaksi
            <small class="text-muted">
                <?= htmlspecialchars($transaksi['nama_siswa']) ?> (NIS: <?= htmlspecialchars($transaksi['nis']) ?>)
            </small>
        </h2>
        <a href="index.php?siswa_id=<?= $transaksi['siswa_id'] ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger">
            <?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <div class="card">
        <div class="card-body">
            <form method="post">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" 
                                   value="<?= htmlspecialchars($transaksi['tanggal']) ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jenis" 
                                       id="masuk" value="masuk" <?= $transaksi['jenis'] == 'masuk' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="masuk">Setoran (Uang Masuk)</label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jenis" 
                                       id="keluar" value="keluar" <?= $transaksi['jenis'] == 'keluar' ? 'checked' : '' ?>>
                                <label class="form-check-label" for="keluar">Penarikan (Uang Keluar)</label>
                            </div>
                        </div>
                    </div>
                    
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="jumlah" class="form-label">Jumlah <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control rupiah-input" id="jumlah" 
                                       name="jumlah" value="<?= number_format($transaksi['jumlah'], 0, ',', '.') ?>" required>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="2"><?= 
                                htmlspecialchars($transaksi['keterangan'] ?? '') ?></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="alert alert-info mt-3">
                    <strong>Saldo sebelum transaksi ini:</strong> <?= formatRupiah($saldo_sebelumnya) ?><br>
                    <strong>Saldo setelah transaksi ini:</strong> <?= formatRupiah($transaksi['saldo']) ?>
                </div>
                
                <button type="submit" class="btn btn-primary">
                    <i class="fas fa-save"></i> Simpan Perubahan
                </button>
            </form>
        </div>
    </div>
</div>

<script>
// Format input uang
document.getElementById('jumlah').addEventListener('input', function(e) {
    let value = this.value.replace(/[^\d]/g, '');
    if (value.length > 3) {
        value = value.replace(/\B(?=(\d{3})+(?!\d))/g, ".");
    }
    this.value = value;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>

