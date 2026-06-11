<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();

// Filter berdasarkan siswa jika ada parameter siswa_id
$siswa_id = $_GET['siswa_id'] ?? null;
$siswa = null;

if ($siswa_id) {
    // Ambil data siswa beserta nama kelas
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
    
    // Ambil transaksi spesifik siswa
    $stmt = $pdo->prepare("
        SELECT t.*, s.nama as nama_siswa 
        FROM transaksi t
        JOIN siswa s ON t.siswa_id = s.id
        WHERE t.siswa_id = ?
        ORDER BY t.tanggal DESC, t.id DESC
    ");
    $stmt->execute([$siswa_id]);
} else {
    // Ambil semua transaksi
    $stmt = $pdo->query("
        SELECT t.*, s.nama as nama_siswa, s.nis
        FROM transaksi t
        JOIN siswa s ON t.siswa_id = s.id
        ORDER BY t.tanggal DESC, t.id DESC
        LIMIT 100
    ");
}

$transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <?php if ($siswa): ?>
                <i class="fas fa-user-graduate me-2"></i>Transaksi <?= htmlspecialchars($siswa['nama']) ?>
            <?php else: ?>
                <i class="fas fa-exchange-alt me-2"></i>Semua Transaksi
            <?php endif; ?>
        </h1>
        <div>
            <?php if ($siswa): ?>
                <a href="tambah.php?siswa_id=<?= $siswa['id'] ?>" class="btn btn-success btn-icon-split me-2">
                    <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                    <span class="text">Transaksi Baru</span>
                </a>
                <a href="../siswa/index.php" class="btn btn-secondary btn-icon-split">
                    <span class="icon text-white-50"><i class="fas fa-arrow-left"></i></span>
                    <span class="text">Kembali</span>
                </a>
            <?php else: ?>
                <a href="tambah.php" class="btn btn-success btn-icon-split me-2">
                    <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
                    <span class="text">Tambah Transaksi</span>
                </a>
                <a href="../siswa/index.php" class="btn btn-primary btn-icon-split">
                    <span class="icon text-white-50"><i class="fas fa-users"></i></span>
                    <span class="text">Lihat Siswa</span>
                </a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $_SESSION['success'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $_SESSION['error'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Student Info Cards (jika filter siswa) -->
    <?php if ($siswa): ?>
    <div class="row mb-4">
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Informasi Siswa</div>
                            <div class="h6 mb-0 font-weight-bold text-gray-800"><?= htmlspecialchars($siswa['nama']) ?></div>
                            <div class="text-xs text-muted mt-1">
                                NIS: <?= htmlspecialchars($siswa['nis']) ?> | 
                                Kelas: <?= htmlspecialchars($siswa['nama_kelas'] ?? 'Belum ditentukan') ?>
                            </div>
                        </div>
                        <div class="col-auto"><i class="fas fa-user-graduate fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Setoran</div>
                            <?php
                            $stmt = $pdo->prepare("SELECT SUM(jumlah) FROM transaksi WHERE siswa_id = ? AND jenis = 'masuk'");
                            $stmt->execute([$siswa_id]);
                            $total_masuk = $stmt->fetchColumn() ?: 0;
                            ?>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatRupiah($total_masuk) ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-arrow-up fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Saldo Akhir</div>
                            <?php $saldo_akhir = updateSaldo($pdo, $siswa_id); ?>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= formatRupiah($saldo_akhir) ?></div>
                        </div>
                        <div class="col-auto"><i class="fas fa-piggy-bank fa-2x text-gray-300"></i></div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- Transactions Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><?= $siswa ? 'Riwayat Transaksi' : 'Transaksi Terbaru' ?></h6>
            <div class="text-xs text-muted"><i class="fas fa-list me-1"></i> Total: <?= count($transaksi) ?> transaksi</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>#</th>
                            <th>Tanggal & Waktu</th>
                            <?php if (!$siswa): ?>
                                <th>Siswa</th>
                                <th>NIS</th>
                            <?php endif; ?>
                            <th>Jenis</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Saldo</th>
                            <th>Keterangan</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transaksi)): ?>
                            <tr>
                                <td colspan="<?= $siswa ? 8 : 10 ?>" class="text-center py-4">
                                    <div class="text-muted"><i class="fas fa-inbox fa-2x mb-3"></i><p class="mb-0">Belum ada transaksi</p></div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transaksi as $index => $t): ?>
                            <tr>
                                <td class="text-muted"><?= $index + 1 ?></td>
                                <td>
                                    <div class="text-xs font-weight-bold text-primary">
                                        <?= date('d/m/Y', strtotime($t['tanggal'])) ?>
                                    </div>
                                    <div class="text-xs text-muted">
                                        <?= date('H:i:s', strtotime($t['created_at'])) ?>
                                    </div>
                                </td>
                                <?php if (!$siswa): ?>
                                    <td><div class="font-weight-bold text-gray-800"><?= htmlspecialchars($t['nama_siswa']) ?></div></td>
                                    <td><span class="text-xs text-muted"><?= htmlspecialchars($t['nis']) ?></span></td>
                                <?php endif; ?>
                                <td>
                                    <span class="badge badge-<?= $t['jenis'] == 'masuk' ? 'success' : 'danger' ?>">
                                        <i class="fas fa-<?= $t['jenis'] == 'masuk' ? 'plus' : 'minus' ?> fa-sm me-1"></i>
                                        <?= $t['jenis'] == 'masuk' ? 'Setoran' : 'Penarikan' ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="font-weight-bold <?= $t['jenis'] == 'masuk' ? 'text-success' : 'text-danger' ?>">
                                        <?= $t['jenis'] == 'masuk' ? '+' : '-' ?><?= formatRupiah($t['jumlah']) ?>
                                    </span>
                                </td>
                                <td class="text-end">
                                    <span class="font-weight-bold text-gray-800"><?= formatRupiah($t['saldo']) ?></span>
                                </td>
                                <td><span class="text-xs text-muted"><?= htmlspecialchars($t['keterangan'] ?? '-') ?></span></td>
                                <td class="text-center">
                                    <div class="btn-group btn-group-sm">
                                        <a href="edit.php?id=<?= $t['id'] ?>" class="btn btn-warning" data-bs-toggle="tooltip" title="Edit Transaksi"><i class="fas fa-edit"></i></a>
                                        <a href="hapus.php?id=<?= $t['id'] ?>" class="btn btn-danger" data-bs-toggle="tooltip" title="Hapus Transaksi" onclick="return confirm('Hapus transaksi ini?')"><i class="fas fa-trash"></i></a>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_sidebar.php'; ?>