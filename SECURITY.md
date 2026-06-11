# 🔐 Dokumentasi Security Improvements

Dokumen ini menjelaskan semua improvement keamanan yang telah diimplementasikan pada project Tabungan Siswa.

---

## 📋 Daftar Improvement

### 1. ✅ Password Hashing (SELESAI)
**File:** `includes/security.php`

#### Masalah Lama:
- Password admin disimpan plaintext di database
- Jika database bocor, semua password terekspos

#### Solusi:
- Menggunakan `password_hash()` dengan algoritma **Argon2id**
- Password verification dengan `password_verify()`
- Support untuk password rehashing otomatis

#### Cara Kerja:
```php
// Hash password
$hashed = hashPassword('mypassword123');

// Verify password saat login
if (verifyPassword('mypassword123', $hash)) {
    // Password benar
}

// Check apakah perlu rehash
if (passwordNeedsRehash($hash)) {
    $newHash = hashPassword($password);
    // Update ke database
}
```

#### Langkah Implementasi:
1. Jalankan script migration: `http://localhost/tabungansiswa/scripts/hash_admin_passwords.php?token=HASH_ADMIN_YYYY-MM-DD`
2. Token berubah setiap hari (untuk keamanan)
3. Script hanya bisa dijalankan dari localhost
4. **HAPUS FILE SCRIPT setelah selesai**

---

### 2. ✅ CSRF (Cross-Site Request Forgery) Protection (SELESAI)
**File:** `includes/security.php`, auth/login.php, siswa/tambah.php, dst

#### Masalah Lama:
- Form POST tidak ada token protection
- Attacker bisa membuat form palsu dan trick user untuk submit
- Contoh: Form untuk delete semua siswa

#### Solusi:
- Generate unique token untuk setiap session
- Token di-validate sebelum form processing
- Token di-regenerate setelah login berhasil

#### Implementasi di Form:
```php
// Di form HTML
<?= csrfTokenField() ?>
// Output: <input type="hidden" name="csrf_token" value="...">

// Di PHP processing
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Keamanan form gagal";
}
```

#### Files yang Sudah Diupdate:
- ✅ auth/login.php
- ✅ siswa/tambah.php
- 🔄 Masih perlu: siswa/edit.php, transaksi/tambah.php, etc

---

### 3. ✅ Input Sanitization & Validation (SELESAI)
**File:** `includes/security.php`

#### Helper Functions:
```php
sanitizeString($input)      // Trim + htmlspecialchars
sanitizeInt($input)         // Filter integer
sanitizeFloat($input)       // Filter float
sanitizeEmail($input)       // Filter email
sanitizePhone($input)       // Filter phone (0-9, +, -)

validateNIS($nis)           // Regex: ^[0-9]{4,20}$
validateName($name)         // Regex: hanya huruf & spasi
validatePasswordStrength()  // Min 8 char, uppercase, lowercase, angka, special
```

#### Implementasi:
```php
$nis = sanitizeString($_POST['nis']);
if (!validateNIS($nis)) {
    $errors[] = "NIS hanya boleh angka";
}

// Prevent XSS saat display
echo htmlspecialchars($variable);
```

#### Benefit:
- Prevent SQL Injection (dengan prepared statement)
- Prevent XSS (dengan htmlspecialchars)
- Enforce valid data format

---

### 4. ✅ Session Security (SELESAI)
**File:** `includes/security.php`, config/auth.php

#### Features:
- **Session Timeout:** 30 menit inactivity → auto logout
- **User Agent Validation:** Detect session hijacking
- **Session Regeneration:** Setelah login sukses
- **HttpOnly Cookies:** Tidak bisa diakses JavaScript
- **Secure Cookies:** HTTPS only (otomatis di production)
- **SameSite=Strict:** CSRF protection

#### Implementasi:
```php
// Di awal request
initializeSecureSession();

// Check session aktif & valid
if (!validateActiveSession()) {
    // Redirect ke login
}

// Setelah login sukses
createUserSession($admin_id, $admin_name, $username);

// Saat logout
destroyUserSession();
```

