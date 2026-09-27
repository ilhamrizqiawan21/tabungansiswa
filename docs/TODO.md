# TODO — Tabungan Siswa

**Diperbarui:** 27 September 2026
**Sumber:** `docs/audit/LAPORAN-AUDIT-PROYEK.md` (26/09/2026), dicek ulang terhadap working tree saat ini, ditambah audit UI/UX, fitur, dan desain.

Legenda prioritas: **P0** blocker/kritis · **P1** tinggi · **P2** sedang · **P3** nice-to-have
`[x]` = sudah dikerjakan di working tree (belum di-commit) · `[ ]` = belum

---

## A. Status temuan laporan audit

### Sudah dikerjakan (perlu di-commit + dites)
- [x] `admin.role` ditambahkan ke `/backup`, `/pengaturan`, `/laporan/*` (`routes/web.php:42-55`)
- [x] Bug kelas Tailwind dinamis di Dashboard → lookup map literal (`Dashboard.vue:14-24`)
- [x] `tsconfig.json` + script `type-check` di `package.json`
- [x] Header `Content-Security-Policy` (non-local) di `SecurityHeaders.php`
- [x] Scaffold `User`, `UserFactory`, tabel `users`/`password_reset_tokens` dihapus (+ migration `drop_unused_users_scaffold_tables`)
- [x] `down()` migration `redesign_transaksi_approval_as_request` tidak lagi menghapus diam-diam, sekarang melempar exception
- [x] Validasi dipindah ke Form Request (`app/Http/Requests/*`)
- [x] Ekspresi SQL saldo diseragamkan (`Transaksi::SALDO_EXPRESSION`, `RINGKASAN_EXPRESSION`)
- [x] Query ganda di `ReportController` diubah (ada `tests/Feature/ReportControllerTest.php`)

### Masih terbuka
- [ ] **P0** Autentikasi: hubungkan `AdminAuthController` ke `POST /login` + logout, batasi `UsePersonalAdmin` di balik flag env (mis. `APP_SINGLE_USER_MODE`). Wajib sebelum diakses lewat LAN/server.
- [ ] **P0** Hapus `akun_sementara.md` atau masukkan ke `.gitignore` (masih belum ada di `.gitignore`).
- [x] **P1** `SoftDeletes` untuk `Siswa` & `Transaksi`; ubah `transaksi.siswa_id` dari `cascadeOnDelete` ke `restrictOnDelete`.
- [x] **P1** Samakan `onDelete`: `transaksi.siswa_id` (cascade) vs `transaksi_approval.siswa_id` (restrict); `requested_by` (restrict default) vs `approved_by` (nullOnDelete).
- [x] **P1** `.env.example`: `APP_DEBUG=false`.
- [x] **P2** Jalankan `npm run type-check` dan perbaiki error yang muncul (tsconfig baru ditambahkan, belum pernah dijalankan bersih).
- [x] **P2** Buat `resources/js/types/` (`Siswa`, `Transaksi`, `Paginated<T>`, `PageProps`) dan ganti `any` di props halaman.
- [x] **P2** `Login.vue`: tampilkan error untuk field `password`.
- [x] **P2** Batas `max` untuk `jumlah` di `StoreTransaksiRequest.php:23`.
- [x] **P2** Test race-condition/double-processing untuk `ApprovalController::update`; unit test `TransactionService`.
- [x] **P2** Perintah/job rekonsiliasi: bandingkan `transaksi.saldo` (snapshot) dengan `SUM()` per siswa.
- [x] **P3** Factory model domain (`Siswa`, `Kelas`, `Transaksi`, `TahunPelajaran`) — folder `database/factories` sekarang kosong.
- [x] **P3** Index `siswa.nama`; tangani unique `kelas(nama_kelas, tahun_pelajaran_id)` saat tahun NULL.
- [x] **P3** Hapus file mati `resources/js/app.js`.
- [x] **P3** ESLint + Prettier untuk Vue/TS; format ulang file yang di-minify satu baris (`Master/Siswa.vue`, `Master/Kelas.vue`, `Master/KelasForm.vue`, `Reports/Index.vue`, `Approvals/Index.vue`, `Audit/Index.vue`, `Dashboard.vue`).
- [x] **P3** Catatan README/deploy: `npm run build` wajib sebelum serve non-dev; dokumentasikan password seeder.
- [x] **P3** Rapikan migration `create_tabungan_domain_tables` (gaya closure array satu baris).

