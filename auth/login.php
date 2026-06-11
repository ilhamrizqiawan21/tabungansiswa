<?php
/**
 * LOGIN PAGE - SECURE VERSION
 * Dengan CSRF protection, password hashing, rate limiting
 */

require_once '../includes/security.php';
require_once '../config/database.php';

// Initialize session
initializeSecureSession();

$error = '';
$expired = isset($_GET['expired']) ? true : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // 1. Verify CSRF token
    if (!verifyCSRFToken($_POST['csrf_token'] ?? '')) {
        $error = "Keamanan form gagal. Silakan coba lagi.";
        logSecurityEvent('CSRF_FAILED', 'CSRF token mismatch on login', null, 'WARNING');
    } 
    // 2. Check rate limiting
    elseif (!checkRateLimit($_SERVER['REMOTE_ADDR'], 5, 900)) {
        $error = "Terlalu banyak percobaan login. Silakan coba lagi dalam 15 menit.";
        logSecurityEvent('LOGIN_RATE_LIMITED', 'Too many login attempts from ' . getRealUserIP(), null, 'WARNING');
    }
    // 3. Validate & sanitize input
    else {
        $username = sanitizeString($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        
        // Check input kosong
        if (empty($username) || empty($password)) {
            $error = "Username dan password harus diisi.";
        } else {
            // 4. Query database dengan prepared statement
            try {
                $stmt = $pdo->prepare("SELECT id, username, password, nama FROM admin WHERE username = ? LIMIT 1");
                $stmt->execute([$username]);
                $admin = $stmt->fetch(PDO::FETCH_ASSOC);
                
                // 5. Verify password dengan hash
                if ($admin && verifyPassword($password, $admin['password'])) {
                    // Login berhasil
                    createUserSession($admin['id'], $admin['nama'], $admin['username']);
                    logSecurityEvent('LOGIN_SUCCESS', 'User ' . $admin['username'] . ' logged in', $admin['id'], 'INFO');
                    
                    // Redirect ke halaman sebelumnya atau dashboard
                    $redirect = $_SESSION['redirect_after_login'] ?? '../dashboard.php';
                    unset($_SESSION['redirect_after_login']);
                    header("Location: " . $redirect);
                    exit();
                } else {
                    // Login gagal - jangan reveal apakah username atau password yang salah
                    $error = "Username atau password tidak valid.";
                    logSecurityEvent('LOGIN_FAILED', 'Failed login attempt for username: ' . $username, null, 'WARNING');
                }
            } catch (PDOException $e) {
                $error = "Terjadi kesalahan. Silakan coba lagi nanti.";
                logSecurityEvent('LOGIN_ERROR', 'Database error: ' . $e->getMessage(), null, 'ERROR');
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login Admin - Tabungan Siswa</title>
    <link href="https://fonts.googleapis.com/css2?family=Poppins:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* (CSS sama seperti kode Anda, tidak diubah) */
        :root {
            --primary-color: #4361ee;
            --secondary-color: #3f37c9;
            --accent-color: #4895ef;
            --light-color: #f8f9fa;
            --dark-color: #212529;
        }
        
        body {
            font-family: 'Poppins', sans-serif;
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            display: flex;
            align-items: center;
        }
        
        .login-container {
            max-width: 450px;
            width: 100%;
            margin: 0 auto;
        }
        
        .login-card {
            border: none;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
            overflow: hidden;
            background-color: white;
        }
        
        .login-header {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            color: white;
            padding: 1.5rem;
            text-align: center;
        }
        
        .login-header h4 {
            font-weight: 600;
            margin: 0;
        }
        
        .login-body {
            padding: 2rem;
        }
        
        .form-control {
            border-radius: 8px;
            padding: 12px 15px;
            border: 1px solid #e0e0e0;
            transition: all 0.3s;
        }
        
        .form-control:focus {
            border-color: var(--accent-color);
            box-shadow: 0 0 0 0.25rem rgba(67, 97, 238, 0.25);
        }
        
        .btn-login {
            background: linear-gradient(to right, var(--primary-color), var(--secondary-color));
            border: none;
            border-radius: 8px;
            padding: 12px;
            font-weight: 500;
            width: 100%;
            transition: all 0.3s;
        }
        
        .btn-login:hover {
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(67, 97, 238, 0.3);
        }
        
        .login-footer {
            text-align: center;
            padding: 1rem;
            color: #6c757d;
            font-size: 0.9rem;
        }
        
        .error-message {
            animation: fadeIn 0.3s ease-in-out;
        }
        
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        .brand-logo {
            width: 60px;
            height: 60px;
            margin-bottom: 15px;
            background-color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 10px rgba(0, 0, 0, 0.1);
            margin: 0 auto 20px;
        }
        
        .brand-logo i {
            font-size: 28px;
            color: var(--primary-color);
        }
    </style>
</head>
<body>
    <div class="login-container">
        <div class="login-card">
            <div class="login-header">
                <div class="brand-logo">
                    <i class="fas fa-piggy-bank"></i>
                </div>
                <h4>Login Admin Tabungan Siswa</h4>
            </div>
            <div class="login-body">
                <?php if ($expired): ?>
                    <div class="alert alert-warning error-message">
                        <i class="fas fa-exclamation-triangle me-2"></i>
                        Sesi Anda telah berakhir. Silakan login kembali.
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['logout_message'])): ?>
                    <div class="alert alert-success error-message">
                        <i class="fas fa-check-circle me-2"></i>
                        <?= htmlspecialchars($_SESSION['logout_message']) ?>
                    </div>
                    <?php unset($_SESSION['logout_message']); ?>
                <?php endif; ?>
                
                <?php if (!empty($error)): ?>
                    <div class="alert alert-danger error-message">
                        <i class="fas fa-times-circle me-2"></i>
                        <?= htmlspecialchars($error) ?>
                    </div>
                <?php endif; ?>
                
                <form method="post" action="" novalidate>
                    <?= csrfTokenField() ?>
                    
                    <div class="mb-3">
                        <label for="username" class="form-label fw-500">Username</label>
                        <input 
                            type="text" 
                            class="form-control" 
                            id="username" 
                            name="username" 
                            placeholder="Masukkan username" 
                            value="<?= htmlspecialchars($_POST['username'] ?? '') ?>"
                            required 
                            autofocus
                            maxlength="50">
                    </div>
                    
                    <div class="mb-4">
                        <label for="password" class="form-label fw-500">Password</label>
                        <input 
                            type="password" 
                            class="form-control" 
                            id="password" 
                            name="password" 
                            placeholder="Masukkan password" 
                            required
                            maxlength="255">
                    </div>
                    
                    <button type="submit" class="btn btn-primary btn-login" id="loginBtn">
                        <i class="fas fa-sign-in-alt me-2"></i> Login
                    </button>
                </form>
            </div>
            <div class="login-footer">
                Sistem Tabungan Siswa &copy; <?= date('Y') ?>
            </div>
        </div>
    </div>

    <script src="https://kit.fontawesome.com/a076d05399.js" crossorigin="anonymous"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>