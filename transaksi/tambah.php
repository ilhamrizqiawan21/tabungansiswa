<?php
require_once __DIR__ . '/../config/auth.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/functions.php';
redirectIfNotLoggedIn();

$siswa_id = $_GET['siswa_id'] ?? null;
$siswa = null;
$saldo_terakhir = 0;

// Jika tidak ada siswa_id, tampilkan form pilih siswa
if (!$siswa_id) {
    // Ambil semua siswa dengan JOIN ke tabel kelas untuk mendapatkan nama_kelas
    $siswa_list = $pdo->query("
        SELECT s.id, s.nis, s.nama, k.nama_kelas 
        FROM siswa s
        LEFT JOIN kelas k ON s.kelas_id = k.id
        ORDER BY s.nama
    ")->fetchAll();
    
    // Proses pemilihan siswa via POST
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['pilih_siswa'])) {
        $selected_siswa_id = $_POST['siswa_id'] ?? 0;
        if ($selected_siswa_id) {
            header("Location: tambah.php?siswa_id=" . $selected_siswa_id);
            exit();
        } else {
            $_SESSION['error'] = "Silakan pilih siswa terlebih dahulu.";
        }
    }
    
    require_once __DIR__ . '/../includes/header.php';
    ?>
    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2><i class="fas fa-plus-circle"></i> Tambah Transaksi</h2>
            <a href="index.php" class="btn btn-secondary"><i class="fas fa-arrow-left"></i> Kembali</a>
        </div>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger"><?= $_SESSION['error'] ?><?php unset($_SESSION['error']); ?></div>
        <?php endif; ?>

        <div class="card">
            <div class="card-header bg-primary text-white">
                <h5 class="mb-0">Pilih Siswa</h5>
            </div>
            <div class="card-body">
                <form method="post">
                    <div class="mb-3">
                        <label for="siswa_id" class="form-label">Siswa <span class="text-danger">*</span></label>
                        <select name="siswa_id" id="siswa_id" class="form-select" required>
                            <option value="">-- Pilih Siswa --</option>
                            <?php foreach ($siswa_list as $s): ?>
                                <option value="<?= $s['id'] ?>">
                                    <?= htmlspecialchars($s['nis']) ?> - <?= htmlspecialchars($s['nama']) ?> (<?= htmlspecialchars($s['nama_kelas'] ?? 'Tanpa Kelas') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="submit" name="pilih_siswa" class="btn btn-primary">Lanjutkan ke Form Transaksi</button>
                </form>
            </div>
        </div>
    </div>
    <?php
    require_once __DIR__ . '/../includes/footer.php';
    exit();
}

// --- Jika ada siswa_id, proses seperti biasa ---
// Ambil data siswa beserta nama kelas (JOIN)
$stmt = $pdo->prepare("
    SELECT s.*, k.nama_kelas 
    FROM siswa s
    LEFT JOIN kelas k ON s.kelas_id = k.id
    WHERE s.id = ?
");
$stmt->execute([$siswa_id]);
$siswa = $stmt->fetch();

if (!$siswa) {
    $_SESSION['error'] = "Siswa tidak ditemukan!";
    header("Location: ../siswa/index.php");
    exit();
}

// Hitung saldo terakhir
$saldo_terakhir = updateSaldo($pdo, $siswa_id);

// Proses form transaksi
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['simpan_transaksi'])) {
    $tanggal = $_POST['tanggal'];
    $jenis = $_POST['jenis'];
    $jumlah_raw = preg_replace('/[^0-9]/', '', $_POST['jumlah']);
    $jumlah = (float) $jumlah_raw;
    $keterangan = trim($_POST['keterangan'] ?? '');
    
    $errors = [];
    
    if ($jumlah <= 0) {
        $errors[] = "Jumlah transaksi harus lebih dari 0!";
    }
    
    if ($jenis == 'keluar') {
        $saldo_cek = updateSaldo($pdo, $siswa_id);
        if ($jumlah > $saldo_cek) {
            $errors[] = "Saldo tidak mencukupi! Saldo saat ini: " . formatRupiah($saldo_cek);
        }
    }
    
    if (empty($errors)) {
        try {
            $saldo_baru = $jenis == 'masuk' 
                ? $saldo_terakhir + $jumlah 
                : $saldo_terakhir - $jumlah;
            
            $stmt = $pdo->prepare("
                INSERT INTO transaksi 
                (siswa_id, tanggal, jenis, jumlah, keterangan, saldo) 
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$siswa_id, $tanggal, $jenis, $jumlah, $keterangan, $saldo_baru]);
            
            $_SESSION['success'] = "Transaksi berhasil dicatat!";
            header("Location: index.php?siswa_id=" . $siswa_id);
            exit();
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode('<br>', $errors);
    }
}