---

## B. Navigasi & Informasi Arsitektur

- [x] **P1** Halaman **Kelas** dan **Tahun pelajaran** tidak ada di sidebar dan tidak ditautkan dari halaman mana pun — hanya bisa dibuka lewat URL langsung (`MainLayout.vue:16-26`). Tambahkan ke grup menu (mis. "Master data") atau hapus jika memang tidak dipakai.
- [x] **P1** Dua sumber kebenaran untuk sesi aktif: Pengaturan memakai input teks bebas untuk tahun/kelas (`Settings/Index.vue:131,143`), sementara ada CRUD master Kelas & Tahun pelajaran terpisah. Putuskan satu model: ganti input teks menjadi `<select>` dari data master, atau buang CRUD master.
- [x] **P1** Menu untuk role `operator` tidak sinkron dengan route: `visibleItems` hanya menyembunyikan Approval & Audit log (`MainLayout.vue:27`), padahal Backup, Pengaturan, Laporan sekarang admin-only → operator klik menu dan mendapat 403. Link "Ubah sesi" di session bar (`MainLayout.vue:82`) juga ke `/pengaturan`.
- [x] **P2** Judul header (`activeItem`) salah untuk sub-halaman: `/master/kelas` dan `/master/tahun-pelajaran` tampil "Panel administrasi"; `/master/siswa/{id}/buku` tampil "Siswa".
- [x] **P2** Tambahkan `<Head title>` di 9 halaman yang belum punya: Dashboard, Approvals, Audit, Login, Kelas, KelasForm, Siswa, TahunPelajaran, Reports (tab browser semua tampil sama).
- [x] **P2** Badge jumlah approval pending di menu "Approval" (share lewat `HandleInertiaRequests`), agar admin tahu ada yang menunggu.
- [ ] **P3** Tombol logout/profil di header setelah autentikasi diaktifkan. _(ditunda: menunggu P0 autentikasi)_

---

## C. UI/UX per halaman

### Dashboard (`Pages/Dashboard.vue`)
- [x] **P2** Grafik tren: bar minimum 4% membuat bulan bernilai 0 tampak punya transaksi — pakai 0 untuk nilai 0.
- [x] **P2** Grafik tanpa nilai/tooltip dan tanpa teks alternatif — tambahkan `title`/`aria-label` per bar atau tabel ringkas tersembunyi untuk screen reader.
- [x] **P2** Ikon kartu memakai glyph unicode (`♙`, `↕`, `▤`, `◫`) — tidak konsisten dengan ikon SVG di sidebar dan render berbeda per OS/font. Ganti ke SVG.
- [x] **P3** Sapaan `page.props.appSettings.teacherName` bisa kosong → tampilkan fallback "Administrator" seperti di layout.
- [x] **P3** Jelaskan cakupan statistik (kelas aktif atau semua kelas?) di label kartu.

### Siswa (`Pages/Master/Siswa.vue`)
- [x] **P1** Klik "Edit" di baris tabel (paling bawah halaman) memuat data ke form di atas tanpa scroll/fokus — pengguna tidak melihat perubahan apa pun. Scroll ke form + fokus ke input pertama, atau gunakan dialog edit.
- [x] **P2** Urutan halaman: impor/ekspor → form tambah → baru daftar siswa. Untuk pemakaian harian, daftar seharusnya di atas; pindahkan impor/ekspor ke panel yang bisa dilipat dan form tambah ke dialog/sidebar.
- [x] **P2** Pesan error form tampil dua kali (ringkasan `role=alert` + per field `nis`).
- [x] **P3** Tombol aksi baris (Buku tabungan/Edit/Hapus) padat di mobile.

