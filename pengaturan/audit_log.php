<?php
/**
 * AUDIT LOG VIEWER PAGE
 */

require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/audit.php';
redirectIfNotLoggedIn();

require_once '../includes/sidebar.php';

// Filters
$filters = [
    'table_name' => $_GET['table_name'] ?? '',
    'action' => $_GET['action'] ?? '',
    'admin_id' => $_GET['admin_id'] ?? '',
    'start_date' => $_GET['start_date'] ?? date('Y-m-01'),
    'end_date' => $_GET['end_date'] ?? date('Y-m-d')
];

// Pagination
$page = $_GET['page'] ?? 1;
$per_page = 50;
$offset = ($page - 1) * $per_page;

// Get data
$logs = getAuditLogs($pdo, $filters, $per_page, $offset);
$total_count = getAuditLogsCount($pdo, $filters);
$total_pages = ceil($total_count / $per_page);

// Get list of tables & admins for filter
$tables = $pdo->query("
    SELECT DISTINCT table_name 
    FROM audit_log 
    ORDER BY table_name
")->fetchAll(PDO::FETCH_COLUMN);

$admins = $pdo->query("
    SELECT id, nama, username 
    FROM admin 
    ORDER BY nama
")->fetchAll(PDO::FETCH_ASSOC);
?>

<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-history me-2"></i>Audit Trail
        </h1>
    </div>

    <!-- Filter Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Filter</h6>
        </div>
        <div class="card-body">
            <form method="get" class="row g-3 align-items-end">
                <div class="col-md-2">
                    <label for="table_name" class="form-label">Tabel</label>
                    <select class="form-select" id="table_name" name="table_name">
                        <option value="">Semua Tabel</option>
                        <?php foreach ($tables as $table): ?>
                            <option value="<?= htmlspecialchars($table) ?>" <?= $filters['table_name'] === $table ? 'selected' : '' ?>>
                                <?= htmlspecialchars($table) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="action" class="form-label">Action</label>
                    <select class="form-select" id="action" name="action">
                        <option value="">Semua Action</option>
                        <option value="CREATE" <?= $filters['action'] === 'CREATE' ? 'selected' : '' ?>>CREATE</option>
                        <option value="UPDATE" <?= $filters['action'] === 'UPDATE' ? 'selected' : '' ?>>UPDATE</option>
                        <option value="DELETE" <?= $filters['action'] === 'DELETE' ? 'selected' : '' ?>>DELETE</option>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="admin_id" class="form-label">Admin</label>
                    <select class="form-select" id="admin_id" name="admin_id">
                        <option value="">Semua Admin</option>
                        <?php foreach ($admins as $admin): ?>
                            <option value="<?= $admin['id'] ?>" <?= $filters['admin_id'] == $admin['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($admin['nama']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="col-md-2">
                    <label for="start_date" class="form-label">Dari Tanggal</label>
                    <input type="date" class="form-control" id="start_date" name="start_date" value="<?= $filters['start_date'] ?>">
                </div>

                <div class="col-md-2">
                    <label for="end_date" class="form-label">Sampai Tanggal</label>
                    <input type="date" class="form-control" id="end_date" name="end_date" value="<?= $filters['end_date'] ?>">
                </div>

                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary w-100">
                        <i class="fas fa-filter me-1"></i>Filter
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Results -->
    <div class="card shadow">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                Hasil: <?= number_format($total_count) ?> entri
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu</th>
                            <th>Admin</th>
                            <th>Tabel</th>
                            <th>Record ID</th>
                            <th>Action</th>
                            <th>Deskripsi</th>
                            <th>IP Address</th>
                            <th>Detail</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($logs)): ?>
                            <tr>
                                <td colspan="8" class="text-center text-muted">Tidak ada data</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($logs as $log): ?>
                                <tr>
                                    <td>
                                        <small><?= date('d/m/Y H:i:s', strtotime($log['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($log['admin_nama'] ?? 'System') ?></small>
                                    </td>
                                    <td>
                                        <code><?= htmlspecialchars($log['table_name']) ?></code>
                                    </td>
                                    <td>
                                        <small><?= $log['record_id'] ?></small>
                                    </td>
                                    <td>
                                        <?php 
                                        $badges = [
                                            'CREATE' => '<span class="badge bg-success">CREATE</span>',
                                            'UPDATE' => '<span class="badge bg-warning">UPDATE</span>',
                                            'DELETE' => '<span class="badge bg-danger">DELETE</span>'
                                        ];
                                        echo $badges[$log['action']] ?? $log['action'];
                                        ?>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars(substr($log['description'] ?? '', 0, 40)) ?></small>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($log['ip_address']) ?></small>
                                    </td>
                                    <td>
                                        <button class="btn btn-sm btn-outline-primary" data-bs-toggle="modal" data-bs-target="#detailModal" 
                                                onclick="showDetail(<?= htmlspecialchars(json_encode($log)) ?>)">
                                            <i class="fas fa-eye"></i>
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- Pagination -->
            <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation">
                    <ul class="pagination justify-content-center mt-3">
                        <?php if ($page > 1): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=1&<?= http_build_query($filters) ?>">First</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page - 1 ?>&<?= http_build_query($filters) ?>">Previous</a>
                            </li>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($total_pages, $page + 2); $i++): ?>
                            <li class="page-item <?= $i === $page ? 'active' : '' ?>">
                                <a class="page-link" href="?page=<?= $i ?>&<?= http_build_query($filters) ?>">
                                    <?= $i ?>
                                </a>
                            </li>
                        <?php endfor; ?>

                        <?php if ($page < $total_pages): ?>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $page + 1 ?>&<?= http_build_query($filters) ?>">Next</a>
                            </li>
                            <li class="page-item">
                                <a class="page-link" href="?page=<?= $total_pages ?>&<?= http_build_query($filters) ?>">Last</a>
                            </li>
                        <?php endif; ?>
                    </ul>
                </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Detail Modal -->
<div class="modal fade" id="detailModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Detail Audit Log</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <div id="detailContent"></div>
            </div>
        </div>
    </div>
</div>

<script>
function showDetail(log) {
    let html = '<table class="table table-sm"><tr><td><strong>Waktu:</strong></td><td>' + new Date(log.created_at).toLocaleString('id-ID') + '</td></tr>';
    html += '<tr><td><strong>Admin:</strong></td><td>' + (log.admin_nama || 'System') + '</td></tr>';
    html += '<tr><td><strong>Tabel:</strong></td><td><code>' + log.table_name + '</code></td></tr>';
    html += '<tr><td><strong>Record ID:</strong></td><td>' + log.record_id + '</td></tr>';
    html += '<tr><td><strong>Action:</strong></td><td>' + log.action + '</td></tr>';
    html += '<tr><td><strong>IP Address:</strong></td><td>' + log.ip_address + '</td></tr>';
    html += '<tr><td><strong>Deskripsi:</strong></td><td>' + (log.description || '-') + '</td></tr>';
    
    if (log.old_values) {
        try {
            let oldValues = JSON.parse(log.old_values);
            html += '<tr><td><strong>Old Values:</strong></td><td><pre>' + JSON.stringify(oldValues, null, 2) + '</pre></td></tr>';
        } catch(e) {}
    }
    
    if (log.new_values) {
        try {
            let newValues = JSON.parse(log.new_values);
            html += '<tr><td><strong>New Values:</strong></td><td><pre>' + JSON.stringify(newValues, null, 2) + '</pre></td></tr>';
        } catch(e) {}
    }
    
    html += '</table>';
    document.getElementById('detailContent').innerHTML = html;
}
</script>

<?php require_once '../includes/footer_sidebar.php'; ?>
