# Laporan Audit Komprehensif — Tabungan Siswa

**Tanggal audit:** 26 September 2026
**Stack:** Laravel 13.30.1 · Inertia.js v3 (Vue 3 + TypeScript) · Tailwind CSS v4 · MySQL

Dokumen ini merangkum audit menyeluruh terhadap backend, frontend/UI-UX, database & relasi, serta keamanan aplikasi. Setiap temuan dilengkapi referensi file agar mudah ditindaklanjuti.

---

## 0. Masalah "Manifest Not Found" — SUDAH DIPERBAIKI

**Root cause:** folder `public/build/` (hasil build Vite) belum pernah di-generate di checkout ini, padahal `node_modules` dan `vendor` sudah terpasang. `public/build` memang sengaja di-`.gitignore` (baris 17), jadi ini bukan bug kode — hanya langkah build yang belum dijalankan.

**Perbaikan yang sudah dilakukan:** menjalankan `npm run build` → berhasil, 604 module ter-transform, manifest `public/build/manifest.json` sudah ada.

**Tindak lanjut disarankan:** tambahkan catatan di README/deploy checklist bahwa `npm run build` wajib dijalankan sebelum serve di luar mode dev (atau pastikan `composer run dev` selalu dipakai untuk lokal, dan proses deploy/produksi menjalankan `npm run build` otomatis).

---

## 1. Ringkasan Eksekutif

Aplikasi ini adalah **aplikasi desktop-style single-operator** (bukan SaaS multi-tenant) untuk mengelola tabungan siswa di sekolah. Kualitas kode backend untuk logika keuangan (locking, approval threshold, service layer) **tergolong kuat**. Namun ada beberapa temuan penting:

| # | Temuan | Area | Severity |
|---|--------|------|----------|
| 1 | Tidak ada autentikasi nyata — middleware `UsePersonalAdmin` auto-login sebagai admin pertama tanpa cek kredensial sama sekali | Security | **Kritis (jika pernah diakses lewat jaringan)** |
| 2 | Hapus siswa men-cascade hapus seluruh riwayat transaksi keuangan (hard delete, tanpa soft delete di model manapun) | Database | **Tinggi** |
| 3 | Route `/backup`, `/pengaturan`, `/laporan/*` tidak punya proteksi role `admin.role` | Security | **Tinggi** |
| 4 | Bug kelas Tailwind dinamis di `Dashboard.vue` — sebagian warna badge/progress bar tidak ter-compile, sehingga elemen tampil tanpa warna di production | Frontend | **Sedang-Tinggi** |
| 5 | `tsconfig.json` tidak ada sama sekali padahal semua halaman pakai `lang="ts"` — TypeScript tidak pernah benar-benar type-checked | Frontend | **Sedang** |
| 6 | File `akun_sementara.md` di root berisi password admin sementara dalam bentuk plaintext | Security | **Sedang** |
| 7 | Tidak ada CSP header; hanya security header dasar yang aktif | Security | **Rendah-Sedang** |
| 8 | Duplikasi UI signifikan (card, tabel, form field, flash banner) tanpa komponen bersama | Frontend | **Rendah (maintainability)** |
| 9 | Migration rollback (`down()`) pada `redesign_transaksi_approval_as_request` bisa diam-diam menghapus baris approval yang masih pending | Database | **Sedang** |

Lihat bagian masing-masing untuk detail lengkap dan rekomendasi.

---

## 2. Backend

### 2.1 Struktur aplikasi
- `app/Http/Controllers`: 11 controller + `Auth/AdminAuthController` (dead code, lihat §4.1).
- `app/Http/Middleware`: 4 middleware kustom (`UsePersonalAdmin`, `AdminRole`, `SecurityHeaders`, `HandleInertiaRequests`).
- `app/Models`: 9 model.
- `app/Observers`: `AuditableObserver` (mencatat perubahan ke tabel `audit_log`).
- `app/Services`: `TransactionService`, `BackupService`.
- **Tidak ada** `app/Http/Requests` (semua validasi inline) dan **tidak ada** `app/Policies` (otorisasi ad-hoc via `abort_unless`).
- Tidak ada `routes/api.php` — murni aplikasi Inertia/session.

