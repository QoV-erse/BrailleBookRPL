<?php
session_start();
require_once '../includes/config.php';

// Cek apakah user sudah login
if (!isset($_SESSION['user_id'])) {
    header("Location: ../login.php");
    exit;
}

// Jika admin, redirect ke admin dashboard
if ($_SESSION['role'] == 'admin') {
    header("Location: ../admin/dashboard.php");
    exit;
}

// Ambil data user
$user_id = $_SESSION['user_id'];

// Hitung statistik user
$total_favorit = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM favorit WHERE user_id = $user_id"
))['total'] ?? 0;

$total_pesanan = mysqli_fetch_assoc(mysqli_query($conn, 
    "SELECT COUNT(*) as total FROM pesanan WHERE user_id = $user_id"
))['total'] ?? 0;

// Ambil 4 buku terbaru untuk rekomendasi
$buku_rekomendasi = mysqli_query($conn, 
    "SELECT * FROM buku ORDER BY created_at DESC LIMIT 4"
);

// Ambil buku favorit user (maksimal 4)
$buku_favorit = mysqli_query($conn, 
    "SELECT b.* FROM buku b 
     JOIN favorit f ON b.id = f.buku_id 
     WHERE f.user_id = $user_id 
     ORDER BY f.created_at DESC LIMIT 4"
);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - Braille Book Catalog</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ========== WARNA UTAMA: TEAL/TOSCA ========== */
        :root {
            --primary-main: #1cceac;        /* Hijau Tosca */
            --primary-dark: #15a38a;         /* Hijau Tosca Gelap */
            --primary-light: #4ddbc3;        /* Hijau Tosca Muda */
            --accent-gold: #FFD700;          /* Kuning emas */
            --accent-yellow: #FFC107;        /* Kuning */
            --accent-orange: #FF8C00;        /* Oranye sebagai aksen tambahan */
            --light-bg: #f8f9fa;
            --dark-text: #2c3e50;
        }
        
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }
        
        body {
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }
        
        /* ========== NAVBAR ========== */
        .navbar {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%) !important;
            box-shadow: 0 4px 15px rgba(28, 206, 172, 0.2);
            padding: 15px 0;
        }
        
        .navbar-brand {
            font-size: 1.5rem;
            font-weight: bold;
            color: white !important;
        }
        
        .navbar-brand i {
            color: var(--accent-gold);
            margin-right: 10px;
        }
        
        .navbar-nav .nav-link {
            color: rgba(255,255,255,0.95) !important;
            font-weight: 500;
            padding: 8px 16px !important;
            transition: all 0.3s;
        }
        
        .navbar-nav .nav-link:hover,
        .navbar-nav .nav-link.active {
            color: var(--accent-gold) !important;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
        }
        
        .user-badge {
            background: rgba(255,255,255,0.2);
            padding: 8px 16px;
            border-radius: 50px;
            color: white;
        }
        
        .user-badge i {
            color: var(--accent-gold);
            margin-right: 8px;
        }
        
        /* ========== WELCOME SECTION ========== */
        .welcome-section {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 30px 30px;
        }
        
        .welcome-title {
            font-size: 2rem;
            font-weight: bold;
        }
        
        .welcome-title i {
            color: var(--accent-gold);
        }
        
        /* ========== STAT CARDS ========== */
        .stat-card {
            background: white;
            border-radius: 20px;
            padding: 25px 20px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.08);
            transition: all 0.3s;
            border: 1px solid rgba(0,0,0,0.05);
            height: 100%;
        }
        
        .stat-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(28, 206, 172, 0.15);
            border-color: var(--accent-gold);
        }
        
        .stat-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 20px;
            color: white;
            font-size: 32px;
        }
        
        .stat-value {
            font-size: 36px;
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
        
        /* ========== SECTION TITLE ========== */
        .section-title {
            display: flex;
            align-items: center;
            margin-bottom: 25px;
        }
        
        .section-title h3 {
            font-weight: bold;
            color: var(--primary-dark);
            margin: 0;
        }
        
        .section-title i {
            color: var(--primary-main);
            margin-right: 12px;
            font-size: 28px;
        }
        
        .section-title .line {
            flex: 1;
            height: 3px;
            background: linear-gradient(90deg, var(--primary-main), transparent);
            margin-left: 20px;
        }
        
        /* ========== BOOK CARD ========== */
        .book-card {
            background: white;
            border-radius: 15px;
            overflow: hidden;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
            border: 1px solid #eee;
        }
        
        .book-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 30px rgba(28, 206, 172, 0.15);
            border-color: var(--primary-main);
        }
        
        .book-img {
            height: 200px;
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
        }
        
        .book-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
        }
        
        .book-body {
            padding: 20px;
        }
        
        .book-category {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
            color: white;
            padding: 5px 15px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            margin-bottom: 12px;
        }
        
        .book-title {
            font-weight: bold;
            font-size: 1.1rem;
            color: var(--dark-text);
            margin-bottom: 8px;
        }
        
        .book-author {
            color: #666;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .book-author i {
            color: var(--primary-main);
            margin-right: 5px;
        }
        
        .btn-detail {
            background: var(--primary-main);
            color: white;
            border: none;
            padding: 8px 20px;
            border-radius: 30px;
            font-weight: 500;
            transition: all 0.3s;
            width: 100%;
        }
        
        .btn-detail:hover {
            background: var(--accent-gold);
            color: var(--primary-dark);
            font-weight: 600;
        }
        
        /* ========== KATEGORI CEPAT ========== */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
        }
        
        .category-item {
            background: white;
            padding: 20px 15px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s;
            border: 1px solid #eee;
            text-decoration: none;
        }
        
        .category-item:hover {
            background: var(--primary-main);
            color: white;
            transform: translateY(-3px);
            border-color: var(--accent-gold);
        }
        
        .category-item i {
            font-size: 28px;
            color: var(--primary-main);
            margin-bottom: 10px;
        }
        
        .category-item:hover i,
        .category-item:hover span {
            color: var(--accent-gold) !important;
        }
        
        .category-item span {
            display: block;
            font-weight: 600;
            color: var(--dark-text);
            font-size: 14px;
        }
        
        .category-item:hover span {
            color: white;
        }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .welcome-title {
                font-size: 1.5rem;
            }
            
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .category-item {
                padding: 15px 10px;
            }
            
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .stat-card {
                padding: 20px 15px;
            }
            
            .stat-value {
                font-size: 28px;
            }
            
            .book-img {
                height: 160px;
            }
        }
        
        @media (max-width: 576px) {
            .category-grid {
                grid-template-columns: 1fr;
            }
        }
        
        /* ========== FOOTER ========== */
        .footer {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 30px 0;
            margin-top: 50px;
        }
        
        .footer a {
            color: var(--accent-gold);
            text-decoration: none;
        }
        
        .footer a:hover {
            text-decoration: underline;
        }
        
        /* ========== BUTTON OUTLINE STYLE ========== */
        .btn-outline-primary {
            color: var(--primary-main);
            border-color: var(--primary-main);
        }
        
        .btn-outline-primary:hover {
            background: var(--primary-main);
            border-color: var(--primary-main);
            color: white;
        }
        
        .btn-warning {
            background: var(--accent-gold);
            border-color: var(--accent-gold);
            color: var(--dark-text);
            font-weight: 600;
        }
        
        .btn-warning:hover {
            background: var(--accent-yellow);
            border-color: var(--accent-yellow);
            color: var(--dark-text);
        }
    </style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="../index.php">
            <i class="fas fa-book-open"></i> BRAILLE BOOK CATALOG
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link" href="../index.php">KATALOG</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">ABOUT</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="#">CONTACT</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link active" href="dashboard.php">
                        <i class="fas fa-user"></i> DASHBOARD
                    </a>
                </li>
            </ul>
            <div class="user-badge">
                <i class="fas fa-user-circle"></i> 
                <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                <a href="../logout.php" class="btn btn-sm btn-outline-light ms-3">
                    <i class="fas fa-sign-out-alt"></i> Logout
                </a>
            </div>
        </div>
    </div>
