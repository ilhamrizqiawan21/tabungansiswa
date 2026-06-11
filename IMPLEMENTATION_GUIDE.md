# 🚀 Quick Start - Security Implementation Guide

Panduan langkah demi langkah untuk mengimplementasikan security improvements.

---

## ⚡ Quick Summary

Sudah diimplementasikan:
- ✅ Password hashing dengan Argon2id
- ✅ CSRF token protection
- ✅ Input sanitization & validation
- ✅ Session security (30 min timeout)
- ✅ Rate limiting (5 attempts/15 min)
- ✅ Security logging
- ✅ Error handling yang aman

---

## 📝 LANGKAH IMPLEMENTASI

### LANGKAH 1: Hash Password Admin yang Sudah Ada (⏱️ 2 menit)

**Tujuan:** Mengubah password admin dari plaintext menjadi hash yang aman

1. **Buka di browser:**
   ```
   http://localhost/tabungansiswa/scripts/hash_admin_passwords.php
   ```

2. **Halaman akan menampilkan:**
   - Token khusus untuk hari ini
   - Contoh: `HASH_ADMIN_2024-06-03`

3. **Copy token kemudian akses script dengan token:**
   ```
   http://localhost/tabungansiswa/scripts/hash_admin_passwords.php?token=HASH_ADMIN_2024-06-03
   ```

4. **Klik tombol "Run Migration"**

5. **Tunggu hingga selesai, Anda akan melihat:**
   ```
   ✓ Migration Selesai!
   Updated: 1 | Skipped: 0 | Total: 1
   ```

6. **IMPORTANT: Delete file script**
   ```
   Hapus: scripts/hash_admin_passwords.php
   ```
   (Untuk keamanan, jangan tinggalkan file ini di server)

---

### LANGKAH 2: Test Login dengan Password Hashing (⏱️ 2 menit)

1. **Buka login page:**
   ```
   http://localhost/tabungansiswa/auth/login.php
   ```

2. **Masukkan credential admin:**
   - Username: `ilhamzp`
   - Password: `admin123` (password yang sama)

3. **Harus login berhasil**

4. **Verifikasi di database:**
   - Buka phpMyAdmin
   - Lihat tabel `admin`
   - Password harus berisi hash (panjang, dimulai dengan `$argon2id$`)
   - Bukan plaintext `admin123` lagi

---

### LANGKAH 3: Test CSRF Protection (⏱️ 3 menit)

1. **Login ke sistem**

2. **Buka form tambah siswa:**
   ```
   http://localhost/tabungansiswa/siswa/tambah.php
   ```

3. **Inspect HTML form** (F12 → Elements)
   ```
   Cari: <input type="hidden" name="csrf_token" value="...">
   ```

4. **Test CSRF protection:**
   - Buka Browser Console (F12)
   - Buka Form Developer Tools (F12)
   - Ubah value csrf_token menjadi nilai salah
   - Isi form dengan data
   - Submit form
   - Harus error: "Keamanan form gagal"

---

### LANGKAH 4: Check Security Logs (⏱️ 2 menit)

1. **Logs disimpan di:**
   ```
   /tabungansiswa/logs/security_YYYY-MM-DD.log
   ```

2. **Lihat file log:**
   - Lewat File Manager
   - Atau terminal: `cat logs/security_2024-06-03.log`

3. **Expected entries:**
   ```
   [2024-06-03 14:30:45] [INFO] Type: LOGIN_SUCCESS | User: 1 | ...
   [2024-06-03 14:31:20] [WARNING] Type: CSRF_FAILED | User: Anonymous | ...
   ```

---

### LANGKAH 5: Update Remaining Forms (⏱️ 15-30 menit)

Files yang masih perlu CSRF token:

```
- siswa/edit.php
- siswa/hapus.php (jika POST)
- transaksi/tambah.php
- transaksi/edit.php
- transaksi/hapus.php (jika POST)
- kelas/tambah.php
- kelas/edit.php
- kelas/hapus.php (jika POST)
```

**Template untuk setiap form:**

```php
<?php
require_once '../config/auth.php';
require_once '../config/database.php';
require_once '../includes/security.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $_SESSION['error'] = "Keamanan form gagal. Silakan coba lagi.";
    } else {
        // 2. Sanitize input
        $input = sanitizeString($_POST['input'] ?? '');
        
        // 3. Validate input
        if (empty($input)) {
            $_SESSION['error'] = "Field harus diisi";
        } else {
            // 4. Process data
            // ... database query ...
            logSecurityEvent('ACTION_DONE', 'Description', $_SESSION['admin_id'], 'INFO');
        }
    }
}
?>

<form method="post">
    <?= csrfTokenField() ?>
    <!-- Isi form Anda -->
</form>
```

---

## 🧪 TESTING CHECKLIST

