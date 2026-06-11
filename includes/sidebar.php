<?php
/**
 * Sidebar Navigation Include
 * Menggantikan top navbar dengan sidebar
 */
require_once __DIR__ . '/../includes/security.php';
$base_url = '/tabungansiswa/';
$current_page = basename($_SERVER['PHP_SELF']);
$current_dir = basename(dirname($_SERVER['PHP_SELF']));

// Helper function untuk check active menu
function isActive($page, $current) {
    return ($page === $current || (strpos($current, $page) !== false)) ? 'active' : '';
}

// Get current user info
$user = $_SESSION['user'] ?? null;
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Tabungan Siswa</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:opsz,wght@14..32,300;14..32,400;14..32,500;14..32,600;14..32,700&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary-color: #4f46e5;
            --primary-dark: #4338ca;
            --secondary-color: #0ea5e9;
            --dark-bg: #0f172a;
            --glass-bg: rgba(255, 255, 255, 0.1);
            --glass-border: rgba(255, 255, 255, 0.2);
            --sidebar-width: 260px;
            --sidebar-collapse-width: 80px;
        }

        * {
            font-family: 'Inter', sans-serif;
        }

        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #e9eef3 100%);
            min-height: 100vh;
            margin: 0;
            padding: 0;
        }

        /* ============================================ */
        /* SIDEBAR STYLES */
        /* ============================================ */
        .sidebar {
            position: fixed;
            left: 0;
            top: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: rgba(15, 23, 42, 0.95);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-right: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 8px 0 32px rgba(0, 0, 0, 0.1);
            overflow-y: auto;
            z-index: 1000;
            transition: all 0.3s ease;
        }

        /* Sidebar collapse state */
        body.sidebar-collapsed .sidebar {
            width: var(--sidebar-collapse-width);
        }

        /* Hide text when collapsed */
        body.sidebar-collapsed .sidebar-text {
            display: none;
        }

        /* Logo Section */
        .sidebar-header {
            padding: 1.5rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            display: flex;
            align-items: center;
            justify-content: space-between;
            min-height: 70px;
        }

        .sidebar-logo {
            display: flex;
            align-items: center;
            gap: 12px;
            text-decoration: none;
            color: white;
            font-weight: 700;
            font-size: 1.1rem;
            transition: all 0.2s;
        }

        .sidebar-logo:hover {
            transform: scale(1.05);
        }

        .sidebar-logo i {
            font-size: 1.5rem;
            background: linear-gradient(135deg, #818cf8, #c7d2fe);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        .sidebar-logo-text {
            background: linear-gradient(135deg, #fff, #c7d2fe);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent;
        }

        body.sidebar-collapsed .sidebar-logo-text {
            display: none;
        }

        /* Sidebar Menu */
        .sidebar-menu {
            padding: 1.5rem 0;
            list-style: none;
            margin: 0;
        }

        .sidebar-menu-title {
            padding: 1rem 1.5rem 0.5rem;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            color: rgba(255, 255, 255, 0.5);
            letter-spacing: 0.5px;
        }

        body.sidebar-collapsed .sidebar-menu-title {
            display: none;
        }

        .sidebar-menu-item {
            margin: 0.25rem 0.75rem;
        }

        .sidebar-menu-link {
            display: flex;
            align-items: center;
            gap: 12px;
            padding: 0.75rem 1rem;
            color: rgba(255, 255, 255, 0.7);
            text-decoration: none;
            border-radius: 12px;
            transition: all 0.2s ease;
            font-weight: 500;
            font-size: 0.95rem;
            white-space: nowrap;
        }

        .sidebar-menu-link:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(4px);
        }

        .sidebar-menu-link.active {
            background: linear-gradient(135deg, rgba(79, 70, 229, 0.3), rgba(79, 70, 229, 0.1));
            color: #a5b4fc;
            border-left: 3px solid var(--primary-color);
            padding-left: calc(1rem - 3px);
        }

        .sidebar-menu-link i {
            width: 20px;
            text-align: center;
            font-size: 1.1rem;
        }

        .sidebar-menu-text {
            flex: 1;
        }

        body.sidebar-collapsed .sidebar-menu-text {
            display: none;
        }

        body.sidebar-collapsed .sidebar-menu-link {
            justify-content: center;
            padding: 0.75rem;
        }

        /* Badge untuk menu items */
        .sidebar-badge {
            display: inline-block;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            padding: 0.2rem 0.5rem;
            border-radius: 999px;
            font-weight: 600;
            margin-left: auto;
        }

        body.sidebar-collapsed .sidebar-badge {
            display: none;
        }

        /* Collapse Toggle */
        .sidebar-toggle {
            position: absolute;
            right: -18px;
            top: 1.5rem;
            width: 36px;
            height: 36px;
            background: rgba(79, 70, 229, 0.9);
            border: none;
            border-radius: 50%;
            color: white;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: all 0.2s;
            box-shadow: 0 4px 12px rgba(79, 70, 229, 0.3);
            z-index: 1001;
        }

        .sidebar-toggle:hover {
            background: var(--primary-color);
            transform: scale(1.1);
        }

        .sidebar-toggle i {
            font-size: 1rem;
        }

        /* ============================================ */
        /* MAIN CONTENT ADJUSTMENT */
        /* ============================================ */
        .main-wrapper {
            margin-left: var(--sidebar-width);
            transition: all 0.3s ease;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        body.sidebar-collapsed .main-wrapper {
            margin-left: var(--sidebar-collapse-width);
        }

        .main-content {
            flex: 1;
            padding: 2rem;
        }

        /* Top Bar dengan user info */
        .topbar {
            background: white;
            border-bottom: 1px solid rgba(0, 0, 0, 0.1);
            padding: 1rem 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            min-height: 70px;
            box-shadow: 0 2px 8px rgba(0, 0, 0, 0.05);
        }

        .topbar-left {
            flex: 1;
        }

        .topbar-title {
            font-size: 1.5rem;
            font-weight: 700;
            color: #1e293b;
            margin: 0;
        }

        .topbar-right {
            display: flex;
            align-items: center;
            gap: 1.5rem;
        }

        .topbar-item {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.95rem;
        }

        .user-dropdown {
            position: relative;
        }

        .user-menu-btn {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            background: rgba(79, 70, 229, 0.1);
            border: 1px solid rgba(79, 70, 229, 0.2);
            padding: 0.5rem 1rem;
            border-radius: 12px;
            cursor: pointer;
            color: #1e293b;
            text-decoration: none;
            transition: all 0.2s;
            font-weight: 500;
        }

        .user-menu-btn:hover {
            background: rgba(79, 70, 229, 0.2);
            border-color: rgba(79, 70, 229, 0.4);
        }

        .user-avatar {
            width: 32px;
            height: 32px;
            background: linear-gradient(135deg, #818cf8, #c7d2fe);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: #0f172a;
            font-weight: 700;
        }

        .user-dropdown-menu {
            position: absolute;
            right: 0;
            top: 45px;
            background: white;
            border: 1px solid rgba(0, 0, 0, 0.1);
            border-radius: 12px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1);
            min-width: 200px;
            padding: 0.5rem;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.2s;
            z-index: 1002;
        }

        .user-dropdown.open .user-dropdown-menu {
            opacity: 1;
            visibility: visible;
            transform: translateY(0);
        }

        .user-dropdown-item {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            padding: 0.75rem 1rem;
            color: #1e293b;
            text-decoration: none;
            border-radius: 8px;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .user-dropdown-item:hover {
            background: rgba(79, 70, 229, 0.1);
            color: var(--primary-color);
            transform: translateX(4px);
        }

        .user-dropdown-divider {
            height: 1px;
            background: rgba(0, 0, 0, 0.1);
            margin: 0.5rem 0;
        }

        /* ============================================ */
        /* RESPONSIVE */
        /* ============================================ */
        @media (max-width: 768px) {
            .sidebar {
                width: var(--sidebar-collapse-width);
            }

            .sidebar-text,
            .sidebar-menu-title,
            .sidebar-menu-text,
            .sidebar-badge {
                display: none;
            }

            .main-wrapper {
                margin-left: var(--sidebar-collapse-width);
            }

            .topbar {
                padding: 1rem;
            }

            .topbar-title {
                font-size: 1.2rem;
            }

            .main-content {
                padding: 1rem;
            }
        }

        /* ============================================ */
        /* ANIMATIONS */
        /* ============================================ */
        @keyframes slideInDown {
            from {
                opacity: 0;
                transform: translateY(-20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        .main-content {
            animation: slideInDown 0.3s ease;
        }

        /* ============================================ */
        /* SCROLLBAR */
        /* ============================================ */
        .sidebar::-webkit-scrollbar {
            width: 6px;
        }

        .sidebar::-webkit-scrollbar-track {
            background: transparent;
        }

        .sidebar::-webkit-scrollbar-thumb {
            background: rgba(255, 255, 255, 0.2);
            border-radius: 3px;
        }

        .sidebar::-webkit-scrollbar-thumb:hover {
            background: rgba(255, 255, 255, 0.3);
        }
    </style>
</head>
<body>

<!-- ============================================ -->
<!-- SIDEBAR NAVIGATION -->
<!-- ============================================ -->
<aside class="sidebar">
    <!-- Logo Section -->
    <div class="sidebar-header">
        <a href="<?= $base_url ?>dashboard.php" class="sidebar-logo">
            <i class="fas fa-piggy-bank"></i>
            <span class="sidebar-logo-text">TabunganSiswa</span>
        </a>
    </div>

    <!-- Navigation Menu -->
    <nav>
        <ul class="sidebar-menu">
            <!-- Main Menu -->
            <li class="sidebar-menu-title">Main</li>

            <li class="sidebar-menu-item">
                <a href="<?= $base_url ?>dashboard.php" class="sidebar-menu-link <?= isActive('dashboard', $current_page) ?>">
                    <i class="fas fa-home"></i>
                    <span class="sidebar-menu-text">Dashboard</span>
                </a>
            </li>

            <!-- Data Management -->
            <li class="sidebar-menu-title">Data</li>

            <li class="sidebar-menu-item">
                <a href="<?= $base_url ?>siswa/index.php" class="sidebar-menu-link <?= isActive('siswa', $current_dir) ?>">
                    <i class="fas fa-users"></i>
                    <span class="sidebar-menu-text">Siswa</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="<?= $base_url ?>kelas/index.php" class="sidebar-menu-link <?= isActive('kelas', $current_dir) ?>">
                    <i class="fas fa-layer-group"></i>
                    <span class="sidebar-menu-text">Kelas</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="<?= $base_url ?>transaksi/index.php" class="sidebar-menu-link <?= isActive('transaksi', $current_dir) ?>">
                    <i class="fas fa-exchange-alt"></i>
                    <span class="sidebar-menu-text">Transaksi</span>
                </a>
            </li>

            <!-- Reports & Analytics -->
            <li class="sidebar-menu-title">Laporan</li>

            <li class="sidebar-menu-item">
                <a href="<?= $base_url ?>laporan/index.php" class="sidebar-menu-link <?= isActive('index', $current_page) && $current_dir === 'laporan' ? 'active' : '' ?>">
                    <i class="fas fa-file-alt"></i>
                    <span class="sidebar-menu-text">Laporan Standar</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="<?= $base_url ?>laporan/analytics.php" class="sidebar-menu-link <?= isActive('analytics', $current_page) ?>">
                    <i class="fas fa-chart-line"></i>
                    <span class="sidebar-menu-text">Analytics</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="<?= $base_url ?>laporan/approval_workflow.php" class="sidebar-menu-link <?= isActive('approval', $current_page) ?>">
                    <i class="fas fa-check-circle"></i>
                    <span class="sidebar-menu-text">Approval</span>
                </a>
            </li>

            <!-- Settings & Management -->
            <li class="sidebar-menu-title">Pengaturan</li>

            <li class="sidebar-menu-item">
                <a href="<?= $base_url ?>pengaturan/tahun_pelajaran.php" class="sidebar-menu-link <?= isActive('tahun_pelajaran', $current_page) ?>">
                    <i class="fas fa-calendar"></i>
                    <span class="sidebar-menu-text">Tahun Pelajaran</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="<?= $base_url ?>pengaturan/audit_log.php" class="sidebar-menu-link <?= isActive('audit_log', $current_page) ?>">
                    <i class="fas fa-history"></i>
                    <span class="sidebar-menu-text">Audit Log</span>
                </a>
            </li>

            <li class="sidebar-menu-item">
                <a href="<?= $base_url ?>pengaturan/index.php" class="sidebar-menu-link <?= isActive('index', $current_page) && $current_dir === 'pengaturan' ? 'active' : '' ?>">
                    <i class="fas fa-cog"></i>
                    <span class="sidebar-menu-text">Pengaturan</span>
                </a>
            </li>

            <!-- Actions -->
            <li class="sidebar-menu-title">Aksi</li>

            <li class="sidebar-menu-item">
                <a href="<?= $base_url ?>auth/logout.php" class="sidebar-menu-link" onclick="return confirm('Yakin ingin logout?')">
                    <i class="fas fa-sign-out-alt"></i>
                    <span class="sidebar-menu-text">Logout</span>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Collapse Toggle Button -->
    <button class="sidebar-toggle" title="Collapse sidebar" onclick="toggleSidebar()">
        <i class="fas fa-chevron-left"></i>
    </button>
</aside>

<!-- ============================================ -->
<!-- MAIN CONTENT WRAPPER -->
<!-- ============================================ -->
<div class="main-wrapper">
    <!-- Top Bar -->
    <div class="topbar">
        <div class="topbar-left">
            <h1 class="topbar-title">
                <i class="fas fa-chart-bar me-2"></i>Sistem Tabungan Siswa
            </h1>
        </div>
        <div class="topbar-right">
            <!-- Time Display (optional) -->
            <div class="topbar-item">
                <i class="far fa-clock"></i>
                <span id="current-time">--:--</span>
            </div>

            <!-- User Menu -->
            <div class="user-dropdown">
                <button class="user-menu-btn" onclick="toggleUserMenu(event)">
                    <div class="user-avatar">
                        <?= strtoupper(substr($user['username'] ?? 'A', 0, 1)) ?>
                    </div>
                    <span><?= htmlspecialchars($user['username'] ?? 'Admin') ?></span>
                    <i class="fas fa-chevron-down" style="font-size: 0.8rem;"></i>
                </button>

                <div class="user-dropdown-menu">
                    <div style="padding: 0.75rem 1rem; color: #64748b; font-size: 0.9rem; border-bottom: 1px solid rgba(0,0,0,0.1);">
                        <?= htmlspecialchars($user['nama'] ?? 'Administrator') ?>
                    </div>
                    <a href="<?= $base_url ?>pengaturan/index.php" class="user-dropdown-item">
                        <i class="fas fa-user"></i> Profil
                    </a>
                    <a href="<?= $base_url ?>pengaturan/index.php" class="user-dropdown-item">
                        <i class="fas fa-sliders-h"></i> Preferensi
                    </a>
                    <div class="user-dropdown-divider"></div>
                    <a href="<?= $base_url ?>auth/logout.php" class="user-dropdown-item" onclick="return confirm('Yakin ingin logout?')">
                        <i class="fas fa-sign-out-alt"></i> Logout
                    </a>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content Area -->
    <main class="main-content">