</nav>

<!-- ========== WELCOME SECTION ========== -->
<section class="welcome-section">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-8">
                <h1 class="welcome-title">
                    <i class="fas fa-hand-peace"></i> Selamat Datang,<br>
                    <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>!
                </h1>
                <p class="mt-3 mb-0" style="opacity: 0.95;">
                    <i class="fas fa-book-reader me-2"></i>
                    Kelola koleksi buku favorit dan pantau status pesanan Anda di sini.
                </p>
            </div>
            <div class="col-md-4 text-md-end mt-3 mt-md-0">
                <a href="../index.php" class="btn btn-warning btn-lg">
                    <i class="fas fa-shopping-bag me-2"></i>Lihat Katalog
                </a>
            </div>
        </div>
    </div>
</section>

<!-- ========== MAIN CONTENT ========== -->
<div class="container">
    
    <!-- Statistik Cards -->
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <div class="stat-value"><?= $total_favorit ?></div>
                <div class="stat-label">Buku Favorit</div>
                <small class="text-muted">Buku yang Anda simpan</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <div class="stat-value"><?= $total_pesanan ?></div>
                <div class="stat-label">Total Pesanan</div>
                <small class="text-muted">Pre-order & pembelian</small>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card text-center">
                <div class="stat-icon">
                    <i class="fas fa-clock"></i>
                </div>
                <div class="stat-value">
                    <?= date('d') ?>
                </div>
                <div class="stat-label"><?= $bulan_ini = date('F Y') ?></div>
                <small class="text-muted"><?= date('H:i') ?> WIB</small>
            </div>
        </div>
    </div>
    
    <!-- KATEGORI-KATEGORI (Sesuai Desain PNG) -->
    <div class="section-title">
        <i class="fas fa-layer-group"></i>
        <h3>KATEGORI-KATEGORI</h3>
        <span class="line"></span>
    </div>
    
    <div class="category-grid mb-5">
        <a href="../index.php?kategori=Al-Quran" class="category-item">
            <i class="fas fa-quran"></i>
            <span>AL-QUR'AN</span>
        </a>
        <a href="../index.php?kategori=Islam" class="category-item">
            <i class="fas fa-star-and-crescent"></i>
            <span>BUKU ISLAM</span>
        </a>
        <a href="../index.php?kategori=Panduan" class="category-item">
            <i class="fas fa-book"></i>
            <span>BUKU PANDUAN</span>
        </a>
        <a href="../index.php?kategori=Solat" class="category-item">
            <i class="fas fa-pray"></i>
            <span>BUKU TENTANG SOLAT</span>
        </a>
    </div>
    
    <!-- Menu Utama Dashboard -->
    <div class="section-title">
        <i class="fas fa-th-large"></i>
        <h3>MENU DASHBOARD</h3>
        <span class="line"></span>
    </div>
    
    <div class="row g-4 mb-5">
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-heart"></i>
                </div>
                <h5 class="text-center mb-3">Buku Favorit</h5>
                <p class="text-center text-muted">Lihat dan kelola daftar buku yang Anda simpan sebagai favorit.</p>
                <a href="favorit.php" class="btn btn-detail">
                    <i class="fas fa-arrow-right me-2"></i>Lihat Favorit
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-shopping-cart"></i>
                </div>
                <h5 class="text-center mb-3">Pesanan Saya</h5>
                <p class="text-center text-muted">Pantau status pre-order dan riwayat pemesanan Anda.</p>
                <a href="pesanan.php" class="btn btn-detail">
                    <i class="fas fa-arrow-right me-2"></i>Lihat Pesanan
                </a>
            </div>
        </div>
        <div class="col-md-4">
            <div class="stat-card">
                <div class="stat-icon">
                    <i class="fas fa-user-edit"></i>
                </div>
                <h5 class="text-center mb-3">Profil Saya</h5>
                <p class="text-center text-muted">Edit informasi profil dan pengaturan akun Anda.</p>
                <a href="profil.php" class="btn btn-detail">
                    <i class="fas fa-arrow-right me-2"></i>Edit Profil
                </a>
            </div>
        </div>
    </div>
    
    <!-- Buku Favorit User -->
    <?php if (mysqli_num_rows($buku_favorit) > 0): ?>
    <div class="section-title">
        <i class="fas fa-heart" style="color: #e74c3c;"></i>
        <h3>BUKU FAVORIT ANDA</h3>
        <span class="line"></span>
        <a href="favorit.php" class="btn btn-outline-primary ms-3">
            Lihat Semua <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    
    <div class="row g-4 mb-5">
        <?php while ($buku = mysqli_fetch_assoc($buku_favorit)): ?>
        <div class="col-md-3 col-sm-6">
            <div class="book-card">
                <div class="book-img">
                    <?php if ($buku['gambar'] && file_exists('../assets/img/' . $buku['gambar'])): ?>
                        <img src="../assets/img/<?= $buku['gambar'] ?>" alt="<?= $buku['judul'] ?>">
                    <?php else: ?>
                        <i class="fas fa-book"></i>
                    <?php endif; ?>
                </div>
                <div class="book-body">
                    <span class="book-category"><?= $buku['kategori'] ?></span>
                    <h6 class="book-title"><?= htmlspecialchars($buku['judul']) ?></h6>
                    <p class="book-author">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($buku['pengarang']) ?>
                    </p>
                    <a href="../detail.php?id=<?= $buku['id'] ?>" class="btn btn-detail">
                        <i class="fas fa-info-circle me-2"></i>DETAIL
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
    <?php endif; ?>
    
    <!-- Rekomendasi Buku Terbaru -->
    <div class="section-title">
        <i class="fas fa-star" style="color: var(--accent-gold);"></i>
        <h3>REKOMENDASI BUKU TERBARU</h3>
        <span class="line"></span>
        <a href="../index.php" class="btn btn-outline-primary ms-3">
            Lihat Semua <i class="fas fa-arrow-right"></i>
        </a>
    </div>
    
    <div class="row g-4 mb-4">
        <?php while ($buku = mysqli_fetch_assoc($buku_rekomendasi)): ?>
        <div class="col-md-3 col-sm-6">
            <div class="book-card">
                <div class="book-img">
                    <?php if ($buku['gambar'] && file_exists('../assets/img/' . $buku['gambar'])): ?>
                        <img src="../assets/img/<?= $buku['gambar'] ?>" alt="<?= $buku['judul'] ?>">
                    <?php else: ?>
                        <i class="fas fa-book"></i>
                    <?php endif; ?>
                </div>
                <div class="book-body">
                    <span class="book-category"><?= $buku['kategori'] ?></span>
                    <h6 class="book-title"><?= htmlspecialchars($buku['judul']) ?></h6>
                    <p class="book-author">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($buku['pengarang']) ?>
                    </p>
                    <a href="../detail.php?id=<?= $buku['id'] ?>" class="btn btn-detail">
                        <i class="fas fa-info-circle me-2"></i>DETAIL
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        
        <?php if (mysqli_num_rows($buku_rekomendasi) == 0): ?>
        <div class="col-12">
            <div class="text-center py-5">
                <i class="fas fa-box-open fa-4x text-muted mb-3"></i>
                <h5>Belum ada buku dalam katalog</h5>
                <p class="text-muted">Silakan cek kembali nanti.</p>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========== FOOTER ========== -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-6">
                <h5>
                    <i class="fas fa-book-open me-2" style="color: var(--accent-gold);"></i>
                    Braille Book Catalog
                </h5>
                <p class="mb-0" style="opacity: 0.9;">
                    Yayasan Raudlatul Makfufin - Percetakan Buku Islam Braille
                </p>
            </div>
            <div class="col-md-6 text-md-end mt-3 mt-md-0">
                <a href="../index.php" class="text-white me-3">HOME</a>
                <a href="#" class="text-white me-3">ABOUT</a>
                <a href="#" class="text-white me-3">CONTACT</a>
                <a href="dashboard.php" class="text-white">DASHBOARD</a>
                <p class="mt-3 mb-0" style="opacity: 0.8;">
                    <small>© 2026 Yayasan Raudlatul Makfufin. All rights reserved.</small>
                </p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>