<?php
/**
 * PRINT MODULE
 * 
 * Handles printing format untuk:
 * - Buku tabungan siswa
 * - Laporan transaksi
 * - Laporan per siswa
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/functions.php';

/**
 * Get siswa dengan transaksi lengkap untuk print
 */
function getSiswaForPrint($pdo, $siswa_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                s.*,
                k.nama_kelas,
                k.tingkat,
                tp.tahun as tahun_pelajaran
            FROM siswa s
            LEFT JOIN kelas k ON s.kelas_id = k.id
            LEFT JOIN tahun_pelajaran tp ON k.tahun_pelajaran_id = tp.id
            WHERE s.id = ?
        ");
        
        $stmt->execute([$siswa_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get siswa for print error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get transaksi untuk print
 */
function getTransaksiForPrint($pdo, $siswa_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT *
            FROM transaksi
            WHERE siswa_id = ?
            ORDER BY tanggal ASC, id ASC
        ");
        
        $stmt->execute([$siswa_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get transaksi for print error: " . $e->getMessage());
        return [];
    }
}

/**
 * Generate HTML print format untuk buku tabungan
 */
function generateBukuTabunganHTML($pdo, $siswa_id) {
    $siswa = getSiswaForPrint($pdo, $siswa_id);
    $transaksi = getTransaksiForPrint($pdo, $siswa_id);
    
    if (!$siswa) {
        return '<p>Data siswa tidak ditemukan</p>';
    }
    
    $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Buku Tabungan - {$siswa['nama']}</title>
    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Arial', sans-serif;
            color: #333;
            background: #f5f5f5;
        }
        
        .page {
            width: 21cm;
            height: 29.7cm;
            margin: 10mm auto;
            padding: 15mm;
            background: white;
            box-shadow: 0 0 10px rgba(0,0,0,0.1);
            page-break-after: always;
        }
        
        .header {
            text-align: center;
            margin-bottom: 20px;
            border-bottom: 3px solid #333;
            padding-bottom: 10px;
        }
        
        .header h1 {
            font-size: 24px;
            margin-bottom: 5px;
            color: #4361ee;
        }
        
        .header p {
            font-size: 12px;
            color: #666;
        }
        
        .student-info {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
            font-size: 13px;
        }
        
        .info-group {
            display: grid;
            grid-template-columns: 100px 1fr;
            gap: 10px;
        }
        
        .info-label {
            font-weight: bold;
            color: #4361ee;
        }
        
        .info-value {
            border-bottom: 1px solid #ccc;
            padding-bottom: 2px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 12px;
        }
        
        table thead {
            background: #4361ee;
            color: white;
        }
        
        table th {
            padding: 8px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        table td {
            padding: 6px;
            border: 1px solid #ddd;
        }
        
        table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .text-center {
            text-align: center;
        }
        
        .text-right {
            text-align: right;
        }
        
        .amount {
            font-family: 'Courier New', monospace;
        }
        
        .jenis-masuk {
            color: #28a745;
            font-weight: bold;
        }
        
        .jenis-keluar {
            color: #dc3545;
            font-weight: bold;
        }
        
        .summary {
            margin-top: 20px;
            padding: 10px;
            background: #f0f0f0;
            border-radius: 5px;
            font-size: 13px;
        }
        
        .summary-row {
            display: flex;
            justify-content: space-between;
            margin-bottom: 5px;
        }
        
        .summary-label {
            font-weight: bold;
        }
        
        .summary-value {
            text-align: right;
        }
        
        .footer {
            margin-top: 20px;
            text-align: right;
            font-size: 11px;
            color: #666;
        }
        
        @media print {
            body {
                background: white;
            }
            .page {
                margin: 0;
                box-shadow: none;
                padding: 0;
            }
        }
    </style>
</head>
<body>
    <div class="page">
        <div class="header">
            <h1>📚 BUKU TABUNGAN SISWA</h1>
            <p>Sekolah Menengah Pertama</p>
        </div>
        
        <div class="student-info">
            <div class="info-group">
                <div class="info-label">NIS</div>
                <div class="info-value">{$siswa['nis']}</div>
            </div>
            <div class="info-group">
                <div class="info-label">Tanggal Cetak</div>
                <div class="info-value">HTML
                . date('d/m/Y H:i') . HTML
                </div>
            </div>
            
            <div class="info-group">
                <div class="info-label">Nama</div>
                <div class="info-value">{$siswa['nama']}</div>
            </div>
            <div class="info-group">
                <div class="info-label">Kelas</div>
                <div class="info-value">{$siswa['nama_kelas']} - Tingkat {$siswa['tingkat']}</div>
            </div>
        </div>
        
        <table>
            <thead>
                <tr>
                    <th>No</th>
                    <th>Tanggal</th>
                    <th>Keterangan</th>
                    <th class="text-right">Setoran</th>
                    <th class="text-right">Penarikan</th>
                    <th class="text-right">Saldo</th>
                </tr>
            </thead>
            <tbody>
HTML;
    
    if (empty($transaksi)) {
        $html .= '<tr><td colspan="6" class="text-center">Tidak ada transaksi</td></tr>';
    } else {
        $no = 1;
        foreach ($transaksi as $t) {
            $tanggal = date('d/m/Y', strtotime($t['tanggal']));
            $setoran = $t['jenis'] === 'masuk' ? formatRupiah($t['jumlah']) : '-';
            $penarikan = $t['jenis'] === 'keluar' ? formatRupiah($t['jumlah']) : '-';
            $saldo = formatRupiah($t['saldo']);
            
            $html .= <<<HTML
                <tr>
                    <td class="text-center">{$no}</td>
                    <td>{$tanggal}</td>
                    <td>{$t['keterangan']}</td>
                    <td class="text-right amount">{$setoran}</td>
                    <td class="text-right amount">{$penarikan}</td>
                    <td class="text-right amount"><strong>{$saldo}</strong></td>
                </tr>
HTML;
            $no++;
        }
    }
    
    // Calculate totals
    $total_masuk = 0;
    $total_keluar = 0;
    $saldo_akhir = 0;
    
    foreach ($transaksi as $t) {
        if ($t['jenis'] === 'masuk') {
            $total_masuk += $t['jumlah'];
        } else {
            $total_keluar += $t['jumlah'];
        }
        $saldo_akhir = $t['saldo'];
    }
    
    $html .= <<<HTML
            </tbody>
        </table>
        
        <div class="summary">
            <div class="summary-row">
                <span class="summary-label">Total Setoran:</span>
                <span class="summary-value amount">
                    <span class="jenis-masuk">+ HTML
                    . formatRupiah($total_masuk) . HTML
                    </span>
                </span>
            </div>
            <div class="summary-row">
                <span class="summary-label">Total Penarikan:</span>
                <span class="summary-value amount">
                    <span class="jenis-keluar">- HTML
                    . formatRupiah($total_keluar) . HTML
                    </span>
                </span>
            </div>
            <div class="summary-row" style="font-size: 14px; font-weight: bold; border-top: 2px solid #333; padding-top: 8px; margin-top: 8px;">
                <span>Saldo Akhir:</span>
                <span class="summary-value amount" style="color: #4361ee;">
                    HTML
                    . formatRupiah($saldo_akhir) . HTML
                </span>
            </div>
        </div>
        
        <div class="footer">
            <p>Dicetak pada: HTML
            . date('d/m/Y H:i:s') . HTML
            </p>
        </div>
    </div>
    
    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>
HTML;
    
    return $html;
}

/**
 * Generate summary report HTML
 */
function generateSummaryReportHTML($pdo, $title, $data_array, $periode = '') {
    $html = <<<HTML
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <title>$title</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 20px;
            color: #333;
        }
        
        .report-header {
            text-align: center;
            margin-bottom: 30px;
            border-bottom: 2px solid #333;
            padding-bottom: 10px;
        }
        
        .report-header h1 {
            margin: 0;
            font-size: 20px;
        }
        
        .report-header p {
            margin: 5px 0;
            font-size: 13px;
            color: #666;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
        }
        
        table thead {
            background: #4361ee;
            color: white;
        }
        
        table th {
            padding: 10px;
            text-align: left;
            border: 1px solid #ddd;
        }
        
        table td {
            padding: 8px;
            border: 1px solid #ddd;
        }
        
        table tbody tr:nth-child(even) {
            background: #f9f9f9;
        }
        
        .text-right {
            text-align: right;
        }
        
        .text-center {
            text-align: center;
        }
        
        .print-footer {
            text-align: right;
            font-size: 12px;
            color: #666;
            margin-top: 40px;
        }
        
        @media print {
            body {
                margin: 0;
            }
        }
    </style>
</head>
<body>
    <div class="report-header">
        <h1>$title</h1>
        <p>HTML
        . (!empty($periode) ? "Periode: $periode" : "Laporan Transaksi Siswa") . HTML
        </p>
        <p>Dicetak: HTML
        . date('d/m/Y H:i:s') . HTML
        </p>
    </div>
    
    <table>
        <thead>
            <tr>
                <th>No</th>
                <th>Nama Siswa</th>
                <th>NIS</th>
                <th class="text-right">Total Setoran</th>
                <th class="text-right">Total Penarikan</th>
                <th class="text-right">Saldo Akhir</th>
            </tr>
        </thead>
        <tbody>
HTML;
    
    $no = 1;
    foreach ($data_array as $item) {
        $html .= <<<HTML
            <tr>
                <td class="text-center">{$no}</td>
                <td>{$item['nama']}</td>
                <td>{$item['nis']}</td>
                <td class="text-right">{$item['total_setoran']}</td>
                <td class="text-right">{$item['total_penarikan']}</td>
                <td class="text-right"><strong>{$item['saldo_akhir']}</strong></td>
            </tr>
HTML;
        $no++;
    }
    
    $html .= <<<HTML
        </tbody>
    </table>
    
    <div class="print-footer">
        <p>Laporan ini dicetak secara otomatis dari Sistem Tabungan Siswa</p>
    </div>
    
    <script>
        window.addEventListener('load', function() {
            window.print();
        });
    </script>
</body>
</html>
HTML;
    
    return $html;
}

?>
