<?php
require_once '../includes/session.php';
require_once '../includes/config.php';

// Cek role admin
if ($_SESSION['role'] != 'admin') {
    header("Location: ../login.php");
    exit;
}

// Hitung statistik untuk dashboard
$total_buku = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM buku"))['total'];
$total_user = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'user'"))['total'];
$total_admin = mysqli_fetch_assoc(mysqli_query($conn, "SELECT COUNT(*) as total FROM users WHERE role = 'admin'"))['total'];

// Buku terbaru (5 data)
$buku_terbaru = mysqli_query($conn, "SELECT * FROM buku ORDER BY created_at DESC LIMIT 5");
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard Admin - Braille Store</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <!-- Chart.js (Untuk Grafik) -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --primary-dark: #15a38a;      /* Teal Gelap */
            --primary-light: #1cceac;      /* Teal Terang */
            --accent-yellow: #FFD700;      /* Kuning Emas */
            --sidebar-width: 280px;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: #f4f6f9;
        }
        
        /* Sidebar */
        .sidebar {
            position: fixed;
            top: 0;
            left: 0;
            height: 100vh;
            width: var(--sidebar-width);
            background: linear-gradient(180deg, var(--primary-light) 0%, var(--primary-dark) 100%);
            box-shadow: 4px 0 20px rgba(28, 206, 172, 0.15);
            z-index: 1000;
            transition: all 0.3s;
        }
        
        .sidebar-brand {
            padding: 25px 20px;
            border-bottom: 1px solid rgba(255,255,255,0.2);
            text-align: center;
        }
        
        .sidebar-brand i {
            font-size: 40px;
            color: var(--accent-yellow);
        }
        
        .sidebar-brand h4 {
            color: white;
            margin-top: 10px;
            font-weight: bold;
        }
        
        .sidebar-nav {
            padding: 20px 0;
        }
        
        .sidebar-nav .nav-link {
            color: rgba(255,255,255,0.85);
            padding: 15px 25px;
            font-size: 16px;
            font-weight: 500;
            transition: all 0.3s;
            border-left: 4px solid transparent;
        }
        
        .sidebar-nav .nav-link:hover {
            color: white;
            background: rgba(255,255,255,0.15);
            border-left-color: var(--accent-yellow);
        }
        
        .sidebar-nav .nav-link.active {
            color: white;
            background: rgba(255,255,255,0.2);
            border-left-color: var(--accent-yellow);
        }
        
        .sidebar-nav .nav-link i {
            width: 25px;
            margin-right: 10px;
            color: var(--accent-yellow);
        }
        
        .sidebar-footer {
            position: absolute;
            bottom: 0;
            width: 100%;
            padding: 20px;
            border-top: 1px solid rgba(255,255,255,0.2);
            color: rgba(255,255,255,0.8);
        }
        
        /* Main Content */
        .main-content {
            margin-left: var(--sidebar-width);
            min-height: 100vh;
            padding: 20px 30px;
        }
        
        /* Top Bar */
        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 30px;
            padding-bottom: 20px;
            border-bottom: 1px solid #e0e0e0;
        }
        
        .admin-profile {
            display: flex;
            align-items: center;
            gap: 15px;
        }
        
        .admin-avatar {
            width: 50px;
            height: 50px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-dark));
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 24px;
            font-weight: bold;
        }
        
        /* Stat Cards */
        .stat-card {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            transition: transform 0.3s, box-shadow 0.3s;
            border: 1px solid #eee;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 25px rgba(28, 206, 172, 0.15);
        }
        
        .stat-icon {
            width: 60px;
            height: 60px;
            background: linear-gradient(135deg, var(--primary-light), var(--primary-dark));
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 28px;
            margin-bottom: 15px;
        }
        
        .stat-value {
            font-size: 32px;
            font-weight: bold;
            color: var(--primary-dark);
            margin-bottom: 5px;
        }
        
        .stat-label {
            color: #666;
            font-size: 14px;
            text-transform: uppercase;
            letter-spacing: 1px;
        }
        
        /* Table */
        .table-container {
            background: white;
            border-radius: 15px;
            padding: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
            border: 1px solid #eee;
        }
        
        .table th {
            background-color: var(--primary-dark);
            color: white;
            font-weight: 500;
        }
        
        .badge-status {
            padding: 5px 12px;
            border-radius: 20px;
            font-weight: 500;
        }
        
        /* Hover Shadow untuk Menu Cepat */
        .hover-shadow {
            transition: all 0.3s;
        }
        
        .hover-shadow:hover {
            box-shadow: 0 5px 15px rgba(28, 206, 172, 0.15);
            border-color: var(--primary-light) !important;
        }
        
        /* Button Outline */
        .btn-outline-primary {
            color: var(--primary-dark);
            border-color: var(--primary-dark);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-dark);
            border-color: var(--primary-dark);
            color: white;
        }
        
        /* Responsive */
        @media (max-width: 768px) {
            .sidebar {
                transform: translateX(-100%);
            }
            .sidebar.active {
                transform: translateX(0);
            }
            .main-content {
                margin-left: 0;
            }
        }
    </style>
