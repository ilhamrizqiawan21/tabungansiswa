# 📊 Tabungan Siswa (Student Savings Management System)

Sistem Manajemen Tabungan Siswa adalah aplikasi berbasis web yang dirancang untuk membantu sekolah dalam mengelola data tabungan siswa secara efisien, transparan, dan aman.

## 🚀 Fitur Utama

### 1. 👥 Manajemen Data
*   **Data Siswa**: Pengelolaan lengkap (CRUD) informasi siswa.
*   **Data Kelas**: Pengelolaan kelas yang terintegrasi dengan Tahun Pelajaran.
*   **Multi Tahun Pelajaran**: Mendukung isolasi data per tahun akademik (Ganjil/Genap) dengan status tahun aktif.

### 2. 💰 Transaksi Tabungan
*   **Setoran & Penarikan**: Pencatatan mutasi saldo siswa secara real-time.
*   **Approval Workflow**: Sistem persetujuan untuk transaksi besar atau khusus (Pending → Approved/Rejected).
*   **Riwayat Saldo**: Pelacakan saldo otomatis setiap transaksi.

### 3. 🛡️ Keamanan & Audit (Enterprise Ready)
*   **Password Hashing**: Menggunakan algoritma **Argon2id** (standard industri terbaru).
*   **Audit Log**: Mencatat setiap perubahan data (Siapa, Kapan, Apa, Nilai Lama & Baru).
*   **CSRF Protection**: Melindungi form dari serangan Cross-Site Request Forgery.
*   **Rate Limiting**: Pencegahan Brute Force pada sistem login.
*   **Secure Session**: Manajemen session dengan timeout otomatis.

### 4. 📈 Laporan & Analitik
*   **Dashboard Analytics**: Visualisasi tren transaksi dan distribusi saldo menggunakan Chart.js.
*   **Cetak Buku Tabungan**: Format cetak profesional untuk buku tabungan siswa.
*   **Export Excel**: Export laporan transaksi menggunakan PHPSpreadsheet.
*   **Laporan Bulanan**: Rekapitulasi transaksi per periode tertentu.

## 🛠️ Tech Stack

*   **Core**: PHP (Vanilla)
*   **Database**: MySQL / MariaDB
*   **UI/UX**: Bootstrap, FontAwesome, Chart.js
*   **Dependencies**: Composer, PHPSpreadsheet
*   **Security**: Argon2id, CSRF Token, Rate Limiter

## 📁 Struktur Proyek

```text
├── auth/           # Login & Logout logic
├── config/         # Database & Auth configuration
├── includes/       # Core modules (Security, Audit, Analytics, Functions)
├── siswa/          # Modul CRUD Siswa
├── kelas/          # Modul CRUD Kelas
├── transaksi/      # Modul Transaksi Setoran & Penarikan
├── laporan/        # Modul Export, Analytics, & Print
├── pengaturan/     # Audit Log & Tahun Pelajaran settings
├── scripts/        # Migration & Utility scripts
├── assets/         # Images & Static files
└── vendor/         # Composer dependencies
```

## ⚙️ Instalasi

1.  **Clone Repository**
    ```bash
    git clone https://github.com/ilhamrizqiawan21/tabungansiswa.git
    cd tabungansiswa
    ```

2.  **Install Dependencies**
    ```bash
    composer install
    ```

3.  **Konfigurasi Database**
    *   Buat database baru di MySQL.
    *   Import schema database yang tersedia di repository. Periksa dan sanitasi data sebelum digunakan.
    *   Sesuaikan konfigurasi di `config/database.php`.

4.  **Jalankan Migrasi Keamanan**
    *   Akses `scripts/hash_admin_passwords.php` melalui browser untuk mengamankan password admin lama.
    *   Ikuti instruksi di `IMPLEMENTATION_GUIDE.md` untuk aktivasi fitur tambahan.

5.  **Akses Aplikasi**
    *   Buka `http://localhost/tabungansiswa` di browser Anda.

## 📝 Panduan Tambahan

*   **[FEATURES_GUIDE.md](FEATURES_GUIDE.md)**: Detail teknis implementasi fitur Audit Log, Multi-Tahun, dll.
*   **[IMPLEMENTATION_GUIDE.md](IMPLEMENTATION_GUIDE.md)**: Langkah-langkah setup awal keamanan.
*   **[SECURITY.md](SECURITY.md)**: Dokumentasi standar keamanan yang diterapkan.

---

**Developed with ❤️ for Better Education Management**
