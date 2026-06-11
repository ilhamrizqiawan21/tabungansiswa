<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nama_kelas = $_POST['nama_kelas'] ?? '';
    $tingkat = $_POST['tingkat'] ?? '';
    $jurusan = $_POST['jurusan'] ?? '';
    $wali_kelas = $_POST['wali_kelas'] ?? '';

    // Validasi
    if (empty($nama_kelas) || empty($tingkat)) {
        $_SESSION['error'] = "Nama kelas dan tingkat harus diisi!";
    } else {
        try {
            // Cek apakah kelas sudah ada
            $stmt = $pdo->prepare("SELECT id FROM kelas WHERE nama_kelas = ?");
            $stmt->execute([$nama_kelas]);
            
            if ($stmt->fetch()) {
                $_SESSION['error'] = "Kelas dengan nama '$nama_kelas' sudah ada!";
            } else {
                // Insert kelas baru
                $stmt = $pdo->prepare("
                    INSERT INTO kelas (nama_kelas, tingkat, jurusan, wali_kelas) 
                    VALUES (?, ?, ?, ?)
                ");
                $stmt->execute([$nama_kelas, $tingkat, $jurusan, $wali_kelas]);
                
                $_SESSION['success'] = "Kelas berhasil ditambahkan!";
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
            <i class="fas fa-plus me-2"></i>Tambah Kelas Baru
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

    <!-- Form Tambah Kelas -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Form Tambah Kelas</h6>
        </div>
        <div class="card-body">
            <form method="POST" id="formTambahKelas">
                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="nama_kelas" class="form-label">Nama Kelas <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" 
                                   value="<?= htmlspecialchars($_POST['nama_kelas'] ?? '') ?>" 
                                   required maxlength="20" placeholder="Contoh: X IPA 1">
                            <div class="form-text">Format: Tingkat Jurusan Nomor (contoh: X IPA 1)</div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="tingkat" class="form-label">Tingkat <span class="text-danger">*</span></label>
                            <select class="form-control" id="tingkat" name="tingkat" required>
                                <option value="">Pilih Tingkat</option>
                                <option value="X" <?= ($_POST['tingkat'] ?? '') == 'X' ? 'selected' : '' ?>>X</option>
                                <option value="XI" <?= ($_POST['tingkat'] ?? '') == 'XI' ? 'selected' : '' ?>>XI</option>
                                <option value="XII" <?= ($_POST['tingkat'] ?? '') == 'XII' ? 'selected' : '' ?>>XII</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="jurusan" class="form-label">Jurusan</label>
                            <select class="form-control" id="jurusan" name="jurusan">
                                <option value="">Pilih Jurusan</option>
                                <option value="IPA" <?= ($_POST['jurusan'] ?? '') == 'IPA' ? 'selected' : '' ?>>IPA</option>
                                <option value="IPS" <?= ($_POST['jurusan'] ?? '') == 'IPS' ? 'selected' : '' ?>>IPS</option>
                                <option value="Bahasa" <?= ($_POST['jurusan'] ?? '') == 'Bahasa' ? 'selected' : '' ?>>Bahasa</option>
                                <option value="Agama" <?= ($_POST['jurusan'] ?? '') == 'Agama' ? 'selected' : '' ?>>Agama</option>
                                <option value="Umum" <?= ($_POST['jurusan'] ?? '') == 'Umum' ? 'selected' : '' ?>>Umum</option>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="mb-3">
                            <label for="wali_kelas" class="form-label">Wali Kelas</label>
                            <input type="text" class="form-control" id="wali_kelas" name="wali_kelas" 
                                   value="<?= htmlspecialchars($_POST['wali_kelas'] ?? '') ?>" 
                                   maxlength="100" placeholder="Nama wali kelas">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-12">
                        <div class="d-flex justify-content-end">
                            <button type="reset" class="btn btn-secondary me-2">
                                <i class="fas fa-undo me-1"></i>Reset
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-save me-1"></i>Simpan Kelas
                            </button>
                        </div>
                    </div>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
document.getElementById('formTambahKelas').addEventListener('submit', function(e) {
    const namaKelas = document.getElementById('nama_kelas').value.trim();
    const tingkat = document.getElementById('tingkat').value;
    
    if (!namaKelas || !tingkat) {
        e.preventDefault();
        alert('Nama kelas dan tingkat harus diisi!');
        return false;
    }
});
</script>

<?php require_once '../includes/footer_sidebar.php'; ?>