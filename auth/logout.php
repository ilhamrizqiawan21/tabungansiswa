<?php
/**
 * LOGOUT PAGE - SECURE VERSION
 */

require_once '../config/auth.php';

// Ambil info user sebelum session dihapus
$user = getCurrentUser();

// Log logout event
if ($user) {
    logSecurityEvent('LOGOUT', 'User ' . $user['username'] . ' logged out', $user['id'], 'INFO');
}

// Set pesan logout sebelum destroy session
$_SESSION['logout_message'] = "Anda telah berhasil logout. Sampai jumpa lagi!";

// Destroy session dengan aman
destroyUserSession();

// Reinitialize session untuk set logout message
session_start();
$_SESSION['logout_message'] = "Anda telah berhasil logout. Sampai jumpa lagi!";

// Redirect ke login
header("Location: login.php");
exit();
?>