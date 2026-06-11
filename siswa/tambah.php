<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
redirectIfNotLoggedIn();

// Ambil daftar kelas dari database
$kelas_list = $pdo->query("SELECT id, nama_kelas, tingkat, jurusan FROM kelas ORDER BY tingkat, nama_kelas")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Keamanan form gagal. Silakan coba lagi.";
        logSecurityEvent('CSRF_FAILED', 'CSRF token mismatch on siswa/tambah.php', $_SESSION['admin_id'] ?? null, 'WARNING');
    } else {
        // Sanitize dan validate input
        $nis = sanitizeString($_POST['nis'] ?? '');
        $nama = sanitizeString($_POST['nama'] ?? '');
        $kelas_id = sanitizeInt($_POST['kelas_id'] ?? null);
        $kontak = sanitizePhone($_POST['kontak'] ?? '');

        $errors = [];
        
        // Validasi NIS
        if (empty($nis)) {
            $errors[] = "NIS harus diisi";
        } elseif (!validateNIS($nis)) {
            $errors[] = "NIS hanya boleh angka (4-20 karakter)";
        }
        
        // Validasi nama
        if (empty($nama)) {
            $errors[] = "Nama harus diisi";
        } elseif (!validateName($nama)) {
            $errors[] = "Nama hanya boleh berisi huruf dan spasi";
        } elseif (strlen($nama) > 100) {
            $errors[] = "Nama maksimal 100 karakter";
        }
        
        // Validasi kelas
        if (empty($kelas_id)) {
            $errors[] = "Kelas harus dipilih";
        }
        
        // Validasi kontak (optional)
        if (!empty($kontak) && strlen($kontak) > 20) {
            $errors[] = "Nomor kontak maksimal 20 karakter";
        }

        if (empty($errors)) {
            try {
                // Check duplikasi NIS
                $stmt = $pdo->prepare("SELECT id FROM siswa WHERE nis = ? LIMIT 1");
                $stmt->execute([$nis]);
                if ($stmt->fetch()) {
                    $_SESSION['error'] = "NIS sudah terdaftar!";
                    logSecurityEvent('DUPLICATE_NIS', 'Attempt to add duplicate NIS: ' . $nis, $_SESSION['admin_id'] ?? null, 'WARNING');
                } else {
                    // Insert data
                    $stmt = $pdo->prepare("INSERT INTO siswa (nis, nama, kelas_id, kontak) VALUES (?, ?, ?, ?)");
                    $stmt->execute([$nis, $nama, $kelas_id, !empty($kontak) ? $kontak : null]);
                    
                    $_SESSION['success'] = "Data siswa '" . $nama . "' berhasil ditambahkan!";
                    logSecurityEvent('SISWA_CREATED', 'New student added: NIS ' . $nis . ', Name: ' . $nama, $_SESSION['admin_id'] ?? null, 'INFO');
                    
                    header("Location: index.php");
                    exit();
                }
            } catch (PDOException $e) {
                $_SESSION['error'] = "Error: Terjadi kesalahan pada database.";
                logSecurityEvent('DB_ERROR', 'PDOException: ' . $e->getMessage(), $_SESSION['admin_id'] ?? null, 'ERROR');
            }
        } else {
            $_SESSION['error'] = implode("<br>", $errors);
        }
    }
}

require_once '../includes/sidebar.php';
?>

<div class="container mt-4">
    <h2>Tambah Data Siswa</h2>
    <div class="card">
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    <?php unset($_SESSION['error']); ?>
                </div>
            <?php endif; ?>
            <form method="post" novalidate>
                <?= csrfTokenField() ?>
                
                <div class="mb-3">
                    <label for="nis" class="form-label">NIS <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        name="nis" 
                        id="nis"
                        class="form-control" 
                        required 
                        pattern="[0-9]{4,20}"
                        placeholder="Masukkan NIS (angka, 4-20 digit)"
                        maxlength="20"
                        value="<?= htmlspecialchars($_POST['nis'] ?? '') ?>">
                    <small class="form-text text-muted">Hanya angka, minimal 4 digit</small>
                </div>
                
                <div class="mb-3">
                    <label for="nama" class="form-label">Nama Lengkap <span class="text-danger">*</span></label>
                    <input 
                        type="text" 
                        name="nama" 
                        id="nama"
                        class="form-control" 
                        required 
                        placeholder="Masukkan nama lengkap siswa"
                        maxlength="100"
                        value="<?= htmlspecialchars($_POST['nama'] ?? '') ?>">
                    <small class="form-text text-muted">Maksimal 100 karakter</small>
                </div>
                
                <div class="mb-3">
                    <label for="kelas_id" class="form-label">Kelas <span class="text-danger">*</span></label>
                    <select name="kelas_id" id="kelas_id" class="form-select" required>
                        <option value="">-- Pilih Kelas --</option>
                        <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= htmlspecialchars($k['id']) ?>" <?= (isset($_POST['kelas_id']) && $_POST['kelas_id'] == $k['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['tingkat']) ?> - <?= htmlspecialchars($k['nama_kelas']) ?> <?= !empty($k['jurusan']) ? "({$k['jurusan']})" : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                
                <div class="mb-3">
                    <label for="kontak" class="form-label">Kontak (Opsional)</label>
                    <input 
                        type="tel" 
                        name="kontak" 
                        id="kontak"
                        class="form-control" 
                        placeholder="Nomor telepon/WhatsApp"
                        maxlength="20"
                        value="<?= htmlspecialchars($_POST['kontak'] ?? '') ?>">
                    <small class="form-text text-muted">Format: 08xx atau +62xx</small>
                </div>
                
                <div class="d-flex gap-2">
                    <button type="submit" class="btn btn-primary">
                        <i class="fas fa-save me-2"></i> Simpan
                    </button>
                    <a href="index.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i> Kembali
                    </a>
                </div>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_sidebar.php'; ?>