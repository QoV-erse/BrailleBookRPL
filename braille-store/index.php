<?php
session_start();
require_once 'includes/config.php';

// Filter kategori jika ada parameter
$kategori_filter = isset($_GET['kategori']) ? $_GET['kategori'] : '';
$where_clause = '';
if ($kategori_filter && in_array($kategori_filter, ['Al-Quran', 'Islam', 'Panduan', 'Solat'])) {
    $where_clause = "WHERE kategori = '$kategori_filter'";
}

// Ambil data buku dari database, urutkan sesuai abjad (Wawancara No.7)
$query = "SELECT * FROM buku $where_clause ORDER BY judul ASC";
$result = mysqli_query($conn, $query);
?>

<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Braille Book Catalog - Yayasan Raudlatul Makfufin</title>
    
    <!-- Bootstrap 5 -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <style>
        /* ========== WARNA UTAMA: HIJAU TOSCA ========== */
        :root {
            --primary-main: #1cceac;
            --primary-dark: #15a38a;
            --primary-light: #4ddbc3;
            --accent-gold: #FFD700;
            --accent-yellow: #FFC107;
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
            font-size: 16px;
            background-color: var(--light-bg);
            color: var(--dark-text);
        }
        
        /* ========== NAVBAR ========== */
        .navbar {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%) !important;
            box-shadow: 0 4px 15px rgba(28, 206, 172, 0.2);
            padding: 15px 0;
            position: sticky;
            top: 0;
            z-index: 999;
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
            font-size: 1rem;
        }
        
        .navbar-nav .nav-link:hover,
        .user-badge {
            background: rgba(255,255,255,0.2);
            padding: 7px 15px;
            border-radius: 50px;
            color: white;
            font-size: .95rem;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .user-badge i {
            color: var(--accent-gold);
        }
        .navbar-nav .nav-link.active {
            color: var(--accent-gold) !important;
            background: rgba(255,255,255,0.15);
            border-radius: 8px;
        }
        
        /* ========== SEARCH SECTION ========== */
        .search-section {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            padding: 40px 0;
            margin-bottom: 30px;
            border-radius: 0 0 30px 30px;
        }

        .search-box {
            background: white;
            border-radius: 50px;
            padding: 5px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.15);
        }

        .search-box input {
            border: none;
            padding: 15px 20px;
            font-size: 1.1rem;
            border-radius: 50px 0 0 50px;
            width: 100%;
        }

        .search-box input:focus {
            outline: none;
            box-shadow: none;
        }

        .search-box button {
            background: var(--accent-gold);
            color: var(--dark-text);
            border: none;
            padding: 15px 30px;
            font-size: 1.1rem;
            font-weight: 600;
            border-radius: 50px;
            transition: all 0.3s;
            white-space: nowrap;
        }

        .search-box button:hover {
            background: var(--accent-yellow);
            transform: scale(1.02);
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
            font-size: 1.5rem;
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
        
        .kategori-badge {
            background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
            color: white;
            padding: 10px 25px;
            border-radius: 30px;
            font-weight: bold;
            font-size: 1.2rem;
            display: inline-block;
            box-shadow: 0 5px 15px rgba(28, 206, 172, 0.3);
        }
        
        /* ========== KATEGORI GRID ========== */
        .category-grid {
            display: grid;
            grid-template-columns: repeat(4, 1fr);
            gap: 15px;
            margin-bottom: 30px;
        }
        
        .category-item {
            background: white;
            padding: 20px 15px;
            border-radius: 15px;
            text-align: center;
            transition: all 0.3s;
            border: 2px solid #eee;
            text-decoration: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.05);
        }
        
        .category-item:hover {
            background: var(--primary-main);
            color: white;
            transform: translateY(-5px);
            border-color: var(--accent-gold);
            box-shadow: 0 15px 30px rgba(28, 206, 172, 0.2);
        }
        
        .category-item i {
            font-size: 32px;
            color: var(--primary-main);
            margin-bottom: 12px;
        }
        
        .category-item:hover i,
        .category-item:hover span {
            color: var(--accent-gold) !important;
        }
        
        .category-item span {
            display: block;
            font-weight: 600;
            color: var(--dark-text);
            font-size: 1rem;
        }
        
        .category-item.active {
            background: var(--primary-main);
            border-color: var(--accent-gold);
        }
        
        .category-item.active i,
        .category-item.active span {
            color: white !important;
        }
        
        /* ========== BOOK CARD ========== */
        .book-card {
            background: white;
            border-radius: 20px;
            overflow: hidden;
            box-shadow: 0 10px 25px rgba(0,0,0,0.08);
            transition: all 0.3s;
            height: 100%;
            border: 2px solid #eee;
            display: flex;
            flex-direction: column;
        }
        
        .book-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 20px 40px rgba(28, 206, 172, 0.2);
            border-color: var(--primary-main);
        }
        
        .book-img {
            height: 220px;
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-size: 48px;
            position: relative;
            overflow: hidden;
        }
        
        .book-img img {
            width: 100%;
            height: 100%;
            object-fit: cover;
            transition: transform 0.3s;
        }
        
        .book-card:hover .book-img img {
            transform: scale(1.05);
        }
        
        .book-body {
            padding: 20px;
            flex: 1;
            display: flex;
            flex-direction: column;
        }
        
        .book-category {
            display: inline-block;
            background: linear-gradient(135deg, var(--primary-main), var(--primary-dark));
            color: white;
            padding: 6px 15px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
            margin-bottom: 12px;
            align-self: flex-start;
        }
        
        .book-title {
            font-weight: bold;
            font-size: 1.2rem;
            color: var(--dark-text);
            margin-bottom: 8px;
            line-height: 1.4;
        }
        
        .book-author {
            color: #666;
            font-size: 0.95rem;
            margin-bottom: 8px;
        }
        
        .book-author i {
            color: var(--primary-main);
            margin-right: 5px;
        }
        
        .book-size {
            color: #888;
            font-size: 0.9rem;
            margin-bottom: 15px;
        }
        
        .book-size i {
            color: var(--primary-main);
            margin-right: 5px;
        }
        
        .btn-detail {
            background: var(--primary-main);
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 30px;
            font-weight: 600;
            transition: all 0.3s;
            width: 100%;
            margin-top: auto;
            font-size: 1rem;
        }
        
        .btn-detail:hover {
            background: var(--accent-gold);
            color: var(--primary-dark);
            transform: scale(1.02);
        }
        
        /* ========== EMPTY STATE ========== */
        .empty-state {
            background: white;
            border-radius: 20px;
            padding: 50px;
            text-align: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.05);
        }
        
        .empty-state i {
            color: var(--primary-main);
            opacity: 0.5;
        }
        
        /* ========== FOOTER ========== */
        .footer {
            background: linear-gradient(135deg, var(--primary-main) 0%, var(--primary-dark) 100%);
            color: white;
            padding: 40px 0;
            margin-top: 60px;
        }
        
        .footer h5 {
            color: var(--accent-gold);
            margin-bottom: 15px;
        }
        
        .footer a {
            color: rgba(255,255,255,0.9);
            text-decoration: none;
            transition: color 0.3s;
        }
        
        .footer a:hover {
            color: var(--accent-gold);
        }
        
        .footer .social-links a {
            display: inline-block;
            width: 40px;
            height: 40px;
            background: rgba(255,255,255,0.1);
            border-radius: 50%;
            text-align: center;
            line-height: 40px;
            margin-right: 10px;
            transition: all 0.3s;
        }
        
        .footer .social-links a:hover {
            background: var(--accent-gold);
            color: var(--primary-dark);
            transform: translateY(-3px);
        }
        
        /* ========== RESPONSIVE ========== */
        @media (max-width: 768px) {
            .navbar-brand {
                font-size: 1.2rem;
            }
            
            .category-grid {
                grid-template-columns: repeat(2, 1fr);
                gap: 10px;
            }
            
            .category-item {
                padding: 15px 10px;
            }
            
            .category-item i {
                font-size: 24px;
            }
            
            .section-title h3 {
                font-size: 1.3rem;
            }
            
            .book-img {
                height: 180px;
            }
            
            .book-title {
                font-size: 1rem;
            }

            .search-box input {
                font-size: 1rem;
                padding: 12px 15px;
            }

            .search-box button {
                padding: 12px 18px;
                font-size: 0.95rem;
            }
        }
        
        @media (max-width: 576px) {
            .category-grid {
                grid-template-columns: 1fr;
            }
            
            .kategori-badge {
                font-size: 1rem;
                padding: 8px 20px;
            }
        }
        
        /* ========== BADGE STYLES ========== */
        .badge-category {
            background: var(--primary-main) !important;
            color: white !important;
            font-weight: 500;
            padding: 5px 12px;
            border-radius: 20px;
        }
        
        /* ========== BUTTON OUTLINE ========== */
        .btn-outline-category {
            color: var(--primary-dark);
            border: 2px solid var(--primary-main);
            background: white;
            font-weight: 600;
        }
        
        .btn-outline-category:hover {
            background: var(--primary-main);
            border-color: var(--primary-main);
            color: white;
        }
        
        .btn-outline-category.active {
            background: var(--primary-main);
            color: white;
        }
    </style>