Pola arsitektur: "fat controller" tipis dengan dua service untuk logika yang benar-benar stateful (transaksi & backup). Cukup wajar untuk skala aplikasi ini, tapi validasi inline berulang di banyak controller sebaiknya dipindah ke Form Request agar reusable.

### 2.2 Model — catatan penting
- **`Siswa`** (`app/Models/Siswa.php:23-43`): saldo dihitung on-the-fly lewat accessor `SUM(CASE WHEN jenis='masuk'...)`, bukan kolom tersimpan di level siswa.
- **`Transaksi`**: punya `scopeEffective()` (`Transaksi.php:11-19`) yang menganggap transaksi "valid" hanya jika tidak punya approval pending/rejected. Scope ini **load-bearing** untuk semua perhitungan saldo/laporan — developer baru mudah lupa memakainya jika query `Transaksi` langsung.
- **`Admin`**: otorisasi hanya berupa perbandingan string kolom `role` (`isAdmin()`), tanpa package roles/permissions.
- **`User`** (model scaffold Laravel default) **tidak terpakai** — guard aplikasi adalah `admin`, bukan `web`/`users`. Tabel `users`, `UserFactory`, `password_reset_tokens` adalah sisa scaffold `laravel new` yang sebaiknya dibersihkan agar tidak membingungkan mana sumber otentikasi yang sebenarnya.
- Semua model pakai `$fillable` eksplisit (tidak ada `$guarded = []` yang berbahaya).

### 2.3 Controller
- Tidak ada Form Request — validasi inline di setiap action (`ApprovalController.php:70-81`, `MasterDataController.php:24,59,72,98,109`, dst).
- Otorisasi redundan: `ApprovalController::update` (baris 65-68) melakukan `abort_unless(...isAdmin(), 403)` padahal route sudah dijaga middleware `admin.role` di route yang sama (`routes/web.php:47`) — defense-in-depth yang baik meski duplikatif.
- Query list konsisten pakai eager loading (`with()`/`withCount()`/`withSum()`) — tidak ditemukan N+1 nyata.
- `ReportController` menjalankan query yang sama dua kali (paginated + `->get()` untuk summary) — pemborosan round-trip DB. `TransactionController::index` sudah lebih baik dengan pola `(clone $query)` (`TransactionController.php:37`) — sebaiknya `ReportController` disamakan.
- **Bagus:** endpoint yang menyentuh uang (`TransactionService::create`, `ApprovalController::update`) dibungkus `DB::transaction()` + `lockForUpdate()` — pola locking yang benar untuk mencegah race condition.

### 2.4 Logika bisnis keuangan (bagian terkuat dari codebase)
- `TransactionService::create` (`app/Services/TransactionService.php:16-64`) mengunci baris `Siswa` (`lockForUpdate()`) sebelum baca+tulis saldo.
- Penarikan di atas `config('tabungan.approval_threshold')` (default Rp 1.000.000, `config/tabungan.php:4`, env `APPROVAL_THRESHOLD`) dialihkan menjadi baris `TransaksiApproval` pending, saldo tidak berubah sampai disetujui.
- `ApprovalController::update` mengunci ulang baris approval dan mengecek ulang status `pending` (mencegah double-processing) serta memvalidasi ulang saldo cukup saat approval (bukan hanya saat request awal) — solid.
- **Risiko drift:** `Transaksi.saldo` adalah snapshot running-balance per baris, sedangkan `Siswa::getSaldoAttribute()` menghitung ulang dari `SUM(...)`. Tidak ada job rekonsiliasi/invariant check — jika ada transaksi yang diedit/dihapus di luar jalur resmi (mis. lewat Tinker), dua sumber ini bisa berbeda.
- Validasi `jumlah` di `TransactionController::store` (baris 70) pakai `numeric|min:1` tanpa `max` — setoran/penarikan dengan nominal ekstrem tidak dibatasi sanity check.

### 2.5 Testing
688 baris di 7 Feature test file (`PersonalAccessTest`, `TransactionTest`, `SettingsTest`, `StudentSpreadsheetTest`, `BackupTest`, `DailyManagementTest`, dll) + 1 Unit test stok bawaan. Tidak ada Unit test terisolasi untuk `TransactionService`/locking logic (hanya diuji lewat HTTP layer). Tidak ditemukan test khusus untuk race-condition guard di `ApprovalController::update` — layak ditambahkan mengingat ini jalur paling sensitif-konkurensi di aplikasi.

