<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();

$id = $_GET['id'] ?? 0;

// Ambil data kelas
$stmt = $pdo->prepare("SELECT * FROM kelas WHERE id = ?");
$stmt->execute([$id]);
$kelas = $stmt->fetch();

if (!$kelas) {
    $_SESSION['error'] = "Kelas tidak ditemukan!";
    header("Location: index.php");
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = $_POST['nama_kelas'] ?? '';
    $tingkat = $_POST['tingkat'] ?? '';
    $wali_kelas = $_POST['wali_kelas'] ?? '';

    // Validasi
    if (empty($nama_kelas) || empty($tingkat)) {
        $_SESSION['error'] = "Nama kelas dan tingkat harus diisi!";
    } else {
        try {
            // Cek apakah nama kelas sudah digunakan oleh kelas lain
            $stmt = $pdo->prepare("SELECT id FROM kelas WHERE nama_kelas = ? AND id != ?");
            $stmt->execute([$nama_kelas, $id]);
            
            if ($stmt->fetch()) {
                $_SESSION['error'] = "Kelas dengan nama '$nama_kelas' sudah ada!";
            } else {
                // Update kelas
                $stmt = $pdo->prepare("
                    UPDATE kelas 
                    SET nama_kelas = ?, tingkat = ?, wali_kelas = ?, updated_at = NOW()
                    WHERE id = ?
                ");
                $stmt->execute([$nama_kelas, $tingkat, $wali_kelas, $id]);
                
                $_SESSION['success'] = "Data kelas berhasil diperbarui!";
                header("Location: index.php");
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    }
}

require_once '../includes/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-edit me-2"></i>Edit Kelas
        </h1>
        <a href="index.php" class="btn btn-secondary btn-icon-split">
            <span class="icon text-white-50">
                <i class="fas fa-arrow-left"></i>
            </span>
            <span class="text">Kembali</span>
        </a>
    </div>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show">
            <div class="d-flex align-items-center">
                <i class="fas fa-exclamation-circle me-2"></i>
                <div class="flex-grow-1"><?= $_SESSION['error'] ?></div>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Form Edit Kelas -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Edit Kelas</h6>
        </div>
        <div class="card-body">
            <form method="POST" id="formEditKelas">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nama_kelas" class="form-label">Nama Kelas <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" 
                                   value="<?= htmlspecialchars($kelas['nama_kelas']) ?>" 
                                   required maxlength="20" placeholder="Contoh: X IPA 1">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tingkat" class="form-label">Tingkat <span class="text-danger">*</span></label>
                            <select class="form-control" id="tingkat" name="tingkat" required>
                                <option value="">Pilih Tingkat</option>
                                <option value="X" <?= $kelas['tingkat'] == 'X' ? 'selected' : '' ?>>X</option>
                                <option value="XI" <?= $kelas['tingkat'] == 'XI' ? 'selected' : '' ?>>XI</option>
                                <option value="XII" <?= $kelas['tingkat'] == 'XII' ? 'selected' : '' ?>>XII</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="wali_kelas" class="form-label">Wali Kelas</label>
                            <input type="text" class="form-control" id="wali_kelas" name="wali_kelas" 
                                   value="<?= htmlspecialchars($kelas['wali_kelas'] ?? '') ?>" 
                                   maxlength="100" placeholder="Nama wali kelas">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="mb-3">
                            <div class="text-xs text-muted">
                                <i class="fas fa-info-circle me-1"></i>
                                Dibuat: <?= date('d/m/Y H:i', strtotime($kelas['created_at'])) ?> | 
                                Diupdate: <?= date('d/m/Y H:i', strtotime($kelas['updated_at'])) ?>
                            </div>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <a href="index.php" class="btn btn-secondary me-2">
                                <i class="fas fa-times me-1"></i>Batal
                            </a>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Update Kelas
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_sidebar.php'; ?>