<?php
require_once '../config/auth.php';
redirectIfNotLoggedIn();

require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/audit.php';
require_once '../includes/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">Data Siswa</h1>
        <a href="tambah.php" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50">
                <i class="fas fa-user-plus"></i>
            </span>
            <span class="text">Tambah Siswa</span>
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <div class="d-flex align-items-center">
                <i class="fas fa-check-circle me-2"></i>
                <div class="flex-grow-1"><?= $_SESSION['success'] ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['success']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div class="flex-grow-1"><?= $_SESSION['error'] ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Data Siswa Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Siswa</h6>
            <div class="text-xs text-muted">
                <i class="fas fa-sync-alt fa-sm me-1"></i>Data terbaru
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover" width="100%" cellspacing="0">
                    <thead class="table-light">
                        <tr>
                            <th>NIS</th>
                            <th>Nama Siswa</th>
                            <th>Kelas</th>
                            <th>Kontak</th>
                            <th class="text-end">Saldo</th>
                            <th class="text-center">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        // Query dengan JOIN ke tabel kelas untuk mendapatkan nama_kelas
                        $stmt = $pdo->query("
                            SELECT 
                                s.*, 
                                k.nama_kelas,
                                k.tingkat,
                                (SELECT COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END), 0) 
                                 FROM transaksi 
                                 WHERE siswa_id = s.id) as saldo
                            FROM siswa s 
                            LEFT JOIN kelas k ON s.kelas_id = k.id 
                            ORDER BY s.nama
                        ");
                        
                        $total_siswa = 0;
                        $total_saldo = 0;
                        
                        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                            $total_siswa++;
                            $saldo = $row['saldo'] ?? 0;
                            $total_saldo += $saldo;
                        ?>
                        <tr>
                            <td>
                                <span class="font-weight-bold text-primary"><?= htmlspecialchars($row['nis']) ?></span>
                            </td>
                            <td>
                                <div class="d-flex align-items-center">
                                    <div class="avatar-sm bg-primary text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 36px; height: 36px;">
                                        <i class="fas fa-user-graduate"></i>
                                    </div>
                                    <div>
                                        <div class="font-weight-bold text-gray-800"><?= htmlspecialchars($row['nama']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <?php if ($row['nama_kelas']): ?>
                                    <span class="badge badge-info">
                                        <?= htmlspecialchars($row['tingkat']) ?>-<?= htmlspecialchars($row['nama_kelas']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="badge badge-secondary">Belum ada kelas</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($row['kontak']): ?>
                                    <span class="text-xs text-muted">
                                        <i class="fas fa-phone fa-sm me-1"></i><?= htmlspecialchars($row['kontak']) ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-xs text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td class="text-end">
                                <div class="font-weight-bold <?= $saldo > 0 ? 'text-success' : ($saldo < 0 ? 'text-danger' : 'text-gray-600') ?>">
                                    <?= formatRupiah($saldo) ?>
                                </div>
                                <?php if ($saldo > 0): ?>
                                    <div class="text-xs text-success">
                                        <i class="fas fa-piggy-bank fa-sm me-1"></i>Aktif
                                    </div>
                                <?php elseif ($saldo < 0): ?>
                                    <div class="text-xs text-danger">
                                        <i class="fas fa-exclamation-triangle fa-sm me-1"></i>Minus
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td class="text-center">
                                <div class="btn-group" role="group">
                                    <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm" data-bs-toggle="tooltip" title="Edit Siswa">
                                        <i class="fas fa-edit"></i>
                                    </a>
                                    <a href="../transaksi/index.php?siswa_id=<?= $row['id'] ?>" class="btn btn-info btn-sm" data-bs-toggle="tooltip" title="Lihat Transaksi">
                                        <i class="fas fa-exchange-alt"></i>
                                    </a>
                                    <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" data-bs-toggle="tooltip" title="Hapus Siswa" onclick="return confirm('Apakah Anda yakin ingin menghapus siswa <?= htmlspecialchars($row['nama']) ?>?')">
                                        <i class="fas fa-trash"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- Summary -->
            <div class="mt-4 pt-3 border-top">
                <div class="row">
                    <div class="col-md-6">
                        <div class="text-xs text-muted">
                            <i class="fas fa-users me-1"></i>
                            Total: <?= $total_siswa ?> siswa
                        </div>
                        <div class="text-xs text-muted">
                            <i class="fas fa-coins me-1"></i>
                            Total Saldo: <?= formatRupiah($total_saldo) ?>
                        </div>
                    </div>
                    <div class="col-md-6 text-end">
                        <div class="text-xs text-muted">
                            <i class="fas fa-clock me-1"></i>
                            Terakhir diperbarui: <?= date('d/m/Y H:i:s') ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Initialize tooltips
var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'))
var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
    return new bootstrap.Tooltip(tooltipTriggerEl)
});
</script>

<?php require_once '../includes/footer_sidebar.php'; ?>