---

## 3. Frontend & UI/UX

### 3.1 Struktur
- `resources/js/Pages`, `Components` (hanya 4 komponen reusable), `Layouts` (`MainLayout.vue`), plus modul state kecil custom (`activity.ts`, `confirmation.ts`) — tidak pakai Pinia/Vuex.
- **File mati:** `resources/js/app.js` (isi cuma komentar `//`) — entry point sebenarnya adalah `app.ts`. Aman dihapus.
- Tidak ada dark mode sama sekali (tidak ada `dark:` variant atau `prefers-color-scheme`).

### 3.2 Halaman & komponen
Halaman tercakup lengkap: Auth/Login, Dashboard, Master (Kelas, Siswa, Statement/buku tabungan, TahunPelajaran), Transactions (Index, Create), Approvals, Audit, Reports, Backups, Settings. Semua form pakai Inertia `useForm` — tidak ada penggunaan `Inertia::defer`/deferred props (semua data dimuat sinkron dari server), jadi tidak perlu skeleton loading, tapi juga tidak ada keuntungan performa dari partial loading di halaman berat (Dashboard, Reports).

Hanya 4 komponen reusable (`Pagination`, `ConfirmDialog`, `ActivityFeedback`, `DownloadLink`) — bagus untuk pagination/konfirmasi/download/loading, tapi **tidak ada komponen bersama untuk**: card (`sneat-card` diulang manual di mana-mana), tabel (markup `<thead>/<tbody>` nyaris identik diduplikasi di ~8 halaman), form field (pola label/pesan error disalin manual per field), dan flash-message banner (disalin di minimal 5 halaman dengan markup/warna sedikit berbeda-beda). Ini smell duplikasi nyata — membuat komponen `FlashBanner`, `DataTable`, dan `FormField` akan menghilangkan sebagian besar duplikasi ini.

### 3.3 Bug yang ditemukan
1. **Bug Tailwind dinamis di `Dashboard.vue`** (baris 14): kelas dibangun secara dinamis seperti `` `bg-${card.color}-100 text-${card.color}-600` `` untuk warna `indigo/emerald/orange/blue`, dan `` `bg-${item.color}-400`/`text-${item.color}-600` `` untuk `emerald/rose`. Tailwind v4 hanya mendeteksi string kelas literal — tidak ada safelist. **Sudah diverifikasi**: setelah `npm run build`, kelas `bg-orange-100`, `text-orange-600`, `bg-emerald-100`, `text-blue-600` **tidak ada** di CSS hasil compile (`public/build/assets/app-CUN84GUj.css`). Akibatnya badge "Transaksi hari ini" (orange), "Total transaksi" (blue), dan progress bar emerald/rose akan tampil tanpa warna di production. **Perbaikan:** ganti dengan lookup map berisi string kelas lengkap/literal, atau tambahkan safelist eksplisit.
2. **`Login.vue`** — input password tidak punya paragraf error sama sekali (hanya `username` yang punya). Jika backend mengembalikan error tervalidasi pada key `password`, user tidak melihat apa-apa.
3. Handler `remove()` di `Siswa.vue`/`Kelas.vue` tidak punya `onError` — mengandalkan flash message dari server; jika request gagal sebelum sampai server (offline), tidak ada error lokal selain toast `networkError` global.

### 3.4 TypeScript
**Tidak ada `tsconfig.json` di seluruh repo**, padahal semua file `.vue` pakai `<script setup lang="ts">` dan `typescript`/`vue-tsc` ada di devDependencies. Tidak ada script `type-check` di `package.json`. Artinya **TypeScript tidak pernah benar-benar di-type-check** — Vite/esbuild hanya strip tipe tanpa validasi. Ditambah penggunaan `any` yang pervasif di hampir semua props halaman (`defineProps<{items:any}>()` dst, kecuali Dashboard dan Settings yang punya interface). Tidak ada tipe bersama (`Siswa`, `Transaksi`, `PaginatedResponse<T>`) — tiap halaman mendefinisikan ulang shape yang tumpang tindih.

