<?php
// File: pengaturan/index.php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();

require_once '../includes/sidebar.php';
?>

<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-cog me-2"></i>Pengaturan Sistem
        </h1>
    </div>

    <!-- Tab Navigation -->
    <ul class="nav nav-tabs mb-4" id="pengaturanTab" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active" id="kelas-tab" data-bs-toggle="tab" data-bs-target="#kelas" type="button" role="tab">
                <i class="fas fa-door-open me-2"></i>Manajemen Kelas
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link" id="tahun-tab" data-bs-toggle="tab" data-bs-target="#tahun" type="button" role="tab">
                <i class="fas fa-calendar-alt me-2"></i>Tahun Pelajaran
            </button>
        </li>
    </ul>

    <!-- Tab Content -->
    <div class="tab-content" id="pengaturanTabContent">
        
        <!-- Tab Kelas -->
        <div class="tab-pane fade show active" id="kelas" role="tabpanel">
            <div class="row">
                <div class="col-lg-4">
                    <!-- Form Tambah/Edit Kelas -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary" id="formKelasTitle">
                                <i class="fas fa-plus-circle me-2"></i>Tambah Kelas Baru
                            </h6>
                        </div>
                        <div class="card-body">
                            <form id="formKelas" method="post" action="kelas_proses.php">
                                <input type="hidden" name="id" id="kelas_id" value="">
                                <div class="mb-3">
                                    <label for="nama_kelas" class="form-label">Nama Kelas <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="nama_kelas" name="nama_kelas" required>
                                </div>
                                <div class="mb-3">
                                    <label for="tingkat" class="form-label">Tingkat <span class="text-danger">*</span></label>
                                    <select class="form-select" id="tingkat" name="tingkat" required>
                                        <option value="">Pilih Tingkat</option>
                                        <option value="7">7</option>
                                        <option value="8">8</option>
                                        <option value="9">9</option>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="tahun_pelajaran_id" class="form-label">Tahun Pelajaran <span class="text-danger">*</span></label>
                                    <select class="form-select" id="tahun_pelajaran_id" name="tahun_pelajaran_id" required>
                                        <option value="">Pilih Tahun Pelajaran</option>
                                        <?php
                                        $stmt = $pdo->query("SELECT * FROM tahun_pelajaran ORDER BY tahun DESC, semester DESC");
                                        while ($row = $stmt->fetch()):
                                        ?>
                                            <option value="<?= $row['id'] ?>">
                                                <?= $row['tahun'] ?> - <?= ucfirst($row['semester']) ?>
                                                <?= $row['status'] == 'aktif' ? ' (Aktif)' : '' ?>
                                            </option>
                                        <?php endwhile; ?>
                                    </select>
                                </div>
                                <div class="mb-3">
                                    <label for="wali_kelas" class="form-label">Wali Kelas</label>
                                    <input type="text" class="form-control" id="wali_kelas" name="wali_kelas">
                                </div>
                                <div class="d-grid gap-2">
                                    <button type="submit" class="btn btn-primary" name="simpan">
                                        <i class="fas fa-save me-2"></i>Simpan
                                    </button>
                                    <button type="button" class="btn btn-secondary" id="batalEdit" style="display: none;">
                                        <i class="fas fa-times me-2"></i>Batal
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <!-- Daftar Kelas -->
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Daftar Kelas</h6>
                            <div class="text-xs text-muted">
                                <i class="fas fa-list me-1"></i>Total: 
                                <span id="totalKelas">0</span> kelas
                            </div>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover" id="tabelKelas">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Nama Kelas</th>
                                            <th>Tingkat</th>
                                            <th>Tahun Pelajaran</th>
                                            <th>Wali Kelas</th>
                                            <th>Jumlah Siswa</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("
                                            SELECT k.*, tp.tahun, tp.semester, tp.status,
                                            (SELECT COUNT(*) FROM siswa s WHERE s.kelas_id = k.id) as jumlah_siswa
                                            FROM kelas k
                                            JOIN tahun_pelajaran tp ON k.tahun_pelajaran_id = tp.id
                                            ORDER BY k.tingkat, k.nama_kelas
                                        ");
                                        
                                        $total_kelas = 0;
                                        while ($kelas = $stmt->fetch()):
                                            $total_kelas++;
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="font-weight-bold text-primary"><?= htmlspecialchars($kelas['nama_kelas']) ?></span>
                                            </td>
                                            <td>
                                                <span class="badge badge-info">Kelas <?= $kelas['tingkat'] ?></span>
                                            </td>
                                            <td>
                                                <div class="text-xs">
                                                    <?= $kelas['tahun'] ?> - <?= ucfirst($kelas['semester']) ?>
                                                    <?php if ($kelas['status'] == 'aktif'): ?>
                                                        <span class="badge badge-success ms-1">Aktif</span>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                            <td>
                                                <?= $kelas['wali_kelas'] ? htmlspecialchars($kelas['wali_kelas']) : '<span class="text-muted">-</span>' ?>
                                            </td>
                                            <td>
                                                <span class="font-weight-bold <?= $kelas['jumlah_siswa'] > 0 ? 'text-success' : 'text-muted' ?>">
                                                    <?= $kelas['jumlah_siswa'] ?> siswa
                                                </span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-warning edit-kelas" 
                                                            data-id="<?= $kelas['id'] ?>"
                                                            data-nama="<?= htmlspecialchars($kelas['nama_kelas']) ?>"
                                                            data-tingkat="<?= $kelas['tingkat'] ?>"
                                                            data-tahun="<?= $kelas['tahun_pelajaran_id'] ?>"
                                                            data-wali="<?= htmlspecialchars($kelas['wali_kelas'] ?? '') ?>">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <button class="btn btn-danger hapus-kelas" 
                                                            data-id="<?= $kelas['id'] ?>"
                                                            data-nama="<?= htmlspecialchars($kelas['nama_kelas']) ?>"
                                                            <?= $kelas['jumlah_siswa'] > 0 ? 'disabled' : '' ?>>
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                                <script>
                                    document.getElementById('totalKelas').textContent = '<?= $total_kelas ?>';
                                </script>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tab Tahun Pelajaran -->
        <div class="tab-pane fade" id="tahun" role="tabpanel">
            <div class="row">
                <div class="col-lg-4">
                    <!-- Form Tahun Pelajaran -->
                    <div class="card shadow mb-4">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">
                                <i class="fas fa-calendar-plus me-2"></i>Tambah Tahun Pelajaran
                            </h6>
                        </div>
                        <div class="card-body">
                            <form method="post" action="tahun_proses.php">
                                <div class="mb-3">
                                    <label for="tahun" class="form-label">Tahun Pelajaran <span class="text-danger">*</span></label>
                                    <input type="text" class="form-control" id="tahun" name="tahun" 
                                           placeholder="2024/2025" pattern="\d{4}/\d{4}" required>
                                    <div class="form-text">Format: XXXX/XXXX (contoh: 2024/2025)</div>
                                </div>
                                <div class="mb-3">
                                    <label for="semester" class="form-label">Semester <span class="text-danger">*</span></label>
                                    <select class="form-select" id="semester" name="semester" required>
                                        <option value="">Pilih Semester</option>
                                        <option value="ganjil">Ganjil</option>
                                        <option value="genap">Genap</option>
                                    </select>
                                </div>
                                <div class="mb-3 form-check">
                                    <input type="checkbox" class="form-check-input" id="set_aktif" name="set_aktif" value="1">
                                    <label class="form-check-label" for="set_aktif">Set sebagai tahun pelajaran aktif</label>
                                </div>
                                <div class="d-grid">
                                    <button type="submit" class="btn btn-primary" name="simpan">
                                        <i class="fas fa-save me-2"></i>Simpan
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>

                <div class="col-lg-8">
                    <!-- Daftar Tahun Pelajaran -->
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center">
                            <h6 class="m-0 font-weight-bold text-primary">Daftar Tahun Pelajaran</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead class="table-light">
                                        <tr>
                                            <th>Tahun Pelajaran</th>
                                            <th>Semester</th>
                                            <th>Status</th>
                                            <th>Jumlah Kelas</th>
                                            <th class="text-center">Aksi</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php
                                        $stmt = $pdo->query("
                                            SELECT tp.*, 
                                            (SELECT COUNT(*) FROM kelas k WHERE k.tahun_pelajaran_id = tp.id) as jumlah_kelas
                                            FROM tahun_pelajaran tp
                                            ORDER BY tp.tahun DESC, tp.semester DESC
                                        ");
                                        
                                        while ($tahun = $stmt->fetch()):
                                        ?>
                                        <tr>
                                            <td>
                                                <span class="font-weight-bold text-primary"><?= $tahun['tahun'] ?></span>
                                            </td>
                                            <td>
                                                <span class="text-capitalize"><?= $tahun['semester'] ?></span>
                                            </td>
                                            <td>
                                                <?php if ($tahun['status'] == 'aktif'): ?>
                                                    <span class="badge badge-success">Aktif</span>
                                                <?php else: ?>
                                                    <span class="badge badge-secondary">Nonaktif</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <span class="font-weight-bold"><?= $tahun['jumlah_kelas'] ?> kelas</span>
                                            </td>
                                            <td class="text-center">
                                                <div class="btn-group btn-group-sm">
                                                    <?php if ($tahun['status'] == 'nonaktif'): ?>
                                                        <a href="tahun_proses.php?set_aktif=<?= $tahun['id'] ?>" 
                                                           class="btn btn-success" 
                                                           onclick="return confirm('Set tahun pelajaran ini sebagai aktif?')">
                                                            <i class="fas fa-check"></i> Aktifkan
                                                        </a>
                                                    <?php endif; ?>
                                                    <?php if ($tahun['jumlah_kelas'] == 0): ?>
                                                        <a href="tahun_proses.php?hapus=<?= $tahun['id'] ?>" 
                                                           class="btn btn-danger" 
                                                           onclick="return confirm('Hapus tahun pelajaran ini?')">
                                                            <i class="fas fa-trash"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        <?php endwhile; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Script untuk manajemen kelas
document.addEventListener('DOMContentLoaded', function() {
    const formKelas = document.getElementById('formKelas');
    const formTitle = document.getElementById('formKelasTitle');
    const batalBtn = document.getElementById('batalEdit');
    
    // Edit kelas
    document.querySelectorAll('.edit-kelas').forEach(btn => {
        btn.addEventListener('click', function() {
            const id = this.dataset.id;
            const nama = this.dataset.nama;
            const tingkat = this.dataset.tingkat;
            const tahun = this.dataset.tahun;
            const wali = this.dataset.wali;
            
            document.getElementById('kelas_id').value = id;
            document.getElementById('nama_kelas').value = nama;
            document.getElementById('tingkat').value = tingkat;
            document.getElementById('tahun_pelajaran_id').value = tahun;
            document.getElementById('wali_kelas').value = wali;
            
            formTitle.innerHTML = '<i class="fas fa-edit me-2"></i>Edit Kelas';
            batalBtn.style.display = 'block';
            
            // Scroll ke form
            document.getElementById('formKelas').scrollIntoView({ behavior: 'smooth' });
        });
    });
    
    // Batal edit
    batalBtn.addEventListener('click', function() {
        formKelas.reset();
        document.getElementById('kelas_id').value = '';
        formTitle.innerHTML = '<i class="fas fa-plus-circle me-2"></i>Tambah Kelas Baru';
        this.style.display = 'none';
    });
    
    // Hapus kelas
    document.querySelectorAll('.hapus-kelas').forEach(btn => {
        if (!btn.disabled) {
            btn.addEventListener('click', function() {
                const id = this.dataset.id;
                const nama = this.dataset.nama;
                
                if (confirm(`Hapus kelas "${nama}"?`)) {
                    window.location.href = `kelas_proses.php?hapus=${id}`;
                }
            });
        }
    });
});
</script>

<?php require_once '../includes/footer_sidebar.php'; ?>