<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();

// Filter tanggal
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Query data transaksi - UPDATE QUERY INI
$stmt = $pdo->prepare("
    SELECT 
        t.tanggal,
        s.nis,
        s.nama as nama_siswa,
        k.nama_kelas,
        t.jenis,
        t.jumlah,
        t.saldo,
        t.keterangan
    FROM transaksi t
    JOIN siswa s ON t.siswa_id = s.id
    LEFT JOIN kelas k ON s.kelas_id = k.id
    WHERE t.tanggal BETWEEN ? AND ?
    ORDER BY t.tanggal DESC
");
$stmt->execute([$start_date, $end_date]);
$transaksi = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Hitung total
$total_masuk = 0;
$total_keluar = 0;

foreach($transaksi as $t) {
    if($t['jenis'] == 'masuk') {
        $total_masuk += $t['jumlah'];
    } else {
        $total_keluar += $t['jumlah'];
    }
}

$saldo_bersih = $total_masuk - $total_keluar;

require_once '../includes/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-alt me-2"></i>Laporan Transaksi
        </h1>
        <a href="cetak_simple.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
           class="btn btn-primary btn-icon-split" target="_blank">
            <span class="icon text-white-50">
                <i class="fas fa-print"></i>
            </span>
            <span class="text">Cetak Laporan</span>
        </a>
        <a href="export_custom.php?start_date=<?= $start_date ?>&end_date=<?= $end_date ?>" 
           class="btn btn-success btn-icon-split ms-2">
            <span class="icon text-white-50">
                <i class="fas fa-file-excel"></i>
            </span>
            <span class="text">Export Excel</span>
        </a>
    </div>

    <!-- Filter Form -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter Periode</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label text-xs font-weight-bold text-uppercase text-muted">Dari Tanggal</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" 
                           value="<?= htmlspecialchars($start_date) ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label text-xs font-weight-bold text-uppercase text-muted">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" 
                           value="<?= htmlspecialchars($end_date) ?>" required>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-2"></i>Filter
                    </button>
                </div>
                <div class="col-md-4 text-end">
                    <div class="text-xs text-muted">
                        <i class="fas fa-calendar me-1"></i>
                        Periode: <?= date('d M Y', strtotime($start_date)) ?> - <?= date('d M Y', strtotime($end_date)) ?>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Summary Cards -->
    <div class="row mb-4">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Total Setoran</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= formatRupiah($total_masuk) ?>
                            </div>
                            <div class="text-xs text-muted mt-1">
                                <?= count(array_filter($transaksi, fn($t) => $t['jenis'] == 'masuk')) ?> transaksi
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-up fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">
                                Total Penarikan</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= formatRupiah($total_keluar) ?>
                            </div>
                            <div class="text-xs text-muted mt-1">
                                <?= count(array_filter($transaksi, fn($t) => $t['jenis'] == 'keluar')) ?> transaksi
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-arrow-down fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Saldo Bersih</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= formatRupiah($saldo_bersih) ?>
                            </div>
                            <div class="text-xs text-muted mt-1">
                                <?= count($transaksi) ?> total transaksi
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-balance-scale fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Rata-rata Transaksi</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= count($transaksi) > 0 ? formatRupiah(($total_masuk + $total_keluar) / count($transaksi)) : 'Rp 0' ?>
                            </div>
                            <div class="text-xs text-muted mt-1">
                                Per transaksi
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-line fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Transactions Table -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Detail Transaksi</h6>
            <div class="text-xs text-muted">
                <i class="fas fa-list me-1"></i>
                Menampilkan <?= count($transaksi) ?> transaksi
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal</th>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Jenis</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Saldo</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($transaksi)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4">
                                    <div class="text-muted">
                                        <i class="fas fa-inbox fa-2x mb-3"></i>
                                        <p class="mb-0">Tidak ada transaksi pada periode ini</p>
                                    </div>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach($transaksi as $t): ?>
                            <tr>
                                <td>
                                    <div class="text-xs font-weight-bold text-primary">
                                        <?= date('d/m/Y', strtotime($t['tanggal'])) ?>
                                    </div>
                                    <div class="text-xs text-muted">
                                        <?= date('H:i', strtotime($t['tanggal'])) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="font-weight-bold text-gray-800"><?= htmlspecialchars($t['nis']) ?></span>
                                </td>
                                <td>
                                    <div class="font-weight-bold text-gray-800">
                                        <?= htmlspecialchars($t['nama_siswa']) ?>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-info"><?= htmlspecialchars($t['nama_kelas'] ?? '-') ?></span> <!-- UPDATE: nama_kelas -->
                                </td>
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
                                    <span class="font-weight-bold text-gray-800">
                                        <?= formatRupiah($t['saldo']) ?>
                                    </span>
                                </td>
                                <td>
                                    <span class="text-xs text-muted">
                                        <?= htmlspecialchars($t['keterangan'] ?? '-') ?>
                                    </span>
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