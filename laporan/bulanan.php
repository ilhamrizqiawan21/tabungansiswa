<?php
require_once '../config/auth.php';
redirectIfNotLoggedIn();

require_once '../config/database.php';
require_once '../includes/functions.php';

$bulan = $_GET['bulan'] ?? date('Y-m');
$search = $_GET['search'] ?? '';

$prev_month = date('Y-m', strtotime($bulan . ' -1 month'));
$next_month = date('Y-m', strtotime($bulan . ' +1 month'));

// Query rekapitulasi per siswa dalam bulan tersebut
$sql = "
    SELECT 
        s.id,
        s.nis,
        s.nama,
        s.kelas,
        COALESCE(SUM(CASE WHEN t.jenis = 'masuk' THEN t.jumlah ELSE 0 END), 0) as total_masuk,
        COALESCE(SUM(CASE WHEN t.jenis = 'keluar' THEN t.jumlah ELSE 0 END), 0) as total_keluar
    FROM siswa s
    LEFT JOIN transaksi t ON s.id = t.siswa_id AND DATE_FORMAT(t.tanggal, '%Y-%m') = ?
    WHERE 1=1
";

$params = [$bulan];
if (!empty($search)) {
    $sql .= " AND (s.nama LIKE ? OR s.nis LIKE ?)";
    $params[] = "%$search%";
    $params[] = "%$search%";
}

$sql .= " GROUP BY s.id ORDER BY s.nama ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$ringkasan = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Total keseluruhan
$total_setoran_bulan = 0;
$total_penarikan_bulan = 0;
foreach ($ringkasan as $r) {
    $total_setoran_bulan += $r['total_masuk'];
    $total_penarikan_bulan += $r['total_keluar'];
}
$saldo_bersih_bulan = $total_setoran_bulan - $total_penarikan_bulan;

