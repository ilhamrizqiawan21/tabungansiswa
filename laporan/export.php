<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();

// Load autoloader untuk PHPSpreadsheet
require_once '../vendor/autoload.php';

use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Fill;

// Filter berdasarkan bulan (untuk laporan bulanan)
$bulan = $_GET['bulan'] ?? date('Y-m');

// Query data transaksi bulanan dengan JOIN ke tabel kelas
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
    WHERE DATE_FORMAT(t.tanggal, '%Y-%m') = ?
    ORDER BY t.tanggal DESC, t.id DESC
");
$stmt->execute([$bulan]);
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

// Buat spreadsheet baru
$spreadsheet = new Spreadsheet();
$sheet = $spreadsheet->getActiveSheet();

// Set judul laporan
$sheet->setCellValue('A1', 'LAPORAN BULANAN TABUNGAN SISWA');
$sheet->mergeCells('A1:I1');
$sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16);
$sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A2', 'Bulan: ' . date('F Y', strtotime($bulan . '-01')));
$sheet->mergeCells('A2:I2');
$sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

$sheet->setCellValue('A3', 'Dicetak pada: ' . date('d/m/Y H:i:s'));
$sheet->mergeCells('A3:I3');
$sheet->getStyle('A3')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

// Header tabel
$headers = ['No', 'Tanggal', 'NIS', 'Nama Siswa', 'Kelas', 'Jenis', 'Jumlah', 'Saldo', 'Keterangan'];
$column = 'A';
foreach ($headers as $header) {
    $sheet->setCellValue($column . '5', $header);
    $sheet->getColumnDimension($column)->setAutoSize(true);
    $column++;
}

// Style header tabel
$headerStyle = [
    'font' => ['bold' => true, 'color' => ['rgb' => 'FFFFFF']],
    'fill' => ['fillType' => Fill::FILL_SOLID, 'startColor' => ['rgb' => '4CAF50']],
    'alignment' => ['horizontal' => Alignment::HORIZONTAL_CENTER],
    'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]]
];
$sheet->getStyle('A5:I5')->applyFromArray($headerStyle);

// Isi data transaksi
$row = 6;
$no = 1;

if (empty($transaksi)) {
    $sheet->setCellValue('A' . $row, 'Tidak ada transaksi pada bulan ini');
    $sheet->mergeCells('A' . $row . ':I' . $row);
    $sheet->getStyle('A' . $row)->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
} else {
    foreach ($transaksi as $t) {
        $sheet->setCellValue('A' . $row, $no);
        $sheet->setCellValue('B' . $row, date('d/m/Y', strtotime($t['tanggal'])));
        $sheet->setCellValue('C' . $row, $t['nis']);
        $sheet->setCellValue('D' . $row, $t['nama_siswa']);
        
        // Format kelas: tingkat-nama_kelas (contoh: 8-VIII-E)
        $kelas = $t['nama_kelas'] ? $t['tingkat'] . '-' . $t['nama_kelas'] : '-';
        $sheet->setCellValue('E' . $row, $kelas);
        
        $sheet->setCellValue('F' . $row, $t['jenis'] == 'masuk' ? 'Setoran' : 'Penarikan');
        $sheet->setCellValue('G' . $row, $t['jumlah']);
        $sheet->setCellValue('H' . $row, $t['saldo']);
        $sheet->setCellValue('I' . $row, $t['keterangan'] ?? '-');
        
        // Format angka untuk kolom jumlah dan saldo
        $sheet->getStyle('G' . $row . ':H' . $row)->getNumberFormat()->setFormatCode('#,##0');
        
        // Warna untuk jenis transaksi
        if ($t['jenis'] == 'masuk') {
            $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('2E7D32');
        } else {
            $sheet->getStyle('F' . $row)->getFont()->getColor()->setRGB('C62828');
        }
        
        $row++;
        $no++;
    }
    
    // Style untuk data
    $dataStyle = [
        'borders' => ['allBorders' => ['borderStyle' => Border::BORDER_THIN]],
        'alignment' => ['vertical' => Alignment::VERTICAL_TOP]
    ];
    $sheet->getStyle('A6:I' . ($row-1))->applyFromArray($dataStyle);
    
    // Rekapitulasi
    $row++;
    $sheet->setCellValue('A' . $row, 'REKAPITULASI');
    $sheet->getStyle('A' . $row)->getFont()->setBold(true);
    $sheet->mergeCells('A' . $row . ':B' . $row);
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Total Setoran:');
    $sheet->setCellValue('B' . $row, $total_masuk);
    $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Total Penarikan:');
    $sheet->setCellValue('B' . $row, $total_keluar);
    $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true);
    $row++;
    
    $sheet->setCellValue('A' . $row, 'Saldo Bersih:');
    $sheet->setCellValue('B' . $row, $total_masuk - $total_keluar);
    $sheet->getStyle('B' . $row)->getNumberFormat()->setFormatCode('#,##0');
    $sheet->getStyle('A' . $row . ':B' . $row)->getFont()->setBold(true)->getColor()->setRGB('1976D2');
}

// Set header untuk download
$filename = 'Laporan_Tabungan_' . date('F_Y', strtotime($bulan . '-01')) . '.xlsx';

header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
header('Content-Disposition: attachment;filename="' . $filename . '"');
header('Cache-Control: max-age=0');

$writer = new Xlsx($spreadsheet);
$writer->save('php://output');
exit;