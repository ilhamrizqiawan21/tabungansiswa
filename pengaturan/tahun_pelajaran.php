<?php
/**
 * TAHUN PELAJARAN MANAGEMENT PAGE
 */

require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/security.php';
require_once '../includes/audit.php';
redirectIfNotLoggedIn();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Keamanan form gagal";
    } else {
        $action = $_POST['action'] ?? '';
        
        if ($action === 'add') {
            // Add new tahun pelajaran
            $tahun = sanitizeString($_POST['tahun'] ?? '');
            $semester = sanitizeString($_POST['semester'] ?? '');
            
            if (empty($tahun) || empty($semester)) {
                $_SESSION['error'] = "Tahun dan semester harus diisi";
            } else {
                try {
                    $stmt = $pdo->prepare("INSERT INTO tahun_pelajaran (tahun, semester, status) VALUES (?, ?, 'nonaktif')");
                    $stmt->execute([$tahun, $semester]);
                    $_SESSION['success'] = "Tahun pelajaran berhasil ditambahkan";
                    
                    auditLog($pdo, 'tahun_pelajaran', $pdo->lastInsertId(), 'CREATE', 
                        ['tahun' => $tahun, 'semester' => $semester], 
                        "Tahun pelajaran $tahun semester $semester ditambahkan");
                    
                    header("Location: tahun_pelajaran.php");
                    exit;
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'update_status') {
            // Update status tahun pelajaran
            $id = sanitizeInt($_POST['id']);
            $status = sanitizeString($_POST['status'] ?? '');
            
            if (!$id || !in_array($status, ['aktif', 'nonaktif'])) {
                $_SESSION['error'] = "Data tidak valid";
            } else {
                try {
                    // Jika set aktif, set yang lain menjadi nonaktif
                    if ($status === 'aktif') {
                        $stmt = $pdo->prepare("UPDATE tahun_pelajaran SET status = 'nonaktif'");
                        $stmt->execute();
                    }
                    
                    $stmt = $pdo->prepare("UPDATE tahun_pelajaran SET status = ? WHERE id = ?");
                    $stmt->execute([$status, $id]);
                    $_SESSION['success'] = "Status tahun pelajaran berhasil diubah";
                    
                    auditLog($pdo, 'tahun_pelajaran', $id, 'UPDATE', 
                        ['status' => 'changed'], 
                        "Status tahun pelajaran diubah ke $status");
                    
                    header("Location: tahun_pelajaran.php");
                    exit;
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
            }
        } elseif ($action === 'delete') {
            // Delete tahun pelajaran
            $id = sanitizeInt($_POST['id']);
            
            if (!$id) {
                $_SESSION['error'] = "Data tidak valid";
            } else {
                try {
                    // Get data first
                    $stmt = $pdo->prepare("SELECT * FROM tahun_pelajaran WHERE id = ?");
                    $stmt->execute([$id]);
                    $old_data = $stmt->fetch(PDO::FETCH_ASSOC);
                    
                    $stmt = $pdo->prepare("DELETE FROM tahun_pelajaran WHERE id = ?");
                    $stmt->execute([$id]);
                    $_SESSION['success'] = "Tahun pelajaran berhasil dihapus";
                    
                    auditLog($pdo, 'tahun_pelajaran', $id, 'DELETE', $old_data, 
                        "Tahun pelajaran {$old_data['tahun']} dihapus");
                    
                    header("Location: tahun_pelajaran.php");
                    exit;
                } catch (Exception $e) {
                    $_SESSION['error'] = "Error: " . $e->getMessage();
                }
            }
        }
    }
}

// Get data
$tahun_pelajaran = $pdo->query("SELECT * FROM tahun_pelajaran ORDER BY tahun DESC")->fetchAll();
$current_tahun = $pdo->query("SELECT * FROM tahun_pelajaran WHERE status = 'aktif'")->fetch();

require_once '../includes/sidebar.php';
?>

<div class="container-fluid mt-4">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-calendar-alt me-2"></i>Manajemen Tahun Pelajaran
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

    <!-- Add New Card -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Tambah Tahun Pelajaran Baru</h6>
        </div>
        <div class="card-body">
            <form method="post" novalidate>
                <?= csrfTokenField() ?>
                <input type="hidden" name="action" value="add">
                
                <div class="row">
                    <div class="col-md-4">
                        <label for="tahun" class="form-label">Tahun Pelajaran <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="tahun" name="tahun" 
                               placeholder="Contoh: 2025/2026" required 
                               maxlength="9">
                        <small class="text-muted">Format: YYYY/YYYY</small>
                    </div>
                    
                    <div class="col-md-4">
                        <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                        <select class="form-select" id="semester" name="semester" required>
                            <option value="">-- Pilih Semester --</option>
                            <option value="ganjil">Ganjil</option>
                            <option value="genap">Genap</option>
                        </select>
                    </div>
                    
                    <div class="col-md-4 d-flex align-items-end">
                        <button type="submit" class="btn btn-primary w-100">
                            <i class="fas fa-plus me-1"></i>Tambah
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Data Table -->
    <div class="card shadow">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                Daftar Tahun Pelajaran (<?= count($tahun_pelajaran) ?>)
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-hover">
                    <thead class="table-light">
                        <tr>
                            <th>Tahun Pelajaran</th>
                            <th>Semester</th>
                            <th>Status</th>
                            <th>Total Kelas</th>
                            <th>Total Siswa</th>
                            <th>Dibuat</th>
                            <th>Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($tahun_pelajaran)): ?>
                            <tr>
                                <td colspan="7" class="text-center text-muted">Tidak ada data</td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($tahun_pelajaran as $tp): ?>
                                <?php 
                                // Count classes & students
                                $stmt = $pdo->prepare("SELECT COUNT(*) as total FROM kelas WHERE tahun_pelajaran_id = ?");
                                $stmt->execute([$tp['id']]);
                                $kelas_count = $stmt->fetchColumn();
                                
                                $stmt = $pdo->prepare("
                                    SELECT COUNT(*) as total FROM siswa 
                                    WHERE kelas_id IN (SELECT id FROM kelas WHERE tahun_pelajaran_id = ?)
                                ");
                                $stmt->execute([$tp['id']]);
                                $siswa_count = $stmt->fetchColumn();
                                ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($tp['tahun']) ?></strong>
                                    </td>
                                    <td>
                                        <?= ucfirst($tp['semester']) ?>
                                    </td>
                                    <td>
                                        <?php if ($tp['status'] === 'aktif'): ?>
                                            <span class="badge bg-success">AKTIF</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Tidak Aktif</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-info"><?= $kelas_count ?> Kelas</span>
                                    </td>
                                    <td>
                                        <span class="badge bg-primary"><?= $siswa_count ?> Siswa</span>
                                    </td>
                                    <td>
                                        <small><?= date('d/m/Y', strtotime($tp['created_at'])) ?></small>
                                    </td>
                                    <td>
                                        <?php if ($tp['status'] !== 'aktif'): ?>
                                            <form method="post" style="display: inline;">
                                                <?= csrfTokenField() ?>
                                                <input type="hidden" name="action" value="update_status">
                                                <input type="hidden" name="id" value="<?= $tp['id'] ?>">
                                                <input type="hidden" name="status" value="aktif">
                                                <button type="submit" class="btn btn-sm btn-outline-success" 
                                                        onclick="return confirm('Set sebagai tahun pelajaran aktif?')">
                                                    <i class="fas fa-check me-1"></i>Aktifkan
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">-</span>
                                        <?php endif; ?>
                                        
                                        <form method="post" style="display: inline;">
                                            <?= csrfTokenField() ?>
                                            <input type="hidden" name="action" value="delete">
                                            <input type="hidden" name="id" value="<?= $tp['id'] ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                                    onclick="return confirm('Apakah Anda yakin ingin menghapus?')">
                                                <i class="fas fa-trash me-1"></i>Hapus
                                            </button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_sidebar.php'; ?>