</head>
<body>

<!-- ========== NAVBAR ========== -->
<nav class="navbar navbar-expand-lg">
    <div class="container">
        <a class="navbar-brand" href="index.php">
            <i class="fas fa-book-open"></i> <strong>BRAILLE BOOK CATALOG</strong>
        </a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav mx-auto">
                <li class="nav-item">
                    <a class="nav-link active" href="index.php">
                        <i class="fas fa-home"></i> KATALOG
                    </a>
                </li>
                <?php if (isset($_SESSION['user_id'])): ?>
                <li class="nav-item">
                    <a class="nav-link" href="user/dashboard.php">
                        <i class="fas fa-th-large"></i> DASHBOARD
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user/pesanan.php">
                        <i class="fas fa-shopping-bag"></i> PESANAN
                    </a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="user/profil.php">
                        <i class="fas fa-user-circle"></i> PROFIL
                    </a>
                </li>
                <?php endif; ?>
            </ul>
            <?php if (isset($_SESSION['user_id'])): ?>
            <div class="user-badge">
                <i class="fas fa-user-circle"></i>
                <?= htmlspecialchars($_SESSION['nama_lengkap']) ?>
                <a href="logout.php" class="btn btn-sm btn-outline-light ms-3">
                    <i class="fas fa-sign-out-alt"></i>
                </a>
            </div>
            <?php else: ?>
            <a href="login.php" class="btn btn-outline-light btn-sm">
                <i class="fas fa-sign-in-alt me-1"></i> LOGIN
            </a>
            <?php endif; ?>
        </div>
    </div>