// Detail transaksi (untuk tabel bawah, bisa dibatasi)
$stmt_detail = $pdo->prepare("
    SELECT 
        DATE_FORMAT(t.tanggal, '%d/%m/%Y') as tanggal_format,
        s.nis, s.nama, s.kelas,
        t.jenis, t.jumlah, t.saldo, t.keterangan
    FROM transaksi t
    JOIN siswa s ON t.siswa_id = s.id
    WHERE DATE_FORMAT(t.tanggal, '%Y-%m') = ?
    ORDER BY t.tanggal DESC, t.id DESC
    LIMIT 200
");
$stmt_detail->execute([$bulan]);
$transaksi = $stmt_detail->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/sidebar.php';
?>

<div class="container-fluid mt-4">
    <h2><i class="fas fa-chart-line"></i> Laporan Bulanan Tabungan Siswa</h2>
    
    <!-- Filter Form -->
    <div class="card mb-4">
        <div class="card-body">
            <form method="get" class="row g-3">
                <div class="col-md-3">
                    <label for="bulan" class="form-label">Bulan</label>
                    <input type="month" class="form-control" id="bulan" name="bulan" 
                           value="<?= htmlspecialchars($bulan) ?>">
                </div>
                <div class="col-md-4">
                    <label for="search" class="form-label">Cari Siswa</label>
                    <input type="text" class="form-control" id="search" name="search" 
                           placeholder="Nama atau NIS" value="<?= htmlspecialchars($search) ?>">
                </div>
                <div class="col-md-2 align-self-end">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter"></i> Tampilkan
                    </button>
                </div>
                <div class="col-md-3 align-self-end text-end">
                    <a href="export_bulanan.php?bulan=<?= $bulan ?>&search=<?= urlencode($search) ?>" 
                       class="btn btn-success">
                        <i class="fas fa-file-excel"></i> Export Excel
                    </a>
                    <a href="cetak_bulanan.php?bulan=<?= $bulan ?>" class="btn btn-secondary" target="_blank">
                        <i class="fas fa-print"></i> Cetak
                    </a>
                </div>
            </form>
        </div>
    </div>
    
    <!-- Navigasi Bulan -->
    <div class="d-flex justify-content-between align-items-center mb-3">
        <a href="?bulan=<?= $prev_month ?>&search=<?= urlencode($search) ?>" class="btn btn-outline-primary">
            <i class="fas fa-chevron-left"></i> <?= date('F Y', strtotime($prev_month . '-01')) ?>
        </a>
        <h3 class="text-primary"><?= date('F Y', strtotime($bulan . '-01')) ?></h3>
        <a href="?bulan=<?= $next_month ?>&search=<?= urlencode($search) ?>" class="btn btn-outline-primary">
            <?= date('F Y', strtotime($next_month . '-01')) ?> <i class="fas fa-chevron-right"></i>
        </a>
    </div>
    
    <!-- Ringkasan Kartu -->
    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card text-white bg-success">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-arrow-down"></i> Total Setoran</h5>
                    <h3><?= formatRupiah($total_setoran_bulan) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-danger">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-arrow-up"></i> Total Penarikan</h5>
                    <h3><?= formatRupiah($total_penarikan_bulan) ?></h3>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card text-white bg-info">
                <div class="card-body">
                    <h5 class="card-title"><i class="fas fa-chart-simple"></i> Saldo Bersih Bulan</h5>
                    <h3><?= formatRupiah($saldo_bersih_bulan) ?></h3>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Rekapitulasi per Siswa -->
    <div class="card mb-4">
        <div class="card-header bg-primary text-white">
            <h5 class="mb-0"><i class="fas fa-table"></i> Rekapitulasi per Siswa</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-striped table-bordered table-hover">
                    <thead class="table-dark">
                        <tr>
                            <th>No</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th class="text-end">Setoran</th>
                            <th class="text-end">Penarikan</th>
                            <th class="text-end">Saldo Bulan Ini</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($ringkasan)): ?>
                            <tr>
                                <td colspan="7" class="text-center">Tidak ada data siswa atau transaksi pada bulan ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php $no = 1; ?>
                            <?php foreach ($ringkasan as $r): ?>
                                <?php $saldo_siswa = $r['total_masuk'] - $r['total_keluar']; ?>
                                <tr>
                                    <td><?= $no++ ?></td>
                                    <td><?= htmlspecialchars($r['nis']) ?></td>
                                    <td><?= htmlspecialchars($r['nama']) ?></td>
                                    <td><?= htmlspecialchars($r['kelas']) ?></td>
                                    <td class="text-end text-success"><?= formatRupiah($r['total_masuk']) ?></td>
                                    <td class="text-end text-danger"><?= formatRupiah($r['total_keluar']) ?></td>
                                    <td class="text-end fw-bold"><?= formatRupiah($saldo_siswa) ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                    <tfoot class="table-secondary">
                        <tr>
                            <th colspan="4" class="text-end">Total:</th>
                            <th class="text-end"><?= formatRupiah($total_setoran_bulan) ?></th>
                            <th class="text-end"><?= formatRupiah($total_penarikan_bulan) ?></th>
                            <th class="text-end"><?= formatRupiah($saldo_bersih_bulan) ?></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
    
    <!-- Detail Transaksi -->
    <div class="card">
        <div class="card-header bg-secondary text-white">
            <h5 class="mb-0"><i class="fas fa-list"></i> Detail Transaksi (Terbaru)</h5>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm table-striped">
                    <thead>
                        <tr>
                            <th>Tanggal</th>
                            <th>NIS</th>
                            <th>Nama</th>
                            <th>Kelas</th>
                            <th>Jenis</th>
                            <th class="text-end">Jumlah</th>
                            <th class="text-end">Saldo</th>
                            <th>Keterangan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($transaksi)): ?>
                            <tr>
                                <td colspan="8" class="text-center">Belum ada transaksi di bulan ini.</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($transaksi as $t): ?>
                                <tr>
                                    <td><?= $t['tanggal_format'] ?></td>
                                    <td><?= htmlspecialchars($t['nis']) ?></td>
                                    <td><?= htmlspecialchars($t['nama']) ?></td>
                                    <td><?= htmlspecialchars($t['kelas']) ?></td>
                                    <td>
                                        <span class="badge bg-<?= $t['jenis'] == 'masuk' ? 'success' : 'danger' ?>">
                                            <?= $t['jenis'] == 'masuk' ? 'Setoran' : 'Penarikan' ?>
                                        </span>
                                    </td>
                                    <td class="text-end"><?= formatRupiah($t['jumlah']) ?></td>
                                    <td class="text-end"><?= formatRupiah($t['saldo']) ?></td>
                                    <td><?= htmlspecialchars($t['keterangan'] ?? '-') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php if (count($transaksi) >= 200): ?>
                <p class="text-muted mt-2">* Menampilkan maksimal 200 transaksi terbaru. Gunakan filter untuk detail lebih lanjut.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_sidebar.php'; ?>