#### Session Timeout:
- Idle time: **30 menit** (bisa dikonfigurasi)
- Auto-logout jika session timeout
- User tidak perlu logout manual

---

### 5. ✅ Rate Limiting (SELESAI)
**File:** `includes/security.php`, auth/login.php

#### Masalah:
- Brute force attack pada login
- Attacker bisa coba banyak password

#### Solusi:
- Max 5 percobaan login dalam 15 menit
- Jika exceed: user harus tunggu 15 menit
- Tracked per IP address

#### Implementasi:
```php
if (!checkRateLimit($ip_address, 5, 900)) {
    $error = "Terlalu banyak percobaan login";
}

// Get remaining attempts
$remaining = getRateLimitRemaining($ip_address, 5, 900);
```

---

### 6. ✅ Security Logging (SELESAI)
**File:** `logs/` directory, `includes/security.php`

#### Log Events:
- Login sukses/gagal
- CSRF token mismatch
- Rate limit exceeded
- Data creation/update
- Database errors
- Logout

#### Format Log:
```
[2024-06-03 14:30:45] [INFO] [POST] Type: LOGIN_SUCCESS | User: 1 | IP: 127.0.0.1 | Description: User ilhamzp logged in
[2024-06-03 14:31:20] [WARNING] [POST] Type: CSRF_FAILED | User: Anonymous | IP: 192.168.1.100 | Description: CSRF token mismatch on login
```

#### Cara Menggunakan:
```php
logSecurityEvent('EVENT_TYPE', 'Description', $user_id, 'SEVERITY');
// SEVERITY: INFO, WARNING, ERROR, CRITICAL
```

#### Log Files:
- Disimpan di: `logs/security_YYYY-MM-DD.log`
- Satu file per hari
- Bisa dianalisis untuk security audit

---

### 7. ✅ Error Handling (SELESAI)
**File:** `auth/login.php`, bentuk lama files

#### Improvement:
- Jangan expose database error ke user
- Log error ke file (bukan display)
- Generic error message untuk user
- Detailed error di log untuk debugging

#### Before:
```php
// Buruk: expose error ke user
catch (PDOException $e) {
    die("Error: " . $e->getMessage());
}
```

#### After:
```php
// Baik: generic message & log error
catch (PDOException $e) {
    $_SESSION['error'] = "Terjadi kesalahan. Silakan coba lagi.";
    logSecurityEvent('DB_ERROR', $e->getMessage(), null, 'ERROR');
}
```

---

## 🚀 Cara Implementasi

### Step 1: Hash Password Admin yang Ada
```
1. Buka: http://localhost/tabungansiswa/scripts/hash_admin_passwords.php
2. Lihat token untuk hari ini di halaman
3. Klik: Run Migration
4. Verifikasi hasilnya
5. HAPUS file: scripts/hash_admin_passwords.php
```

### Step 2: Test Login
```
1. Buka: http://localhost/tabungansiswa/auth/login.php
2. Masukkan username & password (password sama seperti sebelumnya)
3. Harus login berhasil dengan password hashing
```

### Step 3: Update Remaining Forms
File yang masih perlu CSRF token:
- siswa/edit.php
- siswa/hapus.php
- transaksi/tambah.php
- transaksi/edit.php
- transaksi/hapus.php
- kelas/tambah.php
- kelas/edit.php
- kelas/hapus.php

Template untuk update:
```php
// Di form
<?= csrfTokenField() ?>

// Di processing
if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
    $_SESSION['error'] = "Keamanan form gagal";
}
```

---

## 📊 Security Checklist

| Feature | Status | Implemented | Notes |
|---------|--------|-------------|-------|
| Password Hashing | ✅ | Argon2id | Supports rehashing |
| CSRF Protection | ⚠️ | Partial | Perlu update semua form |
| Input Sanitization | ✅ | Complete | Sanitize & validate |
| Session Security | ✅ | Complete | Timeout 30 min |
| Rate Limiting | ✅ | Complete | 5 attempts / 15 min |
| Security Logging | ✅ | Complete | logs/security_*.log |
| Error Handling | ✅ | Complete | Safe error messages |
| SQL Injection Protection | ✅ | Complete | Prepared statements |
| XSS Protection | ✅ | Complete | htmlspecialchars() |