</nav>

<!-- ========== SEARCH SECTION ========== -->
<section class="search-section">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-md-8 col-lg-6">
                <form action="cari.php" method="GET">
                    <div class="search-box d-flex">
                        <input type="search" name="keyword" class="form-control"
                               placeholder="Tulis nama buku atau pengarang..."
                               aria-label="Cari buku">
                        <button type="submit">
                            <i class="fas fa-search me-2"></i> Cari
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</section>

<!-- ========== KATEGORI-KATEGORI ========== -->
<div class="container">
    <div class="section-title">
        <i class="fas fa-layer-group"></i>
        <h3>KATEGORI-KATEGORI</h3>
        <span class="line"></span>
    </div>
    
    <div class="category-grid">
        <a href="index.php?kategori=Al-Quran" class="category-item <?= $kategori_filter == 'Al-Quran' ? 'active' : '' ?>">
            <i class="fas fa-quran"></i>
            <span>AL-QUR'AN</span>
        </a>
        <a href="index.php?kategori=Islam" class="category-item <?= $kategori_filter == 'Islam' ? 'active' : '' ?>">
            <i class="fas fa-star-and-crescent"></i>
            <span>BUKU ISLAM</span>
        </a>
        <a href="index.php?kategori=Panduan" class="category-item <?= $kategori_filter == 'Panduan' ? 'active' : '' ?>">
            <i class="fas fa-book"></i>
            <span>BUKU PANDUAN</span>
        </a>
        <a href="index.php?kategori=Solat" class="category-item <?= $kategori_filter == 'Solat' ? 'active' : '' ?>">
            <i class="fas fa-pray"></i>
            <span>BUKU TENTANG SOLAT</span>
        </a>
    </div>
</div>

