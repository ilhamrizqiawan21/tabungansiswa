<?php
/**
 * APPROVAL WORKFLOW MODULE
 * 
 * Handles approval workflow untuk transaksi tertentu
 * Terutama untuk penarikan yang besar
 */

require_once __DIR__ . '/../config/database.php';

/**
 * Request approval untuk transaksi
 */
function requestApproval($pdo, $transaksi_id, $requested_by) {
    try {
        // Get pending status ID
        $stmt = $pdo->prepare("SELECT id FROM approval_status WHERE name = 'pending' LIMIT 1");
        $stmt->execute();
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        $status_id = $status['id'] ?? null;
        
        if (!$status_id) {
            return ['success' => false, 'message' => 'Status tidak ditemukan'];
        }
        
        // Check apakah sudah ada approval request
        $stmt = $pdo->prepare("SELECT id FROM transaksi_approval WHERE transaksi_id = ?");
        $stmt->execute([$transaksi_id]);
        
        if ($stmt->fetch()) {
            return ['success' => false, 'message' => 'Approval request sudah ada'];
        }
        
        // Create approval request
        $stmt = $pdo->prepare("
            INSERT INTO transaksi_approval (transaksi_id, status_id, requested_by) 
            VALUES (?, ?, ?)
        ");
        
        $stmt->execute([$transaksi_id, $status_id, $requested_by]);
        
        return ['success' => true, 'message' => 'Permohonan approval berhasil dikirim'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Approve transaksi
 */
function approveTransaction($pdo, $transaksi_id, $approved_by) {
    try {
        // Get approved status ID
        $stmt = $pdo->prepare("SELECT id FROM approval_status WHERE name = 'approved' LIMIT 1");
        $stmt->execute();
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        $status_id = $status['id'] ?? null;
        
        if (!$status_id) {
            return ['success' => false, 'message' => 'Status tidak ditemukan'];
        }
        
        // Update approval
        $stmt = $pdo->prepare("
            UPDATE transaksi_approval 
            SET status_id = ?, approved_by = ?, approval_date = NOW()
            WHERE transaksi_id = ?
        ");
        
        $stmt->execute([$status_id, $approved_by, $transaksi_id]);
        
        // Mark transaksi as not requiring approval
        $stmt = $pdo->prepare("
            UPDATE transaksi 
            SET approval_required = FALSE 
            WHERE id = ?
        ");
        $stmt->execute([$transaksi_id]);
        
        return ['success' => true, 'message' => 'Transaksi berhasil disetujui'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Reject transaksi
 */
function rejectTransaction($pdo, $transaksi_id, $rejected_by, $reason = '') {
    try {
        // Get rejected status ID
        $stmt = $pdo->prepare("SELECT id FROM approval_status WHERE name = 'rejected' LIMIT 1");
        $stmt->execute();
        $status = $stmt->fetch(PDO::FETCH_ASSOC);
        $status_id = $status['id'] ?? null;
        
        if (!$status_id) {
            return ['success' => false, 'message' => 'Status tidak ditemukan'];
        }
        
        // Update approval
        $stmt = $pdo->prepare("
            UPDATE transaksi_approval 
            SET status_id = ?, approved_by = ?, rejection_reason = ?, approval_date = NOW()
            WHERE transaksi_id = ?
        ");
        
        $stmt->execute([$status_id, $rejected_by, $reason, $transaksi_id]);
        
        return ['success' => true, 'message' => 'Transaksi berhasil ditolak'];
        
    } catch (Exception $e) {
        return ['success' => false, 'message' => 'Error: ' . $e->getMessage()];
    }
}

/**
 * Get approval info untuk transaksi
 */
function getApprovalInfo($pdo, $transaksi_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ta.*,
                ast.name as status_name,
                req.nama as requested_by_name,
                req.username as requested_by_username,
                app.nama as approved_by_name,
                app.username as approved_by_username
            FROM transaksi_approval ta
            LEFT JOIN approval_status ast ON ta.status_id = ast.id
            LEFT JOIN admin req ON ta.requested_by = req.id
            LEFT JOIN admin app ON ta.approved_by = app.id
            WHERE ta.transaksi_id = ?
            LIMIT 1
        ");
        
        $stmt->execute([$transaksi_id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get approval info error: " . $e->getMessage());
        return null;
    }
}

/**
 * Get pending approvals
 */
function getPendingApprovals($pdo, $limit = 50) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ta.*,
                t.siswa_id,
                t.tanggal,
                t.jenis,
                t.jumlah,
                t.keterangan,
                s.nama as siswa_nama,
                s.nis,
                req.nama as requested_by_name,
                req.username as requested_by_username
            FROM transaksi_approval ta
            JOIN transaksi t ON ta.transaksi_id = t.id
            JOIN siswa s ON t.siswa_id = s.id
            LEFT JOIN admin req ON ta.requested_by = req.id
            WHERE ta.status_id = (SELECT id FROM approval_status WHERE name = 'pending')
            ORDER BY ta.request_date DESC
            LIMIT ?
        ");
        
        $stmt->execute([$limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get pending approvals error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get approval history untuk transaksi
 */
function getApprovalHistory($pdo, $transaksi_id) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                ta.*,
                ast.name as status_name,
                req.nama as requested_by_name,
                app.nama as approved_by_name
            FROM transaksi_approval ta
            LEFT JOIN approval_status ast ON ta.status_id = ast.id
            LEFT JOIN admin req ON ta.requested_by = req.id
            LEFT JOIN admin app ON ta.approved_by = app.id
            WHERE ta.transaksi_id = ?
            ORDER BY ta.request_date DESC
            LIMIT 10
        ");
        
        $stmt->execute([$transaksi_id]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get approval history error: " . $e->getMessage());
        return [];
    }
}

/**
 * Check if transaksi needs approval
 */
function needsApproval($pdo, $transaksi_id) {
    try {
        $stmt = $pdo->prepare("SELECT approval_required FROM transaksi WHERE id = ?");
        $stmt->execute([$transaksi_id]);
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        
        return $result['approval_required'] ?? false;
        
    } catch (Exception $e) {
        return false;
    }
}

/**
 * Get approval status badge
 */
function getApprovalStatusBadge($status_name) {
    $badges = [
        'pending' => '<span class="badge bg-warning">Menunggu Persetujuan</span>',
        'approved' => '<span class="badge bg-success">Disetujui</span>',
        'rejected' => '<span class="badge bg-danger">Ditolak</span>',
        'revised' => '<span class="badge bg-info">Direvisi</span>'
    ];
    
    return $badges[$status_name] ?? '<span class="badge bg-secondary">Unknown</span>';
}

?>