</head>
<body>
    
    <!-- ========== SIDEBAR ========== -->
    <div class="sidebar">
        <div class="sidebar-brand">
            <i class="fas fa-book-open"></i>
            <h4>Braille Admin</h4>
            <small class="text-white-50">Yayasan Raudlatul Makfufin</small>
        </div>
        
        <ul class="nav flex-column sidebar-nav">
            <li class="nav-item">
                <a class="nav-link active" href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> Dashboard
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="buku/index.php">
                    <i class="fas fa-book"></i> Kelola Buku
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-shopping-cart"></i> Pesanan Masuk
                </a>
            </li>
            <li class="nav-item">
                <a class="nav-link" href="#">
                    <i class="fas fa-users"></i> Data Pengguna
                </a>
            </li>
        </ul>
        
        <div class="sidebar-footer">
            <small>
                <i class="far fa-clock"></i> 
                <?= date('d M Y, H:i') ?> WIB
            </small>
        </div>
    </div>
    
    <!-- ========== MAIN CONTENT ========== -->
    <div class="main-content">
        
        <!-- Top Bar -->
        <div class="top-bar">
            <div>
                <h2 class="mb-0" style="color: var(--primary-dark);">
                    <i class="fas fa-tachometer-alt me-2" style="color: var(--accent-yellow);"></i>
                    Dashboard Admin
                </h2>
                <p class="text-muted mb-0">Selamat datang kembali, <?= $_SESSION['nama_lengkap'] ?></p>
            </div>
            
            <div class="admin-profile">
                <div class="text-end">
                    <strong><?= $_SESSION['nama_lengkap'] ?></strong><br>
                    <small class="text-muted">Administrator</small>
                </div>
                <div class="admin-avatar">
                    <?= strtoupper(substr($_SESSION['nama_lengkap'], 0, 1)) ?>
                </div>
                <a href="../logout.php" class="btn btn-outline-danger btn-sm">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
        </div>
        
        <!-- Statistik Cards -->
        <div class="row g-4 mb-4">
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-book"></i>
                    </div>
                    <div class="stat-value"><?= $total_buku ?></div>
                    <div class="stat-label">Total Buku Braille</div>
                    <small class="text-success">
                        <i class="fas fa-arrow-up"></i> Katalog aktif
                    </small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-users"></i>
                    </div>
                    <div class="stat-value"><?= $total_user ?></div>
                    <div class="stat-label">Pengguna Terdaftar</div>
                    <small class="text-muted">User biasa</small>
                </div>
            </div>
            <div class="col-md-4">
                <div class="stat-card">
                    <div class="stat-icon">
                        <i class="fas fa-user-shield"></i>
                    </div>
                    <div class="stat-value"><?= $total_admin ?></div>
                    <div class="stat-label">Administrator</div>
                    <small class="text-muted">Akun admin</small>
                </div>
            </div>
        </div>
        
        <!-- Menu Cepat & Buku Terbaru -->
        <div class="row g-4">
            <!-- Menu Cepat -->
            <div class="col-md-5">
                <div class="table-container h-100">
                    <h5 class="mb-4">
                        <i class="fas fa-bolt me-2" style="color: var(--accent-yellow);"></i>
                        Menu Cepat
                    </h5>
                    <div class="row g-3">
                        <div class="col-6">
                            <a href="buku/tambah.php" class="text-decoration-none">
                                <div class="p-4 text-center border rounded-3 hover-shadow">
                                    <i class="fas fa-plus-circle fa-2x mb-3" style="color: var(--primary-dark);"></i>
                                    <h6>Tambah Buku Baru</h6>
                                    <small class="text-muted">Input data katalog</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="buku/index.php" class="text-decoration-none">
                                <div class="p-4 text-center border rounded-3 hover-shadow">
                                    <i class="fas fa-list fa-2x mb-3" style="color: var(--primary-dark);"></i>
                                    <h6>Lihat Semua Buku</h6>
                                    <small class="text-muted">Kelola katalog</small>
                                </div>
                            </a>
                        </div>
                        <div class="col-6">
                            <a href="../index.php" target="_blank" class="text-decoration-none">
                                <div class="p-4 text-center border rounded-3 hover-shadow">
                                    <i class="fas fa-eye fa-2x mb-3" style="color: var(--primary-dark);"></i>
                                    <h6>Lihat Website</h6>
                                    <small class="text-muted">Tampilan user</small>
                                </div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Buku Terbaru -->
            <div class="col-md-7">
                <div class="table-container h-100">
                    <div class="d-flex justify-content-between align-items-center mb-4">
                        <h5>
                            <i class="fas fa-clock me-2" style="color: var(--accent-yellow);"></i>
                            Buku Terbaru Ditambahkan
                        </h5>
                        <a href="buku/index.php" class="btn btn-sm btn-outline-primary">
                            Lihat Semua <i class="fas fa-arrow-right"></i>
                        </a>
                    </div>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Judul Buku</th>
                                    <th>Kategori</th>
                                    <th>Ukuran</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php while ($buku = mysqli_fetch_assoc($buku_terbaru)): ?>
                                <tr>
                                    <td>
                                        <strong><?= htmlspecialchars($buku['judul']) ?></strong><br>
                                        <small class="text-muted"><?= htmlspecialchars($buku['pengarang']) ?></small>
                                    </td>
                                    <td>
                                        <span class="badge-status bg-info text-white">
                                            <?= $buku['kategori'] ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if ($buku['ukuran'] == 'Besar'): ?>
                                            <span class="badge-status bg-primary text-white">Besar</span>
                                        <?php else: ?>
                                            <span class="badge-status bg-secondary text-white">Kecil</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <a href="buku/edit.php?id=<?= $buku['id'] ?>" class="btn btn-sm btn-warning">
                                            <i class="fas fa-edit"></i>
                                        </a>
                                        <a href="buku/detail.php?id=<?= $buku['id'] ?>" class="btn btn-sm btn-info">
                                            <i class="fas fa-eye"></i>
                                        </a>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                                <?php if (mysqli_num_rows($buku_terbaru) == 0): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4 text-muted">
                                        <i class="fas fa-box-open fa-2x mb-2"></i><br>
                                        Belum ada buku. Klik "Tambah Buku Baru".
                                    </td>
                                </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Footer -->
        <footer class="mt-5 pt-3 text-center text-muted border-top">
            <small>© 2026 Yayasan Raudlatul Makfufin - Sistem Informasi Katalog Buku Braille</small>
        </footer>
    </div>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>