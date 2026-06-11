<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/functions.php';
redirectIfNotLoggedIn();

$id = $_GET['id'] ?? 0;
$stmt = $pdo->prepare("SELECT * FROM siswa WHERE id = ?");
$stmt->execute([$id]);
$siswa = $stmt->fetch();
if (!$siswa) {
    $_SESSION['error'] = "Siswa tidak ditemukan";
    header("Location: index.php");
    exit();
}

$kelas_list = $pdo->query("SELECT id, nama_kelas, tingkat, jurusan FROM kelas ORDER BY tingkat, nama_kelas")->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nis = trim($_POST['nis']);
    $nama = trim($_POST['nama']);
    $kelas_id = $_POST['kelas_id'] ?? null;
    $kontak = trim($_POST['kontak'] ?? '');

    $errors = [];
    if (empty($nis)) $errors[] = "NIS harus diisi";
    if (!preg_match('/^[0-9]+$/', $nis)) $errors[] = "NIS hanya boleh angka";
    if (empty($nama)) $errors[] = "Nama harus diisi";
    if (empty($kelas_id)) $errors[] = "Kelas harus dipilih";

    if (empty($errors)) {
        try {
            // Cek duplikat NIS kecuali dirinya sendiri
            $stmt = $pdo->prepare("SELECT id FROM siswa WHERE nis = ? AND id != ?");
            $stmt->execute([$nis, $id]);
            if ($stmt->fetch()) {
                $_SESSION['error'] = "NIS sudah digunakan siswa lain!";
            } else {
                $stmt = $pdo->prepare("UPDATE siswa SET nis=?, nama=?, kelas_id=?, kontak=? WHERE id=?");
                $stmt->execute([$nis, $nama, $kelas_id, $kontak, $id]);
                $_SESSION['success'] = "Data siswa berhasil diupdate!";
                header("Location: index.php");
                exit();
            }
        } catch (PDOException $e) {
            $_SESSION['error'] = "Error: " . $e->getMessage();
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

require_once '../includes/sidebar.php';
?>

<div class="container mt-4">
    <h2>Edit Data Siswa</h2>
    <div class="card">
        <div class="card-body">
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger"><?= $_SESSION['error'] ?><?php unset($_SESSION['error']); ?></div>
            <?php endif; ?>
            <form method="post">
                <div class="mb-3">
                    <label>NIS</label>
                    <input type="text" name="nis" class="form-control" required pattern="[0-9]+" value="<?= htmlspecialchars($siswa['nis']) ?>">
                </div>
                <div class="mb-3">
                    <label>Nama Lengkap</label>
                    <input type="text" name="nama" class="form-control" required value="<?= htmlspecialchars($siswa['nama']) ?>">
                </div>
                <div class="mb-3">
                    <label>Kelas</label>
                    <select name="kelas_id" class="form-select" required>
                        <option value="">Pilih Kelas</option>
                        <?php foreach ($kelas_list as $k): ?>
                            <option value="<?= $k['id'] ?>" <?= $k['id'] == $siswa['kelas_id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($k['tingkat']) ?> - <?= htmlspecialchars($k['nama_kelas']) ?> <?= $k['jurusan'] ? "({$k['jurusan']})" : '' ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="mb-3">
                    <label>Kontak</label>
                    <input type="text" name="kontak" class="form-control" value="<?= htmlspecialchars($siswa['kontak'] ?? '') ?>">
                </div>
                <button type="submit" class="btn btn-primary">Update</button>
                <a href="index.php" class="btn btn-secondary">Kembali</a>
            </form>
        </div>
    </div>
</div>

<?php require_once '../includes/footer_sidebar.php'; ?>