**Rekomendasi:** tambahkan `tsconfig.json` + script `"type-check": "vue-tsc --noEmit"`, dan buat folder `resources/js/types/` untuk tipe domain bersama.

### 3.5 Kualitas kode & formatting
Tidak ada ESLint/Prettier untuk JS/TS/Vue (Pint hanya format PHP). Beberapa file di-minify manual jadi satu baris panjang (`Master/Siswa.vue`, `Master/Kelas.vue`, `Reports/Index.vue`, `Approvals/Index.vue`, `Audit/Index.vue`), sementara yang lain terformat rapi (`Settings/Index.vue`, `Transactions/Create.vue`) — inkonsistensi ini menyulitkan review/diff. Tidak ditemukan `console.log`/`debugger`/`TODO` yang tertinggal — bersih di sisi itu.

Aksesibilitas (aria-label, aria-live, role=alert) diterapkan dengan baik di Settings & Transactions/Create, tapi nihil (grep 0 hasil) di `Master/Kelas.vue`, `Master/Statement.vue`, `Dashboard.vue`, `Audit/Index.vue` — perlu diratakan.

---

## 4. Database & Relasi

### 4.1 Skema tabel
| Tabel | Ringkasan |
|---|---|
| `admin` | username(unik), password, nama, role enum(admin,operator) |
| `tahun_pelajaran` | tahun, semester enum, status enum; unique(tahun,semester) |
| `kelas` | nama_kelas, tingkat, jurusan, tahun_pelajaran_id (FK nullable, nullOnDelete), wali_kelas; unique(nama_kelas, tahun_pelajaran_id) |
| `siswa` | nis(unik), nama, kelas_id (FK nullable, nullOnDelete), kontak |
| `transaksi` | siswa_id (FK, **cascadeOnDelete**), tanggal, jenis enum(masuk,keluar), jumlah decimal(15,2), saldo decimal(15,2), approval_required |
| `approval_status` | name(unik) — seeded: pending/approved/rejected/revised |
| `transaksi_approval` | transaksi_id (FK unik, cascadeOnDelete), status_id, requested_by, approved_by (nullOnDelete), siswa_id (FK **restrictOnDelete** — lihat inkonsistensi di bawah), tanggal/jumlah/keterangan terduplikasi dari `transaksi` |
| `audit_log` | admin_id (nullOnDelete), table_name, record_id, action enum(CREATE,UPDATE,DELETE), old_values/new_values json, ip_address, user_agent — append-only (`created_at` saja) |
| `settings` | key(unik)/value |
| `users`, `password_reset_tokens`, `sessions`, `cache`, `jobs` | scaffold Laravel default, **tidak dipakai** aplikasi (lihat §2.2) |

**Uang disimpan dengan benar** sebagai `decimal(15,2)` — tidak ditemukan bug float-for-currency.

### 4.2 Temuan integritas data — prioritas tinggi

1. **Cascade delete siswa menghapus seluruh riwayat transaksi** (`transaksi.siswa_id` → `cascadeOnDelete`). **Tidak ada model yang pakai `SoftDeletes`** sama sekali di seluruh skema (`siswa`, `transaksi`, `transaksi_approval`, `admin`). Untuk sistem tabungan/keuangan, ini berarti menghapus siswa = **hard delete permanen** seluruh buku tabungannya tanpa jejak, tanpa jalur pemulihan. Ini temuan paling konsekuensial dalam audit database — sebagian besar praktik akuntansi/regulasi tabungan sekolah mensyaratkan riwayat transaksi tetap tersimpan/terarsip walau entitas induknya dihapus.
2. **Inkonsistensi cascade**: `transaksi.siswa_id` cascade (hapus riwayat), tapi `transaksi_approval.siswa_id` restrict (blokir penghapusan) — untuk parent yang sama (siswa), dua child punya perilaku delete yang berlawanan. Kemungkinan besar tidak disengaja.
3. `transaksi_approval.requested_by` (FK ke admin) tidak menentukan `onDelete` → default **RESTRICT**, sedangkan `approved_by` pakai `nullOnDelete` — admin yang pernah membuat request approval apa pun tidak akan pernah bisa dihapus, padahal `approved_by` bisa. Inkonsisten, sebaiknya disamakan (nullOnDelete direkomendasikan agar histori tetap ada meski admin dihapus).
4. Duplikasi data: `transaksi_approval` menyimpan `siswa_id`/`tanggal`/`jumlah`/`keterangan` sendiri, padahal data yang sama bisa dijangkau lewat `transaksi_approval.transaksi.siswa/tanggal/jumlah` — berpotensi drift dari sumber aslinya. Perlu diverifikasi di service layer apakah `transaksi_id` memang bisa null (mis. untuk draft request sebelum baris `transaksi` dibuat).
5. `kelas` unique(nama_kelas, tahun_pelajaran_id) — karena `tahun_pelajaran_id` nullable dan MySQL menganggap NULL berbeda-beda di unique index, beberapa baris "kelas X" dengan tahun NULL bisa duplikat tanpa terdeteksi. Edge case minor.
6. Tidak ada index eksplisit pada `siswa.nama` — berisiko full table scan jika UI melakukan pencarian nama saat data membesar.

