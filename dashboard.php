<?php
require_once 'config/auth.php';
require_once 'config/database.php';
require_once 'includes/functions.php';
require_once 'includes/analytics.php';
redirectIfNotLoggedIn();
require_once 'includes/sidebar.php';
?>

<div class="container mt-4">
    <h2>Dashboard Tabungan Siswa</h2>
    
    <div class="row mt-4">
        <div class="col-md-4">
            <div class="card text-white bg-primary mb-3">
                <div class="card-header">Total Siswa</div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("SELECT COUNT(*) FROM siswa");
                    $total_siswa = $stmt->fetchColumn();
                    ?>
                    <h1 class="card-title"><?= $total_siswa ?></h1>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-white bg-success mb-3">
                <div class="card-header">Total Tabungan</div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("SELECT SUM(jumlah) FROM transaksi WHERE jenis = 'masuk'");
                    $total_masuk = $stmt->fetchColumn() ?: 0;
                    
                    $stmt = $pdo->query("SELECT SUM(jumlah) FROM transaksi WHERE jenis = 'keluar'");
                    $total_keluar = $stmt->fetchColumn() ?: 0;
                    
                    $total_tabungan = $total_masuk - $total_keluar;
                    ?>
                    <h1 class="card-title"><?= formatRupiah($total_tabungan) ?></h1>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card text-white bg-info mb-3">
                <div class="card-header">Transaksi Hari Ini</div>
                <div class="card-body">
                    <?php
                    $stmt = $pdo->query("SELECT COUNT(*) FROM transaksi WHERE DATE(tanggal) = CURDATE()");
                    $transaksi_hari_ini = $stmt->fetchColumn();
                    ?>
                    <h1 class="card-title"><?= $transaksi_hari_ini ?></h1>
                </div>
            </div>
        </div>
    </div>
    
    <div class="card mt-4">
        <div class="card-header">
            <h4>5 Transaksi Terakhir</h4>
        </div>
        <div class="card-body">
            <table class="table table-striped">
                <thead>
                    <tr>
                        <th>Tanggal</th>
                        <th>Siswa</th>
                        <th>Jenis</th>
                        <th>Jumlah</th>
                        <th>Saldo</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $stmt = $pdo->query("
                        SELECT t.*, s.nama as nama_siswa 
                        FROM transaksi t
                        JOIN siswa s ON t.siswa_id = s.id
                        ORDER BY t.id DESC
                        LIMIT 5
                    ");
                    
                    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)):
                    ?>
                    <tr>
                        <td><?= date('d/m/Y', strtotime($row['tanggal'])) ?></td>
                        <td><?= htmlspecialchars($row['nama_siswa']) ?></td>
                        <td><?= $row['jenis'] == 'masuk' ? 'Masuk' : 'Keluar' ?></td>
                        <td><?= formatRupiah($row['jumlah']) ?></td>
                        <td><?= formatRupiah($row['saldo']) ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php require_once 'includes/footer_sidebar.php'; ?>