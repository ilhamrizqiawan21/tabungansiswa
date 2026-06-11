<?php
/**
 * APPROVAL WORKFLOW PAGE
 */

require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/approval.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();

require_once '../includes/sidebar.php';

// Handle approval actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Keamanan form gagal";
    } else {
        $action = $_POST['action'] ?? '';
        $transaksi_id = sanitizeInt($_POST['transaksi_id'] ?? '');
        $admin_id = $_SESSION['admin_id'];
        
        if ($action === 'approve' && $transaksi_id) {
            $result = approveTransaction($pdo, $transaksi_id, $admin_id);
            $_SESSION[($result['success'] ? 'success' : 'error')] = $result['message'];
        } elseif ($action === 'reject' && $transaksi_id) {
            $reason = sanitizeString($_POST['reason'] ?? '');
            $result = rejectTransaction($pdo, $transaksi_id, $admin_id, $reason);
            $_SESSION[($result['success'] ? 'success' : 'error')] = $result['message'];
        }
        
        header("Location: approval_workflow.php");
        exit;
    }
}

// Get pending & recent approvals
$pending_approvals = getPendingApprovals($pdo, 100);

// Get approved/rejected
$recent_approvals = $pdo->query("
    SELECT 
        ta.*,
        t.siswa_id,
        t.tanggal,
        t.jenis,
        t.jumlah,
        s.nama as siswa_nama,
        req.nama as requested_by_name,
        app.nama as approved_by_name,
        ast.name as status_name
    FROM transaksi_approval ta
    JOIN transaksi t ON ta.transaksi_id = t.id
    JOIN siswa s ON t.siswa_id = s.id
    LEFT JOIN admin req ON ta.requested_by = req.id
    LEFT JOIN admin app ON ta.approved_by = app.id
    LEFT JOIN approval_status ast ON ta.status_id = ast.id
    WHERE ta.status_id IN (SELECT id FROM approval_status WHERE name IN ('approved', 'rejected'))
    ORDER BY ta.approval_date DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

require_once '../includes/sidebar.php';
?>

<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-check-square me-2"></i>Workflow Persetujuan
        </h1>
    </div>

    <?php if (isset($_SESSION['success'])): ?>
        <div class="alert alert-success alert-dismissible fade show">
            <i class="fas fa-check-circle me-2"></i><?= $_SESSION['success'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['success']); ?>
        </div>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <i class="fas fa-exclamation-circle me-2"></i><?= $_SESSION['error'] ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Pending Approvals -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-hourglass-half me-1"></i>Menunggu Persetujuan (<?= count($pending_approvals) ?>)
            </h6>
        </div>
        <div class="card-body">
            <?php if (empty($pending_approvals)): ?>
                <div class="alert alert-info">Tidak ada permohonan persetujuan yang menunggu</div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead class="table-light">
                            <tr>
                                <th>Tanggal</th>
                                <th>Siswa</th>
                                <th>NIS</th>
                                <th>Jenis</th>
                                <th class="text-end">Jumlah</th>
                                <th>Diajukan Oleh</th>
                                <th>Waktu Ajuan</th>
                                <th>Aksi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($pending_approvals as $approval): ?>
                                <tr>
                                    <td><?= date('d/m/Y', strtotime($approval['tanggal'])) ?></td>
                                    <td><?= htmlspecialchars($approval['siswa_nama']) ?></td>
                                    <td><code><?= htmlspecialchars($approval['nis']) ?></code></td>
                                    <td>
                                        <?php if ($approval['jenis'] === 'masuk'): ?>
                                            <span class="badge bg-success">Setoran</span>
                                        <?php else: ?>
                                            <span class="badge bg-danger">Penarikan</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end"><?= formatRupiah($approval['jumlah']) ?></td>
                                    <td><?= htmlspecialchars($approval['requested_by_name']) ?></td>
                                    <td><small><?= date('d/m/Y H:i', strtotime($approval['request_date'])) ?></small></td>
                                    <td>
                                        <div class="btn-group btn-group-sm" role="group">
                                            <button type="button" class="btn btn-outline-success" 
                                                    onclick="approveTransaction(<?= $approval['transaksi_id'] ?>)">
                                                <i class="fas fa-check me-1"></i>Setujui
                                            </button>
                                            <button type="button" class="btn btn-outline-danger" 
                                                    onclick="rejectTransaction(<?= $approval['transaksi_id'] ?>)">
                                                <i class="fas fa-times me-1"></i>Tolak
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Recent Decisions -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-history me-1"></i>Riwayat Persetujuan (50 Terbaru)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm">
                    <thead class="table-light">
                        <tr>
                            <th>Tanggal Transaksi</th>
                            <th>Siswa</th>
                            <th>Jumlah</th>
                            <th>Status</th>
                            <th>Diajukan</th>
                            <th>Disetujui</th>
                            <th>Waktu Keputusan</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($recent_approvals as $approval): ?>
                            <tr>
                                <td><?= date('d/m/Y', strtotime($approval['tanggal'])) ?></td>
                                <td><?= htmlspecialchars($approval['siswa_nama']) ?></td>
                                <td><?= formatRupiah($approval['jumlah']) ?></td>
                                <td>
                                    <?php
                                    $badges = [
                                        'approved' => '<span class="badge bg-success">Disetujui</span>',
                                        'rejected' => '<span class="badge bg-danger">Ditolak</span>'
                                    ];
                                    echo $badges[$approval['status_name']] ?? $approval['status_name'];
                                    ?>
                                </td>
                                <td><small><?= htmlspecialchars($approval['requested_by_name']) ?></small></td>
                                <td><small><?= htmlspecialchars($approval['approved_by_name']) ?></small></td>
                                <td><small><?= date('d/m/Y H:i', strtotime($approval['approval_date'])) ?></small></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Approve Modal -->
<div class="modal fade" id="approveModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrfTokenField() ?>
                <input type="hidden" name="action" value="approve">
                <input type="hidden" name="transaksi_id" id="approveTransaksiId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Setujui Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>Apakah Anda yakin ingin menyetujui transaksi ini?</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-success">Setujui</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Reject Modal -->
<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="post">
                <?= csrfTokenField() ?>
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="transaksi_id" id="rejectTransaksiId">
                
                <div class="modal-header">
                    <h5 class="modal-title">Tolak Transaksi</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <label for="reason" class="form-label">Alasan Penolakan</label>
                    <textarea class="form-control" id="reason" name="reason" rows="4" 
                              placeholder="Jelaskan mengapa transaksi ini ditolak..." required></textarea>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-danger">Tolak</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function approveTransaction(transaksiId) {
    document.getElementById('approveTransaksiId').value = transaksiId;
    new bootstrap.Modal(document.getElementById('approveModal')).show();
}

function rejectTransaction(transaksiId) {
    document.getElementById('rejectTransaksiId').value = transaksiId;
    new bootstrap.Modal(document.getElementById('rejectModal')).show();
}
</script>

<?php require_once '../includes/footer_sidebar.php'; ?>
