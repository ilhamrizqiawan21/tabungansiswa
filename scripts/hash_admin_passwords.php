<?php
/**
 * MIGRATION SCRIPT: Hash Password Admin yang Sudah Ada
 * 
 * Script ini dijalankan SEKALI untuk mengkonversi password admin
 * dari plaintext menjadi hash password yang secure
 * 
 * CARA MENJALANKAN:
 * 1. Buka browser: http://localhost/tabungansiswa/scripts/hash_admin_passwords.php
 * 2. Lihat hasilnya
 * 3. Hapus file ini setelah selesai (untuk security)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

// SECURITY: Check apakah file ini diakses dari localhost saja
$allowedIPs = ['127.0.0.1', '::1'];
$clientIP = getRealUserIP();

if (!in_array($clientIP, $allowedIPs)) {
    http_response_code(403);
    die('Access Denied. This script can only be run from localhost.');
}

// Verifikasi menggunakan token khusus
$expectedToken = 'HASH_ADMIN_' . date('Y-m-d');
$providedToken = $_GET['token'] ?? '';

if ($providedToken !== $expectedToken) {
    echo '<style>body{font-family:Arial;margin:20px;}</style>';
    echo '<h2>⚠️ Script Hash Password Admin</h2>';
    echo '<p>Script ini mengubah password admin dari plaintext ke hash yang aman.</p>';
    echo '<p><strong>Token untuk hari ini (24 jam):</strong> <code>' . $expectedToken . '</code></p>';
    echo '<p><a href="?token=' . $expectedToken . '" class="btn">Run Migration</a></p>';
    echo '<p><small>*Token berubah setiap hari untuk keamanan</small></p>';
    exit;
}

?>
<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hash Admin Passwords</title>
    <style>
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            max-width: 800px;
            margin: 40px auto;
            padding: 20px;
            background: #f5f5f5;
        }
        .container {
            background: white;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }
        h1 {
            color: #333;
            border-bottom: 3px solid #4361ee;
            padding-bottom: 10px;
        }
        .status {
            padding: 15px;
            margin: 15px 0;
            border-radius: 5px;
            border-left: 4px solid;
        }
        .status.success {
            background: #d4edda;
            border-left-color: #28a745;
            color: #155724;
        }
        .status.info {
            background: #d1ecf1;
            border-left-color: #17a2b8;
            color: #0c5460;
        }
        .status.warning {
            background: #fff3cd;
            border-left-color: #ffc107;
            color: #856404;
        }
        .status.error {
            background: #f8d7da;
            border-left-color: #f5c6cb;
            color: #721c24;
        }
        .results {
            background: #f9f9f9;
            border: 1px solid #ddd;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
            max-height: 300px;
            overflow-y: auto;
            font-family: 'Courier New', monospace;
            font-size: 12px;
        }
        .result-item {
            padding: 8px;
            border-bottom: 1px solid #eee;
            margin-bottom: 5px;
        }
        .result-item:last-child {
            border-bottom: none;
        }
        .result-item strong {
            color: #4361ee;
        }
        .warning-box {
            background: #fff3cd;
            border: 2px solid #ffc107;
            padding: 15px;
            border-radius: 5px;
            margin: 20px 0;
        }
        .action-box {
            margin-top: 30px;
            padding: 15px;
            background: #e8f4f8;
            border-radius: 5px;
            border-left: 4px solid #17a2b8;
        }
        code {
            background: #f4f4f4;
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔐 Hash Admin Passwords Migration</h1>
        
        <?php
        try {
            // 1. Ambil semua admin dengan password plaintext
            $stmt = $pdo->query("SELECT id, username, password, nama FROM admin WHERE password IS NOT NULL");
            $admins = $stmt->fetchAll(PDO::FETCH_ASSOC);
            
            if (empty($admins)) {
                echo '<div class="status info">✓ Tidak ada admin yang perlu di-hash (semua sudah ter-hash atau kosong)</div>';
            } else {
                echo '<div class="status info">Ditemukan ' . count($admins) . ' admin yang perlu di-update</div>';
                
                $results = [];
                $updated = 0;
                $skipped = 0;
                
                foreach ($admins as $admin) {
                    // Check apakah sudah hash (hash dimulai dengan '$' dan panjang > 50)
                    if (password_get_info($admin['password'])['algo'] !== 0 || strlen($admin['password']) > 50) {
                        $skipped++;
                        $results[] = "⊘ Skipped: {$admin['username']} (sudah ter-hash)";
                        continue;
                    }
                    
                    // Hash password
                    $hashed = hashPassword($admin['password']);
                    
                    // Update ke database
                    $updateStmt = $pdo->prepare("UPDATE admin SET password = ? WHERE id = ?");
                    $updateStmt->execute([$hashed, $admin['id']]);
                    
                    $updated++;
                    $results[] = "✓ Updated: {$admin['username']} (ID: {$admin['id']})";
                }
                
                // Display results
                echo '<div class="status success">
                    <strong>✓ Migration Selesai!</strong><br>
                    Updated: ' . $updated . ' | Skipped: ' . $skipped . ' | Total: ' . count($admins) . '
                </div>';
                
                if (!empty($results)) {
                    echo '<div class="results">';
                    foreach ($results as $result) {
                        echo '<div class="result-item">' . htmlspecialchars($result) . '</div>';
                    }
                    echo '</div>';
                }
                
                if ($updated > 0) {
                    echo '<div class="status success">
                        <strong>✓ Password berhasil di-hash!</strong><br>
                        Semua admin dapat login dengan password mereka seperti biasa.
                    </div>';
                }
            }
            
            // 2. Show current admin info
            echo '<hr style="margin: 30px 0;">';
            echo '<h2>📋 Admin yang Tersimpan</h2>';
            
            $allAdmins = $pdo->query("SELECT id, username, nama, created_at FROM admin")->fetchAll(PDO::FETCH_ASSOC);
            
            if (!empty($allAdmins)) {
                echo '<table style="width:100%; border-collapse: collapse; margin-top: 10px;">';
                echo '<tr style="background: #f0f0f0;">';
                echo '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">ID</th>';
                echo '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Username</th>';
                echo '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Nama</th>';
                echo '<th style="padding: 10px; text-align: left; border: 1px solid #ddd;">Created</th>';
                echo '</tr>';
                
                foreach ($allAdmins as $admin) {
                    echo '<tr>';
                    echo '<td style="padding: 10px; border: 1px solid #ddd;">' . $admin['id'] . '</td>';
                    echo '<td style="padding: 10px; border: 1px solid #ddd;"><code>' . htmlspecialchars($admin['username']) . '</code></td>';
                    echo '<td style="padding: 10px; border: 1px solid #ddd;">' . htmlspecialchars($admin['nama']) . '</td>';
                    echo '<td style="padding: 10px; border: 1px solid #ddd;">' . $admin['created_at'] . '</td>';
                    echo '</tr>';
                }
                
                echo '</table>';
            }
            
        } catch (Exception $e) {
            echo '<div class="status error">
                <strong>❌ Error:</strong> ' . htmlspecialchars($e->getMessage()) . '
            </div>';
        }
        ?>
        
        <div class="action-box">
            <strong>⚠️ Langkah Selanjutnya:</strong>
            <ol>
                <li>Verifikasi hasil di atas</li>
                <li>Test login dengan credential admin</li>
                <li><strong>Hapus file ini</strong> dari server (untuk keamanan)</li>
                <li>Jalankan update keamanan lainnya</li>
            </ol>
        </div>
        
        <div class="warning-box">
            <strong>⚠️ PENTING:</strong><br>
            File script ini (<code>hash_admin_passwords.php</code>) harus dihapus dari server 
            setelah migration selesai untuk alasan keamanan. Jangan tinggalkan di production!
        </div>
    </div>
</body>
</html>
<?php
