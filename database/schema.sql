-- Tabungan Siswa - schema lengkap untuk MySQL/MariaDB
-- Import file ini melalui Adminer setelah membuat database tabungan_siswa.
SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

CREATE TABLE IF NOT EXISTS admin (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  username VARCHAR(50) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  nama VARCHAR(100) NOT NULL,
  role ENUM('admin','operator') NOT NULL DEFAULT 'admin',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS tahun_pelajaran (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  tahun VARCHAR(9) NOT NULL,
  semester ENUM('ganjil','genap') NOT NULL,
  status ENUM('aktif','nonaktif') NOT NULL DEFAULT 'nonaktif',
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY uq_tahun_semester (tahun, semester)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS kelas (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nama_kelas VARCHAR(50) NOT NULL,
  tingkat VARCHAR(10) NOT NULL,
  jurusan VARCHAR(50) NULL,
  tahun_pelajaran_id INT UNSIGNED NULL,
  wali_kelas VARCHAR(100) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY uq_kelas_tahun (nama_kelas, tahun_pelajaran_id),
  CONSTRAINT fk_kelas_tahun FOREIGN KEY (tahun_pelajaran_id) REFERENCES tahun_pelajaran(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS siswa (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  nis VARCHAR(20) NOT NULL UNIQUE,
  nama VARCHAR(100) NOT NULL,
  kelas_id INT UNSIGNED NULL,
  kontak VARCHAR(25) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_siswa_kelas FOREIGN KEY (kelas_id) REFERENCES kelas(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS transaksi (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  siswa_id INT UNSIGNED NOT NULL,
  tanggal DATE NOT NULL,
  jenis ENUM('masuk','keluar') NOT NULL,
  jumlah DECIMAL(15,2) NOT NULL,
  keterangan VARCHAR(255) NULL,
  saldo DECIMAL(15,2) NOT NULL DEFAULT 0,
  approval_required BOOLEAN NOT NULL DEFAULT FALSE,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at TIMESTAMP NULL DEFAULT NULL ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT fk_transaksi_siswa FOREIGN KEY (siswa_id) REFERENCES siswa(id) ON DELETE CASCADE,
  INDEX idx_transaksi_siswa_tanggal (siswa_id, tanggal, id),
  CONSTRAINT chk_transaksi_jumlah CHECK (jumlah > 0)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS approval_status (
  id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(50) NOT NULL UNIQUE,
  description VARCHAR(255) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
INSERT IGNORE INTO approval_status (name) VALUES ('pending'),('approved'),('rejected'),('revised');

CREATE TABLE IF NOT EXISTS transaksi_approval (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  transaksi_id BIGINT UNSIGNED NOT NULL UNIQUE,
  status_id INT UNSIGNED NOT NULL,
  requested_by INT UNSIGNED NOT NULL,
  approved_by INT UNSIGNED NULL,
  rejection_reason TEXT NULL,
  request_date TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  approval_date TIMESTAMP NULL,
  FOREIGN KEY (transaksi_id) REFERENCES transaksi(id) ON DELETE CASCADE,
  FOREIGN KEY (status_id) REFERENCES approval_status(id),
  FOREIGN KEY (requested_by) REFERENCES admin(id),
  FOREIGN KEY (approved_by) REFERENCES admin(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS audit_log (
  id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
  admin_id INT UNSIGNED NULL,
  table_name VARCHAR(100) NOT NULL,
  record_id BIGINT UNSIGNED NULL,
  action ENUM('CREATE','UPDATE','DELETE') NOT NULL,
  old_values JSON NULL,
  new_values JSON NULL,
  description TEXT NULL,
  ip_address VARCHAR(45) NULL,
  user_agent VARCHAR(500) NULL,
  created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FOREIGN KEY (admin_id) REFERENCES admin(id) ON DELETE SET NULL,
  INDEX idx_audit_table_action (table_name, action),
  INDEX idx_audit_created (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

SET FOREIGN_KEY_CHECKS = 1;