### 4.3 Migration
- Konsisten pakai gaya migration anonymous class (Laravel 9+). `down()` diimplementasikan di semua migration kustom.
- **Migration `redesign_transaksi_approval_as_request`**: method `down()`-nya menjalankan `DB::table('transaksi_approval')->whereNull('transaksi_id')->delete();` sebelum mengembalikan `transaksi_id` ke NOT NULL. Ini **destruktif** — rollback migration ini di production akan diam-diam menghapus baris approval-request yang belum punya `transaksi_id` (justru baris pending yang ingin didukung fitur redesign ini). Minimal perlu warning/log, atau blokir rollback jika ada baris seperti itu.
- Migration `create_tabungan_domain_tables` pakai gaya closure array satu baris (`Schema::create('x', fn($t) => [...])`) — tidak salah, tapi tidak konsisten dengan gaya migration lain di proyek (block style biasa) dan lebih sulit di-diff/dibaca.

### 4.4 Seeder & Factory
- Hanya `DatabaseSeeder` (idempotent via `updateOrInsert`) yang membuat 1 admin, 4 baris `approval_status`, 3 baris `settings`. Password admin default (`SEED_ADMIN_PASSWORD` atau `Str::random(24)`) hanya tampil di output console — tidak tersimpan di tempat lain, perlu didokumentasikan di README agar tidak hilang.
- **Tidak ada factory** untuk model domain (`Siswa`, `Kelas`, `Transaksi`, `TahunPelajaran`) — hanya `UserFactory` bawaan (untuk tabel `users` yang tidak dipakai). Tidak ada cara cepat generate data demo/testing realistis.

### 4.5 Audit trail
Tabel `audit_log` sudah ada dan menangkap `admin_id`, `action`, `old_values`/`new_values` (JSON), IP, user-agent — bagus. Namun **perlu diverifikasi apakah setiap mutasi finansial benar-benar tercatat** secara konsisten (lewat `AuditableObserver`) atau ada jalur tulis yang terlewat — audit trail sistem keuangan hanya sekuat titik tulis yang paling lemah/belum terinstrumentasi.

---

## 5. Keamanan

> Catatan: ini adalah audit keamanan internal atas aplikasi milik sendiri (defensive review), bukan penetration test aktif.

### 5.1 Autentikasi — **KRITIS**
Ada implementasi login yang layak (`app/Http/Controllers/Auth/AdminAuthController.php`) lengkap dengan rate limiting (5 percobaan/15 menit), `Hash`-based `Auth::guard('admin')->attempt()`, regenerasi session saat login, invalidasi saat logout. **Tapi controller ini tidak pernah dihubungkan ke `routes/web.php`.** `GET /login` hanya `fn () => to_route('dashboard')` (`web.php:17`), dan tidak ada route `POST /login` atau logout sama sekali.

Sebagai gantinya, setiap route group dibungkus middleware `UsePersonalAdmin` (`web.php:19`) yang **otomatis meng-autentikasi setiap request sebagai baris Admin pertama (atau membuatnya secara diam-diam jika belum ada) tanpa pengecekan kredensial apa pun** (`UsePersonalAdmin.php:20-33`). Artinya seluruh aplikasi **tidak punya batas otentikasi** — siapa pun yang mencapai route ini otomatis dianggap admin penuh.

