<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/audit.php';
redirectIfNotLoggedIn();

require_once '../includes/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chalkboard-teacher me-2"></i>Data Kelas
        </h1>
        <a href="tambah.php" class="btn btn-primary btn-icon-split">
            <span class="icon text-white-50"><i class="fas fa-plus"></i></span>
            <span class="text">Tambah Kelas</span>
        </a>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show"><?= $_SESSION['success'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['success']); endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show"><?= $_SESSION['error'] ?><button type="button" class="btn-close" data-bs-dismiss="alert"></button></div>
        <?php unset($_SESSION['error']); endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">Daftar Kelas</h6>
            <div class="text-xs text-muted"><i class="fas fa-sync-alt fa-sm me-1"></i>Data terbaru</div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr><th>#</th><th>Nama Kelas</th><th>Tingkat</th><th>Jurusan</th><th>Wali Kelas</th><th>Jumlah Siswa</th><th>Aksi</th></tr>
                    </thead>
                    <tbody>
                        <?php
                        $stmt = $pdo->query("
                            SELECT k.*, 
                                   COUNT(s.id) as jumlah_siswa
                            FROM kelas k
                            LEFT JOIN siswa s ON k.id = s.kelas_id
                            GROUP BY k.id
                            ORDER BY k.tingkat, k.nama_kelas
                        ");
                        $no=1;
                        while ($row = $stmt->fetch()):
                        ?>
                        <tr>
                            <td><?= $no++ ?></td>
                            <td><strong><?= htmlspecialchars($row['nama_kelas']) ?></strong></td>
                            <td>Kelas <?= htmlspecialchars($row['tingkat']) ?></td>
                            <td><?= htmlspecialchars($row['jurusan'] ?? '-') ?></td>
                            <td><?= htmlspecialchars($row['wali_kelas'] ?? '-') ?></td>
                            <td><?= $row['jumlah_siswa'] ?> siswa</td>
                            <td>
                                <a href="edit.php?id=<?= $row['id'] ?>" class="btn btn-warning btn-sm"><i class="fas fa-edit"></i></a>
                                <a href="hapus.php?id=<?= $row['id'] ?>" class="btn btn-danger btn-sm" onclick="return confirm('Hapus kelas ini?')"><i class="fas fa-trash"></i></a>
                            </td>
                        </tr>
                        <?php endwhile; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_sidebar.php'; ?>