<?php
/**
 * AUDIT LOG MODULE
 * 
 * Handles all audit logging untuk tracking data changes
 * Mencatat: CREATE, UPDATE, DELETE operations
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/security.php';

/**
 * Log audit trail untuk CREATE action
 */
function auditLog($pdo, $table_name, $record_id, $action, $new_values = [], $description = '') {
    try {
        $admin_id = $_SESSION['admin_id'] ?? null;
        $ip_address = getRealUserIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_log 
            (admin_id, table_name, record_id, action, new_values, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $admin_id,
            $table_name,
            $record_id,
            $action,
            json_encode($new_values, JSON_UNESCAPED_UNICODE),
            $description,
            $ip_address,
            $user_agent
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log UPDATE action dengan before & after values
 */
function auditLogUpdate($pdo, $table_name, $record_id, $old_values, $new_values, $description = '') {
    try {
        $admin_id = $_SESSION['admin_id'] ?? null;
        $ip_address = getRealUserIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        // Hanya log jika ada perubahan
        $changes = array_diff_assoc($new_values, $old_values);
        if (empty($changes)) {
            return true; // Tidak ada perubahan
        }
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_log 
            (admin_id, table_name, record_id, action, old_values, new_values, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $admin_id,
            $table_name,
            $record_id,
            'UPDATE',
            json_encode($old_values, JSON_UNESCAPED_UNICODE),
            json_encode($new_values, JSON_UNESCAPED_UNICODE),
            $description,
            $ip_address,
            $user_agent
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Log DELETE action
 */
function auditLogDelete($pdo, $table_name, $record_id, $old_values, $description = '') {
    try {
        $admin_id = $_SESSION['admin_id'] ?? null;
        $ip_address = getRealUserIP();
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? '';
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_log 
            (admin_id, table_name, record_id, action, old_values, description, ip_address, user_agent) 
            VALUES (?, ?, ?, ?, ?, ?, ?, ?)
        ");
        
        $stmt->execute([
            $admin_id,
            $table_name,
            $record_id,
            'DELETE',
            json_encode($old_values, JSON_UNESCAPED_UNICODE),
            $description,
            $ip_address,
            $user_agent
        ]);
        
        return true;
    } catch (Exception $e) {
        error_log("Audit log error: " . $e->getMessage());
        return false;
    }
}

/**
 * Get audit trail untuk record tertentu
 */
function getAuditTrail($pdo, $table_name, $record_id, $limit = 50) {
    try {
        $stmt = $pdo->prepare("
            SELECT 
                a.*,
                ad.nama as admin_nama,
                ad.username
            FROM audit_log a
            LEFT JOIN admin ad ON a.admin_id = ad.id
            WHERE a.table_name = ? AND a.record_id = ?
            ORDER BY a.created_at DESC
            LIMIT ?
        ");
        
        $stmt->execute([$table_name, $record_id, $limit]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get audit trail error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get all audit logs dengan filter
 */
function getAuditLogs($pdo, $filters = [], $limit = 100, $offset = 0) {
    try {
        $where = [];
        $params = [];
        
        if (!empty($filters['table_name'])) {
            $where[] = "a.table_name = ?";
            $params[] = $filters['table_name'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = "a.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['admin_id'])) {
            $where[] = "a.admin_id = ?";
            $params[] = $filters['admin_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "DATE(a.created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "DATE(a.created_at) <= ?";
            $params[] = $filters['end_date'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "
            SELECT 
                a.*,
                ad.nama as admin_nama,
                ad.username
            FROM audit_log a
            LEFT JOIN admin ad ON a.admin_id = ad.id
            $whereClause
            ORDER BY a.created_at DESC
            LIMIT ? OFFSET ?
        ";
        
        $params[] = $limit;
        $params[] = $offset;
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
        
    } catch (Exception $e) {
        error_log("Get audit logs error: " . $e->getMessage());
        return [];
    }
}

/**
 * Get audit logs count
 */
function getAuditLogsCount($pdo, $filters = []) {
    try {
        $where = [];
        $params = [];
        
        if (!empty($filters['table_name'])) {
            $where[] = "a.table_name = ?";
            $params[] = $filters['table_name'];
        }
        
        if (!empty($filters['action'])) {
            $where[] = "a.action = ?";
            $params[] = $filters['action'];
        }
        
        if (!empty($filters['admin_id'])) {
            $where[] = "a.admin_id = ?";
            $params[] = $filters['admin_id'];
        }
        
        if (!empty($filters['start_date'])) {
            $where[] = "DATE(a.created_at) >= ?";
            $params[] = $filters['start_date'];
        }
        
        if (!empty($filters['end_date'])) {
            $where[] = "DATE(a.created_at) <= ?";
            $params[] = $filters['end_date'];
        }
        
        $whereClause = !empty($where) ? "WHERE " . implode(" AND ", $where) : "";
        
        $sql = "SELECT COUNT(*) as total FROM audit_log a LEFT JOIN admin ad ON a.admin_id = ad.id $whereClause";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result['total'] ?? 0;
        
    } catch (Exception $e) {
        error_log("Get audit logs count error: " . $e->getMessage());
        return 0;
    }
}

/**
 * Format old/new values untuk display
 */
function formatAuditValues($values_json) {
    if (empty($values_json)) {
        return [];
    }
    
    try {
        $values = json_decode($values_json, true);
        return is_array($values) ? $values : [];
    } catch (Exception $e) {
        return [];
    }
}

/**
 * Get human-readable action name
 */
function getActionLabel($action) {
    $labels = [
        'CREATE' => '<span class="badge bg-success">Ditambah</span>',
        'UPDATE' => '<span class="badge bg-warning">Diubah</span>',
        'DELETE' => '<span class="badge bg-danger">Dihapus</span>'
    ];
    
    return $labels[$action] ?? $action;
}

?>