---

## ⚙️ Konfigurasi yang Bisa Diubah

### Session Timeout (30 menit)
File: `includes/security.php`, fungsi `initializeSecureSession()`
```php
// Ubah ke berapa menit?
$timeout = 30 * 60; // 30 menit dalam detik
```

### Rate Limiting (5 attempts / 15 min)
File: `auth/login.php`
```php
if (!checkRateLimit($ip, 5, 900)) { // 5 attempts, 900 detik (15 menit)
```

### Password Requirements
File: `includes/security.php`, fungsi `validatePasswordStrength()`
```php
// Ubah minimum requirements:
$minLength = 8;  // Minimal 8 karakter
// Require: uppercase, lowercase, number, special char
```

---

## 🧪 Testing

### Test CSRF Protection:
1. Buka form tambah siswa
2. Edit HTML form, ubah csrf_token value
3. Submit form → harus error "Keamanan form gagal"

### Test Session Timeout:
1. Login
2. Biarkan selama 31 menit tanpa activity
3. Refresh halaman → harus redirect ke login

### Test Rate Limiting:
1. Coba login 5x dengan password salah
2. Attempt ke-6 → error "Terlalu banyak percobaan"
3. Tunggu 15 menit, bisa coba lagi

### Test Password Hashing:
1. Login dengan username & password
2. Check database: password harus hash (bukan plaintext)
3. Password hash dimulai dengan `$argon2id$`

---

## 📝 Best Practices untuk Development

1. **Selalu gunakan Prepared Statements**
   ```php
   // ✅ Baik
   $stmt = $pdo->prepare("SELECT * FROM admin WHERE username = ?");
   $stmt->execute([$username]);
   
   // ❌ Buruk
   $result = $pdo->query("SELECT * FROM admin WHERE username = '$username'");
   ```

2. **Sanitize Output**
   ```php
   // ✅ Baik
   echo htmlspecialchars($variable);
   
   // ❌ Buruk
   echo $variable;
   ```

3. **Gunakan CSRF Token di Semua Form**
   ```php
   // ✅ Baik
   <form method="post">
       <?= csrfTokenField() ?>
       ...
   </form>
   
   // ❌ Buruk (tanpa token)
   <form method="post">
       ...
   </form>
   ```

4. **Log Semua Action Penting**
   ```php
   logSecurityEvent('ACTION', 'Description', $user_id, 'SEVERITY');
   ```

5. **Validate Input di Server-Side**
   ```php
   // ✅ Baik
   if (!validateNIS($nis)) {
       $errors[] = "Invalid NIS";
   }
   
   // ❌ Buruk (hanya client-side)
   <input pattern="[0-9]+">
   ```

---

## 🔄 Next Steps - Phase 2 Security

Masalah keamanan yang masih perlu diperbaiki:

1. **Role-Based Access Control (RBAC)**
   - Implementasi role: admin, guru, bendahara
   - Permission matrix untuk setiap role
   - Check role di setiap halaman protected

2. **Data Encryption**
   - Encrypt nomor kontak siswa
   - Encrypt data sensitif di database

3. **Two-Factor Authentication (2FA)**
   - SMS OTP saat login
   - Google Authenticator support

4. **API Security**
   - API key management
   - Rate limiting per API
   - Request signing

5. **Database Security**
   - Encrypted database backups
   - User privilege isolation
   - Regular security audit

---

## 📞 Support

Untuk pertanyaan atau issue terkait security:
- Cek logs di: `logs/security_*.log`
- Test functionality di: `scripts/hash_admin_passwords.php`
- Review security functions: `includes/security.php`

---

**Last Updated:** 3 Juni 2024
**Security Level:** 🟡 Medium (Improved)
**Next Level:** 🟢 High (dengan Phase 2)
