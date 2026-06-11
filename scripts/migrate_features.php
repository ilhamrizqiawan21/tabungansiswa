<?php
/**
 * DATABASE MIGRATION
 * 
 * Script untuk membuat tabel-tabel baru:
 * - audit_log
 * - approval_workflow
 * - transaksi_approval
 * 
 * CARA MENJALANKAN:
 * 1. Buka: http://localhost/tabungansiswa/scripts/migrate_features.php?token=MIGRATE_YYYY-MM-DD
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

// SECURITY: Check localhost only
$allowedIPs = ['127.0.0.1', '::1'];
$clientIP = getRealUserIP();

if (!in_array($clientIP, $allowedIPs)) {
    http_response_code(403);
    die('Access Denied. This script can only be run from localhost.');
}

// Verify token
$expectedToken = 'MIGRATE_' . date('Y-m-d');
$providedToken = $_GET['token'] ?? '';

if ($providedToken !== $expectedToken) {
    echo '<style>body{font-family:Arial;margin:20px;}</style>';
    echo '<h2>⚙️ Database Migration Script</h2>';
    echo '<p>Token untuk hari ini (24 jam):</p>';
    echo '<p><code>' . $expectedToken . '</code></p>';
    echo '<p><a href="?token=' . $expectedToken . '" style="padding:10px 20px; background:#4361ee; color:white; text-decoration:none; border-radius:5px; display:inline-block;">Run Migration</a></p>';
    exit;
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Database Migration</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #f5f5f5; }
        .container { background: white; padding: 30px; border-radius: 10px; max-width: 900px; margin: 0 auto; }
        h1 { color: #333; border-bottom: 3px solid #4361ee; padding-bottom: 10px; }
        .status { padding: 15px; margin: 15px 0; border-radius: 5px; border-left: 4px solid; }
        .status.success { background: #d4edda; border-left-color: #28a745; color: #155724; }
        .status.error { background: #f8d7da; border-left-color: #f5c6cb; color: #721c24; }
        .results { background: #f9f9f9; border: 1px solid #ddd; padding: 15px; border-radius: 5px; margin: 20px 0; max-height: 400px; overflow-y: auto; font-family: monospace; font-size: 12px; }
        .result-item { padding: 8px; border-bottom: 1px solid #eee; }
        .result-item.success { color: #28a745; }
        .result-item.error { color: #721c24; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔄 Database Migration - New Features</h1>
        
        <?php
        $results = [];
        $errors = [];
        
        try {
            // ========================================
            // 1. CREATE audit_log TABLE
            // ========================================
            $sql = "CREATE TABLE IF NOT EXISTS audit_log (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `admin_id` INT,
                `table_name` VARCHAR(100) NOT NULL,
                `record_id` INT,
                `action` ENUM('CREATE', 'UPDATE', 'DELETE') NOT NULL,
                `old_values` JSON,
                `new_values` JSON,
                `description` TEXT,
                `ip_address` VARCHAR(45),
                `user_agent` VARCHAR(500),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE SET NULL,
                INDEX idx_table_action (table_name, action),
                INDEX idx_created_at (created_at),
                INDEX idx_admin_id (admin_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            $results[] = ['status' => 'success', 'message' => '✓ Created table: audit_log'];
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            $results[] = ['status' => 'error', 'message' => '✗ audit_log: ' . $e->getMessage()];
        }
        
        try {
            // ========================================
            // 2. CREATE approval_status TABLE
            // ========================================
            $sql = "CREATE TABLE IF NOT EXISTS approval_status (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `name` VARCHAR(50) NOT NULL UNIQUE,
                `description` VARCHAR(255),
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            
            // Insert default statuses
            $statuses = ['pending', 'approved', 'rejected', 'revised'];
            foreach ($statuses as $status) {
                try {
                    $stmt = $pdo->prepare("INSERT IGNORE INTO approval_status (name) VALUES (?)");
                    $stmt->execute([$status]);
                } catch (Exception $e) {
                    // Already exists
                }
            }
            
            $results[] = ['status' => 'success', 'message' => '✓ Created table: approval_status'];
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            $results[] = ['status' => 'error', 'message' => '✗ approval_status: ' . $e->getMessage()];
        }
        
        try {
            // ========================================
            // 3. CREATE transaksi_approval TABLE
            // ========================================
            $sql = "CREATE TABLE IF NOT EXISTS transaksi_approval (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `transaksi_id` INT NOT NULL,
                `status_id` INT NOT NULL,
                `requested_by` INT NOT NULL,
                `approved_by` INT,
                `rejection_reason` TEXT,
                `request_date` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `approval_date` TIMESTAMP NULL,
                FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
                FOREIGN KEY (status_id) REFERENCES approval_status(id),
                FOREIGN KEY (requested_by) REFERENCES admin(id),
                FOREIGN KEY (approved_by) REFERENCES admin(id) ON DELETE SET NULL,
                INDEX idx_transaksi_id (transaksi_id),
                INDEX idx_status_id (status_id),
                UNIQUE KEY unique_transaksi (transaksi_id)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
            
            $pdo->exec($sql);
            $results[] = ['status' => 'success', 'message' => '✓ Created table: transaksi_approval'];
            
        } catch (Exception $e) {
            $errors[] = $e->getMessage();
            $results[] = ['status' => 'error', 'message' => '✗ transaksi_approval: ' . $e->getMessage()];
        }
        
        try {
            // ========================================
            // 4. ADD COLUMN transaksi.approval_required
            // ========================================
            $stmt = $pdo->query("SHOW COLUMNS FROM transaksi LIKE 'approval_required'");
            if ($stmt->rowCount() == 0) {
                $sql = "ALTER TABLE transaksi ADD COLUMN approval_required BOOLEAN DEFAULT FALSE AFTER keterangan";
                $pdo->exec($sql);
                $results[] = ['status' => 'success', 'message' => '✓ Added column: transaksi.approval_required'];
            } else {
                $results[] = ['status' => 'success', 'message' => '⊘ Column already exists: transaksi.approval_required'];
            }
        } catch (Exception $e) {
            $results[] = ['status' => 'error', 'message' => '✗ transaksi.approval_required: ' . $e->getMessage()];
        }
        
        try {
            // ========================================
            // 5. ADD FOREIGN KEY constraints
            // ========================================
            $stmt = $pdo->query("SELECT CONSTRAINT_NAME FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE WHERE TABLE_NAME = 'transaksi' AND COLUMN_NAME = 'siswa_id' AND REFERENCED_TABLE_NAME = 'siswa'");
            if ($stmt->rowCount() == 0) {
                $sql = "ALTER TABLE transaksi ADD FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE";
                $pdo->exec($sql);
                $results[] = ['status' => 'success', 'message' => '✓ Added foreign key: transaksi.siswa_id'];
            } else {
                $results[] = ['status' => 'success', 'message' => '⊘ Foreign key already exists'];
            }
        } catch (Exception $e) {
            $results[] = ['status' => 'error', 'message' => '✗ Foreign key constraint: ' . $e->getMessage()];
        }
        
        // Display results
        ?>
        
        <div class="results">
            <?php foreach ($results as $result): ?>
                <div class="result-item <?= $result['status'] ?>">
                    <?= htmlspecialchars($result['message']) ?>
                </div>
            <?php endforeach; ?>
        </div>
        
        <?php if (empty($errors)): ?>
            <div class="status success">
                <strong>✓ Migration Berhasil!</strong><br>
                Semua tabel telah dibuat. Siap menggunakan fitur baru.
            </div>
        <?php else: ?>
            <div class="status error">
                <strong>✗ Ada Beberapa Error:</strong><br>
                <?php foreach ($errors as $error): ?>
                    - <?= htmlspecialchars($error) ?><br>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        
    </div>
</body>
</html>
<?php
