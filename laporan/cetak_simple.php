<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();

// Filter tanggal
$start_date = $_GET['start_date'] ?? date('Y-m-01');
$end_date = $_GET['end_date'] ?? date('Y-m-t');

// Query data transaksi dengan JOIN ke tabel kelas
$stmt = $pdo->prepare("
    SELECT 
        t.tanggal,
        s.nis,
        s.nama as nama_siswa,
        k.nama_kelas,
        k.tingkat,
        t.jenis,
        t.jumlah,
        t.saldo,
        t.keterangan
    FROM transaksi t
    JOIN siswa s ON t.siswa_id = s.id
    LEFT JOIN kelas k ON s.kelas_id = k.id
    WHERE t.tanggal BETWEEN ? AND ?
    ORDER BY t.tanggal DESC, t.id DESC
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

// Header untuk HTML
header("Content-Type: text/html; charset=UTF-8");
?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Cetak Laporan Tabungan</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 20px; }
        .header { text-align: center; margin-bottom: 20px; }
        .header h1 { margin: 0; font-size: 20px; }
        .header p { margin: 5px 0 0; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; }
        th, td { border: 1px solid #ddd; padding: 8px; text-align: left; }
        th { background-color: #f2f2f2; }
        .text-right { text-align: right; }
        .text-center { text-align: center; }
        .summary { margin-top: 20px; display: flex; justify-content: space-between; }
        .summary-box { border: 1px solid #ddd; padding: 10px; width: 30%; text-align: center; }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
            .summary-box { border: 1px solid #000; }
            th, td { border: 1px solid #000; }
        }
    </style>
</head>
<body>
    <div class="header">
        <h1>LAPORAN TRANSAKSI TABUNGAN SISWA</h1>
        <p>Periode: <?= date('d/m/Y', strtotime($start_date)) ?> - <?= date('d/m/Y', strtotime($end_date)) ?></p>
        <p>Dicetak pada: <?= date('d/m/Y H:i:s') ?></p>
    </div>

    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Tanggal</th>
                <th>NIS</th>
                <th>Nama Siswa</th>
                <th>Kelas</th>
                <th>Jenis</th>
                <th class="text-right">Jumlah</th>
                <th class="text-right">Saldo</th>
                <th>Keterangan</th>
            </tr>
        </thead>
        <tbody>
            <?php if(empty($transaksi)): ?>
                <tr>
                    <td colspan="9" style="text-align: center;">Tidak ada transaksi pada periode ini</td>
                </tr>
            <?php else: ?>
                <?php foreach($transaksi as $i => $t): ?>
                <tr>
                    <td class="text-center"><?= $i+1 ?></td>
                    <td><?= date('d/m/Y', strtotime($t['tanggal'])) ?></td>
                    <td><?= htmlspecialchars($t['nis']) ?></td>
                    <td><?= htmlspecialchars($t['nama_siswa']) ?></td>
                    <td class="text-center">
                        <?php if ($t['nama_kelas']): ?>
                            <?= htmlspecialchars($t['tingkat']) ?>-<?= htmlspecialchars($t['nama_kelas']) ?>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td class="text-center"><?= $t['jenis'] == 'masuk' ? 'Setoran' : 'Penarikan' ?></td>
                    <td class="text-right"><?= formatRupiah($t['jumlah']) ?></td>
                    <td class="text-right"><?= formatRupiah($t['saldo']) ?></td>
                    <td><?= htmlspecialchars($t['keterangan'] ?? '-') ?></td>
                </tr>
                <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <div class="summary">
        <div class="summary-box">
            <strong>Total Setoran</strong><br>
            <?= formatRupiah($total_masuk) ?>
        </div>
        <div class="summary-box">
            <strong>Total Penarikan</strong><br>
            <?= formatRupiah($total_keluar) ?>
        </div>
        <div class="summary-box">
            <strong>Saldo Bersih</strong><br>
            <?= formatRupiah($total_masuk - $total_keluar) ?>
        </div>
    </div>

    <div class="no-print" style="margin-top: 20px; text-align: center;">
        <button onclick="window.print()" style="padding: 10px 20px; background: #4CAF50; color: white; border: none; cursor: pointer;">
            Cetak Laporan
        </button>
        <button onclick="window.close()" style="padding: 10px 20px; background: #f44336; color: white; border: none; cursor: pointer; margin-left: 10px;">
            Tutup
        </button>
    </div>

    <script>
        // Auto print jika diinginkan (opsional)
        window.onload = function() {
            // window.print(); // Uncomment untuk auto print
        };
    </script>
</body>
</html>