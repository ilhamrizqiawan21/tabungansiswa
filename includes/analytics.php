<?php
/**
 * ANALYTICS & STATISTICS MODULE
 * 
 * Handles data analytics dan statistics untuk dashboard
 * Menyediakan data untuk charts dan reporting
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Get dashboard summary statistics
 */
function getDashboardStats($pdo, $tahun_pelajaran_id = null) {
    try {
        $results = [];
        
        // Total siswa
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM siswa");
        $results['total_siswa'] = $stmt->fetchColumn();
        
        // Total saldo tabungan
        $stmt = $pdo->query("
            SELECT COALESCE(SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE -jumlah END), 0) as total
            FROM transaksi
        ");
        $results['total_saldo'] = $stmt->fetchColumn();
        
        // Transaksi hari ini
        $stmt = $pdo->query("
            SELECT COUNT(*) as total
            FROM transaksi
            WHERE DATE(tanggal) = CURDATE()
        ");
        $results['transaksi_hari_ini'] = $stmt->fetchColumn();
        
        // Total transaksi
        $stmt = $pdo->query("SELECT COUNT(*) as total FROM transaksi");
        $results['total_transaksi'] = $stmt->fetchColumn();
        
        // Rata-rata saldo per siswa
        $stmt = $pdo->query("
            SELECT AVG(saldo_akhir) as avg_saldo
            FROM (
                SELECT 
                    siswa_id,
                    MAX(saldo) as saldo_akhir
                FROM transaksi
                GROUP BY siswa_id
            ) as last_balance
        ");
        $results['rata_rata_saldo'] = $stmt->fetchColumn() ?? 0;
        
        return $results;
        
    } catch (Exception $e) {
        error_log("Dashboard stats error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get transaksi per hari (untuk line chart)
 */
function getTransaksiPerHari($pdo, $start_date, $end_date) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                DATE(tanggal) as tanggal,
                COUNT(*) as jumlah_transaksi,
                SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END) as total_masuk,
                SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END) as total_keluar
            FROM transaksi
            WHERE DATE(tanggal) BETWEEN ? AND ?
            GROUP BY DATE(tanggal)
            ORDER BY tanggal
        ");
        
        $stmt->execute([$start_date, $end_date]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Transaksi per hari error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get saldo distribution (untuk bar chart)
 */
function getSaldoDistribution($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                CASE 
                    WHEN saldo_akhir < 100000 THEN '< 100k'
                    WHEN saldo_akhir < 500000 THEN '100k - 500k'
                    WHEN saldo_akhir < 1000000 THEN '500k - 1jt'
                    WHEN saldo_akhir < 2000000 THEN '1jt - 2jt'
                    ELSE '> 2jt'
                END as range,
                COUNT(*) as jumlah_siswa
            FROM (
                SELECT 
                    siswa_id,
                    MAX(saldo) as saldo_akhir
                FROM transaksi
                GROUP BY siswa_id
            ) as t
            GROUP BY range
            ORDER BY saldo_akhir
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Saldo distribution error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get top siswa by saldo
 */
function getTopSiswaByBalance($pdo, $limit = 10) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                s.id,
                s.nis,
                s.nama,
                k.nama_kelas,
                MAX(t.saldo) as saldo_akhir,
                COUNT(t.id) as jumlah_transaksi
            FROM siswa s
            LEFT JOIN transaksi t ON s.id = t.siswa_id
            LEFT JOIN kelas k ON s.kelas_id = k.id
            GROUP BY s.id
            ORDER BY saldo_akhir DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Top siswa error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get transaksi trend per minggu
 */
function getWeeklyTrend($pdo, $weeks = 12) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                WEEK(tanggal) as week,
                YEAR(tanggal) as year,
                DATE_FORMAT(tanggal, '%Y-W%u') as week_label,
                COUNT(*) as jumlah_transaksi,
                SUM(CASE WHEN jenis = 'masuk' THEN jumlah ELSE 0 END) as total_masuk,
                SUM(CASE WHEN jenis = 'keluar' THEN jumlah ELSE 0 END) as total_keluar
            FROM transaksi
            WHERE tanggal >= DATE_SUB(CURDATE(), INTERVAL ? WEEK)
            GROUP BY YEAR(tanggal), WEEK(tanggal)
            ORDER BY year, week
        ");
        
        $stmt->execute([$weeks]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Weekly trend error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get transaksi per kelas
 */
function getTransaksiPerKelas($pdo) {
    try {
        $stmt = $pdo->query("
            SELECT 
                k.nama_kelas,
                k.tingkat,
                COUNT(t.id) as jumlah_transaksi,
                COUNT(DISTINCT s.id) as jumlah_siswa,
                SUM(CASE WHEN t.jenis = 'masuk' THEN t.jumlah ELSE 0 END) as total_masuk,
                SUM(CASE WHEN t.jenis = 'keluar' THEN t.jumlah ELSE 0 END) as total_keluar,
                AVG(t.jumlah) as rata_rata_transaksi
            FROM kelas k
            LEFT JOIN siswa s ON k.id = s.kelas_id
            LEFT JOIN transaksi t ON s.id = t.siswa_id
            GROUP BY k.id
            ORDER BY k.tingkat, k.nama_kelas
        ");
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Transaksi per kelas error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get jenis transaksi summary
 */
function getTransaksiSummary($pdo, $start_date = null, $end_date = null) {
    try {
        $where = "";
        $params = [];
        
        if ($start_date && $end_date) {
            $where = "WHERE DATE(tanggal) BETWEEN ? AND ?";
            $params = [$start_date, $end_date];
        }
        
        $stmt = $pdo->prepare("
            SELECT 
                jenis,
                COUNT(*) as jumlah_transaksi,
                SUM(jumlah) as total_jumlah,
                AVG(jumlah) as rata_rata,
                MIN(jumlah) as min,
                MAX(jumlah) as max
            FROM transaksi
            $where
            GROUP BY jenis
        ");
        
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Transaksi summary error: " . $e->getMessage());
        return [];
    }
}

/**
 * Format chart data untuk Chart.js
 */
function formatChartData($data, $label_key, $value_key) {
    $labels = [];
    $values = [];
    
    foreach ($data as $item) {
        $labels[] = $item[$label_key];
        $values[] = $item[$value_key];
    }
    
    return [
        'labels' => $labels,
        'data' => $values
    ];
}

/**
 * Get custom report
 */
function generateReport($pdo, $report_type, $filters = []) {
    try {
        $start_date = $filters['start_date'] ?? null;
        $end_date = $filters['end_date'] ?? null;
        $kelas_id = $filters['kelas_id'] ?? null;
        $jenis = $filters['jenis'] ?? null;
        
        $where = "1=1";
        $params = [];
        
        if ($start_date && $end_date) {
            $where .= " AND DATE(t.tanggal) BETWEEN ? AND ?";
            $params[] = $start_date;
            $params[] = $end_date;
        }
        
        if ($kelas_id) {
            $where .= " AND s.kelas_id = ?";
            $params[] = $kelas_id;
        }
        
        if ($jenis) {
            $where .= " AND t.jenis = ?";
            $params[] = $jenis;
        }
        
        $sql = "
            SELECT 
                t.id,
                s.nis,
                s.nama,
                k.nama_kelas,
                t.tanggal,
                t.jenis,
                t.jumlah,
                t.saldo,
                t.keterangan
            FROM transaksi t
            JOIN siswa s ON t.siswa_id = s.id
            LEFT JOIN kelas k ON s.kelas_id = k.id
            WHERE $where
            ORDER BY t.tanggal DESC
        ";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Generate report error: " . $e->getMessage());
        return [];
    }
}

?>
