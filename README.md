# Tabungan Siswa

Aplikasi pencatatan tabungan siswa (setoran, penarikan, approval, laporan, backup) berbasis Laravel 13 + Inertia v3 + Vue 3.

## Kebutuhan

- PHP 8.3 dengan ekstensi `pdo_mysql`/`pdo_sqlite`, `zip`, `gd`, `mbstring`
- Composer, Node.js 20+
- MySQL/MariaDB (disarankan) atau SQLite
- `mysqldump`/`mariadb-dump` di `PATH` bila memakai MySQL (untuk fitur backup)

## Instalasi

```bash
composer install
cp .env.example .env
php artisan key:generate
# atur DB_* di .env, lalu:
php artisan migrate
php artisan db:seed
php artisan storage:link
npm install
npm run build
```

### Akun admin dari seeder

`php artisan db:seed` membuat akun admin dan status approval.

- Atur `SEED_ADMIN_USERNAME`, `SEED_ADMIN_NAME`, dan `SEED_ADMIN_PASSWORD` di `.env` sebelum seeding.
- Jika `SEED_ADMIN_PASSWORD` kosong, seeder membuat password acak dan **menampilkannya sekali** di terminal. Catat dan ganti setelah login.

## Menjalankan

- **Pengembangan:** `composer run dev` (server + Vite dengan hot reload).
- **Non-dev / produksi:** `npm run build` **wajib** dijalankan setiap kali kode frontend berubah; tanpa itu muncul error *Unable to locate file in Vite manifest*. Set `APP_ENV=production` dan `APP_DEBUG=false`.
- **Penjadwal:** backup otomatis harian memakai scheduler Laravel. Jalankan `php artisan schedule:work` (atau cron `* * * * * php artisan schedule:run`). Atur jam dengan `BACKUP_SCHEDULE` (kosongkan untuk menonaktifkan) dan jumlah arsip yang disimpan dengan `BACKUP_RETENTION`.

## Perintah artisan

| Perintah | Fungsi |
| --- | --- |
| `php artisan tabungan:backup [--prune]` | Membuat arsip ZIP (database + uploads); `--prune` menghapus arsip lama di luar batas retensi |
| `php artisan tabungan:rekonsiliasi [--siswa=ID]` | Membandingkan snapshot saldo transaksi terakhir dengan `SUM()` transaksi per siswa |

## Konsep penting

- **Sesi aktif** (tahun pelajaran + kelas) dipilih di *Pengaturan* dari data master *Kelas* dan *Tahun pelajaran*. Siswa, transaksi harian, dan impor/ekspor mengikuti kelas aktif.
- **Koreksi transaksi** tidak mengedit/menghapus baris lama. Admin membuat transaksi pembalik (nominal sama, jenis berlawanan) beserta alasan; tercatat di audit log.
- **Siswa lulus/keluar** diubah statusnya (opsional sekaligus menarik seluruh saldo), bukan dihapus. Siswa dan transaksi memakai *soft delete*, dan siswa yang memiliki transaksi tidak bisa dihapus permanen.
- **Kenaikan kelas**: pilih siswa di halaman *Siswa*, lalu pindahkan ke kelas tujuan. Saldo ikut berpindah.
- Penarikan di atas `APPROVAL_THRESHOLD` menjadi pengajuan dan baru mengurangi saldo setelah disetujui admin.

## Pemulihan backup

Langkah lengkap ada di halaman *Backup data* dan di `PETUNJUK.txt` dalam setiap arsip. Ringkasnya: ekstrak ZIP, impor `database.sql` (MySQL) atau salin `database.sqlite`, salin `uploads/` ke `storage/app/public`, jalankan `php artisan storage:link`, `php artisan migrate`, lalu `php artisan tabungan:rekonsiliasi`.

## Pengujian & kualitas kode

```bash
php artisan test --compact     # PHPUnit
npm run test:ui                # test frontend (node:test)
npm run type-check             # vue-tsc
npm run lint                   # ESLint
npm run format                 # Prettier
vendor/bin/pint                # format PHP
```