<!-- ========== KATALOG BUKU ========== -->
<div class="container">
    <div class="section-title">
        <i class="fas fa-books"></i>
        <h3>
            <?php if ($kategori_filter): ?>
                KATEGORI: <?= strtoupper($kategori_filter) ?>
            <?php else: ?>
                KOLEKSI BUKU BRAILLE
            <?php endif; ?>
        </h3>
        <span class="line"></span>
        <?php if ($kategori_filter): ?>
            <a href="index.php" class="btn btn-outline-category ms-3">
                <i class="fas fa-times"></i> Reset Filter
            </a>
        <?php endif; ?>
    </div>
    
    <div class="row g-4">
        <?php 
        $no = 1;
        while ($row = mysqli_fetch_assoc($result)) : 
        ?>
        <div class="col-lg-3 col-md-4 col-sm-6">
            <div class="book-card">
                <div class="book-img">
                    <?php if ($row['gambar'] && file_exists('assets/img/' . $row['gambar'])): ?>
                        <img src="assets/img/<?= $row['gambar'] ?>" alt="<?= htmlspecialchars($row['judul']) ?>">
                    <?php else: ?>
                        <i class="fas fa-book"></i>
                    <?php endif; ?>
                </div>
                <div class="book-body">
                    <span class="book-category"><?= $row['kategori'] ?></span>
                    <h5 class="book-title"><?= htmlspecialchars($row['judul']) ?></h5>
                    <p class="book-author">
                        <i class="fas fa-user"></i> <?= htmlspecialchars($row['pengarang']) ?>
                    </p>
                    <p class="book-size">
                        <i class="fas fa-ruler"></i> 
                        <?= $row['ukuran'] == 'Besar' ? '25.5 × 30.5 cm' : '1.5 × 25.5 cm' ?>
                    </p>
                    <a href="detail.php?id=<?= $row['id'] ?>" class="btn-detail" style="text-decoration: none;">
                        <i class="fas fa-info-circle me-2"></i> DETAIL
                    </a>
                </div>
            </div>
        </div>
        <?php endwhile; ?>
        
        <?php if (mysqli_num_rows($result) == 0): ?>
        <div class="col-12">
            <div class="empty-state">
                <i class="fas fa-box-open fa-5x mb-4"></i>
                <h3>Belum ada buku dalam katalog</h3>
                <p class="text-muted">Silakan hubungi admin untuk menambahkan data buku.</p>
                <?php if ($kategori_filter): ?>
                    <a href="index.php" class="btn btn-outline-category mt-3">
                        <i class="fas fa-arrow-left me-2"></i> Lihat Semua Kategori
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- ========== FOOTER ========== -->
<footer class="footer">
    <div class="container">
        <div class="row">
            <div class="col-md-5 mb-4 mb-md-0">
                <h5>
                    <i class="fas fa-book-open me-2"></i>
                    Braille Book Catalog
                </h5>
                <p style="opacity: 0.9;">
                    Yayasan Raudlatul Makfufin - Percetakan Buku Islam Braille<br>
                    Menyediakan Al-Qur'an Braille dan literasi Islami untuk penyandang disabilitas netra sejak 1997.
                </p>
                <div class="social-links mt-3">
                    <a href="#"><i class="fab fa-facebook-f"></i></a>
                    <a href="#"><i class="fab fa-instagram"></i></a>
                    <a href="#"><i class="fab fa-whatsapp"></i></a>
                    <a href="#"><i class="far fa-envelope"></i></a>
                </div>
            </div>
            <div class="col-md-3 mb-4 mb-md-0">
                <h5>Quick Links</h5>
                <ul class="list-unstyled">
                    <li class="mb-2"><a href="index.php"><i class="fas fa-chevron-right me-2" style="font-size: 12px;"></i>Home</a></li>
                    <li class="mb-2"><a href="#"><i class="fas fa-chevron-right me-2" style="font-size: 12px;"></i>About Us</a></li>
                    <li class="mb-2"><a href="#"><i class="fas fa-chevron-right me-2" style="font-size: 12px;"></i>Contact</a></li>
                    <li class="mb-2"><a href="login.php"><i class="fas fa-chevron-right me-2" style="font-size: 12px;"></i>Login</a></li>
                </ul>
            </div>
            <div class="col-md-4">
                <h5>Kontak Kami</h5>
                <ul class="list-unstyled">
                    <li class="mb-2">
                        <i class="fas fa-map-marker-alt me-2" style="color: var(--accent-gold);"></i>
                        Jl. ... (alamat yayasan)
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-phone me-2" style="color: var(--accent-gold);"></i>
                        +62 xxx-xxxx-xxxx
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-envelope me-2" style="color: var(--accent-gold);"></i>
                        info@raudlatulmakfufin.org
                    </li>
                    <li class="mb-2">
                        <i class="fas fa-clock me-2" style="color: var(--accent-gold);"></i>
                        Senin - Jumat: 08:00 - 16:00 WIB
                    </li>
                </ul>
            </div>
        </div>
        <hr style="border-color: rgba(255,255,255,0.2); margin: 30px 0 20px;">
        <div class="row">
            <div class="col-md-6">
                <p class="mb-0" style="opacity: 0.8;">
                    © 2026 Yayasan Raudlatul Makfufin. All rights reserved.
                </p>
            </div>
            <div class="col-md-6 text-md-end">
                <p class="mb-0" style="opacity: 0.8;">
                    Developed with <i class="fas fa-heart" style="color: #ff6b6b;"></i> for Accessibility
                </p>
            </div>
        </div>
    </div>
</footer>

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>