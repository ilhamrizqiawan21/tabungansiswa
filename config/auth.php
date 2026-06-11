<?php
/**
 * Authentication & Authorization
 */

// Initialize secure session
require_once __DIR__ . '/../includes/security.php';

// Initialize secure session
if (!initializeSecureSession()) {
    // Session expired
    session_destroy();
    header("Location: /tabungansiswa/auth/login.php?expired=1");
    exit();
}

/**
 * Check apakah user sudah login
 */
function isLoggedIn() {
    if (!isset($_SESSION['admin_id'])) {
        return false;
    }
    
    // Validate active session
    return validateActiveSession();
}

/**
 * Redirect ke login jika belum login
 */
function redirectIfNotLoggedIn() {
    if (!isLoggedIn()) {
        $_SESSION['redirect_after_login'] = $_SERVER['REQUEST_URI'] ?? '/tabungansiswa/dashboard.php';
        header("Location: /tabungansiswa/auth/login.php");
        exit();
    }
}

/**
 * Get logged in user info
 */
function getCurrentUser() {
    if (!isLoggedIn()) {
        return null;
    }
    
    return [
        'id' => $_SESSION['admin_id'] ?? null,
        'nama' => $_SESSION['admin_nama'] ?? null,
        'username' => $_SESSION['admin_username'] ?? null
    ];
}

/**
 * Check apakah user memiliki role tertentu
 * TODO: Implementasi role-based access control
 */
function hasRole($role) {
    // For now, semua logged-in user adalah admin
    return isLoggedIn();
}

?>
