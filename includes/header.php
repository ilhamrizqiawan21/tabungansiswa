<?php
// includes/header.php
require_once __DIR__ . '/../includes/security.php';
$base_url = '/tabungansiswa/';
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
            --topbar-height: 70px;
        }
        body {
            font-family: 'Inter', sans-serif;
            padding-top: var(--topbar-height);
            background: linear-gradient(135deg, #f5f7fa 0%, #e9eef3 100%);
            min-height: 100vh;
        }
        /* Navbar Glassmorphism */
        .navbar {
            height: var(--topbar-height);
            background: rgba(15, 23, 42, 0.85);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 8px 32px rgba(0, 0, 0, 0.1);
            padding: 0 1.5rem;
        }
        .navbar-brand {
            font-weight: 700;
            font-size: 1.4rem;
            letter-spacing: -0.5px;
            background: linear-gradient(135deg, #fff, #c7d2fe);
            -webkit-background-clip: text;
            background-clip: text;
            color: transparent !important;
        }
        .navbar-brand i {
            background: none;
            -webkit-background-clip: unset;
            background-clip: unset;
            color: #a5b4fc;
            margin-right: 8px;
        }
        .navbar-nav .nav-link {
            color: rgba(255, 255, 255, 0.8) !important;
            font-weight: 500;
            margin: 0 0.25rem;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            transition: all 0.2s ease;
            font-size: 0.95rem;
        }
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            background: rgba(255, 255, 255, 0.15);
            color: white !important;
            transform: translateY(-2px);
        }
        .navbar-nav .nav-link i {
            margin-right: 8px;
            font-size: 1rem;
        }
        /* Dropdown modern */
        .dropdown-menu {
            background: rgba(30, 41, 59, 0.95);
            backdrop-filter: blur(8px);
            border: 1px solid rgba(255, 255, 255, 0.2);
            border-radius: 16px;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.2);
            padding: 0.5rem;
        }
        .dropdown-item {
            color: #e2e8f0;
            border-radius: 12px;
            padding: 0.6rem 1rem;
            transition: all 0.2s;
        }
        .dropdown-item:hover {
            background: rgba(255, 255, 255, 0.1);
            color: white;
            transform: translateX(4px);
        }
        .dropdown-divider {
            border-top: 1px solid rgba(255, 255, 255, 0.1);
        }
        /* Avatar & Icon */
        .user-avatar {
            width: 36px;
            height: 36px;
            background: linear-gradient(135deg, #818cf8, #c7d2fe);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            margin-right: 8px;
        }
        .user-avatar i {
            font-size: 1.2rem;
            color: #0f172a;
        }
        .notification-icon {
            position: relative;
            margin-right: 1rem;
            color: rgba(255,255,255,0.8);
            font-size: 1.2rem;
            transition: all 0.2s;
        }
        .notification-icon:hover {
            color: white;
            transform: scale(1.05);
        }
        .notification-badge {
            position: absolute;
            top: -5px;
            right: -8px;
            background: #ef4444;
            color: white;
            font-size: 0.7rem;
            font-weight: bold;
            padding: 0.2rem 0.4rem;
            border-radius: 20px;
            line-height: 1;
        }
        /* Main content */
        .main-content {
            padding: 2rem;
            animation: fadeInUp 0.4s ease;
        }
        @keyframes fadeInUp {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }
        /* Cards dan elemen lain bisa ditambah */
        .card {
            border: none;
            border-radius: 20px;
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.025);
            transition: transform 0.2s, box-shadow 0.2s;
        }
        .card:hover {
            transform: translateY(-4px);
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1);
        }
        .btn {
            border-radius: 40px;
            padding: 0.5rem 1.25rem;
            font-weight: 500;
        }
        .table {
            background: white;
            border-radius: 20px;
            overflow: hidden;
        }
        .table thead th {
            background: #f1f5f9;
            border-bottom: none;
            font-weight: 600;
        }
        @media (max-width: 992px) {
            .navbar-collapse {
                background: rgba(15, 23, 42, 0.98);
                backdrop-filter: blur(12px);
                border-radius: 24px;
                padding: 1rem;
                margin-top: 0.5rem;
            }
            .main-content {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>

<nav class="navbar navbar-expand-lg fixed-top">
    <div class="container-fluid">
        <a class="navbar-brand" href="<?= $base_url ?>dashboard.php">
            <i class="fas fa-piggy-bank"></i> TabunganSiswa
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNavbar">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="mainNavbar">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>" href="<?= $base_url ?>dashboard.php">
                        <i class="fas fa-tachometer-alt"></i> Dashboard
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/siswa/') !== false ? 'active' : '' ?>" href="<?= $base_url ?>siswa/index.php">
                        <i class="fas fa-users"></i> Data Siswa
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/transaksi/') !== false ? 'active' : '' ?>" href="<?= $base_url ?>transaksi/index.php">
                        <i class="fas fa-exchange-alt"></i> Transaksi
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/laporan/') !== false ? 'active' : '' ?>" href="<?= $base_url ?>laporan/index.php">
                        <i class="fas fa-file-alt"></i> Laporan
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link <?= strpos($_SERVER['REQUEST_URI'], '/pengaturan/') !== false ? 'active' : '' ?>" href="<?= $base_url ?>pengaturan/index.php">
                        <i class="fas fa-sliders-h"></i> Pengaturan
                    </a>
                </li>
            </ul>
            <ul class="navbar-nav align-items-center">
                <li class="nav-item">
                    <a class="notification-icon" href="#" id="themeToggle">
                        <i class="fas fa-moon"></i>
                    </a>
                </li>
                <li class="nav-item dropdown">
                    <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="navbarDropdown" role="button" data-bs-toggle="dropdown">
                        <div class="user-avatar">
                            <i class="fas fa-user"></i>
                        </div>
                        <span class="d-none d-sm-inline ms-1">
                            <?= isset($_SESSION['admin_nama']) ? htmlspecialchars($_SESSION['admin_nama']) : 'Admin' ?>
                        </span>
                    </a>
                    <ul class="dropdown-menu dropdown-menu-end">
                        <li><a class="dropdown-item" href="<?= $base_url ?>pengaturan/index.php"><i class="fas fa-user-cog me-2"></i>Profil Saya</a></li>
                        <li><hr class="dropdown-divider"></li>
                        <li><a class="dropdown-item" href="<?= $base_url ?>auth/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                    </ul>
                </li>
            </ul>
        </div>
    </div>
</nav>

<div class="container-fluid">
    <div class="row">
        <main role="main" class="main-content" id="mainContent">
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show mt-3 shadow-sm">
                    <?= $_SESSION['success'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show mt-3 shadow-sm">
                    <?= $_SESSION['error'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error']); ?>
            <?php endif; ?>