### Catat transaksi (`Pages/Transactions/Create.vue`)
- [x] **P1** Alur setoran massal lambat: setelah simpan, redirect ke daftar transaksi (`TransactionController.php:72`). Tambahkan "Simpan & catat lagi" yang kembali ke form dengan tanggal & jenis dipertahankan.
- [x] **P2** Input jumlah `step="0.01"` — rupiah tidak perlu desimal; pakai `step="1"`/`inputmode="numeric"` dan pertimbangkan tombol nominal cepat (5rb, 10rb, 20rb, 50rb).
- [x] **P2** Pencarian + `<select>` terpisah: ganti dengan combobox tunggal (ketik → pilih), lebih cepat untuk kelas besar.
- [x] **P3** Tampilkan saldo setelah transaksi (preview) di samping saldo saat ini.

### Transaksi (`Pages/Transactions/Index.vue`)
- [x] **P2** Kolom jenis hanya teks berwarna; samakan dengan badge di Dashboard. Kolom jumlah tanpa tanda +/−.
- [x] **P3** Filter `siswa_id` + `search` tumpang tindih — sederhanakan.

### Laporan (`Pages/Reports/Index.vue`)
- [x] **P2** Filter tidak memakai `<form>`: tombol "Terapkan" adalah `<Link>` sehingga Enter tidak submit; input hanya punya `aria-label` tanpa label terlihat.
- [x] **P2** Kartu ringkasan pakai gaya berbeda (`bg-emerald-50` polos) dibanding halaman lain (`sneat-card`).
- [x] **P2** Tidak ada filter per siswa/kelas; judul tabel pakai `<b>` bukan heading.

### Approval (`Pages/Approvals/Index.vue`)
- [x] **P2** Label "N transaksi pending di halaman ini" memakai `items.data.length`, bukan total — tampilkan `items.total`.
- [x] **P2** Dialog tidak menampilkan saldo siswa saat ini — admin memutuskan tanpa konteks saldo.
- [x] **P3** Tidak ada riwayat keputusan (disetujui/ditolak) — tambahkan tab/filter status.

### Audit log (`Pages/Audit/Index.vue`)
- [x] **P2** Tidak ada filter (tabel, aksi, tanggal, admin) dan tidak bisa melihat detail `old_values`/`new_values`.

### Login (`Pages/Auth/Login.vue`)
- [x] **P2** Label tidak terhubung ke input (`for`/`id` tidak ada); desain teal lama, belum mengikuti tema indigo (lihat §D).

### Komponen bersama
- [x] **P2** `ConfirmDialog` selalu berjudul "Hapus data?" + tombol "Ya, hapus" — tidak bisa dipakai untuk konfirmasi non-hapus (mis. aktifkan periode). Jadikan judul/label tombol sebagai parameter `confirmAction()`.
- [x] **P2** Aktifkan tahun pelajaran (`TahunPelajaran.vue`) langsung dieksekusi tanpa konfirmasi, padahal mengubah konteks seluruh aplikasi.

---

## D. Konsistensi desain

- [x] **P2** Dua bahasa visual bercampur:
  - Tema "sneat" indigo: `sneat-card`, padding `p-4 sm:p-6 lg:p-8`, `sneat-table`.
  - Gaya lama teal: `Approvals`, `Audit`, `Login` memakai `text-teal-700`, `rounded-2xl ring-1`, padding `p-6 sm:p-10`, `text-3xl`, tabel tanpa `sneat-table`.
  Seragamkan ke tema sneat (termasuk hapus override `.bg-teal-800` di `sneat.css` setelah Login diperbarui).