require_once __DIR__ . '/../includes/header.php';
?>

<style>
.rupiah-input {
    text-align: right;
    font-family: monospace;
    font-size: 1.1rem;
}
</style>

<div class="container mt-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h2>
            <i class="fas fa-plus-circle"></i> Transaksi Baru
            <small class="text-muted">
                untuk <?= htmlspecialchars($siswa['nama']) ?> (NIS: <?= htmlspecialchars($siswa['nis']) ?>)
            </small>
        </h2>
        <a href="index.php?siswa_id=<?= $siswa_id ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left"></i> Kembali
        </a>
    </div>

    <div class="row">
        <div class="col-md-6">
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">Informasi Siswa</h5>
                </div>
                <div class="card-body">
                    <table class="table table-sm table-borderless">
                        <tr><th width="35%">Nama:</th><td><?= htmlspecialchars($siswa['nama']) ?></td></tr>
                        <tr><th>NIS:</th><td><?= htmlspecialchars($siswa['nis']) ?></td></tr>
                        <tr><th>Kelas:</th><td><?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ditentukan') ?></td></tr>
                        <tr><th>Saldo Saat Ini:</th><td><strong class="text-success"><?= formatRupiah($saldo_terakhir) ?></strong></td></tr>
                    </table>
                </div>
            </div>
        </div>
        
        <div class="col-md-6">
            <div class="card">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">Form Transaksi</h5>
                </div>
                <div class="card-body">
                    <?php if (isset($_SESSION['error'])): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <?= $_SESSION['error'] ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                        <?php unset($_SESSION['error']); ?>
                    <?php endif; ?>
                    
                    <form method="post" id="formTransaksi">
                        <div class="mb-3">
                            <label for="tanggal" class="form-label">Tanggal <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" id="tanggal" name="tanggal" 
                                   value="<?= date('Y-m-d') ?>" required>
                        </div>
                        
                        <div class="mb-3">
                            <label class="form-label">Jenis Transaksi <span class="text-danger">*</span></label>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jenis" 
                                       id="masuk" value="masuk" checked>
                                <label class="form-check-label" for="masuk">
                                    <i class="fas fa-arrow-down text-success"></i> Setoran (Uang Masuk)
                                </label>
                            </div>
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="jenis" 
                                       id="keluar" value="keluar">
                                <label class="form-check-label" for="keluar">
                                    <i class="fas fa-arrow-up text-danger"></i> Penarikan (Uang Keluar)
                                </label>
                            </div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="jumlah" class="form-label">Jumlah <span class="text-danger">*</span></label>
                            <div class="input-group">
                                <span class="input-group-text">Rp</span>
                                <input type="text" class="form-control rupiah-input" id="jumlah" 
                                       name="jumlah" placeholder="0" required>
                            </div>
                            <div class="form-text">Gunakan titik sebagai pemisah ribuan (contoh: 1.500)</div>
                        </div>
                        
                        <div class="mb-3">
                            <label for="keterangan" class="form-label">Keterangan (Opsional)</label>
                            <textarea class="form-control" id="keterangan" name="keterangan" rows="2" 
                                      placeholder="Contoh: Pembayaran SPP, Tabungan Hari Raya, dll."></textarea>
                        </div>
                        
                        <button type="submit" name="simpan_transaksi" class="btn btn-primary w-100">
                            <i class="fas fa-save"></i> Simpan Transaksi
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
const jumlahInput = document.getElementById('jumlah');

function formatRupiah(element) {
    let value = element.value.replace(/[^\d]/g, '');
    if (!value) {
        element.value = '';
        return;
    }
    element.value = value.replace(/\B(?=(\d{3})+(?!\d))/g, '.');
}

jumlahInput.addEventListener('input', function(e) {
    formatRupiah(this);
});

jumlahInput.addEventListener('blur', function(e) {
    if (this.value === '') this.value = '0';
    formatRupiah(this);
});

document.getElementById('formTransaksi').addEventListener('submit', function(e) {
    const jumlahRaw = jumlahInput.value.replace(/\./g, '');
    const jumlah = parseInt(jumlahRaw);
    const jenis = document.querySelector('input[name="jenis"]:checked').value;
    const saldoSekarang = <?= (int)$saldo_terakhir ?>;
    
    if (isNaN(jumlah) || jumlah <= 0) {
        e.preventDefault();
        alert('Jumlah transaksi harus lebih dari 0!');
        return false;
    }
    
    if (jenis === 'keluar' && jumlah > saldoSekarang) {
        e.preventDefault();
        alert('Saldo tidak mencukupi! Saldo saat ini: Rp ' + saldoSekarang.toLocaleString('id-ID'));
        return false;
    }
    
    jumlahInput.value = jumlahRaw;
});
</script>

<?php require_once __DIR__ . '/../includes/footer.php'; ?>