**Konfirmasi dari review backend:** perilaku ini **disengaja dan didokumentasikan lewat test** (`tests/Feature/PersonalAccessTest.php` secara eksplisit menguji bahwa `/login` redirect pergi dan dashboard bisa diakses "tanpa login"). Ini memang didesain sebagai **aplikasi single-operator, gaya desktop lokal** — bukan celah yang tidak disadari.

**Rekomendasi:** ini **aman selama aplikasi hanya diakses di localhost/mesin sendiri**. Namun ini menjadi risiko kritis di momen mana pun aplikasi ini:
- dijalankan di jaringan LAN sekolah yang bisa diakses perangkat lain,
- di-port-forward atau di-deploy ke server/cloud yang bisa diakses dari luar.

Jika ada rencana deploy di luar mesin lokal single-user, **wajib mengaktifkan `AdminAuthController` yang sudah ada** (tinggal dihubungkan ke route) sebelum aplikasi diakses dari jaringan.

### 5.2 Otorisasi — **Tinggi**
Hanya route `/approval/*` dan `/audit-log` yang dijaga middleware `admin.role` (cek `Admin.role === 'admin'`). Route `/backup`, `/pengaturan` (termasuk upload file), `/laporan` (export laporan), dan CRUD data master **tidak punya pengecekan role** — jika suatu saat ditambahkan akun staf non-admin, route-route ini akan bocor ke role yang seharusnya tidak berhak.

`ApprovalController::update` melakukan defense-in-depth yang baik: cek ulang `isAdmin()` + `lockForUpdate()` + guard status 409 untuk mencegah double-processing.

### 5.3 Mass assignment — Info
Semua model pakai `$fillable` eksplisit. `Admin::$fillable` menyertakan `role` — saat ini tidak ada endpoint yang menerima input pembuatan admin dari luar (hanya dibuat internal oleh `UsePersonalAdmin`), jadi belum ada vektor aktif, tapi perlu dijaga jika nanti ada UI manajemen admin.

### 5.4 Validasi input — Rendah/Info
Validasi cukup ketat untuk field uang/identitas (`exists:`, `numeric|min:1`, `in:...`, `unique:`). `TransactionController::store` memvalidasi ulang di server bahwa siswa memang ada di kelas aktif, tidak percaya begitu saja pada dropdown client. Kelemahan minor: tidak ada `max` pada nominal transaksi (lihat §2.4).

### 5.5 CSRF/XSS — Aman
Tidak ditemukan `v-html` di seluruh `resources/js/` — tidak ada vektor XSS lewat template Vue. CSRF Laravel standar aktif (tidak di-disable di mana pun).

### 5.6 SQL Injection — Aman
9 penggunaan `DB::raw`/`whereRaw`/`selectRaw`, semuanya SQL statis hardcoded (mis. perhitungan saldo `COALESCE(SUM(CASE WHEN jenis = 'masuk'...))`), tidak ada input user yang di-interpolasi ke dalam raw string.

### 5.7 Penanganan file — Sedang
- Upload logo/avatar (`SettingsController::update`) divalidasi ketat (`image`, `mimes:jpg,jpeg,png,webp`, `max:2048`), file lama dihapus saat diganti, ada rollback saat gagal.
- Import XLSX (`StudentSpreadsheetController::import`) dikeraskan dengan baik: batas baris (≤1001), batas kolom (≤20), menolak sel formula (mencegah formula injection), deteksi NIS duplikat.
- Download backup dibatasi regex nama file di route dan controller — tidak ada celah path traversal.
- Generasi PDF/Excel hanya merender data dari DB via Eloquent, tidak ada string user mentah yang digabung langsung.

### 5.8 Secrets & konfigurasi — Sedang
- `.env.example` men-default `APP_DEBUG=true` — sebaiknya `false` agar tidak tertinggal apa adanya jika seseorang menyalin file ini untuk produksi.
- `.gitignore` sudah benar mengecualikan `.env`, `/vendor`, `/node_modules`, `/public/build`, dll.
- **`akun_sementara.md`** di root (belum tracked git) berisi password admin sementara dalam bentuk plaintext. Karena untracked, tidak akan masuk riwayat git, tapi tetap berisiko jika suatu saat ter-`git add -A`. **Rekomendasi: hapus file ini, atau minimal tambahkan ke `.gitignore` sekarang juga** (karena `UsePersonalAdmin` membuat password admin manapun yang tersimpan jadi tidak relevan untuk login, file ini sudah tidak berguna).

