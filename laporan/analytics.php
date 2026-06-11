<?php
/**
 * ANALYTICS & CHARTS DASHBOARD
 */

require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/analytics.php';
redirectIfNotLoggedIn();

require_once '../includes/sidebar.php';

// Get filter dates
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-d');

// Get statistics
$stats = getDashboardStats($pdo);
$transaksi_per_hari = getTransaksiPerHari($pdo, $start_date, $end_date);
$saldo_distribution = getSaldoDistribution($pdo);
$top_siswa = getTopSiswaByBalance($pdo, 10);
$weekly_trend = getWeeklyTrend($pdo, 12);
$transaksi_per_kelas = getTransaksiPerKelas($pdo);

require_once '../includes/sidebar.php';
?>

<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-line me-2"></i>Analytics & Dashboard
        </h1>
    </div>

    <!-- Date Filter -->
    <div class="card shadow mb-4">
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-3">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $start_date ?>">
                </div>
                <div class="col-md-3">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $end_date ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">Filter</button>
                </div>
                <div class="col-md-2">
                    <button type="button" class="btn btn-outline-secondary w-100" onclick="window.print()">
                        <i class="fas fa-print me-1"></i>Print
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Statistics Cards -->
    <div class="row mb-4">
        <div class="col-md-3">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Siswa</div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total_siswa']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Saldo</div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800"><?= formatRupiah($stats['total_saldo']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Transaksi</div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800"><?= number_format($stats['total_transaksi']) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Rata-rata Saldo</div>
                    <div class="h3 mb-0 font-weight-bold text-gray-800"><?= formatRupiah($stats['rata_rata_saldo']) ?></div>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 1 -->
    <div class="row mb-4">
        <!-- Transaksi Per Hari Chart -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Transaksi Per Hari</h6>
                </div>
                <div class="card-body">
                    <canvas id="transactionChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Saldo Distribution Chart -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Distribusi Saldo Siswa</h6>
                </div>
                <div class="card-body">
                    <canvas id="saldoChart" height="300"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Charts Row 2 -->
    <div class="row mb-4">
        <!-- Top Siswa by Balance -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Top 10 Siswa Dengan Saldo Terbesar</h6>
                </div>
                <div class="card-body">
                    <canvas id="topSiswaChart" height="300"></canvas>
                </div>
            </div>
        </div>

        <!-- Transaksi Per Kelas -->
        <div class="col-lg-6">
            <div class="card shadow mb-4">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">Transaksi Per Kelas</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm">
                            <thead>
                                <tr>
                                    <th>Kelas</th>
                                    <th class="text-end">Jumlah Siswa</th>
                                    <th class="text-end">Transaksi</th>
                                    <th class="text-end">Total Masuk</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($transaksi_per_kelas as $kelas): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($kelas['nama_kelas']) ?></td>
                                        <td class="text-end"><?= $kelas['jumlah_siswa'] ?></td>
                                        <td class="text-end"><?= $kelas['jumlah_transaksi'] ?></td>
                                        <td class="text-end"><?= formatRupiah($kelas['total_masuk']) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Chart.js Script -->
<script src="https://cdn.jsdelivr.net/npm/chart.js@3.9.1/dist/chart.min.js"></script>
<script>
// Data untuk charts
<?php
// Prepare data for transaction per day chart
$dates = [];
$counts = [];
$amounts_in = [];
$amounts_out = [];
foreach ($transaksi_per_hari as $data) {
    $dates[] = date('d/m', strtotime($data['tanggal']));
    $counts[] = $data['jumlah_transaksi'];
    $amounts_in[] = $data['total_masuk'];
    $amounts_out[] = $data['total_keluar'];
}

// Prepare data for saldo distribution
$saldo_ranges = [];
$saldo_counts = [];
foreach ($saldo_distribution as $data) {
    $saldo_ranges[] = $data['range'];
    $saldo_counts[] = $data['jumlah_siswa'];
}

// Prepare data for top siswa
$siswa_names = [];
$siswa_saldos = [];
foreach ($top_siswa as $data) {
    $siswa_names[] = substr($data['nama'], 0, 15);
    $siswa_saldos[] = $data['saldo_akhir'];
}
?>

// Transaksi Per Hari Chart
const transactionCtx = document.getElementById('transactionChart').getContext('2d');
new Chart(transactionCtx, {
    type: 'line',
    data: {
        labels: <?= json_encode($dates) ?>,
        datasets: [
            {
                label: 'Jumlah Transaksi',
                data: <?= json_encode($counts) ?>,
                borderColor: '#4361ee',
                backgroundColor: 'rgba(67, 97, 238, 0.1)',
                borderWidth: 2,
                fill: true,
                tension: 0.4
            },
            {
                label: 'Total Setoran',
                data: <?= json_encode($amounts_in) ?>,
                borderColor: '#28a745',
                borderDash: [5, 5],
                borderWidth: 2,
                fill: false,
                tension: 0.4
            },
            {
                label: 'Total Penarikan',
                data: <?= json_encode($amounts_out) ?>,
                borderColor: '#dc3545',
                borderDash: [5, 5],
                borderWidth: 2,
                fill: false,
                tension: 0.4
            }
        ]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                display: true,
                position: 'top'
            }
        },
        scales: {
            y: {
                beginAtZero: true
            }
        }
    }
});

// Saldo Distribution Chart
const saldoCtx = document.getElementById('saldoChart').getContext('2d');
new Chart(saldoCtx, {
    type: 'doughnut',
    data: {
        labels: <?= json_encode($saldo_ranges) ?>,
        datasets: [{
            data: <?= json_encode($saldo_counts) ?>,
            backgroundColor: [
                '#4361ee',
                '#3f37c9',
                '#4895ef',
                '#00b4d8',
                '#90e0ef'
            ],
            borderColor: '#fff',
            borderWidth: 2
        }]
    },
    options: {
        responsive: true,
        plugins: {
            legend: {
                position: 'bottom'
            }
        }
    }
});

// Top Siswa Chart
const topSiswaCtx = document.getElementById('topSiswaChart').getContext('2d');
new Chart(topSiswaCtx, {
    type: 'bar',
    data: {
        labels: <?= json_encode($siswa_names) ?>,
        datasets: [{
            label: 'Saldo Akhir (Rp)',
            data: <?= json_encode($siswa_saldos) ?>,
            backgroundColor: '#4361ee',
            borderColor: '#3f37c9',
            borderWidth: 1
        }]
    },
    options: {
        indexAxis: 'y',
        responsive: true,
        scales: {
            x: {
                beginAtZero: true
            }
        },
        plugins: {
            legend: {
                display: false
            }
        }
    }
});
</script>

<?php require_once '../includes/footer_sidebar.php'; ?>