- [ ] Login berhasil dengan password hashing
- [ ] Check database: password ter-hash (bukan plaintext)
- [ ] Form tambah siswa ada CSRF token
- [ ] Test CSRF: ubah token → error
- [ ] Check logs: `logs/security_YYYY-MM-DD.log` ada entry
- [ ] Session timeout: idle 31 menit → auto logout
- [ ] Rate limiting: 5x salah password → blocked 15 menit

---

## ❓ FAQ

### Q: Password saya apa sekarang?
**A:** Password tidak berubah! Sama seperti sebelumnya:
- Username: `ilhamzp`
- Password: `admin123`

Yang berubah hanya cara penyimpanan di database (dari plaintext → hash)

---

### Q: Bagaimana kalau lupa password?
**A:** Harus update manual di database atau buat script reset. Untuk sekarang:
1. Buka phpMyAdmin
2. Update tabel `admin` dengan password baru yang sudah di-hash
3. Gunakan script hash atau online tool untuk generate hash

---

### Q: Berapa lama session timeout?
**A:** 30 menit tanpa activity. Jika ingin ubah:
1. Buka: `includes/security.php`
2. Cari fungsi `initializeSecureSession()`
3. Ubah: `if ($elapsed > 1800)` → 1800 adalah 30 menit (dalam detik)

Contoh: 60 menit = 3600 detik

---

### Q: Rate limiting terlalu ketat?
**A:** Bisa diubah di `auth/login.php`:
```php
if (!checkRateLimit($_SERVER['REMOTE_ADDR'], 5, 900)) {
    // 5 = max attempts, 900 = window dalam detik (15 menit)
    // Ubah ke: 10 attempts, 1800 detik (30 menit)
}
```

---

### Q: Bagaimana jika muncul error di login?
**A:** Check file log:
1. Buka: `logs/security_YYYY-MM-DD.log`
2. Cari entry dengan waktu login Anda
3. Lihat error description
4. Jika error database, check phpMyAdmin koneksi

---

## 📊 File-File yang Diubah

| File | Perubahan |
|------|-----------|
| `includes/security.php` | 🆕 Baru - Main security functions |
| `config/auth.php` | 🔄 Updated - Gunakan security functions |
| `auth/login.php` | 🔄 Updated - Password hashing, CSRF, rate limit |
| `auth/logout.php` | 🔄 Updated - Secure session destroy |
| `siswa/tambah.php` | 🔄 Updated - CSRF token + sanitization |
| `includes/header.php` | 🔄 Updated - Include security.php |
| `logs/` | 🆕 Baru - Directory untuk security logs |
| `scripts/hash_admin_passwords.php` | 🆕 Baru - Migration script (HAPUS setelah pakai) |
| `SECURITY.md` | 🆕 Baru - Dokumentasi lengkap |

---

## 🎯 Next Phase - Phase 2 Security

Setelah Phase 1 selesai, improvement selanjutnya:

1. **RBAC (Role-Based Access Control)**
   - Admin, Guru, Bendahara roles
   - Different permissions per role

2. **Forgot Password Feature**
   - Email verification
   - Password reset link

3. **Admin User Management**
   - UI untuk tambah/edit/delete admin
   - Tidak perlu database manual

4. **Audit Trail Detailed**
   - Track semua changes
   - Who, when, what, old value, new value

5. **Data Validation Rules**
   - Min/max saldo
   - Limit transaksi per siswa

---

## 📞 Troubleshooting

### Problem: Login gagal setelah hash password
**Solution:**
1. Check database: password benar-benar sudah ter-hash?
2. Test password hashing:
   ```php
   require_once 'includes/security.php';
   $hash = hashPassword('admin123');
   var_dump(verifyPassword('admin123', $hash)); // harus true
   ```

### Problem: CSRF token error pada form
**Solution:**
1. Check session aktif: login dulu sebelum akses form
2. Check form punya token field: `<?= csrfTokenField() ?>`
3. Check POST request ada `csrf_token` parameter

### Problem: Rate limiting blocking login
**Solution:**
1. Tunggu 15 menit atau
2. Clear browser cache/cookies atau
3. Login dari IP berbeda atau
4. Ubah rate limiting threshold di auth/login.php

---

## ✅ Completion Checklist

- [ ] Run hash password migration script
- [ ] Test login dengan password yang sudah di-hash
- [ ] Verify password di database (ter-hash, bukan plaintext)
- [ ] Test CSRF protection pada form
- [ ] Check security logs created
- [ ] Update remaining forms dengan CSRF token
- [ ] Test semua forms dengan data baru
- [ ] Delete hash password script file
- [ ] Read SECURITY.md untuk best practices

---

**Status:** 🟡 Phase 1 - Security Foundations Complete
**Next:** 🟢 Phase 2 - Advanced Security Features

Siap untuk phase berikutnya? Let me know! 🚀
