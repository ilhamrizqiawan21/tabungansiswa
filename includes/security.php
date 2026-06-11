<?php
/**
 * Security Helper Functions
 * Handles hashing, CSRF tokens, input sanitization, etc.
 */

// ============================================
// PASSWORD HASHING & VERIFICATION
// ============================================

/**
 * Hash password dengan argon2id (PHP 7.2+)
 */
function hashPassword($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 19456,
        'time_cost' => 4,
        'threads' => 1
    ]);
}

/**
 * Verify password terhadap hash
 */
function verifyPassword($password, $hash) {
    return password_verify($password, $hash);
}

/**
 * Check apakah password perlu di-rehash (untuk upgrade algorithm)
 */
function passwordNeedsRehash($hash) {
    return password_needs_rehash($hash, PASSWORD_ARGON2ID);
}

// ============================================
// CSRF TOKEN PROTECTION
// ============================================

/**
 * Generate CSRF token untuk session
 */
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Get CSRF token untuk ditampilkan di form
 */
function getCSRFToken() {
    return generateCSRFToken();
}

/**
 * Verify CSRF token dari POST request
 */
function verifyCSRFToken($token) {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Create hidden input field untuk CSRF token
 */
function csrfTokenField() {
    return sprintf(
        '<input type="hidden" name="csrf_token" value="%s">',
        htmlspecialchars(getCSRFToken())
    );
}

// ============================================
// INPUT SANITIZATION & VALIDATION
// ============================================

/**
 * Sanitize input string (trim + htmlspecialchars)
 */
function sanitizeString($input) {
    return htmlspecialchars(trim($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Sanitize integer input
 */
function sanitizeInt($input) {
    return filter_var($input, FILTER_VALIDATE_INT);
}

/**
 * Sanitize float/decimal input
 */
function sanitizeFloat($input) {
    return filter_var($input, FILTER_VALIDATE_FLOAT);
}

/**
 * Sanitize email
 */
function sanitizeEmail($input) {
    return filter_var($input, FILTER_VALIDATE_EMAIL);
}

/**
 * Sanitize phone number (hanya angka, +, -)
 */
function sanitizePhone($input) {
    return preg_replace('/[^0-9+\-]/', '', $input);
}

/**
 * Validate NIS (hanya angka, 4-20 karakter)
 */
function validateNIS($nis) {
    return preg_match('/^[0-9]{4,20}$/', $nis) ? true : false;
}

/**
 * Validate nama (hanya huruf, spasi, titik, koma)
 */
function validateName($name) {
    return preg_match('/^[a-zA-Z\s.,\-\']+$/u', $name) ? true : false;
}

/**
 * Validate password strength
 * Minimal 8 karakter, 1 uppercase, 1 lowercase, 1 angka, 1 special char
 */
function validatePasswordStrength($password) {
    $minLength = 8;
    $hasUppercase = preg_match('/[A-Z]/', $password);
    $hasLowercase = preg_match('/[a-z]/', $password);
    $hasNumber = preg_match('/[0-9]/', $password);
    $hasSpecial = preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password);
    
    return strlen($password) >= $minLength 
        && $hasUppercase 
        && $hasLowercase 
        && $hasNumber 
        && $hasSpecial;
}

/**
 * Get password strength message
 */
function getPasswordStrengthMessage($password) {
    $strength = 0;
    $messages = [];
    
    if (strlen($password) >= 8) $strength++;
    if (strlen($password) >= 12) $strength++;
    
    if (preg_match('/[a-z]/', $password)) $strength++;
    if (preg_match('/[A-Z]/', $password)) $strength++;
    if (preg_match('/[0-9]/', $password)) $strength++;
    if (preg_match('/[!@#$%^&*()_+\-=\[\]{};\':"\\|,.<>\/?]/', $password)) $strength++;
    
    if ($strength <= 2) return 'Sangat Lemah';
    if ($strength <= 3) return 'Lemah';
    if ($strength <= 4) return 'Sedang';
    if ($strength <= 5) return 'Kuat';
    return 'Sangat Kuat';
}

// ============================================
// SESSION SECURITY
// ============================================

/**
 * Secure session initialization
 */
function initializeSecureSession() {
    // Set session cookie parameters
    $cookieOptions = [
        'lifetime' => 0,           // Browser closes = session ends
        'path' => '/',
        'domain' => '',
        'secure' => (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on'),  // HTTPS only
        'httponly' => true,        // Tidak bisa diakses JavaScript
        'samesite' => 'Strict'     // CSRF protection
    ];
    
    session_set_cookie_params($cookieOptions);
    
    // Start session jika belum
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
    
    // Set session timeout (30 menit)
    if (isset($_SESSION['user_login_time'])) {
        $elapsed = time() - $_SESSION['user_login_time'];
        if ($elapsed > 1800) { // 30 menit
            session_destroy();
            return false;
        }
    }
    
    return true;
}

/**
 * Create user session setelah login
 */
function createUserSession($admin_id, $admin_name, $username) {
    $_SESSION['admin_id'] = $admin_id;
    $_SESSION['admin_nama'] = $admin_name;
    $_SESSION['admin_username'] = $username;
    $_SESSION['user_login_time'] = time();
    $_SESSION['user_agent'] = $_SERVER['HTTP_USER_AGENT'] ?? '';
    $_SESSION['ip_address'] = getRealUserIP();
    
    // Regenerate session ID untuk prevent fixation attack
    session_regenerate_id(true);
}

/**
 * Validate active session (cek user agent & IP)
 */
function validateActiveSession() {
    // Check session timeout
    if (!isset($_SESSION['user_login_time'])) {
        return false;
    }
    
    $elapsed = time() - $_SESSION['user_login_time'];
    if ($elapsed > 1800) { // 30 menit
        session_destroy();
        return false;
    }
    
    // Validate user agent (prevent session hijacking)
    if (($_SERVER['HTTP_USER_AGENT'] ?? '') !== ($_SESSION['user_agent'] ?? '')) {
        session_destroy();
        return false;
    }
    
    // Optional: Validate IP (tapi bisa bermasalah dengan dynamic IP)
    // if (getRealUserIP() !== ($_SESSION['ip_address'] ?? '')) {
    //     session_destroy();
    //     return false;
    // }
    
    return true;
}

/**
 * Destroy session dengan aman
 */
function destroyUserSession() {
    // Clear semua session variables
    $_SESSION = [];
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(
            session_name(),
            '',
            time() - 42000,
            $params["path"],
            $params["domain"],
            $params["secure"],
            $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
}

// ============================================
// UTILITY FUNCTIONS
// ============================================

/**
 * Get real user IP address (handle proxy)
 */
function getRealUserIP() {
    $ip = '';
    
    if (!empty($_SERVER['HTTP_CF_CONNECTING_IP'])) {
        // Cloudflare
        $ip = $_SERVER['HTTP_CF_CONNECTING_IP'];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
        // Proxy
        $ip = explode(',', $_SERVER['HTTP_X_FORWARDED_FOR'])[0];
    } elseif (!empty($_SERVER['HTTP_X_FORWARDED'])) {
        $ip = $_SERVER['HTTP_X_FORWARDED'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED_FOR'])) {
        $ip = $_SERVER['HTTP_FORWARDED_FOR'];
    } elseif (!empty($_SERVER['HTTP_FORWARDED'])) {
        $ip = $_SERVER['HTTP_FORWARDED'];
    } else {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
    
    return filter_var($ip, FILTER_VALIDATE_IP) ? $ip : '0.0.0.0';
}

/**
 * Rate limiting untuk login attempts
 * Simpan di session, bisa dipindah ke database untuk production
 */
function checkRateLimit($identifier, $maxAttempts = 5, $windowSeconds = 900) {
    // Inisialisasi jika belum ada
    if (!isset($_SESSION['rate_limit'])) {
        $_SESSION['rate_limit'] = [];
    }
    
    $key = 'rate_limit_' . md5($identifier);
    
    // Cleanup old entries
    if (isset($_SESSION['rate_limit'][$key])) {
        $_SESSION['rate_limit'][$key] = array_filter(
            $_SESSION['rate_limit'][$key],
            function($timestamp) use ($windowSeconds) {
                return time() - $timestamp < $windowSeconds;
            }
        );
    } else {
        $_SESSION['rate_limit'][$key] = [];
    }
    
    // Check limit
    if (count($_SESSION['rate_limit'][$key]) >= $maxAttempts) {
        return false;
    }
    
    // Add current attempt
    $_SESSION['rate_limit'][$key][] = time();
    return true;
}

/**
 * Get remaining rate limit attempts
 */
function getRateLimitRemaining($identifier, $maxAttempts = 5, $windowSeconds = 900) {
    if (!isset($_SESSION['rate_limit'])) {
        return $maxAttempts;
    }
    
    $key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION['rate_limit'][$key])) {
        return $maxAttempts;
    }
    
    $attempts = array_filter(
        $_SESSION['rate_limit'][$key],
        function($timestamp) use ($windowSeconds) {
            return time() - $timestamp < $windowSeconds;
        }
    );
    
    return max(0, $maxAttempts - count($attempts));
}

/**
 * Log security event (untuk audit trail)
 */
function logSecurityEvent($event_type, $description, $user_id = null, $severity = 'INFO') {
    $logDir = __DIR__ . '/../logs';
    
    // Create logs directory jika tidak ada
    if (!is_dir($logDir)) {
        mkdir($logDir, 0755, true);
    }
    
    $logFile = $logDir . '/security_' . date('Y-m-d') . '.log';
    
    $logEntry = sprintf(
        "[%s] [%s] [%s] Type: %s | User: %s | IP: %s | Description: %s\n",
        date('Y-m-d H:i:s'),
        $severity,
        $_SERVER['REQUEST_METHOD'] ?? 'CLI',
        $event_type,
        $user_id ?? 'Anonymous',
        getRealUserIP(),
        $description
    );
    
    file_put_contents($logFile, $logEntry, FILE_APPEND);
}

?>