### 5.9 Dependency — Info
`laravel/framework` 13.30.1, `inertiajs/inertia-laravel` 3.3.3, `laravel/boost` 2.8.1, `phpunit` 12.5.34, `barryvdh/laravel-dompdf` 3.1.2, `phpoffice/phpspreadsheet` 5.9.0 — semua versi mayor terkini, tidak ada yang diketahui outdated/EOL.

### 5.10 Middleware & header keamanan — Rendah/Sedang
`SecurityHeaders` middleware sudah mengatur `X-Content-Type-Options`, `X-Frame-Options: SAMEORIGIN`, `Referrer-Policy`, `Permissions-Policy`, dan HSTS kondisional saat HTTPS — baseline yang baik. **Belum ada header `Content-Security-Policy`** — tidak ada lapisan defense-in-depth tambahan seandainya suatu saat ada vektor XSS baru. Belum ada konfigurasi trusted proxy (belum perlu untuk pemakaian lokal, tapi wajib diatur jika nanti di-deploy di balik reverse proxy/load balancer).

---

## 6. Rekomendasi Prioritas

### Harus dilakukan sebelum deploy di luar mesin lokal single-user
1. Aktifkan `AdminAuthController` yang sudah ada — hubungkan ke route `POST /login` dan logout, matikan/batasi `UsePersonalAdmin` hanya untuk mode lokal eksplisit (mis. flag env `APP_SINGLE_USER_MODE`).
2. Tambahkan middleware `admin.role` ke route `/backup`, `/pengaturan`, `/laporan/*`.
3. Hapus atau `.gitignore`-kan `akun_sementara.md`.

### Prioritas tinggi (integritas data keuangan)
4. Tambahkan `SoftDeletes` ke `Siswa` dan `Transaksi` minimal, ubah `transaksi.siswa_id` dari `cascadeOnDelete` ke soft-delete-aware / restrict, agar riwayat tabungan tidak pernah hilang permanen saat siswa dihapus.
5. Samakan perilaku `onDelete` antara `transaksi.siswa_id` dan `transaksi_approval.siswa_id`, serta antara `requested_by` dan `approved_by` di `transaksi_approval`.
6. Perbaiki `down()` migration `redesign_transaksi_approval_as_request` agar tidak diam-diam menghapus baris approval pending saat rollback.

### Prioritas sedang (frontend)
7. Perbaiki bug kelas Tailwind dinamis di `Dashboard.vue` (ganti ke lookup map kelas literal).
8. Tambahkan paragraf error untuk field password di `Login.vue`.
9. Tambahkan `tsconfig.json` + script `type-check` (`vue-tsc --noEmit`), lalu kurangi penggunaan `any` di props halaman secara bertahap.
10. Ekstrak komponen bersama: `FlashBanner`, `DataTable`/table wrapper, `FormField` — akan menghapus sebagian besar duplikasi markup lintas halaman.

### Nice-to-have
11. Tambahkan header `Content-Security-Policy`.
12. Tambahkan factory untuk model domain (`Siswa`, `Kelas`, `Transaksi`, `TahunPelajaran`) agar testing/demo data lebih mudah.
13. Bersihkan scaffold Laravel default yang tidak dipakai (`User` model, tabel `users`, `UserFactory`) agar tidak membingungkan developer baru soal sumber auth yang sebenarnya.
14. Tambahkan `max` pada validasi nominal transaksi sebagai sanity check.
15. Standarkan formatting file Vue (pertimbangkan ESLint/Prettier) — beberapa file saat ini di-minify manual jadi satu baris, menyulitkan review.

---

*Laporan ini disusun berdasarkan pembacaan kode sumber langsung (bukan pengujian eksekusi terhadap sistem production), mencakup seluruh direktori `app/`, `resources/js/`, `database/`, `routes/`, dan `config/` pada commit `7bada64`.*