- [x] **P2** Tiga variasi header halaman (breadcrumb "A / B", eyebrow `clay-eyebrow`, eyebrow `uppercase tracking-widest`). Buat komponen `PageHeader` (eyebrow, judul, deskripsi, slot aksi).
- [x] **P2** Ekstrak komponen: `FlashBanner` (sekarang disalin di ≥6 halaman dengan padding/role berbeda, sebagian tanpa `role="status"`), `DataTable`/`TableCard`, `FormField`, `StatCard`, `EmptyState`.
- [x] **P2** Format uang tidak seragam: `Intl` currency tanpa desimal (Dashboard, Approval), `Intl` dengan desimal (Create), `"Rp " + toLocaleString` (Siswa, Transaksi, Laporan, Statement). Buat satu helper `formatRupiah` di `resources/js/`.
- [x] **P2** Lokalisasi: `APP_LOCALE=en` → pesan validasi default Laravel berbahasa Inggris dan format tanggal `d M Y` menghasilkan nama bulan Inggris ("Sep"). Set `id`, pasang file terjemahan `lang/id`, gunakan `translatedFormat()`.
- [x] **P3** Nama kelas CSS campur `sneat-*` dan `clay-*` (warisan dua tema) — satukan penamaan.
- [x] **P3** Dark mode belum ada; token warna sudah di `:root` (`sneat.css:2-10`) sehingga cukup menambah blok `prefers-color-scheme: dark`.
- [x] **P3** Aksesibilitas: fokus ring hanya untuk `a`/`button`, pastikan input juga; tabel tanpa `<caption>`/`scope`; ratakan `aria-*` di Kelas, Statement, Dashboard, Audit.

---

## E. Celah fitur (fungsional)

- [x] **P1** **Koreksi transaksi**: tidak ada edit/batal/void — `Route::resource('transaksi')` hanya `index/create/store`. Salah input nominal tidak bisa diperbaiki lewat aplikasi. Tambahkan transaksi pembalik (reversal) dengan alasan + tercatat di audit log (jangan edit/hapus baris lama agar running balance tetap valid).
- [x] **P1** **Kenaikan kelas / pindah kelas**: siswa terikat ke satu `kelas_id`; tidak ada alur memindahkan siswa (beserta saldonya) ke kelas/tahun baru saat pergantian tahun ajaran.
- [x] **P2** **Pemulihan backup**: backup bisa dibuat & diunduh, tapi tidak ada restore dari UI — dokumentasikan langkah restore atau sediakan fitur restore dengan konfirmasi.
- [x] **P2** **Siswa lulus/nonaktif**: status aktif/lulus/keluar + alur penarikan saldo penuh saat lulus (bukan hapus siswa).
- [x] **P2** **Rekap per kelas/siswa**: laporan saldo semua siswa per kelas (bukan hanya daftar transaksi), bisa dicetak/export.
- [x] **P3** Kirim bukti transaksi ke orang tua (link WhatsApp `wa.me` dari kolom `kontak`).
- [ ] **P3** Manajemen akun admin/operator (setelah autentikasi aktif) — perhatikan `role` di `Admin::$fillable`. _(ditunda: menunggu P0 autentikasi)_
- [x] **P3** Backup otomatis terjadwal + retensi.

---

## Urutan pengerjaan yang disarankan

1. Commit perubahan audit yang sudah selesai (bagian A) setelah `php artisan test` & `npm run type-check` lulus.
2. P0: `akun_sementara.md`, autentikasi (jika akan dipakai di jaringan).
3. P1 data: SoftDeletes + perilaku `onDelete`, koreksi transaksi.
4. P1 UX: navigasi Kelas/Tahun + sinkron menu operator, satu model sesi aktif, Edit siswa, "Simpan & catat lagi".
5. P2 desain: komponen bersama (`PageHeader`, `FlashBanner`, `DataTable`, `formatRupiah`) lalu seragamkan Approvals/Audit/Login.
6. Sisanya bertahap.
